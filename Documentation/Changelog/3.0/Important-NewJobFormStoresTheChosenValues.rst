.. _important-new-job-form-stores-the-chosen-values:

====================================================
Important: The new job form stores the chosen values
====================================================

Description
===========

The new job form stored two kinds of values the visitor had not entered:

*   The :guilabel:`Working hours` and :guilabel:`Job Type` selects are required,
    but a submission that kept their :guilabel:`Please choose` option was
    stored with the value ``0``. The rule ``required`` of
    :file:`Configuration/AcademicJobs/Settings.yaml` now refuses ``0`` for a
    field stored as a number, and the form is shown again.
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

TYPO3 v13 and v14 behave alike here.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form` plugin.

.. index:: Frontend, ext:academic_jobs
