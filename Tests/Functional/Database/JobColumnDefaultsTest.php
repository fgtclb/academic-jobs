<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Database;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A job record is written by more than the frontend form, which names every
 * mapped column: imports, fixtures and the backend rely on the optional columns
 * being optional at database level as well.
 */
final class JobColumnDefaultsTest extends AbstractAcademicJobsTestCase
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    #[Test]
    public function jobRowCanBeInsertedWithoutTheOptionalColumns(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $connection->insert(
            self::TABLE,
            [
                'pid' => 1,
                'title' => 'Inserted without the optional columns',
            ]
        );

        $this->assertSame(1, $this->countJobs());
    }

    #[Test]
    public function jobCanBeCreatedThroughDataHandler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobColumnDefaults/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobColumnDefaults/pages.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE => [
                    'NEW1' => [
                        'pid' => 1,
                        'title' => 'Created by the data handler',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(1, $this->countJobs());
    }

    private function countJobs(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->executeQuery()
            ->fetchOne();
    }
}
