.. _important-job-contact-wizard-migrates-invisible-jobs:

=========================================================
Important: The job contact wizard migrates invisible jobs
=========================================================

Description
===========

The upgrade wizard `academicJobs_contactRelation`
(`FGTCLB\\AcademicJobs\\Upgrades\\ContactTcaUpgradeWizard`) copies name, phone,
e-mail and additional information of the contact record of a job into the
contact fields of the job itself. The contact records live in the table
:sql:`tx_academicjobs_domain_model_contact`, which version 2.1 removed.

The wizard read the jobs with the default restrictions of the query builder.
A job that was hidden, had a start time in the future or an end time in the
past while the wizard ran was left out. It kept empty contact fields, and the
wizard reported nothing left to do. The wizard now reads every job, so hidden,
scheduled and expired jobs get their contact like every other job. A deleted
job gets it as well, so it has its contact when it is restored from the
recycler.

The old contact table has no TCA once the extension removed it, so no
restriction ever reached its records, and the wizard copied hidden and deleted
contact records as well. It now leaves both out: an editor switched a hidden
contact off for the job, and the contact fields of a job have no switch to
carry that over. A contact record that is left out stays in the old table only
and is gone once the database analyzer drops that table.

The start and end time of a contact record are not looked at, so that the
result does not depend on the moment the wizard runs. A contact record whose
end time has passed is therefore copied as well, and the job shows it from
then on.

Impact
======

A job that is hidden, scheduled, expired or deleted when the wizard runs shows
its contact once it is visible. A hidden or deleted contact record is no longer
copied into a job. An expired contact record is copied and shown with the job.

Affected Installations
======================

Installations that ran the wizard while some of their jobs were hidden,
scheduled or expired. Those jobs show no contact. This is the common case: the
new job form stores every job hidden until an editor approves it, so every job
submitted through the frontend and not yet approved at that moment was left
out.

Installations that ran the wizard while one of their jobs was related to a
hidden or deleted contact record. That record was copied into the job, which
shows it.

Migration
=========

Run the upgrade wizard again. An installation that already executed it has it
recorded as done, so it has to be marked undone first, in the Install Tool
under :guilabel:`Upgrade > Upgrade Wizard`, or on the command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicJobs_contactRelation
    vendor/bin/typo3 upgrade:run academicJobs_contactRelation

Running it again leaves every job alone that has a contact of its own. The
wizard only fills a job whose four contact fields are all empty, from a
contact record that holds a value, so a job an earlier run migrated is not
touched again. Two cases need a look before running it:

*   A job whose contact fields an editor has emptied on purpose since the
    earlier run gets the contact of its old record back.
*   A hidden or deleted contact record an earlier run copied into a job stays
    in that job. Running the wizard again does not undo it. Empty the contact
    fields of such a job by hand where the contact must not be shown.

The wizard reads the old contact table and the :sql:`contact` field of the job
table. Once the database analyzer has renamed or dropped them, there is nothing
left to migrate.

.. index:: Database, ext:academic_jobs
