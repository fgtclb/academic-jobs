<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    $tcaOverrides = [
        'contact' => [
            'exclude' => true,
            'label' => 'tx_academicjobs_domain_model_job.contact',
            'description' => 'tx_academicjobs_domain_model_job.contact.description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_academicjobs_domain_model_contact',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 0,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
            ],

        ],
    ];

    ExtensionManagementUtility::addTCAcolumns(
        'tx_academicjobs_domain_model_job',
        $tcaOverrides
    );
})();
