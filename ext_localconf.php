<?php

use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    // @todo Needs to be migrated to CType due to TYPO3 v13 constraint.
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
    // @todo Needs to be migrated to CType due to TYPO3 v13 constraint.
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'List',
        [
            JobController::class => 'list',
        ],
        [
            JobController::class => 'list',
        ]
    );
    // @todo Needs to be migrated to CType due to TYPO3 v13 constraint.
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'Detail',
        [
            JobController::class => 'show, list',
        ]
    );
})();
