<?php

if (!defined('TYPO3')) {
    die(__CLASS__);
}

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'NewJobForm',
        'Academic Jobs: New Job Form',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'List',
        'Academic Jobs: List Jobs',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicJobs',
        'Detail',
        'Academic Jobs: Detail',
        'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_detail'] = 'recursive,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_list'] = 'pages,layout,select_key,recursive';

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicjobs_list'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicjobs_list',
        'FILE:EXT:academic_jobs/Configuration/Flexforms/PluginList.xml'
    );
})();
