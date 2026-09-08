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
carries the value ``0`` — the value the column holds for a field that was never
set, and the one the :php:`int` property of the domain model accepts.

Two label units are new and available for translation:
``create.job.employmentType.none`` and ``create.job.type.none``.

Impact
======

A job created through the frontend form can now be stored with
:sql:`employment_type` respectively :sql:`type` set to ``0``, where before it
always carried a value the visitor had not necessarily chosen. Both fields are
listed as ``required`` in :file:`Configuration/AcademicJobs/Settings.yaml`, but
that rule maps to :php:`NotEmptyValidator`, which accepts ``0`` — enforcing a
choice is a change of its own and is not part of this one.

Existing records are untouched.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
