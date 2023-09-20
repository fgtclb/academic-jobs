<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicJobs\Migrations\Mysql;

use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;

final class Version20230703162000 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $this->dataMap = [
            'pages' => [
                'NEW1234' => [
                    'uuid' => '6cdf3c98-56c5-494c-adc6-13a7db436f56',
                    'pid' => 0,
                    'doktype' => 1,
                    'title' => 'Academic Jobs',
                    'slug' => '/',
                    'is_siteroot' => 1,
                    'hidden' => 0,
                ],
            ],
        ];

        parent::up($schema);
    }
}
