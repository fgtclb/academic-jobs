<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final class EmploymentTypeItems
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $parameters['items'],
            $this->getEmploymentTypes()
        );
    }

    /**
     * @return array<int, array{label: string|null, value: int}>
     */
    public function getEmploymentTypes(): array
    {
        return [
            [
                'label' => LocalizationUtility::translate('LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.employment_type.fulltime'),
                'value' => 1,
            ],
            [
                'label' => LocalizationUtility::translate('LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.employment_type.parttime'),
                'value' => 2,
            ],
        ];
    }
}
