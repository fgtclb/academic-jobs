<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Upgrades;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicJobs_contactRelation')]
final class ContactTcaUpgradeWizard implements UpgradeWizardInterface
{
    /**
     * The names the old contact table can have, in the order they are looked for: its own,
     * and the one the database analyzer gives it before it drops it. The analyzer prefixes
     * a removed table or field with `zzz_deleted_`, on every TYPO3 version this wizard runs
     * on, and shortens the result to the identifier length of the platform, which both
     * names here stay below.
     */
    private const CONTACT_TABLES = [
        'tx_academicjobs_domain_model_contact',
        'zzz_deleted_tx_academicjobs_domain_model_contact',
    ];

    /**
     * The names the relation field of the job can have, see CONTACT_TABLES. The analyzer
     * renames it in the same step as the table, the job table no longer declares it.
     */
    private const RELATION_FIELDS = [
        'contact',
        'zzz_deleted_contact',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate job contact records from relation to fields directly in the job record';
    }

    public function getDescription(): string
    {
        return 'Copies name, phone, e-mail and additional information of the contact record related to a job'
            . ' into the contact fields of the job itself, for every job whose own contact fields are all empty,'
            . ' hidden, scheduled, expired and deleted jobs included. Hidden and deleted contact records are left out.'
            . ' Jobs store their contact directly since the contact table was removed, the old table is read once'
            . ' and left in place. The database analyzer renames the table and the relation field of the job with'
            . ' the prefix "zzz_deleted_" before it drops them, the wizard reads them under that name as well.';
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_academicjobs_domain_model_job');
        foreach ($this->jobsToMigrate() as $job) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->update('tx_academicjobs_domain_model_job')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($job['uid'])),
                )
                ->set('contact_name', $job['name'] ?? '')
                ->set('contact_phone', $job['phone'] ?? '')
                ->set('contact_email', $job['email'] ?? '')
                ->set('contact_additional_information', $job['additional_information'] ?? '')
                ->executeStatement();
        }

        return true;
    }

    /**
     * Necessary only while a job without a contact of its own relates to a contact record
     * that holds one. The old table as such is no reason: the wizard leaves it in place,
     * and an installation keeps it, renamed or not, after the migration ran.
     */
    public function updateNecessary(): bool
    {
        foreach ($this->jobsToMigrate() as $_) {
            return true;
        }

        return false;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Yields every job that relates to a record of the old contact table, has none of its
     * own contact fields filled and gets a value from that record, together with the
     * values of the record. A contact entered in the job itself is never overwritten.
     * The old table and the relation field are read under their own name or under the
     * one the database analyzer renamed them to, each independently of the other. Yields
     * nothing without the old table or without the relation field of the job.
     *
     * Every job is read, hidden, scheduled, expired and deleted ones included: a job shows
     * its contact once it is visible again, or restored from the recycler, and the result
     * does not depend on the moment the wizard runs.
     *
     * The old table has no TCA in an installation, so no restriction reaches it, and the
     * conditions on it are stated here. A deleted contact record is left out, and so is a
     * hidden one: an editor switched it off for the job, and the contact fields of a job
     * have no switch to carry that over. Start and end time of a contact record are not
     * looked at, so that the result does not depend on the moment the wizard runs either.
     * A contact record whose end time has passed is therefore copied, and the job shows it.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function jobsToMigrate(): \Generator
    {
        $tableName = $this->findContactTable();
        $relationField = $this->findRelationField();
        if ($tableName === null || $relationField === null) {
            return;
        }
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_academicjobs_domain_model_job')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $jobs = $queryBuilder
            ->select(
                'job.uid',
                'job.contact_name',
                'job.contact_phone',
                'job.contact_email',
                'job.contact_additional_information',
                'contact.name',
                'contact.phone',
                'contact.email',
                'contact.additional_information',
            )
            ->from('tx_academicjobs_domain_model_job', 'job')
            ->innerJoin(
                'job',
                $tableName,
                'contact',
                $queryBuilder->expr()->eq('job.' . $relationField, $queryBuilder->quoteIdentifier('contact.uid')),
            )
            ->where(
                $queryBuilder->expr()->eq('contact.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('contact.hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('job.uid')
            ->executeQuery();
        while ($job = $jobs->fetchAssociative()) {
            $own = [$job['contact_name'], $job['contact_phone'], $job['contact_email'], $job['contact_additional_information']];
            $related = [$job['name'], $job['phone'], $job['email'], $job['additional_information']];
            if (array_filter($own, self::isFilled(...)) !== [] || array_filter($related, self::isFilled(...)) === []) {
                continue;
            }
            yield $job;
        }
    }

    private static function isFilled(mixed $value): bool
    {
        return trim((string)$value) !== '';
    }

    private function findContactTable(): ?string
    {
        foreach (self::CONTACT_TABLES as $tableName) {
            $tableExists = $this->connectionPool
                ->getConnectionForTable($tableName)
                ->createSchemaManager()
                ->tablesExist([$tableName]);
            if ($tableExists) {
                return $tableName;
            }
        }

        return null;
    }

    private function findRelationField(): ?string
    {
        $columnNames = array_map(
            static fn(Column $column): string => strtolower($column->getName()),
            $this->connectionPool
                ->getConnectionForTable('tx_academicjobs_domain_model_job')
                ->createSchemaManager()
                ->listTableColumns('tx_academicjobs_domain_model_job')
        );
        foreach (self::RELATION_FIELDS as $fieldName) {
            if (in_array($fieldName, $columnNames, true)) {
                return $fieldName;
            }
        }

        return null;
    }
}
