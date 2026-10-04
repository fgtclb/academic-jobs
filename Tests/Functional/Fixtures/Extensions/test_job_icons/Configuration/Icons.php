<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// Replacements left in the file of the backend registry, which the frontend does not read.
return [
    'tx-academicjobs-info-contact-email' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SiteBackend.svg',
    ],
    'tx-academicjobs-info-work-location' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:test_job_icons/Resources/Public/Icons/SiteBackend.svg',
    ],
];
