..  _breaking-job-validation-settings-merged-per-field:

==========================================================
Breaking: The job validation settings are merged per field
==========================================================

Description
===========

When more than one package ships
:file:`Configuration/AcademicJobs/Settings.yaml`, the files are merged the
way the persons settings are: in package loading order, a later package
changes only the fields it names, at any depth. A list of flags replaces the
flags of that field, and :yaml:`~` removes the field.

Until now the files were merged on the top level only, so a site package that
defined :yaml:`validations` replaced the whole block, and the manual asked for
a copy of it.

Impact
======

A copy of the whole block keeps the flags of every field it names, including
the ones :guilabel:`academic_jobs` changed since: a copy of the 2.x block keeps
:yaml:`number` on the contact phone, which the settings now apply to the
backend, see :ref:`breaking-job-validation-settings-reach-the-backend`. A field
the copy leaves out is no longer dropped: it keeps the flags
:guilabel:`academic_jobs` ships.

Affected installations
======================

Installations whose site package ships
:file:`Configuration/AcademicJobs/Settings.yaml` and leaves out a field the
shipped file configures, to make that field optional.

Migration
=========

Name the field with an empty list, or with :yaml:`~` to remove it. The
difference shows in the backend: an empty list makes the field optional there
as well, :yaml:`~` leaves the backend field as the TCA declares it. The rest of
the copy can go, a site package needs to name only what it changes:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicJobs/Settings.yaml

    validations:
      job:
        description: []

..  index:: Frontend, Backend, NotScanned, ext:academic_jobs
