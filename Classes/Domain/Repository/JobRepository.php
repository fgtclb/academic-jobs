<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Repository;

use FGTCLB\AcademicBase\Domain\Repository\HiddenRecordsQueryTrait;
use FGTCLB\AcademicJobs\Domain\Model\Job;
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
}
