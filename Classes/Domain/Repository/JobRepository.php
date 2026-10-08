<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Repository;

use FGTCLB\AcademicBase\Domain\Repository\HiddenRecordsQueryTrait;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository
{
    use HiddenRecordsQueryTrait;

    /**
     * `starttime` is `0` for every job that was never scheduled, so on its own it leaves
     * most of the list in DBMS row order - which is not the same list twice on PostgreSQL
     * (ACE-491). `uid` settles those ties deterministically.
     */
    protected $defaultOrderings = [
        'starttime' => QueryInterface::ORDER_ASCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @return QueryResultInterface<int, Job>
     */
    public function findByJobType(int $jobType, bool $includeHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        if ($includeHidden) {
            $this->includeHiddenRecords($query);
        }
        $query->matching(
            $query->equals('type', $jobType)
        );
        $this->matchTranslationsOfHiddenRecords($query);
        $jobs = $query->execute();
        $this->fetchIncludingHiddenRecords($jobs);
        return $jobs;
    }

    /**
     * @return QueryResultInterface<int, Job>
     */
    public function findAllJobs(bool $includeHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        if ($includeHidden) {
            $this->includeHiddenRecords($query);
        }
        $this->matchTranslationsOfHiddenRecords($query);
        $jobs = $query->execute();
        $this->fetchIncludingHiddenRecords($jobs);
        return $jobs;
    }

    /**
     * The jobs of the given uids whose record in the default language is hidden, as a map
     * of uid to `true`. A translated job carries the hidden flag of its translation, while
     * the detail page finds a job by its default record, so a list with "Show hidden
     * records" needs the flag of the default record to know which jobs it cannot link.
     *
     * @param int[] $uids The uids of the jobs, which are the uids of their default records.
     * @return array<int, true>
     */
    public function findUidsWithHiddenDefaultRecord(array $uids): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $hiddenUids = $queryBuilder
            ->select('uid')
            ->from('tx_academicjobs_domain_model_job')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_fill_keys(array_map(intval(...), $hiddenUids), true);
    }
}
