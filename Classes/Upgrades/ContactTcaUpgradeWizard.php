<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Upgrades;

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
        return '';
    }

    public function executeUpdate(): bool
    {
        $tableName = $this->tableExist('tx_academicjobs_domain_model_contact') ? 'tx_academicjobs_domain_model_contact' : 'zzz_tx_academicjobs_domain_model_contact';
        $connection = $this->connectionPool->getConnectionForTable('tx_academicjobs_domain_model_job');
        $queryBuilder = $connection->createQueryBuilder();
        $jobs = $queryBuilder
            ->select('job.uid', 'contact.name', 'contact.phone', 'contact.email', 'contact.additional_information')
            ->from('tx_academicjobs_domain_model_job', 'job')
            ->innerJoin('job', $tableName, 'contact', 'job.contact = contact.uid')
            ->executeQuery();
        while ($job = $jobs->fetchAssociative()) {
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

    public function updateNecessary(): bool
    {
        return $this->tableExist('tx_academicjobs_domain_model_contact')
            || $this->tableExist('zzz_tx_academicjobs_domain_model_contact');
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function tableExist(string $tableName): bool
    {
        return $this->connectionPool
            ->getConnectionForTable($tableName)
            ->createSchemaManager()
            ->tablesExist([$tableName]);
    }
}
