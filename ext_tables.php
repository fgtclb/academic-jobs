<?php

defined('TYPO3') || die();

(static function () {
    if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
        // @todo Drop when TYPO3 v11 support is removed. Replaced in TYPO3 v12 with
        //       $GLOBALS['TCA'][$table]['ctrl']['security']['ignorePageTypeRestriction']
        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-98487-TCAOptionCtrlsecurityignorePageTypeRestriction.html
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_academicjobs_domain_model_job');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_academicjobs_domain_model_contact');
    }
})();
