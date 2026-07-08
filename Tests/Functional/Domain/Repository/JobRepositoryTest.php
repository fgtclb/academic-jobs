<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class JobRepositoryTest extends AbstractAcademicJobsTestCase
{
    private function getJobRepository(): JobRepository
    {
        return $this->get(JobRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Job> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $job) {
            $uids[] = (int)$job->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findAllJobsExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobRepository/jobs.csv');
        $result = $this->getJobRepository()->findAllJobs();
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findAllJobsIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobRepository/jobs.csv');
        $result = $this->getJobRepository()->findAllJobs(true);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    #[Test]
    public function findByJobTypeExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobRepository/jobs.csv');
        $result = $this->getJobRepository()->findByJobType(1);
        $this->assertSame([1], $this->resultUids($result));
    }

    #[Test]
    public function findByJobTypeIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobRepository/jobs.csv');
        $result = $this->getJobRepository()->findByJobType(1, true);
        $this->assertSame([1, 2], $this->resultUids($result));
    }
}
