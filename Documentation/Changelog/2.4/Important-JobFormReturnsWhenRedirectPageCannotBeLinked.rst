.. _important-job-form-returns-when-redirect-page-cannot-be-linked:

===================================================================================
Important: The job form returns to its page when the redirect page cannot be linked
===================================================================================

Description
===========

After a saved job, the :guilabel:`Jobs New` content element sent the visitor to
the root of the site when it could not link the redirect page in the language
of the visitor. TYPO3 links no page that has no translation into a language
whose fallback is strict, the default of a new site language, nor for example
a hidden, deleted or missing page, or a page restricted to other users or
outside its publish dates. The link was empty, and the redirect to an empty
address led to the start page of the default language. Nothing was logged, and
a confirmation queued for the redirect page waited in the session for a page
that never rendered it.

Now such a redirect page counts as no redirect page. The visitor returns to
the page of the form in the language of the visitor, and the form shows the
confirmation above itself, as without a redirect page, see
:ref:`important-job-form-returns-to-its-page`. The flash message creation mode
is applied as for a form without a redirect page as well: the mode
:guilabel:`Suppress when a redirect page is set` shows the confirmation on the
form, and :guilabel:`never` shows none.

The controller logs a warning that names the redirect page, the page of the
form and the language, for example:

..  code-block:: text

    The redirect page 6 of the job form on page 5 cannot be linked in language 1 (Deutsch), it is missing, hidden, access restricted, not translated or of a type that cannot be linked. The visitor returns to the form.

It is written by the logger of
:php:`\FGTCLB\AcademicJobs\Controller\JobController`, which the default log
configuration of TYPO3 writes to the log file of the installation.

This holds for every source of the redirect page: the option
:guilabel:`Redirect page after job creation` of the content element, the
setting ``plugin.tx_academicjobs.saveForm.fallbackRedirectPageId``, a listener
of :php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` and the deprecated
setting ``plugin.tx_academicjobs.listPid``.

Impact
======

*   A visitor of a translated form whose redirect page is not translated
    returns to the form with the confirmation, instead of the start page.
*   The warning in the log names the redirect page to translate, or to replace
    by one that exists in every language of the form.
*   A redirect page that can be linked works as before.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs New` content element on a
site with more than one language, and every installation whose redirect page
of the form is hidden or deleted.

.. index:: Frontend, ext:academic_jobs
