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

The block now renders two icons the extension registers,
:php:`tx-academicjobs-info-contact-phone` and
:php:`tx-academicjobs-info-contact-email`. They are rendered like the property
icons of the same view, as the shared Font Awesome Free glyphs of
:guilabel:`academic_base`, inlined and drawn in `currentColor`:

..  code-block:: html

    before: <span class="t3js-icon icon icon-size-small icon-state-default icon-default-not-found"
                  data-identifier="default-not-found" aria-hidden="true">
                <span class="icon-markup"><svg …>…</svg></span>
            </span>
    after:  <span class="t3js-icon icon icon-size-small icon-state-default icon-tx-academicjobs-info-contact-phone"
                  data-identifier="tx-academicjobs-info-contact-phone" aria-hidden="true">
                <span class="icon-markup"><svg … width="1em" height="1em" fill="currentColor">…</svg></span>
            </span>

The e-mail row is the same with :php:`tx-academicjobs-info-contact-email`. The
identifiers are those of
:ref:`breaking-jobs-job-icons-moved-to-the-frontend-icon-registry`.

Impact
======

A stock installation needs nothing, the block shows a phone and an e-mail icon
where it showed the placeholder. They take the colour of the surrounding text
and are as large as its font.

A site package that registered :php:`phone` or :php:`mail` to give this block
an icon now sees the shipped icons in the block. Its registrations stay
harmless and keep serving its own templates. To keep its own drawing in the
block, it registers the file under the identifier the block renders, in the
:file:`Configuration/FrontendIcons.php` of the site package. That file belongs
to the frontend icon registry of :guilabel:`academic_base`, which the block
renders its icons from, see
:ref:`breaking-jobs-job-icons-moved-to-the-frontend-icon-registry`. The site
package requires :composer:`fgtclb/academic-jobs` and lists
:php:`academic_jobs` under :php:`depends` in its :file:`ext_emconf.php`, so it
loads later and its registration wins:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/FrontendIcons.php

    use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

    return [
        'tx-academicjobs-info-contact-phone' => [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/Phone.svg',
        ],
        'tx-academicjobs-info-contact-email' => [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/Mail.svg',
        ],
    ];

:php:`SvgIconProvider` renders the file as an image of 16 by 16 pixels. A file
drawn in `currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
is inlined like the shipped icons.

A stylesheet that selects :css:`.icon-phone` or :css:`.icon-mail` has to select
the new identifier classes instead.

An installation that overrides
:file:`Resources/Private/Partials/Job/Contact.html` in its own site package
keeps its own output and its own identifiers.

Affected Installations
======================

Installations that display a job detail view with contact information.

.. index:: Frontend, Fluid, ext:academic_jobs
