..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, applies the validation settings of
:guilabel:`academic_jobs` to the backend record editor as well, renders the job
views with speaking classes and their icons from the frontend icon registry of
:guilabel:`academic_base`, and loads the editor configuration of the job form
as an ES module. It changes no database table of this extension. Work through
the sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_jobs` 3.0 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires instead of 1.x. Composer updates it
    on its own. A classic installation replaces
    :guilabel:`environment_state_manager` 1.x with 2.x before it activates
    :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

A site package that ships its own
:file:`Configuration/AcademicJobs/Settings.yaml` changes it in the same
deployment as the update:

*   Replace :yaml:`number` with :yaml:`tel` on :yaml:`contactPhone`, or remove
    the field from the file so the shipped flags apply. The flags reach the
    backend record editor of a job now, and a :yaml:`number` there stores
    :code:`+49 30 123` as :code:`49` when an editor saves the job. Move a TCA
    override of :php:`required` or :php:`readOnly` of a job field into the
    settings file, the settings win over it now. See
    :ref:`breaking-job-validation-settings-reach-the-backend`.
*   The files of all packages are merged per field now. A field the copy left
    out to make it optional keeps the flags :guilabel:`academic_jobs` ships.
    Name it with an empty list instead, and drop the fields the site package
    does not change. See
    :ref:`breaking-job-validation-settings-merged-per-field`.
*   Flags are read regardless of case, a flag outside the shared vocabulary is
    ignored, and :yaml:`readonly`, :yaml:`disabled` and
    :yaml:`frontendreadonly` take effect. Check the flags of the file against
    :ref:`configuration-validations`, see
    :ref:`important-job-validation-flags-follow-the-shared-vocabulary`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. The content element wizard
``academicJobs_pluginContent`` of this extension reads the column
:sql:`tt_content.list_type`, which TYPO3 14 removes, so it only runs on
TYPO3 13. The same holds for the content element wizards of
:guilabel:`academic_persons`, :guilabel:`academic_persons_edit` and
:guilabel:`academic_projects` when the installation also has them. Move to
TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-jobs':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_jobs` there, in this order.
Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3` instead
of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The comparison changes no table of this extension. It applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the columns other
academic extensions add in 3.0.

..  _upgrade-from-2-4-to-3-0-wizards:

Run the upgrade wizards
=======================

#.  ``academicJobs_pluginContent``, :guilabel:`Migrate plugin list element from
    academic_jobs to normal content elements`. It turns every content element
    that is still stored as :guilabel:`Insert plugin` with one of the plugins
    `academicjobs_newjobform`, `academicjobs_list` or `academicjobs_detail`
    into a content element of the type of the same name. Such elements are
    left from version 1.x. The wizard reads :sql:`tt_content.list_type`, which
    TYPO3 14 removes, so run it on TYPO3 13, by its name, in
    :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the command line:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicJobs_pluginContent

    Once the column is gone, the wizard finds nothing to do, and the elements
    it would have migrated keep a type nothing renders. TypoScript, page
    TSconfig or SQL of a site package that names one of the plugins through
    :sql:`list_type` matches on :sql:`CType` instead. See
    :ref:`breaking-plugins-require-ctype-on-typo3-v14`.

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension. Run it on the staging copy right after the package update:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the template overrides of
the site with the templates this extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_jobs --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-4-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

The site set, the static templates and the page TSconfig of this extension keep
their names and paths from 2.4.

*   The notification mail about a new job is rendered from the Fluid mail
    template `JobCreated` now, with a default message in the language of the
    page. A site that set :typoscript:`plugin.tx_academicjobs.email.template`
    keeps its own text in the mail. One that wants the old sentence sets it
    there. See :ref:`feature-1790666339` and
    :ref:`configuration-general-notification-mail`.
*   A site that loads its own editor, or none, switches the two scripts of the
    job form off with :typoscript:`plugin.tx_academicjobs.assets.js = 0`, see
    :ref:`feature-1791566501`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The extension shipped no
stylesheet in 2.4 and ships none in 3.0, so a site that relied on the card and
list group classes of a Bootstrap theme styles the new classes itself. Move
the selectors of the site stylesheet to the classes in the table of
:ref:`breaking-jobs-speaking-frontend-classes`. The chapter :ref:`styling`
describes the markup, and :ref:`styling-example` names the stylesheet of the
development instances as a starting point. The new job form renders the form
partials of :guilabel:`academic_base`, whose classes are described in its
`changelog
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Breaking-SpeakingFrontendClasses.html>`__.

..  _upgrade-from-2-4-to-3-0-images:

The job image
-------------

The job image is a :html:`<picture>` with WebP sources now. CSS that selects
the image as a direct child of the card no longer matches. The job views and
the new job form render partials of :guilabel:`academic_base`, so a site that
replaced the partial root paths of the plugin completely adds the path of
:guilabel:`academic_base` below its own:

..  code-block:: typoscript

    plugin.tx_academicjobs.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

See :ref:`breaking-jobs-images-render-as-picture`.

..  _upgrade-from-2-4-to-3-0-rich-text:

The editor configuration
------------------------

The editor configuration of the new job form is an ES module now:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_jobs/Resources/Public/JavaScript/frontend/ckeditor.js`
        -   `EXT:academic_jobs/Resources/Public/JavaScript/frontend/rich-text.js`,
            loaded as the module `@fgtclb/academic-jobs/frontend/rich-text.js`

