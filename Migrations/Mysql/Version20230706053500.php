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

final class Version20230706053500 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('6cdf3c98-56c5-494c-adc6-13a7db436f56');

        $this->dataMap = [
            'pages' => [
                'NEW1688614825' => [
                    'uuid' => '07658e82-d4cb-408d-8367-73c3eb22b7db',
                    'pid' => $rootPageUid,
                    'doktype' => 254,
                    'hidden' => 0,
                    'title' => 'Jobs Storage',
                    'slug' => '/jobs-storage',
                ],
            ],
        ];

        parent::up($schema);
    }
}
