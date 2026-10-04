..  _breaking-jobs-job-icons-moved-to-the-frontend-icon-registry:

===================================================================
Breaking: The job icons are renamed and moved to the frontend icons
===================================================================

Description
===========

Every icon of this extension has a new identifier and a new drawing
(ACE-813, ACE-587).

The seventeen icons of the job properties are shown in the frontend only:

*   Twelve stand in front of the job properties of the job list and the job
    detail view.
*   Two stand in front of the phone number and the e-mail address of the
    contact block.
*   Three are rendered by no template the extension ships: the start time, the
    contact name and the additional contact information. They stay registered,
    so an override that adds one of these properties to the list it shows gets
    its icon without registering one.

They were registered in :file:`Configuration/Icons.php`, for the icon registry
of the TYPO3 backend, and the partials rendered them with ``core:icon``. They
are now registered in :file:`Configuration/FrontendIcons.php`, for the
frontend icon registry of :guilabel:`academic_base`, and are no longer
registered in :file:`Configuration/Icons.php`. The partials
:file:`Partials/Job/Item.html`, :file:`Partials/Job/Information.html` and
:file:`Partials/Job/Contact.html` render them with the ``ab:icon`` ViewHelper
of :guilabel:`academic_base`.

Every job property keeps an identifier of its own, so a site still replaces
the icon of one property without touching the others. The identifiers follow
the scheme of all academic extensions,
``tx-<extension key without underscores>-<group>-<name>``: a property icon is
``tx-academicjobs-info-`` and the property name in kebab case. The partials no
longer build the identifier from the property name, they map each property
they show to its identifier in the list they loop over.

The drawings are the shared Font Awesome Free glyphs of
:guilabel:`academic_base`, several properties share one. They were fifteen
files of this extension in three different inks, grey Material Symbols, black
Font Awesome drawings and the red plugin drawing for the job type, rendered as
an image of 16 by 16 pixels that kept the colour of its file. The glyphs are
inlined now and drawn in `currentColor`: they take the colour of the
surrounding text and are as large as its font.

The content elements and the job record get their own identifiers in
:file:`Configuration/Icons.php`. Both draw the Font Awesome Free briefcase of
the shared set of :guilabel:`academic_base`, :file:`info/employment.svg`, in
`currentColor`, so the content element icon follows the backend colour scheme
like the record icon does.

The old identifiers are removed without an alias:

..  csv-table::
    :header: "2.x identifier", "3.0 identifier", "Registry"

    ":php:`academic_jobs_icon`", ":php:`tx-academicjobs-plugin-jobs`", ":file:`Icons.php`"
    "none, the table named its file", ":php:`tx-academicjobs-record-job`", ":file:`Icons.php`"
    ":php:`academic_jobs-starttime`", ":php:`tx-academicjobs-info-starttime`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-endtime`", ":php:`tx-academicjobs-info-endtime`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-employmentStartDate`", ":php:`tx-academicjobs-info-employment-start-date`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-companyName`", ":php:`tx-academicjobs-info-company-name`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-employmentType`", ":php:`tx-academicjobs-info-employment-type`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-workLocation`", ":php:`tx-academicjobs-info-work-location`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-sector`", ":php:`tx-academicjobs-info-sector`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-type`", ":php:`tx-academicjobs-info-type`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-requiredDegree`", ":php:`tx-academicjobs-info-required-degree`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-contractualRelationship`", ":php:`tx-academicjobs-info-contractual-relationship`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-internationalsWelcome`", ":php:`tx-academicjobs-info-internationals-welcome`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-alumniRecommend`", ":php:`tx-academicjobs-info-alumni-recommend`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-link`", ":php:`tx-academicjobs-info-link`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-contactName`", ":php:`tx-academicjobs-info-contact-name`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-contactEmail`", ":php:`tx-academicjobs-info-contact-email`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-contactPhone`", ":php:`tx-academicjobs-info-contact-phone`", ":file:`FrontendIcons.php`"
    ":php:`academic_jobs-contactAdditionalInformation`", ":php:`tx-academicjobs-info-contact-additional-information`", ":file:`FrontendIcons.php`"

The contact block of 2.x rendered the identifiers :php:`phone` and
:php:`mail` instead of its two registered icons, and nothing registers those,
see :ref:`important-job-contact-block-shows-its-own-icons`.

