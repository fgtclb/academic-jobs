<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
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
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Company.svg',
    ],
    'academic_jobs-workLocation' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/Location.svg',
    ],
];
