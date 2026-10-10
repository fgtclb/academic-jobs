<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Upgrades;

use Doctrine\DBAL\Schema\Column;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\AcademicJobs\Upgrades\ContactTcaUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;

/**
 * An installation that applied the "remove" step of the database analyzer before it
 * ran the wizard has the old contact table and the relation field of the job under the
 * names the analyzer gave them. The test applies that step to the schema of the fixture
 * extension, with the analyzer of the core under test, and runs the wizard afterwards
 * (ACE-895).
 *
 * The step renames tables and fields of the test database, which the next test of the
 * same class would find, so the class holds this one test.
 */
final class ContactTcaUpgradeWizardAfterDatabaseAnalyzerTest extends AbstractAcademicJobsTestCase
{
    private const FIXTURE_SCHEMA = __DIR__ . '/../Fixtures/Extensions/test_jobcontact_schema/ext_tables.sql';

    public function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-jobcontact-schema';
        parent::setUp();
    }

    #[Test]
    public function executeUpdateMigratesAfterTheDatabaseAnalyzerRenamedTheRemovedSchema(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/contact_notDeletedOrHidden.csv');
        $this->applyTheRenamesOfTheDatabaseAnalyzerWithoutTheFixtureSchema();

        $schemaManager = $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createSchemaManager();
        $this->assertFalse($schemaManager->tablesExist(['tx_academicjobs_domain_model_contact']));
        $this->assertTrue($schemaManager->tablesExist(['zzz_deleted_tx_academicjobs_domain_model_contact']));
        $jobColumnNames = array_map(
            static fn(Column $column): string => strtolower($column->getName()),
            array_values($schemaManager->listTableColumns('tx_academicjobs_domain_model_job')),
        );
        $this->assertNotContains('contact', $jobColumnNames);
        $this->assertContains('zzz_deleted_contact', $jobColumnNames);

        $subject = $this->get(ContactTcaUpgradeWizard::class);
        $this->assertInstanceOf(ContactTcaUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() before the migration');

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/contact_notDeletedOrHidden.csv');
        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() after the migration');
    }

    /**
     * Compares the database with the schema of every loaded extension except the fixture,
     * which is the schema of an installation since academic_jobs removed the contact table
     * and the relation field, and executes the renames the analyzer suggests for removal.
     */
    private function applyTheRenamesOfTheDatabaseAnalyzerWithoutTheFixtureSchema(): void
    {
        $sqlReader = $this->get(SqlReader::class);
        $this->assertInstanceOf(SqlReader::class, $sqlReader);
        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $fixtureStatements = $sqlReader->getCreateTableStatementArray((string)file_get_contents(self::FIXTURE_SCHEMA));
        $installationStatements = array_values(array_diff($statements, $fixtureStatements));
        $this->assertCount(count($statements) - count($fixtureStatements), $installationStatements, 'every statement of the fixture is left out');

        $schemaMigrator = $this->get(SchemaMigrator::class);
        $this->assertInstanceOf(SchemaMigrator::class, $schemaMigrator);
        $suggestions = $schemaMigrator->getUpdateSuggestions($installationStatements, true)[ConnectionPool::DEFAULT_CONNECTION_NAME];
        $renames = $suggestions['change'] + $suggestions['change_table'];
        $this->assertNotSame([], $renames, 'the analyzer suggests renames');
        $this->assertSame([], $schemaMigrator->migrate($installationStatements, $renames), 'the renames are executed without an error');
    }
}
