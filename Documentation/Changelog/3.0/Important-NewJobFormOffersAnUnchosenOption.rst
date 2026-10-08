.. _important-new-job-form-offers-an-unchosen-option:

=================================================
Important: New job form offers an unchosen option
=================================================

Description
===========

The :guilabel:`Working hours` and :guilabel:`Job Type` selects of the new job
form offered their real options only. A browser selects the first option of a
select that marks none, so a visitor who never touched either control still
submitted the first working time and the first job type.

Both selects now open on an option labelled :guilabel:`Please choose` that
carries the value ``0``, the value the column holds for a field that was never
set, and the one the :php:`int` property of the domain model accepts.

Two label units are new and available for translation:
``create.job.employmentType.none`` and ``create.job.type.none``.

Impact
======

Before, a job created through the frontend form always carried a value for
:sql:`employment_type` and :sql:`type`, whether the visitor had chosen it or
not. Both fields are listed as ``required`` in
:file:`Configuration/AcademicJobs/Settings.yaml`, and a submission that keeps
the option ``0`` is refused, see
:ref:`important-new-job-form-stores-the-chosen-values`.

Existing records are untouched.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
