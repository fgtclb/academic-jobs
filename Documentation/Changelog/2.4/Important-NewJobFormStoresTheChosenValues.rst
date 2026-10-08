.. _important-new-job-form-stores-the-chosen-values:

====================================================
Important: The new job form stores the chosen values
====================================================

Description
===========

The new job form stored three kinds of values the visitor had not entered:

*   The :guilabel:`Working hours` and :guilabel:`Job Type` selects are required,
    but a submission that kept their :guilabel:`Please choose` option was
    stored with the value ``0``. The rule ``required`` of
    :file:`Configuration/AcademicJobs/Settings.yaml` now refuses ``0`` for a
    field stored as a number, and the form is shown again.
*   The :guilabel:`Phone number` field was a number input, which accepts
    neither a leading ``+`` nor spaces, so ``+49 2166 9404540`` could not be
    entered. The shipped settings now configure it as ``tel``, which renders a
    telephone input. The number is still stored as typed and is not checked
    on submit.
*   :guilabel:`When` and :guilabel:`Application deadline` were stored with the
    time of day of the submission, so a job submitted in the afternoon
    disappeared in the afternoon of its deadline day. The start date is now
    stored at midnight, the deadline at the last second of its day, both in
    the time zone of the server. The employment start date is stored as a date
    and was not affected.

Impact
======

Jobs stored before keep their values, including a ``0`` in
:sql:`employment_type` or :sql:`type` and the time of day of their start date
and deadline. Correct them in the backend where needed.

An installation that ships its own :file:`Configuration/AcademicJobs/Settings.yaml`
replaces the whole :yaml:`validations` block of the extension, and keeps the
:yaml:`number` of its copy for the phone number until it changes it to
:yaml:`tel` as well. See :ref:`configuration-validations`.

TYPO3 v12 and v13 behave alike here.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form` plugin.

.. index:: Frontend, ext:academic_jobs
