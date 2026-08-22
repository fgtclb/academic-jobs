<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {

    //==================================================================================================================
    // Static TypoScript templates, selectable in a "sys_template" record for installations that do not use site sets.
    //
    // The registered folders are the same ones the sets of this extension deliver through their "typoscript" key.
    // Use one mechanism per site, not both - see the extension documentation, chapter "Configuration".
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_jobs',
        'Configuration/TypoScript/NewJobForm',
        'Academic Jobs: Jobs New',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_jobs',
        'Configuration/TypoScript/List',
        'Academic Jobs: Jobs List',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_jobs',
        'Configuration/TypoScript/Detail',
        'Academic Jobs: Jobs Detail',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_jobs',
        'Configuration/TypoScript/Full',
        'Academic Jobs: All components',
    );

    //==================================================================================================================
    // The entry below keeps the value that installations already store in "sys_template.include_static_file".
    //
    // It is the shared "plugin.tx_academicjobs" block every component folder includes - selecting it is equivalent to
    // "All components" as long as no component ships TypoScript of its own.
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_jobs',
        'Configuration/TypoScript',
        'Academic Jobs: Shared plugin settings',
    );

})();
