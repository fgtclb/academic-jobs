.. include:: /Includes.rst.txt

.. _breaking-1749549600:

========================================================
Breaking: Removed `tx_academicjobs_domain_model_contact`
========================================================

Description
===========

As the relation between database tables :sql:`tx_academicjobs_domain_model_job`
and :sql:`tx_academicjobs_domain_model_contact` is 1:1 and there is no reuse of
contact records, it does not make sense to have a separate table and record for
these contacts. Therefore the relation was resolved and an upgrade wizard handles
the migration to the new fields in the :sql:`tx_academicjobs_domain_model_job`.


Impact
======

The change relates all records of type :sql:`tx_academicjobs_domain_model_contact`.


Affected Installations
======================

All installations using the database table :sql:`tx_academicjobs_domain_model_contact`.


Migration
=========

A TYPO3 UpgradeWizard `academicJobs_contactRelation` is provided to migrate
contact records from relation to fields directly in the job record.

.. index:: Database, TCA
