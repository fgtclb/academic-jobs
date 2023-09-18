<?php

use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die(__CLASS__);
}

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'NewJobForm',
        [
            JobController::class => 'newJobForm, saveJob, list, show',
        ],
        [
            JobController::class => 'newJobForm, saveJob',
        ]
    );
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'List',
        [
            JobController::class => 'list, show',
        ],
        [
            JobController::class => 'list',
        ]
    );
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'Detail',
        [
            JobController::class => 'show',
        ]
    );
})();
