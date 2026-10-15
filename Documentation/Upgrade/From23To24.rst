..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_jobs` per
component, hides its content elements until a site enables them and repairs the
job contacts and the jobs of the old new job form with two upgrade wizards.
Work through the sections in order. Skip a step that does not apply to the
installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_jobs` 2.4 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.

:file:`ext_emconf.php` now names the system extensions :guilabel:`rte_ckeditor`
and :guilabel:`fluid_styled_content` by their extension keys. A classic
installation resolves its dependencies from that file, so both have to be
active. See :ref:`important-1790336613`.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-jobs':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796662`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_jobs` there, in this order.
Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3` instead
of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-database:

Update the database
===================

Apply the database changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`, or on the command line:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

For this extension the comparison adds and changes:

*   :sql:`company_name`, :sql:`sector`, :sql:`required_degree` and
    :sql:`contractual_relationship` of :sql:`tx_academicjobs_domain_model_job`
    become nullable. No data is converted, see :ref:`important-1785882500`.
*   :sql:`l10n_source` is added to the same table, where a translation records
    the record it was made from. Existing rows get :sql:`0`, see
    :ref:`important-ace-463-academic-jobs`.

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

..  _upgrade-from-2-3-to-2-4-wizards:

Run the upgrade wizards
=======================

Both wizards need the database changes of the previous section. Run them in
this order, in :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the
command line.

#.  ``academicJobs_contactRelation``, :guilabel:`Migrate job contact records
    from relation to fields directly in the job record`. It copies the contact
    record of a job into the contact fields of the job. Up to 2.3 it left out
    every job that was hidden, scheduled or expired while it ran, which
    includes every job submitted through the frontend form and not yet
    approved. It now reads every job and leaves a job alone that has a contact
    of its own. An installation that already ran it has it recorded as done
    and marks it undone first. TYPO3 13 marks a wizard undone with
    ``vendor/bin/typo3 upgrade:mark:undone academicJobs_contactRelation``.
    TYPO3 12 has no such command: open
    :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, find the wizard under
    :guilabel:`Wizards marked as done` and choose :guilabel:`Mark undone`. For
    a wizard that was never marked done, the TYPO3 13 command reports an
    error, which is harmless. Then run the wizard:

    ..  code-block:: bash

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
    the backend. See :ref:`important-repair-wizard-for-jobs-of-the-old-form`
    and :ref:`important-new-job-form-stores-the-chosen-values`.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are now delivered per content element,
and the three content elements are hidden in the whole installation until the
page TSconfig of their component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-components:

Include the components
----------------------

*   **Site set, TYPO3 13 only.** A site that depends on the set
    `fgtclb/academic-jobs` needs no change. The set keeps its name and now
    brings the page TSconfig of all three content elements along.
*   **Static template.** The stored entry of 2.3 keeps working as
    :guilabel:`Academic Jobs: Shared plugin settings (academic_jobs)`. Replace
    it with :guilabel:`Academic Jobs: All components (academic_jobs)`, or with
    the entries of the content elements the site uses, in the field
    :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options` of the template record.
*   **Page TSconfig.** A site without the site set, which is every site on
    TYPO3 12, adds :guilabel:`Academic Jobs: All components (academic_jobs)`
    in the field :guilabel:`Include static Page TSconfig (from extensions)` on
    the tab :guilabel:`Resources` of its root page, see
    :ref:`static-pagetsconfig`. Without it the content elements are not offered
    for new content.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` 2.4 shows the stored
type in the field :guilabel:`Type` followed by :guilabel:`(not enabled on this
page)`, and saving keeps it. The warning of the changelog entry that saving
such a record rewrites its type describes the state before that fix. A content
element that an older version or a page TSconfig of your own already turned
into another type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-2-4-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and its
    files were reorganised. Take the new path of every :typoscript:`@import`
    from the table of
    :ref:`breaking-site-sets-and-static-templates-restructured`, see also
    :ref:`important-1787141400`. An import of a missing file fails silently.
*   A site whose content element layout renders no header sets
    :typoscript:`plugin.tx_academicjobs.renderContentElementHeader = 1`, on
    TYPO3 13 also available as a site setting. The plugins no longer render the
    header themselves, see :ref:`important-1790323400`.
*   Label overrides move from :typoscript:`plugin.tx_academic_jobs._LOCAL_LANG`
    to :typoscript:`plugin.tx_academicjobs._LOCAL_LANG`, see
    :ref:`important-label-overrides-use-the-documented-path`.
*   An empty
    :typoscript:`plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes`
    accepts every media type now. List the accepted types to keep restricting
    the logo, see :ref:`important-ace-267-academic-jobs`.
*   A site package that ships its own
    :file:`Configuration/AcademicJobs/Settings.yaml` changes the keyword of
    :yaml:`contactPhone` from :yaml:`number` to :yaml:`tel`, see
    :ref:`important-new-job-form-stores-the-chosen-values`.
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
    sets :yaml:`defaultController: 'Job::show'` in the copy, the value the
    shipped file carries since 2.4. See :ref:`configuration-route-enhancers`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

The editor script of the new job form moved:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_jobs/Resources/Public/JavaScript/CKEditor.js`
        -   `EXT:academic_jobs/Resources/Public/JavaScript/frontend/ckeditor.js`

