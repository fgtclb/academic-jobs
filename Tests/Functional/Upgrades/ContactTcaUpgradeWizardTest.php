<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Upgrades;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\AcademicJobs\Upgrades\ContactTcaUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class ContactTcaUpgradeWizardTest extends AbstractAcademicJobsTestCase
{
    public function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-jobcontact-schema';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $schemaManager = $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createSchemaManager();
        if ($schemaManager->tablesExist(['renamedfortest_tx_academicjobs_domain_model_contact'])) {
            $schemaManager->renameTable('renamedfortest_tx_academicjobs_domain_model_contact', 'tx_academicjobs_domain_model_contact');
        }
        parent::tearDown();
    }

    #[Test]
    public function updateNecessaryReturnsFalseWhenTableDoesNotExist(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $connection->createSchemaManager()->renameTable('tx_academicjobs_domain_model_contact', 'renamedfortest_tx_academicjobs_domain_model_contact');
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    public static function txAcademicJobsDomainModelContactDataSets(): \Generator
    {
        yield 'contact - not deleted or hidden' => [
            'fixtureDataSetFile' => 'contact_notDeletedOrHidden.csv',
        ];
        yield 'contact - not deleted but hidden' => [
            'fixtureDataSetFile' => 'contact_notDeletedButHidden.csv',
        ];
        yield 'contact - deleted but not hidden' => [
            'fixtureDataSetFile' => 'contact_deletedButNotHidden.csv',
        ];
    }

    #[DataProvider('txAcademicJobsDomainModelContactDataSets')]
    #[Test]
    public function updateNecessaryReturnsTrueWhenTableExists(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    #[DataProvider('txAcademicJobsDomainModelContactDataSets')]
    #[Test]
    public function executeUpdateMigratesDatabaseRecordsAndReturnsTrue(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
    }
}
