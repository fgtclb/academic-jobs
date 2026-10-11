.. _important-job-detail-cache-ends-with-the-job:

=========================================================
Important: The page cache of a job detail ends with a job
=========================================================

Description
===========

The job list is rendered outside the page cache, but the job detail is cached
with its page. The page cache did not know the start and end time of the job
it showed, so the detail page of a job kept showing the job after its end time
until the cache entry of the page expired.

The cache entry of a page that shows a job in the :guilabel:`Jobs Detail`
content element now ends with the next start or end time of that job, or of a
translation of it. TYPO3 v13 does the same for the records Extbase fetches when
the feature :php:`frontend.cache.autoTagging` is enabled, which it enables for
a new installation and leaves off for an updated one. TYPO3 v12 has no such
feature. The extension limits the lifetime either way. See
:ref:`configuration-general-detail-cache-lifetime`.

Impact
======

A job detail page is rendered again once the job it shows ends, and it then
answers ``404``. A page that shows no job, and a job without a start or end
time in the future, keep the cache lifetime the page has. TYPO3 v12 and v13
behave alike here.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs Detail` plugin.

.. index:: Frontend, ext:academic_jobs
