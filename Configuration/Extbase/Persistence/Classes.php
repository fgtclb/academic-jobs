<?php

declare(strict_types=1);

use FGTCLB\AcademicJobs\Domain\Model\Contact;
use FGTCLB\AcademicJobs\Domain\Model\Job;

return [
    Job::class => [
        'tableName' => 'tx_academicjobs_domain_model_job',
        'recordType' => Job::class,
    ],
    Contact::class => [
        'tableName' => 'tx_academicjobs_domain_model_contact',
        'recordType' => Contact::class,
    ],
];
