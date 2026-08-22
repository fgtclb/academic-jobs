..  _breaking-site-sets-and-static-templates-restructured:

===============================================================
Breaking: Site sets and static templates have been restructured
===============================================================

Description
===========

The TypoScript of this extension was shipped twice: the static template read
:file:`Configuration/TypoScript/`, and the site set :yaml:`fgtclb/academic-jobs`
shipped its own :file:`constants.typoscript` and :file:`setup.typoscript`, each
of them a single :typoscript:`@import` of that folder. The page TSconfig was not
selectable on a page at all — it existed only as the three wizard files under
:file:`Configuration/TSconfig/Plugins/`, glob imported into the always-included
:file:`Configuration/page.tsconfig`.

Both mechanisms now read one physical copy of every file, and both of them
deliver the extension per component instead of as one block:

*   :file:`Configuration/TypoScript/NewJobForm/`,
    :file:`Configuration/TypoScript/List/` and
    :file:`Configuration/TypoScript/Detail/` are what the static templates
    register *and* what the sets point their :yaml:`typoscript` key at.
*   :file:`Configuration/TSconfig/NewJobForm/page.tsconfig`,
    :file:`Configuration/TSconfig/List/page.tsconfig` and
    :file:`Configuration/TSconfig/Detail/page.tsconfig` hold the page TSconfig
    of a component and are what the page field :guilabel:`Page TSconfig` offers
    *and* what the sets point their :yaml:`pagets` key at.
*   :file:`Configuration/TypoScript/Full/` and
    :file:`Configuration/TSconfig/Full/page.tsconfig` are the aggregates for
    installations that do not use site sets.

All three content elements are driven by one Extbase plugin and share one
:typoscript:`plugin.tx_academicjobs` block. **That block did not move**: it stays
in :file:`Configuration/TypoScript/`, which is the value every
:sql:`sys_template` record that selected the static template of this extension
already stores. Each component folder is a subfolder of it and names it in a
one-line :file:`include_static_file.txt`, so the block exists once and is
delivered by both mechanisms alike.

The content elements are now **hidden by default**. The always-included
:file:`Configuration/page.tsconfig` removes :typoscript:`academicjobs_newjobform`,
:typoscript:`academicjobs_list` and :typoscript:`academicjobs_detail` from the
selectable content element types, and the page TSconfig of a component adds its
own element back — so an element is offered where it is wanted instead of on
every page of every installation. The wizard entry of an element moved into the
same file, so it arrives with the component instead of with every installation.
The TCA registration itself did not move, so the frontend renders existing
records exactly as before. Editing such a record in the backend is a different
matter — read the warning below before upgrading.

Four constants changed their shipped default from *empty* to :typoscript:`0`:
:typoscript:`plugin.tx_academicjobs.persistence.storagePid`,
:typoscript:`plugin.tx_academicjobs.detailPid`,
:typoscript:`plugin.tx_academicjobs.listPid` and
:typoscript:`plugin.tx_academicjobs.saveForm.fallbackRedirectPageId`. The site
settings of this extension always declared them as :yaml:`0`, and a site that
uses both delivery mechanisms had the declared default reset to empty by the
second read. Every consumer of the four reads an empty value and a
:typoscript:`0` as "no page selected", so the change is a reconciliation, not a
behaviour change.

Impact
======

A :sql:`sys_template` record that selected the static template of this extension
keeps its stored value, and that value keeps delivering the shared plugin
configuration — but it delivers nothing else, and it never made a content
element selectable.

A site package that imported one of the shipped page TSconfig files by path
fails to resolve it. :typoscript:`@import` of a missing file is silent, so this
shows up as missing configuration rather than as an error message.

The three content elements are no longer offered in the backend until the page
TSconfig of the component is included, through the site set or through the page
field :guilabel:`Page TSconfig`.

