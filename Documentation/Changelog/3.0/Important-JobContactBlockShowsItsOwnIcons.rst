.. _important-job-contact-block-shows-its-own-icons:

====================================================
Important: The job contact block shows its own icons
====================================================

Description
===========

The contact block of the job detail view asked the icon registry for the
identifiers :php:`phone` and :php:`mail`. Neither TYPO3 v13 nor v14 registers
them, and no academic extension did, so a stock installation showed the "icon
not found" drawing of TYPO3 in front of the contact phone number and the
contact e-mail address. A real icon only appeared where the site package
happened to register :php:`phone` and :php:`mail` itself.

The block now renders the two icons the extension ships and has always
registered, :php:`academic_jobs-contactPhone` and
:php:`academic_jobs-contactEmail`. They are rendered like the property icons of
the same view, as an image of 16 by 16 pixels, instead of an inlined SVG:

..  code-block:: html

    before: <span class="t3js-icon icon icon-size-small icon-state-default icon-default-not-found"
                  data-identifier="default-not-found" aria-hidden="true">
                <span class="icon-markup"><svg …>…</svg></span>
            </span>
    after:  <span class="t3js-icon icon icon-size-small icon-state-default icon-academic_jobs-contactPhone"
                  data-identifier="academic_jobs-contactPhone" aria-hidden="true">
                <span class="icon-markup"><img src="…/Icons/Phone.svg" width="16" height="16" alt="" /></span>
            </span>

The e-mail row is the same with :php:`academic_jobs-contactEmail` and
:file:`Icons/Email.svg`. TYPO3 v14 appends a version query string to the image
source.

Impact
======

A stock installation needs nothing, the block shows a phone and an e-mail icon
where it showed the placeholder.

A site package that registered :php:`phone` or :php:`mail` to give this block
an icon now sees the shipped icons in the block. Its registrations stay
harmless and keep serving its own templates. To keep its own drawing in the
block, it registers the file under the identifier the block renders, in the
:file:`Configuration/Icons.php` of the site package. The site package requires
:composer:`fgtclb/academic-jobs` and lists :php:`academic_jobs` under
:php:`depends` in its :file:`ext_emconf.php`, so it loads later and its
registration wins:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/Icons.php

    use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

    return [
        'academic_jobs-contactPhone' => [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/Phone.svg',
        ],
        'academic_jobs-contactEmail' => [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/Mail.svg',
        ],
    ];

A provider that inlines in both markups, such as
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
gives the block an inlined SVG again.

A stylesheet that selects :css:`.icon-phone` or :css:`.icon-mail`, or an
inlined :html:`<svg>` inside :css:`.academic-jobs-contact`, has to select the
new identifier classes and the :html:`<img>` instead.

An installation that overrides
:file:`Resources/Private/Partials/Job/Contact.html` in its own site package
keeps its own output and its own identifiers.

Affected Installations
======================

Installations that display a job detail view with contact information.

.. index:: Frontend, Fluid, ext:academic_jobs
