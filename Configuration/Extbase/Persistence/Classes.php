<?php

declare(strict_types=1);

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Model\Contact;

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
