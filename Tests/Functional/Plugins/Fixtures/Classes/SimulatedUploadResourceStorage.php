<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins\Fixtures\Classes;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\UploadSizeException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Accepts the files a test hands in as uploads, registered as XCLASS of the storage.
 *
 * `ResourceStorage::assureFileUploadPermissions()` requires `is_uploaded_file()` on
 * TYPO3 v12 and v13, which is never true for a file that was not posted to a web
 * server, so a functional test cannot upload anything through the storage. Files below
 * the directory of `uploadDirectory()` skip that one step only. The upload size limit
 * and the checks of `assureFileAddPermissions()`, file extension, user and target
 * folder, still apply to them. Every other file is checked as before.
 */
final class SimulatedUploadResourceStorage extends ResourceStorage
{
    public static function uploadDirectory(): string
    {
        return Environment::getVarPath() . '/transient/simulated-uploads/';
    }

    protected function assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize): void
    {
        if (!str_starts_with(PathUtility::getCanonicalPath((string)$localFilePath), self::uploadDirectory())) {
            parent::assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize);
            return;
        }
        // The checks of the parent method that follow `is_uploaded_file()`, identical on TYPO3 v12 and v13.
        $maxUploadFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
        if ($maxUploadFileSize > 0 && $uploadedFileSize >= $maxUploadFileSize) {
            throw new UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1322110041);
        }
        $this->assureFileAddPermissions($targetFolder, $targetFileName);
    }
}
