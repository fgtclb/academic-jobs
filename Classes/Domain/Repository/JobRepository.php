<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Repository;

use FGTCLB\AcademicBase\Persistence\HiddenRecordsFetcher;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository
{
    /**
     * `starttime` is `0` for every job that was never scheduled, so on its own it leaves
     * most of the list in DBMS row order - which is not the same list twice on PostgreSQL
     * (ACE-491). `uid` settles those ties deterministically.
     */
    protected $defaultOrderings = [
        'starttime' => QueryInterface::ORDER_ASCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING,
    ];

    public function __construct(
        private readonly HiddenRecordsFetcher $hiddenRecordsFetcher,
    ) {
        parent::__construct();
    }

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
        return $this->hiddenRecordsFetcher->execute($query);
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
        return $this->hiddenRecordsFetcher->execute($query);
    }

    /**
     * Include hidden (disabled) records in the query, independent of the
     * Context API visibility settings. Only the "hidden" enable column is
     * ignored; deleted/starttime/endtime/fe_group restrictions stay intact.
     *
     * @param QueryInterface<Job> $query
     */
    private function includeHiddenRecords(QueryInterface $query): void
    {
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setEnableFieldsToBeIgnored(['disabled']);
    }
}
