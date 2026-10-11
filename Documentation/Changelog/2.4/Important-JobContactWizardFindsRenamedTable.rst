.. _important-job-contact-wizard-finds-renamed-table:

==============================================================
Important: The job contact wizard finds the renamed old schema
==============================================================

Description
===========

The upgrade wizard `academicJobs_contactRelation`
(`FGTCLB\\AcademicJobs\\Upgrades\\ContactTcaUpgradeWizard`) copies the contact
record of a job from the table :sql:`tx_academicjobs_domain_model_contact`
into the contact fields of the job. Version 2.1 removed that table and the
relation field :sql:`contact` of the job table.

The database analyzer of the Install Tool does not drop a removed table or
field at once. Its "remove" step renames them first, to
:sql:`zzz_deleted_tx_academicjobs_domain_model_contact` and
:sql:`zzz_deleted_contact`. The wizard looked for the table under its own name
and under :sql:`zzz_tx_academicjobs_domain_model_contact`, a name TYPO3 never
gives a table, and for the field under its own name only. After that step it
found nothing, copied no contact and reported nothing to do.

The wizard now reads the table and the field under either name, each on its
own.

Impact
======

The wizard migrates the contacts of the jobs once the database analyzer has
renamed the old table, the relation field or both.

Affected Installations
======================

Installations that applied the "remove" step of the database analyzer after
the update to version 2.1 or later and before the wizard ran. Their jobs show
no contact, although the contact records are still in the renamed table.

Migration
=========

Check the database for the table
:sql:`zzz_deleted_tx_academicjobs_domain_model_contact` and for the field
:sql:`zzz_deleted_contact` of :sql:`tx_academicjobs_domain_model_job`. The
analyzer may have renamed only one of the two. If either is there, run the
upgrade wizard. The command line marks a wizard as done when it reports
nothing to do, so a wizard that was offered to an earlier
:bash:`upgrade:run` may have to be marked undone first. The Install Tool lists
it under :guilabel:`Upgrade > Upgrade Wizard` among the wizards marked as done,
with a button to mark it undone. TYPO3 v13 can do the same on the command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicJobs_contactRelation
    vendor/bin/typo3 upgrade:run academicJobs_contactRelation

TYPO3 v12 has no command to mark a wizard undone. There it is marked undone in
the Install Tool, and `upgrade:run` runs it afterwards.

Do not apply the "remove" step of the database analyzer again before the
wizard has run: its second pass drops the renamed table and field, and with
them the contacts.

.. index:: Database, ext:academic_jobs