An override of :file:`Templates/Job/New.html` loads it with
:html:`<f:asset.module identifier="@fgtclb/academic-jobs/frontend/rich-text.js" />`.
:html:`<f:asset.script>` does not execute a module. See
:ref:`breaking-ckeditor-configuration-is-a-module`.

..  _upgrade-from-2-4-to-3-0-icons:

Icons
-----

Every icon of the extension has a new identifier. The icons of the job
properties and of the contact block are registered for the frontend icon
registry of :guilabel:`academic_base` in
:file:`Configuration/FrontendIcons.php` and rendered with ``ab:icon``, the
icons of the content elements and of the job record stay backend icons in
:file:`Configuration/Icons.php`. The old identifiers and the icon files of the
extension are removed. Replace the identifiers in templates, TCA, page TSconfig
and PHP code, register a replacement icon under its new identifier in the
matching file of a site package that depends on :guilabel:`academic_jobs`, and
adapt a stylesheet that selected the icon as an :html:`<img>`. See
:ref:`breaking-jobs-job-icons-moved-to-the-frontend-icon-registry`,
:ref:`important-job-contact-block-shows-its-own-icons` and
:ref:`templates-override-icons`.

..  _upgrade-from-2-4-to-3-0-overrides:

Template overrides
------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Job/List.html`
        -   The jobs and the new pagination sit in an `ace-content`, and the
            list renders one page of the paginator while the pagination is
            enabled. A project that paginated the list itself removes its own
            switch.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`,
            :ref:`feature-1790660966`
    *   -   :file:`Templates/Job/Show.html`
        -   Speaking classes, the description in a `ce-bodytext`.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`
    *   -   :file:`Templates/Job/New.html`
        -   Loads the editor configuration as a module, only while
            :typoscript:`settings.assets.js` is set, and renders the new
            partial :file:`Partials/Job/Forms/AdditionalFields.html`.
        -   :ref:`upgrade-from-2-4-to-3-0-rich-text`,
            :ref:`feature-1791566501`,
            :ref:`feature-new-job-form-additional-fields-partial`
    *   -   :file:`Partials/Job/Item.html`,
            :file:`Partials/Job/Information.html`
        -   Speaking classes, the image through the image partial of
            :guilabel:`academic_base`, and a list of properties that maps each
            property to the name of its icon.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`,
            :ref:`upgrade-from-2-4-to-3-0-images`,
            :ref:`upgrade-from-2-4-to-3-0-icons`
    *   -   :file:`Partials/Job/Contact.html`
        -   Speaking classes, and its own phone and e-mail icons.
        -   :ref:`important-job-contact-block-shows-its-own-icons`
    *   -   :file:`Partials/Job/Header.html`,
            :file:`Partials/Job/SectionHeader.html`
        -   They take the class of the heading as `class` instead of
            `positionClass`.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`
    *   -   :file:`Partials/Job/Properties/Job.html`
        -   The two job flags are offered as checkboxes, and a select reads
            `disabled` from its element.
        -   :ref:`important-new-job-form-offers-the-job-flags`,
            :ref:`important-new-job-form-fields-render-shared-partials`
    *   -   :file:`Partials/Job/Forms/*.html`
        -   They render the shared form partials of :guilabel:`academic_base`.
            The ViewHelpers :html:`j:validation.requiredFromValidation` and
            :html:`j:validation.fieldTypeFromValidation` are removed, a copy
            that calls them fails. It reads the validation with
            :html:`validationEnsure` of :guilabel:`academic_base`.
            :file:`Partials/Job/Forms/ErrorMessage.html` is removed.
        -   :ref:`upgrade-from-2-4-to-3-0-code`,
            :ref:`templates-override-form-fields`

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

*   A listener of
    :php:`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent` is
    no longer called. It listens to
    :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` instead and returns
    early for every other plugin, see
    :ref:`breaking-removed-job-new-action-view-event`,
    :ref:`feature-plugin-view-event` and
    :ref:`developers-event-modify-plugin-view-event`.
*   PHP code that uses
    :php:`\FGTCLB\AcademicJobs\Loader\AcademicJobsSettingsLoader`,
    :php:`\FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry` or the
    exceptions of :php:`\FGTCLB\AcademicJobs\Exception` uses the settings
    classes of :guilabel:`academic_base`, see
    :ref:`breaking-job-validation-settings-classes-removed`.
*   Code that names
    :php:`\FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode` imports
    :php:`\FGTCLB\AcademicBase\Form\FlashMessageCreationMode`. The old name
    keeps working until version 4.0, see
    :ref:`deprecation-flash-message-creation-mode-moved`.
*   The FlexForm files moved from :file:`Configuration/Flexforms/Core12/` and
    :file:`Configuration/Flexforms/Core13/` to one file each in
    :file:`Configuration/FlexForms/`. Own TCA that names a data structure of
    this extension by its path takes over the new one, see
    :ref:`important-job-flexform-files-moved`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
*   The content elements are offered as :guilabel:`Job Form`,
    :guilabel:`Job List` and :guilabel:`Job Details` now, see
    :ref:`important-content-element-titles-and-descriptions`.
*   Saving a job in the backend keeps its contact phone number, and a job
    without a company name or a description can only be saved there once both
    are filled in, unless the settings make them optional.
*   A logo uploaded through the form is stored with a random suffix to its
    file name, and a form shown again with errors asks for the logo once
    more. Check code that relies on the file name of an uploaded logo, see
    :ref:`important-migrated-job-image-upload-to-native-extbase-file-upload-handling`.
*   The rich text fields of the form start the editor, and the job views show
    their icons in the colour of the text.
