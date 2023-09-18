<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_academicjobs_domain_model_job');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_academicjobs_domain_model_contact');
})();
