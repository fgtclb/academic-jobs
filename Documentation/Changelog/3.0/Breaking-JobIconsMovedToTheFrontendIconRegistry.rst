..  _breaking-jobs-job-icons-moved-to-the-frontend-icon-registry:

===========================================================
Breaking: The job icons moved to the frontend icon registry
===========================================================

Description
===========

The seventeen :php:`academic_jobs-*` icons are shown in the frontend only:

*   Twelve stand in front of the job properties of the job list and the job
    detail view: ``academic_jobs-employmentStartDate``,
    ``academic_jobs-companyName``, ``academic_jobs-sector``,
    ``academic_jobs-type``, ``academic_jobs-requiredDegree``,
    ``academic_jobs-contractualRelationship``,
    ``academic_jobs-employmentType``, ``academic_jobs-workLocation``,
    ``academic_jobs-internationalsWelcome``,
    ``academic_jobs-alumniRecommend``, ``academic_jobs-link`` and
    ``academic_jobs-endtime``.
*   Two stand in front of the phone number and the e-mail address of the
    contact block: ``academic_jobs-contactPhone`` and
    ``academic_jobs-contactEmail``.
*   Three are rendered by no template the extension ships:
    ``academic_jobs-starttime``, ``academic_jobs-contactName`` and
    ``academic_jobs-contactAdditionalInformation``. The partials build the
    identifier of a property icon as ``academic_jobs-<property>``, so an
    override that adds one of these properties to the list it shows gets its
    icon without registering one.

They were registered in :file:`Configuration/Icons.php`, for the icon registry
of the TYPO3 backend, and the partials rendered them with ``core:icon``.

They are now registered in :file:`Configuration/FrontendIcons.php`, for the
frontend icon registry of :guilabel:`academic_base`, and are no longer
registered in :file:`Configuration/Icons.php`. The partials
:file:`Partials/Job/Item.html`, :file:`Partials/Job/Information.html` and
:file:`Partials/Job/Contact.html` render them with the ``ab:icon`` ViewHelper
of :guilabel:`academic_base`, with the arguments they had. Identifiers, files
and icon provider are unchanged.

The record icon ``tx_academicjobs_domain_model_job`` and the plugin icon
``academic_jobs_icon`` stay in :file:`Configuration/Icons.php`.

Impact
======

Two things change for a site, and neither shows an error:

*   A site package that replaced one of the seventeen icons in its own
    :file:`Configuration/Icons.php` sees the shipped drawing again in the job
    list and the job detail view. The frontend icon registry does not read that
    file.
*   An override of one of the three partials that still renders one of the
    seventeen icons with ``core:icon`` shows TYPO3's not-found icon in its
    place, because the icon registry of the backend no longer knows the
    identifier.

PHP or backend code that asks the :php:`IconFactory` of TYPO3 for one of the
seventeen identifiers gets the not-found icon as well.

The rendered markup of the icons is the same as before, an image of 16 by 16
pixels with the same classes and attributes, so a site stylesheet needs no
change.

Affected Installations
======================

Every installation whose site package replaces one of the seventeen icons, or
overrides one of the three partials, or renders one of the seventeen
identifiers in a template or PHP code of its own.

Migration
=========

#.  Move a replacement of one of the seventeen icons from the
    :file:`Configuration/Icons.php` of the site package to its
    :file:`Configuration/FrontendIcons.php`. The file has the format of
    :file:`Icons.php`. The site package has to depend on
    :guilabel:`academic_jobs`, so its entry is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

        return [
            'academic_jobs-contactPhone' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/Phone.svg',
            ],
            'academic_jobs-companyName' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/Company.svg',
            ],
        ];

#.  In an override of :file:`Item.html`, :file:`Information.html` or
    :file:`Contact.html`, replace ``<core:icon`` with ``<ab:icon`` for the
    seventeen identifiers, keep every argument, and declare the namespace in the
    :html:`<html>` tag of the partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``. An icon
    of the project in the same partial either stays on ``core:icon`` or is
    registered in the :file:`Configuration/FrontendIcons.php` of the site
    package and rendered with ``ab:icon`` too.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Fluid, Frontend, ext:academic_jobs
