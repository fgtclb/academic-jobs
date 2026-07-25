<?php

use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

// Define ACADEMIC_JOBS_CASCADE_REMOVE for Classic (non-Composer) mode, where
// composer.json `autoload.files` is not processed. In Composer mode the constant
// is already defined via autoload.files, so this is skipped. ext_localconf.php is
// cached/concatenated by TYPO3 (so __DIR__ is unreliable) — use extPath().
// @todo Remove together with EXT_CONSTANTS.php once TYPO3 v13 support is dropped.
if (!defined('ACADEMIC_JOBS_CASCADE_REMOVE')) {
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('academic_jobs') . 'EXT_CONSTANTS.php';
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
