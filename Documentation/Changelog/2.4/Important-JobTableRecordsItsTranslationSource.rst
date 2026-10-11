.. _important-ace-463-academic-jobs:

=======================================================
Important: The job table records its translation source
=======================================================

Description
===========

:sql:`tx_academicjobs_domain_model_job` declared a :php:`languageField` and a
:php:`transOrigPointerField`, and no :php:`translationSource`.

The two answer different questions. :sql:`l10n_parent` says which record a
translation belongs to. :sql:`l10n_source` says which record it was written
*from*. They hold the same value when a translation was made from the default
language and different ones when it was made from another translation, and
without the second field that information is not recorded at all.

The :php:`ctrl` section of the table now carries:

..  code-block:: php

    'translationSource' => 'l10n_source',

Impact
======

TYPO3 adds an :sql:`l10n_source` column with an index to the table on the next
database comparison, as an unsigned integer that defaults to :sql:`0`. The
column is derived from the TCA, so there is nothing to add to
:file:`ext_tables.sql`.

The backend records the source of a job translation made from another
translation, and the localization state selector of a translated field can
refer to the value of that source.

Affected Installations
======================

All installations of this extension. Apply the database comparison in
:guilabel:`Admin Tools > Maintenance > Analyze Database Structure`, or with
:bash:`vendor/bin/typo3 extension:setup`.

No data migration is needed, and none is offered. Existing rows get the column
default :sql:`0`:

*   On a **default language record** that is the correct value. Such a record
    has no source.
*   On a **translation that predates this change** it means "not recorded". The
    backend then treats the translation as made from the default language, as
    it did before.

Translations created from now on record their source. A record whose value
matters can be translated again, or the column set by hand. Neither is required
for the extension to work.

.. index:: Database, TCA, ext:academic_jobs
