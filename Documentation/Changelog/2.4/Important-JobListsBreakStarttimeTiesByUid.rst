.. _important-job-lists-break-starttime-ties-by-uid:

================================================
Important: Job lists break starttime ties by uid
================================================

Description
===========

The job lists order by :sql:`starttime` through the repository's
:php:`$defaultOrderings` — but :sql:`starttime` is ``0`` for every job that was
never scheduled, so most of a typical list tied and the relative order of the
tied records belonged to the database. On PostgreSQL that is not the same list
twice. The default orderings now carry :sql:`uid` ascending as a tiebreaker.

Impact
======

No visible change is expected: within equal :sql:`starttime` values,
:sql:`uid` ascending is the order every supported database returned in
practice, it is simply guaranteed now rather than coincidental. Jobs with
different :sql:`starttime` values keep their order.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_jobs
