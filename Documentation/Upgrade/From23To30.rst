..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_jobs` 2.3 straight to
3.0, without the step to 2.4. Version 3.0 drops TYPO3 12, applies the
validation settings to the backend record editor as well and renders the job
views with speaking classes and new icons. From 2.4 it takes over the
configuration per component, the content elements that stay hidden until a site
enables them, and the upgrade wizards for the job contacts and the jobs of the
old new job form. The page leaves out the 2.4 state that 3.0 replaces: the
editor configuration :file:`JavaScript/frontend/ckeditor.js`, see
:ref:`breaking-ckeditor-configuration-moved`. Work through the sections in
order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_jobs` 3.0 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` 2.x
    before it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

:file:`ext_emconf.php` names the system extensions :guilabel:`rte_ckeditor`
and :guilabel:`fluid_styled_content` by their extension keys since 2.4. A
classic installation resolves its dependencies from that file, so both have to
be active. See :ref:`important-1790336613`.

A site package that ships its own
:file:`Configuration/AcademicJobs/Settings.yaml` changes it in the same
deployment as the update:

*   Replace :yaml:`number` with :yaml:`tel` on :yaml:`contactPhone`, or remove
    the field from the file so the shipped flags apply. The shipped file of
    2.3 marked the field with :yaml:`number`, and so does a copy of it. The
    flags reach the backend record editor of a job now, and
    a :yaml:`number` there stores :code:`+49 30 123` as :code:`49` when an
    editor saves the job. Move a TCA override of :php:`required` or
    :php:`readOnly` of a job field into the settings file, the settings win
    over it now. See :ref:`breaking-job-validation-settings-reach-the-backend`
    and :ref:`important-new-job-form-stores-the-chosen-values`.
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

A site without the site set also includes the page TSconfig of the components
in the same deployment, or the content elements are not offered for new
content, see :ref:`upgrade-from-2-3-to-3-0-components`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
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

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-jobs':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_jobs` there, in this order.
Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3` instead
of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

For this extension the comparison adds and changes:

*   :sql:`company_name`, :sql:`sector`, :sql:`required_degree` and
    :sql:`contractual_relationship` of :sql:`tx_academicjobs_domain_model_job`
    become nullable. No data is converted, see :ref:`important-1785882500`.
*   :sql:`l10n_source` is added to the same table, where a translation records
    the record it was made from. Existing rows get :sql:`0`, see
    :ref:`important-ace-463-academic-jobs`.

It also applies the changes of the index definitions TYPO3 13.4.35 and 14.3.7
bring.

..  warning::

    Do not remove the old job contact table and field yet. Version 2.1 dropped
    :sql:`tx_academicjobs_domain_model_contact` and the field :sql:`contact` of
    the job table, and the analyzer offers both in its remove section, first
    to rename them to :sql:`zzz_deleted_tx_academicjobs_domain_model_contact`
    and :sql:`zzz_deleted_contact`, then to drop them. Renaming is harmless.
    Dropping them deletes the contacts the wizard of the next section still
    has to copy, see :ref:`important-job-contact-wizard-finds-renamed-table`.
    :bash:`extension:setup` removes nothing, it applies additions and changes
    only.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

The wizards need the database changes of the previous section. Run them on
TYPO3 13, in this order, in :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`
or on the command line.

