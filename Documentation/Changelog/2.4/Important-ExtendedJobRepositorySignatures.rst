.. _important-ace-250-academic-jobs:

=====================================================
Important: Extended `JobRepository` method signatures
=====================================================

Description
===========

To support the new "Show hidden records" plugin option, the
:php:`\FGTCLB\AcademicJobs\Domain\Repository\JobRepository` gained an
extended and a new method:

* :php:`findByJobType(int $jobType, bool $includeHidden = false): QueryResultInterface`
  — the new optional :php:`$includeHidden` parameter was appended.
* :php:`findAllJobs(bool $includeHidden = false): QueryResultInterface`
  — new method used on the no-filter listing path in place of the
  inherited :php:`findAll()`, so the option can be honoured there as
  well.

When :php:`$includeHidden` is :php:`true`, the query ignores only the
`disabled` (`hidden`) enable field via the Extbase query settings.

Impact
======

The change is non-breaking: the new parameter has a default value and
the additional method does not alter existing signatures. Projects that
extend or replace :php:`JobRepository` should adopt the same signatures
when overriding these methods.

Affected Installations
======================

Only installations that extend or override
:php:`\FGTCLB\AcademicJobs\Domain\Repository\JobRepository` need to take
the extended signatures into account. All other installations are
unaffected.

.. index:: PHP, ext:academic_jobs
