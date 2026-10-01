..  _breaking-job-validation-settings-reach-the-backend:

=======================================================
Breaking: The job validation settings reach the backend
=======================================================

Description
===========

The flags of :file:`Configuration/AcademicJobs/Settings.yaml` apply to the
backend record editor of a job now, not only to the new job form: a
:yaml:`required` field is required there, :yaml:`readonly` and
:yaml:`disabled` make a field read only, and :yaml:`email` and :yaml:`number`
set the type of the field.

A listener of
:php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent` with the
identifier ``academic-jobs/apply-settings-to-tca`` applies them, after every
:file:`Configuration/TCA/Overrides` file. For each configured field it sets
whether the field is required and read only, also when the settings say *no*.

Impact
======

With the shipped settings, the company name and the description of a job are
required in the backend record editor. A job without one of them can only be
saved there once it is filled in. Jobs created through the new job form have
both, since the form required them already.

**A copy of the old settings can lose phone numbers.** The manual of 2.x asked
for a copy of the whole :yaml:`validations` block, and that block marked the
contact phone with :yaml:`number`. A copy that still does turns
:sql:`contact_phone` into a number field in the backend, and saving a job
there stores :code:`+49 30 123` as :code:`49`. The settings files are merged
per field now, so the shipped :yaml:`tel` does not reach a copy that names the
field.

A TCA override of a site package that sets :php:`required` or :php:`readOnly`
on a field the settings configure no longer takes effect. A field the settings
list with :yaml:`url` alone, the link for example, is not required in the
backend, whatever the override says.

Affected installations
======================

Installations whose site package ships
:file:`Configuration/AcademicJobs/Settings.yaml` with :yaml:`number` on
:yaml:`contactPhone`, installations with job records that lack a company name
or a description, and installations that change :php:`required` or
:php:`readOnly` of a configured job field in their TCA overrides.

Migration
=========

Before the update, replace :yaml:`number` with :yaml:`tel` on
:yaml:`contactPhone` in the settings file of the site package, or remove the
field from it so the shipped flags apply:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicJobs/Settings.yaml

    validations:
      job:
        contactPhone:
          - tel

Fill in the missing values when an editor next saves such a record, or make
the fields optional in the settings of the site package:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicJobs/Settings.yaml

    validations:
      job:
        companyName: []
        description: []

Move a TCA override of :php:`required` or :php:`readOnly` into the settings
file. When the backend has to differ from the form, register a listener of
:php:`AfterTcaCompilationEvent` ordered after
``academic-jobs/apply-settings-to-tca``, see :ref:`the validation settings
<configuration-validations-backend-listener>`.

..  index:: Backend, TCA, NotScanned, ext:academic_jobs
