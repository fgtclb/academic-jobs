<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use AndreasWolf\Uuid\Service\TableConfigurationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (ExtensionManagementUtility::isLoaded('uuid')) {
    $tableConfigurationService = GeneralUtility::makeInstance(TableConfigurationService::class);
    $tableConfigurationService->enableUuidForTable('pages');
    $tableConfigurationService->enableUuidForTable('tt_content');
    $tableConfigurationService->enableUuidForTable('tx_academicjobs_domain_model_job');
    $tableConfigurationService->enableUuidForTable('tx_academicjobs_domain_model_contact');
}