An override of :file:`Templates/Job/New.html`, or a site package that loads the
script itself, takes over the new path. Otherwise the rich text fields fall
back to plain text areas. See :ref:`breaking-ckeditor-configuration-moved`.

Version 2.4 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
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
            :typoscript:`settings.renderContentElementHeader`.
        -   :ref:`important-1790323400`
    *   -   :file:`Templates/Job/Show.html`
        -   The contact box renders only for a job with contact data.
        -   :ref:`important-ace-316-academic-jobs`
    *   -   :file:`Templates/Job/New.html`
        -   Loads the moved editor script.
        -   :ref:`breaking-ckeditor-configuration-moved`
    *   -   :file:`Partials/Job/Item.html`
        -   The heading level is corrected, the flags and the link row are
            new, and a hidden job is not linked.
        -   :ref:`important-ace-316-academic-jobs`,
            :ref:`important-ace-596-academic-jobs`,
            :ref:`important-job-detail-answers-not-found-without-a-job`
    *   -   :file:`Partials/Job/Information.html`
        -   The flags and the link row are new.
        -   :ref:`important-ace-596-academic-jobs`
    *   -   :file:`Partials/Job/Contact.html`
        -   The heading level is corrected, and the phone link is dialable.
        -   :ref:`important-ace-316-academic-jobs`,
            :ref:`important-job-contact-phone-link-is-dialable`
    *   -   :file:`Partials/Job/Properties/Job.html`
        -   :html:`richtext: 1` for TYPO3 12, and a :guilabel:`Please choose`
            option for the two selects.
        -   :ref:`important-new-job-form-rich-text-starts-on-typo3-v12`,
            :ref:`important-new-job-form-offers-an-unchosen-option`
    *   -   :file:`Partials/Job/Forms/*.html`
        -   Rejected fields carry ``is-invalid`` and their message, rendered by
            the new partial :file:`Partials/Job/Forms/ErrorMessage.html`. A
            stylesheet that styles ``f3-form-error`` styles ``is-invalid``
            instead.
        -   :ref:`important-new-job-form-marks-rejected-fields`

Every template and partial also passes the extension name
:html:`AcademicJobs` to its translations now, see
:ref:`important-label-overrides-use-the-documented-path`.

..  _upgrade-from-2-3-to-2-4-code:

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
    form page, see
    :ref:`important-redirect-page-zero-from-a-listener-means-none`.
*   A listener of
    :php:`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent`
    that fills a date field from an assigned job gets the date as ``Y-m-d``
    in the form now, see
    :ref:`important-new-job-form-marks-rejected-fields`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The content elements :guilabel:`Jobs New`, :guilabel:`Jobs List` and
    :guilabel:`Jobs Detail` are offered for new content on the pages that
    should have them, and existing ones keep their type.
*   Jobs that were hidden, scheduled or expired when the contact wizard first
    ran show their contact.
*   The jobs the repair wizard listed carry a chosen employment type and job
    type.
*   The header of the job content elements appears once.
*   A job submitted through the form reaches the editors. A notification mail
    that cannot be sent is logged now instead of failing the form, so check
    the log after a change of the mail configuration, see
    :ref:`important-failing-job-mail-is-logged`.
*   Jobs submitted before the update may share one logo file. The query in
    :ref:`important-uploaded-job-logo-keeps-existing-files` finds them.
