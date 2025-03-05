<?php

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
    // @todo When dropping TYPO3 v11 support apply this directly in TCA definitions.
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['starttime']['config']['type'] = 'datetime';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['starttime']['config']['required'] = true;
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['endtime']['config']['type'] = 'datetime';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['endtime']['config']['required'] = true;
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['title']['config']['eval'] = 'trim';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['title']['config']['required'] = true;
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_type']['config']['required'] = true;
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['link']['config']['type'] = 'link';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['type']['config']['required'] = true;
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_start_date']['config']['type'] = 'datetime';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_start_date']['config']['format'] = 'date';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_start_date']['config']['required'] = true;
    unset(
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['starttime']['config']['renderType'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['starttime']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['endtime']['config']['renderType'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['endtime']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_type']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['link']['config']['renderType'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['type']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_start_date']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['employment_start_date']['config']['renderType'],
    );
} else {
    // @todo Remove this when TYPO3 v11 support is dropped.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-98024-TCA-option-cruserid-removed.html
    $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['ctrl']['cruser_id'] = 'cruser_id';
}
