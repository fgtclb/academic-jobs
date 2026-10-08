<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Upgrades;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicJobs_contactRelation')]
final class ContactTcaUpgradeWizard implements UpgradeWizardInterface
{
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
            . ' into the contact fields of the job itself, for every job whose own contact fields are all empty.'
            . ' Jobs store their contact directly since the contact table was removed, the old table, also when'
            . ' renamed to "zzz_tx_academicjobs_domain_model_contact", is read once and left in place.';
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
     * Yields nothing without the old table or without the relation field of the job.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function jobsToMigrate(): \Generator
    {
        $tableName = match (true) {
            $this->tableExist('tx_academicjobs_domain_model_contact') => 'tx_academicjobs_domain_model_contact',
            $this->tableExist('zzz_tx_academicjobs_domain_model_contact') => 'zzz_tx_academicjobs_domain_model_contact',
            default => null,
        };
        if ($tableName === null || !$this->jobTableHasContactColumn()) {
            return;
        }
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_academicjobs_domain_model_job')->createQueryBuilder();
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
            ->innerJoin('job', $tableName, 'contact', 'job.contact = contact.uid')
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

    private function tableExist(string $tableName): bool
    {
        return $this->connectionPool
            ->getConnectionForTable($tableName)
            ->createSchemaManager()
            ->tablesExist([$tableName]);
    }

    private function jobTableHasContactColumn(): bool
    {
        $columnNames = array_map(
            static fn(Column $column): string => strtolower($column->getName()),
            $this->connectionPool
                ->getConnectionForTable('tx_academicjobs_domain_model_job')
                ->createSchemaManager()
                ->listTableColumns('tx_academicjobs_domain_model_job')
        );

        return in_array('contact', $columnNames, true);
    }
}
