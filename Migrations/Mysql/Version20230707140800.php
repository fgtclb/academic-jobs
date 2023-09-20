<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicJobs\Migrations\Mysql;

use AndreasWolf\Uuid\UuidResolverFactory;
use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Version20230707140800 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $jobListPageUid = $uuidResolver->getUidForUuid('46e0243d-8608-4317-8bc7-0ec32a680f56');
        $jobDetailPageUid = $uuidResolver->getUidForUuid('397eec5c-bc79-4b18-8aee-9bbc96743197');
        $jobStorageUid = $uuidResolver->getUidForUuid('07658e82-d4cb-408d-8367-73c3eb22b7db');

        $this->dataMap = [
            'tt_content' => [
                'NEW1688731717' => [
                    'uuid' => '416a7ab3-0762-4f56-b641-1f6d702d5d34',
                    'pid' => $jobListPageUid,
                    'CType' => 'list',
                    'list_type' => 'academicjobs_list',
                    'pages' => (string)$jobStorageUid,
                ],
                'NEW1695217467' => [
                    'uuid' => '416a7ab3-0762-4f56-b641-1f6d702d5d34',
                    'pid' => $jobDetailPageUid,
                    'CType' => 'list',
                    'list_type' => 'academicjobs_detail',
                ],
            ],
        ];

        parent::up($schema);
    }
}