#.  ``academicJobs_contactRelation``, :guilabel:`Migrate job contact records
    from relation to fields directly in the job record`. It copies the contact
    record of a job into the contact fields of the job. Up to 2.3 it left out
    every job that was hidden, scheduled or expired while it ran, which
    includes every job submitted through the frontend form and not yet
    approved. It now reads every job and leaves a job alone that has a contact
    of its own. An installation that already ran it has it recorded as done
    and marks it undone first. For a wizard that was never recorded as done,
    the command reports an error, which is harmless. Then run the wizard:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:mark:undone academicJobs_contactRelation
        vendor/bin/typo3 upgrade:run academicJobs_contactRelation

    Read :ref:`important-job-contact-wizard-migrates-invisible-jobs` before
    running it: a job whose contact fields an editor emptied on purpose gets
    its old contact back.

#.  ``academicJobs_repairNewJobFormValues``, :guilabel:`Repair the jobs stored
    by the old new job form of academic jobs`. It moves the start date and the
    application deadline the old form stored with the time of day of the
    submission to whole days, and lists the jobs that kept an unchosen
    :guilabel:`Employment Type` or :guilabel:`Job Type` for an editor. It
    writes the database directly, in the time zone of the process that runs
    it, and a wrong zone moves the dates in a way that cannot be taken back.
    Check the time zone its description names first. If it is not the zone
    the form ran in, set
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone']`, or run the
    wizard from the upgrade module, which runs in the web server like the form
    did:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:list
        vendor/bin/typo3 upgrade:run academicJobs_repairNewJobFormValues
        vendor/bin/typo3 cache:flush

    Keep the list of jobs it prints, it is shown once. Correct those jobs in
    the backend. See :ref:`important-repair-wizard-for-jobs-of-the-old-form`.

#.  ``academicJobs_pluginContent``, :guilabel:`Migrate plugin list element
    from academic_jobs to normal content elements`. It turns every content
    element that is still stored as :guilabel:`Insert plugin` with one of the
    plugins `academicjobs_newjobform`, `academicjobs_list` or
    `academicjobs_detail` into a content element of the type of the same name.
    Such elements are left from version 1.x. The wizard reads
    :sql:`tt_content.list_type`, which TYPO3 14 removes, so run it before the
    core moves to TYPO3 14, by its name:

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

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are delivered per content element since
2.4, and the three content elements are hidden in the whole installation until
the page TSconfig of their component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension. Run it on the staging copy right after the package update, and again
once the steps of this section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the template overrides of
the site with the templates this extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_jobs --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-components:

Include the components
----------------------

*   **Site set.** A site that depends on the set `fgtclb/academic-jobs` needs
    no change. The set keeps its name and brings the page TSconfig of all
    three content elements along.
*   **Static template.** The stored entry of 2.3 keeps working as
    :guilabel:`Academic Jobs: Shared plugin settings (academic_jobs)`. Replace
    it with :guilabel:`Academic Jobs: All components (academic_jobs)`, or with
    the entries of the content elements the site uses, in the field
    :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options` of the template record.
*   **Page TSconfig.** A site without the site set adds
    :guilabel:`Academic Jobs: All components (academic_jobs)` in the field
    :guilabel:`Include static Page TSconfig (from extensions)` on the tab
    :guilabel:`Resources` of its root page, see :ref:`static-pagetsconfig`.
    Without it the content elements are not offered for new content.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and its
    files were reorganised. Take the new path of every :typoscript:`@import`
    from the table of the changelog entry linked above, see also
    :ref:`important-1787141400`. An import of a missing file fails silently.
*   A site whose content element layout renders no header sets
    :typoscript:`plugin.tx_academicjobs.renderContentElementHeader = 1`, also
    available as a site setting. The plugins no longer render the header
    themselves, see :ref:`important-1790323400`.
*   Label overrides move from :typoscript:`plugin.tx_academic_jobs._LOCAL_LANG`
    to :typoscript:`plugin.tx_academicjobs._LOCAL_LANG`, see
    :ref:`important-label-overrides-use-the-documented-path`.
*   An empty
    :typoscript:`plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes`
    accepts every media type. List the accepted types to keep restricting the
    logo, see :ref:`important-ace-267-academic-jobs`.
*   The job detail page answers ``404`` without a job. Hide it in menus, a menu
    link carries no job. The label
    ``tx_academicjobs.fe.alert.job_not_found.body`` it showed before is
    removed, so an override of it has no effect any more. See
    :ref:`important-job-detail-answers-not-found-without-a-job`.
*   The job detail page sets the page title. A site whose own page title
    provider has to win orders it before :typoscript:`academicJobs`, see
    :ref:`important-job-detail-sets-the-page-title`.
*   A site that imports :file:`Configuration/Routes/Detail.yaml` needs no
    change. A site that copied the route enhancer into its own configuration
    sets :yaml:`defaultController: 'Job::show'` in the copy, see
    :ref:`configuration-route-enhancers`.
