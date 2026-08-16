..  index:: Configuration; Validations
..  _configuration-validations:

===================
Validation settings
===================

:file:`Configuration/AcademicJobs/Settings.yaml` describes which fields of a job
record are required, and how they are rendered in the public *new job* form.

..  attention::
    The syntax of this file is still considered experimental and may change in a
    future release. Read :ref:`configuration-validations-limits` before relying
    on it — not every keyword takes effect in every place.

The file
========

The shipped configuration defines a single set, :yaml:`job`. Its keys are
**property names** in camel case, each carrying a list of flags:

..  code-block:: yaml

    validations:
      job:
        title:
          - required
        link:
          - url
        contactEmail:
          - email
        contactPhone:
          - number

A property that is not listed is neither required nor specially rendered.

Where the settings take effect
==============================

The configuration is read in three independent places, and — this is the
important part — **they do not all understand the same keywords**:

..  list-table::
    :header-rows: 1

    *   -   Keyword
        -   New job form
        -   Server side validation
        -   Backend (TCA)
    *   -   :yaml:`required`
        -   Marks the field with an asterisk
        -   Value must not be empty
        -   Not applied
    *   -   :yaml:`email`
        -   Renders :html:`<input type="email">`
        -   Value must be an email address
        -   Not applied
    *   -   :yaml:`url`
        -   Renders :html:`<input type="url">`
        -   Value must be a URL
        -   Not applied
    *   -   :yaml:`number`
        -   Renders :html:`<input type="number">`
        -   **No validation**
        -   Not applied

Server side validation runs when a job is **created** through the frontend form.
It does not run for records edited in the TYPO3 backend.

..  _configuration-validations-limits:

Current limitations
===================

..  warning::
    :yaml:`disabled` and :yaml:`readonly` are **not supported** by this
    extension, even though older comments inside the shipped
    :file:`Settings.yaml` mention them.

    They do not lock a field anywhere. Worse, because the form takes the first
    keyword that is not :yaml:`required` and uses it directly as the HTML input
    type, writing :yaml:`disabled` produces :html:`<input type="disabled">`,
    which browsers fall back to rendering as an ordinary text field. Do not use
    these two keywords here.

    They *are* supported by :guilabel:`academic_persons`, which is a separate
    mechanism — see `its validation settings
    <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Configuration/Validations/Index.html>`__.

Further points to be aware of:

*   **Keywords are case sensitive.** :yaml:`Required` or :yaml:`EMAIL` are
    silently ignored. Always write them in lower case.
*   **Any unknown keyword becomes an HTML input type** in the new job form, as
    described in the warning above. A typo therefore produces an invalid input
    type rather than being ignored.
*   :yaml:`number` marks the field as numeric in the form but adds no server
    side validation, so a non-numeric value submitted by other means is
    accepted.
*   :yaml:`required` cannot detect an unselected value for the job type and
    employment type fields, because those are stored as numbers and an unset
    selection is indistinguishable from a valid zero.
*   The configuration is **not** applied to the TYPO3 backend. Required fields
    in the backend record editor are configured separately in the extension's
    TCA, and the two may differ.

Overriding the settings
=======================

Settings are collected from **all installed extensions**. Every package that
contains :file:`Configuration/AcademicJobs/Settings.yaml` contributes, and the
package loaded last wins.

To change them for an installation:

#.  Add :file:`Configuration/AcademicJobs/Settings.yaml` to your site package.
#.  Make the site package **depend on** :guilabel:`academic_jobs` in its
    :file:`composer.json` or :file:`ext_emconf.php`, so that it is loaded after
    it.
#.  Repeat the **complete** :yaml:`validations` block, see the warning below.
#.  Flush the TYPO3 caches — the parsed settings are cached, and a changed file
    has no effect until the cache is cleared.

..  warning::
    The files are merged on the top level only. :yaml:`validations` is a
    top-level key, so a site package that defines it replaces the whole block —
    anything it does not repeat is lost, not inherited.

    Copy the whole :yaml:`validations` block from
    :file:`EXT:academic_jobs/Configuration/AcademicJobs/Settings.yaml` and edit
    the copy. There is no syntax for removing a single flag from a single field.

There is no TypoScript and no site set equivalent for these settings.
