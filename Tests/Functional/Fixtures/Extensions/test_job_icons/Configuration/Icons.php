<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// Replacements left in the file of the backend registry, which the frontend does not read.
return [
    'academic_jobs-contactEmail' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SiteBackend.svg',
    ],
    'academic_jobs-workLocation' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SiteBackend.svg',
    ],
];
