.. _important-ace-463-academic-jobs:

=======================================================
Important: The job table records its translation source
=======================================================

Description
===========

:sql:`tx_academicjobs_domain_model_job` declared a :php:`languageField` and a
:php:`transOrigPointerField`, and no :php:`translationSource`. It was the only
table of the academic extensions without one.

The two answer different questions. :sql:`l10n_parent` says which record a
translation belongs to; :sql:`l10n_source` says which record it was written
*from*. They hold the same value when a translation was made from the default
language and different ones when it was made from another translation, and
without the second key that information is not recorded at all.

The :php:`ctrl` section of the table now carries:

..  code-block:: php

    'translationSource' => 'l10n_source',

Impact
======

TYPO3 adds an :sql:`l10n_source` column to the table on the next database
comparison, as :sql:`int unsigned NOT NULL DEFAULT 0` with an index. The column
is derived from the TCA by :php:`DefaultTcaSchema`, so there is nothing to add
to :file:`ext_tables.sql`.

The backend gains the "source language" half of the localization state selector
for job records, and a translation made from another translation is recorded as
such.

Affected Installations
======================

All installations of this extension.

**No migration is needed, and none is offered.** Existing rows get the column
default :sql:`0`:

* On a **default language record** that is the correct value, permanently -
  such a record has no source.
* On a **translation that predates this change** it means "not recorded". Every
  reader in the TYPO3 core guards for that with a :php:`> 0` check, so nothing
  fails: the backend simply does not offer the source language comparison for
  those particular records, and still offers the default language one.

Translations created from now on record their source. A record whose value
matters can be re-translated, or the column set by hand; neither is required for
the extension to work.

.. index:: Database, TCA, ext:academic_jobs
