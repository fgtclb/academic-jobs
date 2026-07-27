<?php

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Not authorized');
}

(static function (): void {
    //==================================================================================================================
    // Plugin: academicjobs_newjobform
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.newjobform.label',
            'value' => 'academicjobs_newjobform',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
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
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicjobs_newjobform',
        'FILE:EXT:academic_jobs/Configuration/FlexForms/Plugin_NewJobForm.xml',
    );

    //==================================================================================================================
    // Plugin: academicjobs_list
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicjobs_list',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
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
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicjobs_list',
        'FILE:EXT:academic_jobs/Configuration/FlexForms/PluginList.xml',
    );

    //==================================================================================================================
    // Plugin: academicjobs_detail
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_jobs/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
            'value' => 'academicjobs_detail',
            'icon' => 'academic_jobs_icon',
            'group' => 'academic',
        ],
        'academic_jobs'
    );
})();
