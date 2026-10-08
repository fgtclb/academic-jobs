.. _important-job-detail-sets-the-page-title:

=============================================
Important: The job detail sets the page title
=============================================

Description
===========

The detail of a job wrote the title of the job into a meta tag
:html:`<meta name="title">`, which neither browsers nor search engines read,
and left the :html:`<title>` of the page at the title of the detail page, the
same for every job. A job without a description, or with an empty paragraph
only, got an empty :html:`<meta name="description">` and empty Open Graph and
Twitter descriptions, and the entities of a description were escaped a second
time, ``&amp;`` showed as ``&amp;amp;``.

The title of the job is now the title of the page, set through the page title
API of TYPO3 with the new provider
:php:`FGTCLB\AcademicJobs\PageTitle\JobTitleProvider`, registered as
:typoscript:`config.pageTitleProviders.academicJobs`. The meta tag
:html:`<meta name="title">` is no longer written. The three description meta
tags carry the text of the description with its entities decoded and its white
space reduced to single spaces, and are written only for a job with a
description. See
:ref:`configuration-general-detail-page-title`.

Impact
======

The :html:`<title>` of a job detail page names the job, with the site title
around it the way the site configures it. A site whose own provider should
win orders it before :typoscript:`academicJobs`.

A job without a description leaves the description of the page in place, the
one :guilabel:`EXT:seo` writes from the page properties for example.

TYPO3 v12 and v13 behave alike here.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs Detail` plugin.

.. index:: Frontend, TypoScript, ext:academic_jobs
