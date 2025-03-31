<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();
    ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'NewJobForm',
        'Academic Jobs: New Job Form',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );
    ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'List',
        'Academic Jobs: List Jobs',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );
    ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'Detail',
        'Academic Jobs: Detail',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_detail'] = 'recursive,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_list'] = 'pages,layout,select_key,recursive';

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicjobs_list'] = 'pi_flexform';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicjobs_newjobform'] = 'pi_flexform';

    ExtensionManagementUtility::addPiFlexFormValue(
        'academicjobs_list',
        sprintf('FILE:EXT:academic_jobs/Configuration/Flexforms/Core%s/PluginList.xml', $typo3MajorVersion)
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        'academicjobs_newjobform',
        sprintf('FILE:EXT:academic_jobs/Configuration/Flexforms/Core%s/Plugin_NewJobForm.xml', $typo3MajorVersion)
    );
})();
