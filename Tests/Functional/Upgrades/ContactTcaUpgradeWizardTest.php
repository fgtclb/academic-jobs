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
        // Start and end time of a contact record are not looked at, an expired one is copied.
        yield 'contact - expired or scheduled' => [
            'fixtureDataSetFile' => 'contact_expiredOrScheduledContact.csv',
        ];
    }

    /**
     * The wizard leaves the old table in place, so its existence alone made the wizard
     * show up as necessary on every installation that ever had it (ACE-871).
     */
    #[Test]
    public function updateNecessaryReturnsFalseWhenNoJobRelatesToAContact(): void
    {
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsFalseOnceMigrated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/contact_notDeletedOrHidden.csv');
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary());

        $subject->executeUpdate();

        $this->assertFalse($subject->updateNecessary());
    }

    /**
     * A contact entered in the job itself is kept, and a contact record without a value
     * has nothing to give, so neither makes the wizard necessary again (ACE-871).
     */
    #[Test]
    public function executeUpdateKeepsTheContactEnteredInTheJob(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/contact_jobsWithOwnContact.csv');
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary());

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/contact_jobsWithOwnContact.csv');
        $this->assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsTrueWhenAJobHasAContactToMigrate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/contact_notDeletedOrHidden.csv');
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    /**
     * The migration skips hidden and deleted contact records, see
     * executeUpdateMigratesDatabaseRecordsAndReturnsTrue(). The contact table has no TCA,
     * as in an installation, so nothing but the wizard itself leaves them out. The wizard
     * reads the same records to decide whether it is necessary.
     */
    public static function contactDataSetsTheMigrationSkips(): \Generator
    {
        yield 'contact - not deleted but hidden' => ['contact_notDeletedButHidden.csv'];
        yield 'contact - deleted but not hidden' => ['contact_deletedButNotHidden.csv'];
    }

    #[DataProvider('contactDataSetsTheMigrationSkips')]
    #[Test]
    public function updateNecessaryReturnsFalseForContactsTheMigrationSkips(string $fixtureDataSetFile): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    /**
     * Each data set holds a job in the named state and a deleted job, both related to the
     * same contact record and both migrated.
     */
    public static function jobsInvisibleInTheFrontendDataSets(): \Generator
    {
        yield 'hidden job' => ['contact_hiddenJob.csv'];
        yield 'expired job' => ['contact_expiredJob.csv'];
        yield 'scheduled job' => ['contact_scheduledJob.csv'];
    }

    /**
     * A job a visitor does not see while the wizard runs gets its contact all the same,
     * otherwise it shows none once it is visible, while the wizard reports nothing left
     * to do. So does a deleted job, for the day it is restored from the recycler (ACE-887).
     */
    #[DataProvider('jobsInvisibleInTheFrontendDataSets')]
    #[Test]
    public function executeUpdateMigratesJobsInvisibleInTheFrontend(string $fixtureDataSetFile): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() before the migration');

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() after the migration');
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
