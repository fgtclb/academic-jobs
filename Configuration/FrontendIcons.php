<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

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
 * `Configuration/Icons.php`. A site package that depends on this extension replaces one
 * by registering its identifier in its own `Configuration/FrontendIcons.php`.
 *
 * The job list and the job detail view build the identifier of a property icon as
 * `academic_jobs-<property>`. `academic_jobs-starttime`, `-contactName` and
 * `-contactAdditionalInformation` are rendered by no shipped template. They are kept so
 * that an override that adds one of these properties to the list it shows gets its icon.
 *
 * Rendered with the default markup, an <img> of 16 by 16 pixels, as they always were.
 */
return [
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
