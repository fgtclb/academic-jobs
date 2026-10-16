<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler of the FlexForm field `settings.job.type` of the job list,
 * offering the job types of the field `type` of the job record behind the items the
 * FlexForm field declares, its "None" among them.
 *
 * The types are built by {@see TypeItems}, which dispatches
 * {@see ModifyTcaSelectFieldItemsEvent}, with the `table` and the `field` of the job
 * record, so a listener that adds or removes a type for the record does the same for
 * the list. Every other parameter is the one of the FlexForm field, and a listener tells
 * the two apart by `flexParentDatabaseRow`, the content element, which only the list
 * passes. The declared items are not handed to the listeners: "None" is no job type but
 * the choice of no filter, and a listener replacing the types cannot take it away.
 *
 * @phpstan-import-type ItemsProcFuncParameters from ModifyTcaSelectFieldItemsEvent
 */
final class JobListTypeItems
{
    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $declaredItems = array_values($parameters['items']);
        $types = [];
        $typeParameters = $parameters;
        // Binds a list of its own to the copy instead of the one FormEngine handed over by
        // reference, which a plain assignment would write through.
        $typeParameters['items'] = &$types;
        $typeParameters['table'] = 'tx_academicjobs_domain_model_job';
        $typeParameters['field'] = 'type';
        GeneralUtility::makeInstance(TypeItems::class)->itemsProcFunc($typeParameters);

        $declaredValues = array_map(
            static fn(array|SelectItem $item): string => (string)($item['value'] ?? ''),
            $declaredItems,
        );
        $parameters['items'] = [
            ...$declaredItems,
            ...array_values(array_filter(
                $typeParameters['items'],
                static fn(array|SelectItem $item): bool => !in_array((string)($item['value'] ?? ''), $declaredValues, true),
            )),
        ];
    }
}
