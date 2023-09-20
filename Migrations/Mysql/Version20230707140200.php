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

final class Version20230707140200 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('6cdf3c98-56c5-494c-adc6-13a7db436f56');

        $this->dataMap = [
            'sys_template' => [
                'NEW1695218200' => [
                    'pid' => $rootPageUid,
                    'title' => 'Academic Jobs',
                    'root' => 1,
                    'include_static_file' => implode(',', [
                        'EXT:bootstrap_package/Configuration/TypoScript/',
                        'EXT:academic_jobs/Configuration/TypoScript/',
                    ]),
                    'config' => '',
                ],
            ],
        ];

        parent::up($schema);
    }
}
