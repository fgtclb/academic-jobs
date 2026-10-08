.. _important-job-detail-answers-not-found-without-a-job:

===========================================================
Important: The job detail answers "not found" without a job
===========================================================

Description
===========

The job detail page answered a request without a job it can show with the
status ``200`` and the message "No job advert could be found." in the content
element. That applied to the page requested without a job, to a job that does
not exist, and to a hidden job. Search engines indexed it as a page of its
own. The page now answers ``404`` with the "page not found" handling of the
site, as the profile detail of :guilabel:`academic_persons` does.

The job list of a content element with :guilabel:`Show hidden records` lists
hidden jobs, but linked them to that detail page, which cannot show them. A
hidden job is listed without a link now. The detail page finds a job by its
record in the default language, so the visible translation of a hidden job is
listed without a link as well. The list template receives the uids of those
jobs as ``jobsWithHiddenDefaultRecord``.

Impact
======

A request of the detail page without a visible job ends with the error page
the site configures for ``404``, or the default error page of TYPO3 when it
configures none. The rest of the page is no longer rendered. The label
``tx_academicjobs.fe.alert.job_not_found.body`` is removed, an override of it
has no effect any more.

A link to the detail page in a menu, which carries no job, leads to that error
page. Hide the detail page in menus.

An installation that overrides :file:`Partials/Job/Item.html` in its own site
package keeps linking hidden jobs until it adopts the change.

TYPO3 v13 and v14 behave alike here.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs Detail` plugin, and the
:guilabel:`Jobs List` plugin with :guilabel:`Show hidden records` switched on.

.. index:: Frontend, ext:academic_jobs
