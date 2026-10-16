<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler dispatching {@see ModifyTcaSelectFieldItemsEvent} PSR-14 event
 * with {@see self::getDefaultEmploymentTypes()} to allow projects or other extension to
 * modify the available select items.
 *
 * This is executed in the backend in FormEngine for TCA fields using this itemsProcFunc
 * handler and also in controllers to retrieve the select options of the field.
 *
 * @phpstan-import-type ItemsProcFuncParameters from ModifyTcaSelectFieldItemsEvent
 */
final class TypeItems
{
    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $parameters['items'] = $this->appendMissingItems($parameters['items'], $this->getDefaultTypes());
        /** @var ModifyTcaSelectFieldItemsEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTcaSelectFieldItemsEvent(parameters: $parameters));
        $parameters = $event->getParameters();
    }

    /**
     * @return array<int, array{
     *     label: string|null,
     *     value: int,
     * }>
     */
    private function getDefaultTypes(): array
    {
        return [
            [
                'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.job',
                'value' => 1,
            ],
            [
                'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.sidejob',
                'value' => 2,
            ],
            [
                'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.thesis',
                'value' => 3,
            ],
        ];
    }

    /**
     * Appends the shipped items after the items the field declares, skipping a shipped
     * item whose value is declared already. Both lists are indexed from 0, so merging them
     * by key let the shipped items replace the declared ones.
     *
     * @param array<int, SelectItem|array{label?: string|null, value?: mixed, icon?: string|null, group?: string|null}> $items
     * @param array<int, array{label: string|null, value: int}> $shippedItems
     * @return array<int, SelectItem|array{label?: string|null, value?: mixed, icon?: string|null, group?: string|null}>
     */
    private function appendMissingItems(array $items, array $shippedItems): array
    {
        $items = array_values($items);
        $declaredValues = array_map(
            static fn(array|SelectItem $item): string => (string)($item['value'] ?? ''),
            $items,
        );
        foreach ($shippedItems as $shippedItem) {
            if (!in_array((string)$shippedItem['value'], $declaredValues, true)) {
                $items[] = $shippedItem;
            }
        }
        return $items;
    }
}
