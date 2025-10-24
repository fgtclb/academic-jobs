.. include:: /Includes.rst.txt

.. _breaking-1746525600:

==============================================================
Breaking: Migrated extbase plugins from `list_type` to `CType`
==============================================================

Description
===========

TYPO3 v13 deprecated the `tt_content` sub-type feature, only used for
`CType=list` sub-typing also known as `list_type` and mostly used based
on old times for extbase based plugins. It has been possible since the
very beginning to register Extbase Plugins directly as `CType` instead
of `CType=list` sub-type, which has now done.

Technically this is a breaking change, and instances upgrading from `1.x`
version of the plugin needs to update corresponding `tt_content` records
in the database and eventually adopt addition, adjustments or overrides
requiring to use the correct CType.

Impact
======

The change relates to following plugins:

* `academicjobs_newjobform`
* `academicjobs_list`
* `academicjobs_detail`

Affected Installations
======================

All installations using the above listed plugins prior V2.1.


Migration
=========

A TYPO3 UpgradeWizard `academicJobs_pluginUpgradeWizard` is provided to migrate
plugins from `CType=list` to dedicated `CTypes` matching the new registration.

.. index:: Database, TCA