The files below :file:`Resources/Public/Icons/` are removed with them:
:file:`Calendar.svg`, :file:`Company.svg`, :file:`Contract.svg`,
:file:`Email.svg`, :file:`Industry.svg`, :file:`Info.svg`, :file:`Link.svg`,
:file:`Location.svg`, :file:`Person.svg`, :file:`Phone.svg`, :file:`Public.svg`,
:file:`School.svg`, :file:`Star.svg`, :file:`Work.svg`, :file:`jobs_icon.svg`,
the former job record icon :file:`tx_academicjobs_domain_model_job.svg`, and
:file:`tx_academicjobs_domain_model_contact.svg`, which nothing referenced any
more. The extension ships no icon file of its own apart from
:file:`Extension.svg`, which is unchanged. The origin and licence of the
shared files are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt` of
:guilabel:`academic_base`.

Impact
======

None of the following shows an error:

*   A template, TCA or page TSconfig of a site package that names one of the
    old identifiers shows the "icon not found" placeholder.
*   A site package that replaced one of the property icons in its own
    :file:`Configuration/Icons.php`, or under its old identifier, sees the
    shipped drawing in the job list and the job detail view. The frontend icon
    registry does not read that file, and nothing renders the old identifier.
*   An override of one of the three partials that still renders a property
    icon with ``core:icon``, or builds the old identifier, shows the "icon not
    found" placeholder in its place.
*   A reference to one of the removed files fails.

PHP or backend code that asks the :php:`IconFactory` of TYPO3 for one of the
seventeen property icons gets the "icon not found" placeholder as well.

The markup of the property icons changes from an image to an inlined drawing:

..  code-block:: html

    before: <span class="t3js-icon icon icon-size-small icon-state-default icon-academic_jobs-workLocation"
                  data-identifier="academic_jobs-workLocation" aria-hidden="true">
                <span class="icon-markup"><img src="…/Icons/Location.svg" width="16" height="16" alt="" /></span>
            </span>
    after:  <span class="t3js-icon icon icon-size-small icon-state-default icon-tx-academicjobs-info-work-location"
                  data-identifier="tx-academicjobs-info-work-location" aria-hidden="true">
                <span class="icon-markup"><svg … width="1em" height="1em" fill="currentColor">…</svg></span>
            </span>

A stylesheet that selects the icon wrapper by an old identifier class
(:css:`.icon-academic_jobs-*`) or the icon as an :html:`<img>` matches nothing.

Affected Installations
======================

Every installation whose site package replaces one of the job icons, styles
them, overrides one of the three partials, or names one of the old identifiers
or files in a template, TCA, page TSconfig or PHP code of its own.

Migration
=========

#.  Replace each old identifier by its 3.0 identifier from the table above.
    Register a replacement of a property icon in the
    :file:`Configuration/FrontendIcons.php` of the site package, the backend
    icons in its :file:`Configuration/Icons.php`. Both files have the same
    format. The site package has to depend on :guilabel:`academic_jobs`, so its
    entry is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

        return [
            'tx-academicjobs-info-contact-phone' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/Phone.svg',
            ],
            'tx-academicjobs-info-company-name' => [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/Company.svg',
            ],
        ];

    :php:`SvgIconProvider` renders the file as an image of 16 by 16 pixels, as
    before. :php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
    inlines a file drawn in `currentColor`, like the shipped icons.
#.  In an override of :file:`Item.html` or :file:`Information.html`, take over
    the property list of the shipped partial, which maps every property to the
    name of its icon, and render
    ``<ab:icon identifier="tx-academicjobs-info-{iconName}"/>``. In an override
    of :file:`Contact.html`, render ``tx-academicjobs-info-contact-phone`` and
    ``tx-academicjobs-info-contact-email``. Render the icons with ``ab:icon``
    instead of ``core:icon``, keep every argument, and declare the namespace in
    the :html:`<html>` tag of the partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``.
#.  Adapt a stylesheet to the :css:`.icon-tx-academicjobs-info-*` classes and
    to an inlined :html:`<svg>` instead of an :html:`<img>`.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Backend, Fluid, Frontend, TCA, TSConfig, ext:academic_jobs
