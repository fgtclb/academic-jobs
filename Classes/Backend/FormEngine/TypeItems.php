<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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
        ArrayUtility::mergeRecursiveWithOverrule(
            $parameters['items'],
            $this->getDefaultTypes()
        );
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
}
