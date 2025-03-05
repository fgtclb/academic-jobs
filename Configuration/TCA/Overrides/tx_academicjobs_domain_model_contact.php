<?php

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
    // @todo When dropping TYPO3 v11 support apply this directly in TCA definitions.
    $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['starttime']['config']['type'] = 'datetime';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['endtime']['config']['type'] = 'datetime';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['email']['config']['type'] = 'email';
    $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['email']['config']['eval'] = 'nospace';
    unset(
        $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['starttime']['config']['renderType'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['starttime']['config']['eval'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['endtime']['config']['renderType'],
        $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['endtime']['config']['eval'],
    );
} else {
    // @todo Remove this when TYPO3 v11 support is dropped.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-98024-TCA-option-cruserid-removed.html
    $GLOBALS['TCA']['tx_academicjobs_domain_model_contact']['ctrl']['cruser_id'] = 'cruser_id';
}
