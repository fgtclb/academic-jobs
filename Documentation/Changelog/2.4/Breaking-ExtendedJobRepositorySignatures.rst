.. _breaking-ace-250-academic-jobs:

====================================================
Breaking: Extended `JobRepository` method signatures
====================================================

Description
===========

To support the new :guilabel:`Show hidden records` plugin option,
:php:`\FGTCLB\AcademicJobs\Domain\Repository\JobRepository` changed one method
and gained two:

*   :php:`findByJobType(int $jobType, bool $includeHidden = false): QueryResultInterface`.
    The optional parameter :php:`$includeHidden` was appended.
*   :php:`findAllJobs(bool $includeHidden = false): QueryResultInterface` is
    new. The job list calls it without a job type instead of the inherited
    :php:`findAll()`, so the option is honoured there as well.
*   :php:`findUidsWithHiddenDefaultRecord(array $uids): array` is new. It
    returns the uids of the given jobs whose record in the default language is
    hidden, which the job list uses to leave those jobs unlinked.

With :php:`$includeHidden` set to :php:`true`, the query ignores only the
hidden flag of the jobs. Deleted records, start and end time and frontend user
groups still apply.

Impact
======

Calls of the repository keep working: the new parameter has a default value.

A class that extends :php:`JobRepository` and overrides :php:`findByJobType()`
with the old signature fails with a PHP fatal error, because the method of the
subclass no longer accepts every parameter of the parent method. A subclass that
already declares a method :php:`findAllJobs()` or
:php:`findUidsWithHiddenDefaultRecord()` with another signature fails the same
way.

Affected Installations
======================

Installations that extend
:php:`\FGTCLB\AcademicJobs\Domain\Repository\JobRepository` and override
:php:`findByJobType()`, or declare one of the two new methods. All other
installations are unaffected.

Migration
=========

Add the parameter to the overriding method and pass it on:

..  code-block:: php

    public function findByJobType(int $jobType, bool $includeHidden = false): QueryResultInterface
    {
        // ...
        return parent::findByJobType($jobType, $includeHidden);
    }

Rename a method of the subclass that collides with one of the new methods.

.. index:: PHP-API, ext:academic_jobs