*   The notification mail about a new job is rendered from the Fluid mail
    template `JobCreated`, with a default message in the language of the page.
    A site that set :typoscript:`plugin.tx_academicjobs.email.template` keeps
    its own text in the mail. See :ref:`feature-1790666339` and
    :ref:`configuration-general-notification-mail`.
*   A site that loads its own editor, or none, switches the two scripts of the
    job form off with :typoscript:`plugin.tx_academicjobs.assets.js = 0`, see
    :ref:`feature-1791566501`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The extension ships no
stylesheet, so a site that relied on the card and list group classes of a
Bootstrap theme styles the new classes itself. Move the selectors of the site
stylesheet to the classes in the table of
:ref:`breaking-jobs-speaking-frontend-classes`. The chapter :ref:`styling`
describes the markup, and :ref:`styling-example` names the stylesheet of the
development instances as a starting point. The new job form renders the form
partials of :guilabel:`academic_base`, whose classes are described in its
`changelog
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Breaking-SpeakingFrontendClasses.html>`__.

..  _upgrade-from-2-3-to-3-0-images:

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

..  _upgrade-from-2-3-to-3-0-rich-text:

The editor configuration
------------------------

The editor configuration of the new job form is an ES module now:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_jobs/Resources/Public/JavaScript/CKEditor.js`
        -   `EXT:academic_jobs/Resources/Public/JavaScript/frontend/rich-text.js`,
            loaded as the module `@fgtclb/academic-jobs/frontend/rich-text.js`

An override of :file:`Templates/Job/New.html` loads it with
:html:`<f:asset.module identifier="@fgtclb/academic-jobs/frontend/rich-text.js" />`.
:html:`<f:asset.script>` does not execute a module. See
:ref:`breaking-ckeditor-configuration-is-a-module`.

..  _upgrade-from-2-3-to-3-0-icons:

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

..  _upgrade-from-2-3-to-3-0-overrides:

Template overrides
------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Job/List.html`,
            :file:`Templates/Job/Show.html`,
            :file:`Templates/Job/New.html`
        -   The header renders only with
            :typoscript:`settings.renderContentElementHeader`, and the
            templates carry speaking classes.
        -   :ref:`important-1790323400`,
            :ref:`upgrade-from-2-3-to-3-0-classes`
    *   -   :file:`Templates/Job/List.html`
        -   The list renders one page of the paginator while the pagination is
            enabled. A project that paginated the list itself removes its own
            switch.
        -   :ref:`feature-1790660966`
    *   -   :file:`Templates/Job/Show.html`
        -   The contact box renders only for a job with contact data.
        -   :ref:`important-ace-316-academic-jobs`
    *   -   :file:`Templates/Job/New.html`
        -   Loads the editor configuration as a module, only while
            :typoscript:`settings.assets.js` is set, and renders the new
            partial :file:`Partials/Job/Forms/AdditionalFields.html`.
        -   :ref:`upgrade-from-2-3-to-3-0-rich-text`,
            :ref:`feature-1791566501`,
            :ref:`feature-new-job-form-additional-fields-partial`
    *   -   :file:`Templates/Job/New.html`
        -   Renders the confirmation for a visitor who returns to the form,
            with :html:`<f:flashMessages queueIdentifier="academic_jobs.new_job_form.confirmation" />`.
            A copy without this line shows no confirmation.
        -   :ref:`important-job-form-returns-to-its-page`,
            :ref:`templates-override-new-job-form-confirmation`
    *   -   :file:`Partials/Job/Item.html`
        -   The heading level is corrected, the flags and the link row are
            new, a hidden job is not linked, the image renders through the
            image partial of :guilabel:`academic_base`, and a list of
            properties maps each property to the name of its icon.
        -   :ref:`important-ace-316-academic-jobs`,
            :ref:`important-ace-596-academic-jobs`,
            :ref:`important-job-detail-answers-not-found-without-a-job`,
            :ref:`upgrade-from-2-3-to-3-0-images`,
            :ref:`upgrade-from-2-3-to-3-0-icons`
    *   -   :file:`Partials/Job/Information.html`
        -   The flags and the link row are new, and a list of properties maps
            each property to the name of its icon.
        -   :ref:`important-ace-596-academic-jobs`
    *   -   :file:`Partials/Job/Contact.html`
        -   The heading level is corrected, the phone link is dialable, and
            the block renders its own phone and e-mail icons.
        -   :ref:`important-ace-316-academic-jobs`,
            :ref:`important-job-contact-phone-link-is-dialable`,
            :ref:`important-job-contact-block-shows-its-own-icons`
    *   -   :file:`Partials/Job/Header.html`,
            :file:`Partials/Job/SectionHeader.html`
        -   They take the class of the heading as `class` instead of
            `positionClass`.
        -   :ref:`upgrade-from-2-3-to-3-0-classes`
    *   -   :file:`Partials/Job/Properties/Job.html`
        -   A :guilabel:`Please choose` option for the two selects, the two
            job flags as checkboxes, and a select reads `disabled` from its
            element.
        -   :ref:`important-new-job-form-offers-an-unchosen-option`,
            :ref:`important-new-job-form-offers-the-job-flags`,
            :ref:`important-new-job-form-fields-render-shared-partials`
    *   -   :file:`Partials/Job/Forms/*.html`
        -   They render the shared form partials of :guilabel:`academic_base`,
            which mark rejected fields and show their message. The ViewHelpers
            :html:`j:validation.requiredFromValidation` and
            :html:`j:validation.fieldTypeFromValidation` are removed, a copy
            that calls them fails. It reads the validation with
            :html:`validationEnsure` of :guilabel:`academic_base`.
        -   :ref:`important-new-job-form-marks-rejected-fields`,
            :ref:`upgrade-from-2-3-to-3-0-code`,
            :ref:`templates-override-form-fields`

