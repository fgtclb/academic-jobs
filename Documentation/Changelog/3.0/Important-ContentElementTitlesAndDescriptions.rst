.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content elements of this extension have new titles and new descriptions, in
English and in German. They follow one wording that all academic extensions
share now. An editor sees the title in the new content element wizard and in
the type field of a content element, and the description below the title in the
wizard.

..  list-table::
    :header-rows: 1

    *   -   Content type
        -   Title until now
        -   Title now
        -   Description now
    *   -   :typoscript:`academicjobs_detail`
        -   :guilabel:`Jobs Detail` (German :guilabel:`Jobs Detail`)
        -   :guilabel:`Job Details` (German :guilabel:`Job Details`)
        -   Detailed view for job vacancies
    *   -   :typoscript:`academicjobs_list`
        -   :guilabel:`Jobs List` (German :guilabel:`Jobs List`)
        -   :guilabel:`Job List` (German :guilabel:`Job Liste`)
        -   List of job vacancies
    *   -   :typoscript:`academicjobs_newjobform`
        -   :guilabel:`Jobs New` (German :guilabel:`Jobs New`)
        -   :guilabel:`Job Form` (German :guilabel:`Job Formular`)
        -   Form for submitting job vacancies

The type field and the wizard read the title from two different labels for
:typoscript:`academicjobs_detail`, :typoscript:`academicjobs_list` and
:typoscript:`academicjobs_newjobform`. Both labels carry the new title.

Impact
======

Editors see the new titles and descriptions. The content types, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_jobs