..  warning::

    Do not open an existing :guilabel:`Jobs New`, :guilabel:`Jobs List` or
    :guilabel:`Jobs Detail` record in the backend form on a page that does not
    include that page TSconfig. An item removed through
    :typoscript:`TCEFORM.tt_content.CType.removeItems` is excluded from the
    :guilabel:`[ invalid value ]` fallback TYPO3 otherwise adds for a stored
    value it does not know, and the stored value is dropped from the form data
    as well. The field :guilabel:`Type` therefore comes up with nothing
    selected, and **saving the record writes whatever the browser preselected
    into** :sql:`CType` — the record silently becomes another content element.
    The frontend keeps rendering it correctly until that happens.

    Include the page TSconfig of the component on every page tree that holds
    such records, and do it before editing them.

The set :yaml:`fgtclb/academic-jobs` keeps its name and keeps delivering
everything, so a site configuration that depends on it needs no change.

Affected Installations
======================

Installations that use one of the content elements of this extension without
including its page TSconfig, and installations that import one of the shipped
page TSconfig files from an own site package.

Migration
=========

The static template entry does not have to be replaced — but selecting one of
the new entries instead documents what a site actually uses:

..  list-table::
    :header-rows: 1

    *   -   Old entry
        -   New entry
    *   -   :guilabel:`Academic Jobs (academic_jobs)`,
            stored as `EXT:academic_jobs/Configuration/TypoScript`
        -   :guilabel:`Academic Jobs: All components (academic_jobs)`,
            stored as `EXT:academic_jobs/Configuration/TypoScript/Full` — or one
            of :guilabel:`Academic Jobs: Jobs New`,
            :guilabel:`Academic Jobs: Jobs List`,
            :guilabel:`Academic Jobs: Jobs Detail`. The old value stays
            registered as :guilabel:`Academic Jobs: Shared plugin settings
            (academic_jobs)` and keeps delivering the shared plugin block.

Add the page TSconfig entry, which did not exist before, in the page record of
the site root, tab :guilabel:`Resources`, field :guilabel:`Page TSconfig`:
:guilabel:`Academic Jobs: All components (academic_jobs)`, stored as
`EXT:academic_jobs/Configuration/TSconfig/Full/page.tsconfig`. Without it the
content elements are not selectable any more, and existing records of them lose
their :sql:`CType` when they are saved from the backend form.

Sites that use the site set instead need no migration — but they must not use
both mechanisms at once, see the :guilabel:`Configuration` chapter.

Adjust every :typoscript:`@import` in an own site package:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_jobs/Configuration/TypoScript/constants.typoscript`
        -   unchanged
    *   -   `EXT:academic_jobs/Configuration/TypoScript/setup.typoscript`
        -   unchanged
    *   -   `EXT:academic_jobs/Configuration/TSconfig/page.tsconfig`
        -   `EXT:academic_jobs/Configuration/TSconfig/Full/page.tsconfig`
    *   -   `EXT:academic_jobs/Configuration/TSconfig/Plugins/NewJobsForm.tsconfig`
        -   `EXT:academic_jobs/Configuration/TSconfig/NewJobForm/page.tsconfig`
    *   -   `EXT:academic_jobs/Configuration/TSconfig/Plugins/List.tsconfig`
        -   `EXT:academic_jobs/Configuration/TSconfig/List/page.tsconfig`
    *   -   `EXT:academic_jobs/Configuration/TSconfig/Plugins/Detail.tsconfig`
        -   `EXT:academic_jobs/Configuration/TSconfig/Detail/page.tsconfig`

A site configuration may name the new component sets instead of the aggregate:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-jobs`
        -   Unchanged in name, now delivers through the component sets below.
    *   -   `fgtclb/academic-jobs-new-job-form`
        -   The :guilabel:`Jobs New` content element only.
    *   -   `fgtclb/academic-jobs-list`
        -   The :guilabel:`Jobs List` content element only.
    *   -   `fgtclb/academic-jobs-detail`
        -   The :guilabel:`Jobs Detail` content element only.

..  index:: TypoScript, TSConfig, Backend, ext:academic_jobs
