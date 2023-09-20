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

final class Version20230707140500 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('6cdf3c98-56c5-494c-adc6-13a7db436f56');

        $this->dataMap = [
            'pages' => [
                'NEW1688731594' => [
                    'uuid' => '46e0243d-8608-4317-8bc7-0ec32a680f56',
                    'pid' => $rootPageUid,
                    'doktype' => 1,
                    'hidden' => 0,
                    'title' => 'Jobs',
                    'slug' => '/jobs',
                ],
                'NEW1695218065' => [
                    'uuid' => '397eec5c-bc79-4b18-8aee-9bbc96743197',
                    'pid' => $rootPageUid,
                    'doktype' => 1,
                    'hidden' => 0,
                    'title' => 'Detail',
                    'slug' => '/detail',
                ],
            ],
        ];

        parent::up($schema);
    }
}
