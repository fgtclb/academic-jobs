<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Repository;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Job>
 */
class JobRepository extends Repository
{
    protected $defaultOrderings = [
        'starttime' => QueryInterface::ORDER_ASCENDING,
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
        return $query->execute();
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
        return $query->execute();
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
