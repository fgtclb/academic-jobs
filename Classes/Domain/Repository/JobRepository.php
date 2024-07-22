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
     * @return QueryResultInterface<Job>
     */
    public function findByJobType(int $jobType): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('type', $jobType)
        );
        return $query->execute();
    }
}
