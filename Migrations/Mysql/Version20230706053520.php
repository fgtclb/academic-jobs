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

final class Version20230706053520 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidPageResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $jobStorageUid = $uuidPageResolver->getUidForUuid('07658e82-d4cb-408d-8367-73c3eb22b7db') ?? 0;

        $this->dataMap = [
            'tx_academicjobs_domain_model_job' => [
                'NEW1688616404' => [
                    'uuid' => '780213a4-bff8-4382-8d0a-a7d25359082a',
                    'pid' => $jobStorageUid,
                    'title' => 'TYPO3 Developer',
                    'company_name' => 'web-vision GmbH',
                    'description' => 'web-vision GmbH is a TYPO3 agency in Mönchengladbach.',
                    'link' => 'https://www.web-vision.de',
                    'type' => '1',
                    'slug' => 'typo3-developer-web-vision-gmbh',
                    'starttime' => '2023-07-06 00:00:00',
                    'endtime' => '2030-07-06 00:00:00',
                    'employment_start_date' => '2023-07-07 00:00:00',
                    'work_location' => 'Remote 50%',
                    'sector' => 'IT',
                    'employment_type' => '1',
                ],
            ],
        ];

        parent::up($schema);
    }
}