Every template and partial also passes the extension name
:html:`AcademicJobs` to its translations, see
:ref:`important-label-overrides-use-the-documented-path`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   A class that extends
    :php:`\FGTCLB\AcademicJobs\Domain\Repository\JobRepository` and overrides
    :php:`findByJobType()` adds the parameter
    :php:`bool $includeHidden = false`. One that declares :php:`findAllJobs()`
    or :php:`findUidsWithHiddenDefaultRecord()` renames it. See
    :ref:`breaking-ace-250-academic-jobs`.
*   A listener of :php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` that
    sets a redirect page of ``0`` or below now sends the visitor back to the
    form page, which shows the form and the confirmation, see
    :ref:`important-redirect-page-zero-from-a-listener-means-none`.
*   Code that reads the answer to a submitted job gets an empty ``303`` now,
    and a page that renders the messages of the form no longer gets the error
    message of Extbase after a rejected submission, see
    :ref:`important-job-form-returns-to-its-page`.
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

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.
*   The content elements :guilabel:`Job Form`, :guilabel:`Job List` and
    :guilabel:`Job Details`, as they are called now, are offered for new
    content on the pages that should have them, and existing ones keep their
    type. See :ref:`important-content-element-titles-and-descriptions`.
*   Jobs that were hidden, scheduled or expired when the contact wizard first
    ran show their contact.
*   The jobs the repair wizard listed carry a chosen employment type and job
    type.
*   The header of the job content elements appears once.
*   A job submitted through a form without a redirect page returns the
    visitor to the form, which shows the confirmation.
*   Saving a job in the backend keeps its contact phone number, and a job
    without a company name or a description can only be saved there once both
    are filled in, unless the settings make them optional.
*   A job submitted through the form reaches the editors. A notification mail
    that cannot be sent is logged instead of failing the form, so check the
    log after a change of the mail configuration, see
    :ref:`important-failing-job-mail-is-logged`.
*   Jobs submitted with 2.3 may share one logo file. The query in
    :ref:`important-uploaded-job-logo-keeps-existing-files` finds them. A logo
    uploaded now is stored with a random suffix to its file name, and a form
    shown again with errors asks for the logo once more, see
    :ref:`important-migrated-job-image-upload-to-native-extbase-file-upload-handling`.
