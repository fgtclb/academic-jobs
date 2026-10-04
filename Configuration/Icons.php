<?php

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The icons the TYPO3 backend shows: the content element icon of the three job plugins,
 * in the page module and in the new content element wizard, and the record icon of the
 * job table. Both draw the Font Awesome Free briefcase of the shared set of
 * EXT:academic_base, whose Resources/Public/Icons/LICENSE-font-awesome.txt lists it.
 *
 * They are registered with the provider of EXT:academic_base, which inlines the file in
 * both markups instead of rendering an <img>. An <img> is opaque to CSS and keeps the
 * colours of its file, so an icon drawn in a dark ink stays dark on the dark cards of the
 * backend colour scheme. Inlined and drawn in `currentColor` it follows the text colour.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`. The icons
 * of the job views are frontend icons and registered in `Configuration/FrontendIcons.php`.
 */
return [
    'tx-academicjobs-plugin-jobs' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
    ],
    'tx-academicjobs-record-job' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
    ],
];
