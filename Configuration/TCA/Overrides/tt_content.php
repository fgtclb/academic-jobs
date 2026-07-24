<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
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
        '',
        'FILE:EXT:academic_jobs/Configuration/FlexForms/Plugin_NewJobForm.xml',
        'academicjobs_newjobform'
    );

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
        '',
        'FILE:EXT:academic_jobs/Configuration/FlexForms/PluginList.xml',
        'academicjobs_list'
    );

    //==================================================================================================================
    // Plugin: academicjobs_detail
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
            'value' => 'academicjobs_detail',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_jobs'
    );
})();
