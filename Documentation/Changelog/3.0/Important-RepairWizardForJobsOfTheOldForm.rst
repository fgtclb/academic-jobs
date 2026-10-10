.. _important-repair-wizard-for-jobs-of-the-old-form:

============================================================
Important: A wizard repairs the jobs of the old new job form
============================================================

Description
===========

Before the new job form stored the values a visitor chose, it stored values
the visitor had not entered, see
:ref:`important-new-job-form-stores-the-chosen-values`. The upgrade wizard
*Repair the jobs stored by the old new job form of academic jobs*
(``academicJobs_repairNewJobFormValues``) repairs what the stored values allow
to repair, and lists what needs an editor.

Start date and application deadline
-----------------------------------

The old form took the time of day of the submission for :guilabel:`When` and
:guilabel:`Application deadline`, the same for both, in the time zone of the
server. Nothing else marks a job as made by the form, and an editor may set a
time of day in the backend fields :guilabel:`Publish Date` and
:guilabel:`Expiration Date` on purpose. The wizard therefore changes a job
only when

*   both :sql:`starttime` and :sql:`endtime` are set,
*   both carry the same time of day down to the second,
*   and that time of day is not midnight,

read in the time zone of the process that runs the wizard. Such a job gets
what the form stores today: the start date at midnight of its day, the
deadline at the last second of its day. The day itself does not change.

The time zone
-------------

The time zone is :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone']` or,
when that is empty, which is its default, the default time zone of PHP in the
process. That one comes from ``date.timezone`` or the environment, and it can
differ between the command line and the web server. The form ran in the web
server, :bash:`vendor/bin/typo3 upgrade:run` runs on the command line.

A wrong time zone writes wrong values that cannot be taken back: jobs whose two
times of day still match are moved to midnight of the wrong zone, which is off
the day in the right one by the difference between the two zones, and the old
values are not kept anywhere. The description of the wizard, shown by
:bash:`vendor/bin/typo3 upgrade:list` and in the upgrade module, names the time
zone it will use. Check it before running the wizard, and set
:php:`phpTimeZone` or run the wizard from the upgrade module of the web server
if it is not the zone the form ran in. After a change of the time zone since
the form ran, the jobs of the old form are not recognised any more.

Employment type and job type
----------------------------

A ``0`` in :sql:`employment_type` (:guilabel:`Working hours` in the form) or
:sql:`type` is what the form stored for a select left on
:guilabel:`Please choose`. The value the visitor meant cannot be derived, so
the wizard does not change these jobs. It lists them in its output with uid,
page and title, and with language and workspace where they are not the default
ones, for an editor to choose the value in the backend.

Which jobs count
----------------

Every job that is not deleted: hidden, scheduled and expired jobs as well,
translations, which carry dates of their own, and workspace versions, so that
publishing one does not bring the old values back. The delete placeholder of a
workspace is left out.

What the wizard cannot repair
-----------------------------

*   A job with only one of the two dates. Both fields are optional in the form,
    and one time of day alone does not tell a submission from an editor.
*   A job whose start date or deadline an editor changed since, so that the
    two times of day differ.
*   A job submitted in a time of day that did not exist on the start day or on
    the deadline day, an hour skipped by the change to summer time, which
    shifted that one value by an hour. The same applies to the rare submission
    whose two values were parsed on both sides of a second.

What it changes although an editor set it
-----------------------------------------

A job whose start date and deadline an editor set to the same time of day, for
example both to 08:00, cannot be told from a job of the form and is moved to
whole days as well. Check the jobs that are visible from or until a particular
hour before running the wizard.

Running it
==========

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicJobs_repairNewJobFormValues

or :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`. The wizard writes the
database directly, not through the :php:`DataHandler`, so the change is not
recorded in the history of a job. Flush the frontend caches afterwards: a page
cached before still shows the jobs with their old times.

The jobs with a ``0`` are listed once, in that run, and the wizard is marked
as done afterwards like any other. Keep the list: a job with a ``0`` does not
bring the wizard back, so neither the upgrade module nor
:guilabel:`System > Reports` reminds anybody of it. To see the list again, mark
the wizard as undone and run it once more:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicJobs_repairNewJobFormValues
    vendor/bin/typo3 upgrade:run academicJobs_repairNewJobFormValues

Running it again changes no date, a repaired job no longer carries the same
time of day on both.

Affected Installations
======================

Every installation that received jobs through the :guilabel:`New job form`
plugin before it stored whole days and refused an unchosen value.

.. index:: Backend, Database, ext:academic_jobs
