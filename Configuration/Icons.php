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
    // The plugin icon of the new content element wizard. The icons of the job views are
    // frontend icons and registered in `Configuration/FrontendIcons.php`.
    'academic_jobs_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_jobs/Resources/Public/Icons/jobs_icon.svg',
    ],
];
