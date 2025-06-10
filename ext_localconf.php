<?php

use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'NewJobForm',
        [
            JobController::class => 'new, create',
        ],
        [
            JobController::class => 'new, create',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'List',
        [
            JobController::class => 'list',
        ],
        [
            JobController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
    ExtensionUtility::configurePlugin(
        'AcademicJobs',
        'Detail',
        [
            JobController::class => 'show',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
})();
