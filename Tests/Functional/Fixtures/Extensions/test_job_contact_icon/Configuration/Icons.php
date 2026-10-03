<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// Replaces the phone icon of the job contact block the way a site package does: under the
// identifier academic_jobs renders, from a package that loads after it.
return [
    'academic_jobs-contactPhone' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_contact_icon/Resources/Public/Icons/SitePhone.svg',
    ],
];
