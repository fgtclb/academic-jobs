.. _important-1785882500:

============================================
Important: Four job columns are nullable now
============================================

Description
===========

Four columns of :sql:`tx_academicjobs_domain_model_job` were declared as
:sql:`TEXT` with a default value:

..  code-block:: sql

    company_name text NOT NULL DEFAULT '',
    sector text NOT NULL DEFAULT '',
    required_degree text NOT NULL DEFAULT '',
    contractual_relationship text NOT NULL DEFAULT '',

MySQL cannot store a default value on a :sql:`TEXT` column. TYPO3 works
around that from v13 on, by expressing the default in the
:sql:`DEFAULT ('')` syntax MySQL 8.0.13 introduced — so the declarations
are harmless on every core version this release supports. On TYPO3 v12
they were not: the columns ended up :sql:`NOT NULL` with no default at
all, and every statement that did not name them was rejected with
:sql:`Field 'company_name' doesn't have a default value`. Imports and
data sets were the reachable case; the frontend form persists a full
record, and the backend fills the columns from their :php:`default` in
TCA.

The columns keep their type and lose the default instead:

..  code-block:: sql

    company_name text DEFAULT NULL,
    sector text DEFAULT NULL,
    required_degree text DEFAULT NULL,
    contractual_relationship text DEFAULT NULL,

The change is carried here as well so that both maintained branches
declare the columns identically.

Impact
======

Records written without naming a column now store :sql:`NULL` where they
previously stored an empty string. Existing rows are not changed, so a
column can hold both. The domain model is unaffected — Extbase casts :sql:`NULL` to
an empty string for its :php:`string` properties — but code reading the
columns directly should compare with :php:`empty()` rather than with
:php:`''`.

Affected Installations
======================

All installations of this extension. The database schema has to be
updated, either in the maintenance area of the install tool or with
:bash:`typo3 extension:setup`. The change only relaxes the columns, so no
data is converted and nothing can be lost.

.. index:: Database, ext:academic_jobs
