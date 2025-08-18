<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();

    //==================================================================================================================
    // Plugin: academicjobs_newjobform
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.newjobform.label',
            'value' => 'academicjobs_newjobform',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_jobs'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicjobs_newjobform',
        'after:header'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        'academicjobs_newjobform',
        sprintf('FILE:EXT:academic_jobs/Configuration/Flexforms/Core%s/Plugin_NewJobForm.xml', $typo3MajorVersion)
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicjobs_newjobform'] = 'pi_flexform';

    //==================================================================================================================
    // Plugin: academicjobs_list
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicjobs_list',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_jobs'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicjobs_list',
        'after:header'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        'academicjobs_list',
        sprintf('FILE:EXT:academic_jobs/Configuration/Flexforms/Core%s/PluginList.xml', $typo3MajorVersion)
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_list'] = 'pages,layout,select_key,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicjobs_list'] = 'pi_flexform';

    //==================================================================================================================
    // Plugin: academicjobs_detail
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicjobs_list',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_jobs'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicjobs_list',
        'after:header'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicjobs_detail'] = 'recursive,select_key';

})();
