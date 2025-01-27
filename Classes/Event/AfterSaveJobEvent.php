<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Event;

use FGTCLB\AcademicJobs\Domain\Model\Job;

final class AfterSaveJobEvent
{
    public function __construct(private readonly Job $job)
    {
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
