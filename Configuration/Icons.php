<?php

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    /*
     * The record icon of the job table. It is registered with the provider of
     * EXT:academic_base, which inlines the file in both markups instead of rendering an
     * <img>. An <img> is opaque to CSS and keeps the colours of its file, so a record
     * icon drawn in a dark ink stays dark on the dark cards of the backend colour
     * scheme. Inlined and drawn in `currentColor` it follows the text colour.
     */
    'tx_academicjobs_domain_model_job' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/tx_academicjobs_domain_model_job.svg',
    ],
    'academic_jobs_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg',
    ],
    'academic_jobs-starttime' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Calendar.svg',
    ],
    'academic_jobs-endtime' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Calendar.svg',
    ],
    'academic_jobs-companyName' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Company.svg',
    ],
    'academic_jobs-employmentType' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Work.svg',
    ],
    'academic_jobs-workLocation' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Location.svg',
    ],
    'academic_jobs-employmentStartDate' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Calendar.svg',
    ],
    'academic_jobs-sector' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Industry.svg',
    ],
    'academic_jobs-type' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg',
    ],
    'academic_jobs-requiredDegree' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/School.svg',
    ],
    'academic_jobs-contractualRelationship' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Contract.svg',
    ],
    'academic_jobs-internationalsWelcome' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Public.svg',
    ],
    'academic_jobs-alumniRecommend' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Star.svg',
    ],
    'academic_jobs-link' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Link.svg',
    ],
    'academic_jobs-contactName' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Person.svg',
    ],
    'academic_jobs-contactEmail' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Email.svg',
    ],
    'academic_jobs-contactPhone' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Phone.svg',
    ],
    'academic_jobs-contactAdditionalInformation' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Info.svg',
    ],
];
