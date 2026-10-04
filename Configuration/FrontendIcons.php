<?php

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * This file is part of the "academic_jobs" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * The icons of the job views, registered in the frontend icon registry of
 * EXT:academic_base and rendered with its `ab:icon` ViewHelper by the partials below
 * `Resources/Private/Partials/Job/`. The backend never shows them, so they are not in
 * `Configuration/Icons.php`.
 *
 * There is one identifier per job property, `tx-academicjobs-info-<property in kebab
 * case>`, so a site package replaces the icon of one property of the job views by
 * registering that identifier in its own `Configuration/FrontendIcons.php`. The drawings
 * are the shared Font Awesome Free glyphs of EXT:academic_base, drawn in `currentColor`
 * and inlined, so they take the colour and the size of the surrounding text. Several
 * properties share one file.
 *
 * The job list and the job detail view map the name of a property to its identifier in
 * `Job/Item.html` and `Job/Information.html`, the contact block names its two identifiers
 * in `Job/Contact.html`. `tx-academicjobs-info-starttime`, `-contact-name` and
 * `-contact-additional-information` are rendered by no shipped template. They are kept so
 * that an override that adds one of these properties to the list it shows gets its icon.
 */
return [
    'tx-academicjobs-info-starttime' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/calendar.svg',
    ],
    'tx-academicjobs-info-endtime' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/calendar.svg',
    ],
    'tx-academicjobs-info-company-name' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/company.svg',
    ],
    'tx-academicjobs-info-employment-type' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
    ],
    'tx-academicjobs-info-work-location' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/location.svg',
    ],
    'tx-academicjobs-info-employment-start-date' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/calendar.svg',
    ],
    'tx-academicjobs-info-sector' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/sector.svg',
    ],
    'tx-academicjobs-info-type' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
    ],
    'tx-academicjobs-info-required-degree' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/degree.svg',
    ],
    'tx-academicjobs-info-contractual-relationship' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/contract.svg',
    ],
    'tx-academicjobs-info-internationals-welcome' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/international.svg',
    ],
    'tx-academicjobs-info-alumni-recommend' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/recommendation.svg',
    ],
    'tx-academicjobs-info-link' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/link.svg',
    ],
    'tx-academicjobs-info-contact-name' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/person.svg',
    ],
    'tx-academicjobs-info-contact-email' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/email.svg',
    ],
    'tx-academicjobs-info-contact-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/phone.svg',
    ],
    'tx-academicjobs-info-contact-additional-information' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/information.svg',
    ],
];
