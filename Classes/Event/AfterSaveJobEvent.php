<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Event;

use FGTCLB\AcademicJobs\Domain\Model\Job;

final class AfterSaveJobEvent
{
    private Job $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function getJob(): Job
    {
        return $this->job;
    }
}
