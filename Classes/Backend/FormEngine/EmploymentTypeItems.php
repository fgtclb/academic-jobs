<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Backend\FormEngine;

use TYPO3\CMS\Core\Utility\ArrayUtility;

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
        // @todo Add PSR-14 event to allow dynamic modification of items in project to avoid the need replace
        //       this itemsProcFunc class.
    }

    /**
     * @return array<int, array{label: string|null, value: int}>
     */
    private function getEmploymentTypes(): array
    {
        return [
            [
                'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.employment_type.fulltime',
                'value' => 1,
            ],
            [
                'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.employment_type.parttime',
                'value' => 2,
            ],
        ];
    }
}
