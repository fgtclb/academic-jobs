<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// The way a site package replaces icons of the job views: under the identifiers
// academic_jobs renders, in the file of the frontend, from a package that loads after it.
return [
    'academic_jobs-contactPhone' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SitePhone.svg',
    ],
    'academic_jobs-companyName' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SiteCompany.svg',
    ],
];
