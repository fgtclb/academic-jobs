<?php

declare(strict_types=1);

return [
    \FGTCLB\AcademicJobs\Domain\Model\Job::class => [
        'tableName' => 'tx_academicjobs_domain_model_job',
        'recordType' => \FGTCLB\AcademicJobs\Domain\Model\Job::class,
    ],
    \FGTCLB\AcademicJobs\Domain\Model\Contact::class => [
        'tableName' => 'tx_academicjobs_domain_model_contact',
        'recordType' => \FGTCLB\AcademicJobs\Domain\Model\Contact::class,
    ],
];
