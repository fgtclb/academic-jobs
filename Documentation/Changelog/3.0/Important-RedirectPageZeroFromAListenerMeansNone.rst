.. _important-redirect-page-zero-from-a-listener-means-none:

===================================================================
Important: A redirect page of 0 or below from a listener means none
===================================================================

Description
===========

A listener of :php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` may set
a redirect page of ``0`` or below with :php:`setRedirectPageId()`, for example
as the cast of an empty setting of its own. The :guilabel:`Job Form` then
redirected the visitor to the link built for that page id, the root of the
domain, while the flash message mode counted it as no redirect page and queued
the confirmation for the form page. The visitor did not see the message after
the save, but on the next visit of the form.

Such a page id now counts as no redirect page for the redirect as well. The
form sends the visitor back to the form page, exactly as when no redirect page
is set, and queues the message as the configured mode says. That is not a
working state: the redirect asks for an action the content element does not
offer, so the form page shows an error in place of the form and of the
message, see :ref:`integrator-job-form-settings`. Keep a redirect page set in
the content element or in the site setting. The deprecated TypoScript setting
``plugin.tx_academicjobs.settings.listPid`` is not used for it, it still only
applies when the listeners leave no redirect page at all.

:php:`getRedirectPageId()` of the event still returns what a listener set, so a
later listener reads the ``0`` of an earlier one. The event is described in
:ref:`developers-event-after-save-job-event`.

Impact
======

A listener that sets a redirect page of ``0`` or below sends the visitor back
to the form page, which then shows an error, instead of to the root of the
domain. The plugin setting
:guilabel:`Redirect page after job creation` and the TypoScript setting
``plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId`` already
treated ``0`` as no redirect page and are unchanged.

Affected Installations
======================

An installation with a listener of :php:`AfterSaveJobEvent` that sets a
redirect page of ``0`` or below.

.. index:: Frontend, PHP-API, ext:academic_jobs
