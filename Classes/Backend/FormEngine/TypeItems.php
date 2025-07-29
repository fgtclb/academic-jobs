<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final class TypeItems
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $parameters['items'],
            $this->getTypes()
        );
    }

    /**
     * @return array<int, array{label: string|null, value: int}>
     */
    public function getTypes(): array
    {
        return [
            [
                'label' => LocalizationUtility::translate('LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.job'),
                'value' => 1,
            ],
            [
                'label' => LocalizationUtility::translate('LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.sidejob'),
                'value' => 2,
            ],
            [
                'label' => LocalizationUtility::translate('LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.thesis'),
                'value' => 3,
            ],
        ];
    }
}
