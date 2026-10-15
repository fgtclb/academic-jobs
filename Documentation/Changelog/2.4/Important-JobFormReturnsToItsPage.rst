.. _important-job-form-returns-to-its-page:

=================================================================
Important: The job form returns to its page with the confirmation
=================================================================

Description
===========

Without a redirect page, the :guilabel:`Jobs New` content element sent the
visitor to an action of the job list on the page of the form, an action the
form does not offer. The visitor got the form page without any word on the
job. The confirmation was queued for the plugin, in
``extbase.flashmessages.tx_academicjobs_newjobform``, and no shipped template
rendered that queue, so it waited in the session until a page rendered it.

Now the form sends the visitor back to the page of the form, to its address
without any argument, whenever no redirect page is set or the redirect page is
the page of the form. The form shows the confirmation above itself, from a
queue of its own, ``academic_jobs.new_job_form.confirmation``, which
:file:`Templates/Job/New.html` renders:

..  code-block:: html

    <f:flashMessages queueIdentifier="academic_jobs.new_job_form.confirmation" />

The same holds for the warning about a notification mail that could not be
sent, and for the message that the job could not be created.

What counts as a redirect page is unchanged: the page of the option
:guilabel:`Redirect page after job creation` of the content element, else the
page of the setting ``plugin.tx_academicjobs.saveForm.fallbackRedirectPageId``,
as a TypoScript constant or a site setting. A listener of
:php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` receives that page and can
replace it. The deprecated setting ``plugin.tx_academicjobs.listPid`` still
counts as the redirect page of last resort: a site that sets it and none of the
others is still sent to that page, and the deprecation is logged. A page of
``0`` or below is no page, see
:ref:`important-redirect-page-zero-from-a-listener-means-none`.

A redirect page works as before: the visitor is sent there, and the
confirmation goes to the queue of the plugin. Only a redirect page that renders
that queue outside the page cache shows it, for example with a
:typoscript:`COA_INT` that renders a Fluid template with this line:

..  code-block:: html

    <f:flashMessages queueIdentifier="extbase.flashmessages.tx_academicjobs_newjobform" />

The form no longer shows a confirmation that was queued for a redirect page,
so a visitor does not meet it at the next visit of the form.

The answer to a saved job is the redirect alone, with the status
``303 See Other``. No page is rendered for the submission any more.

A submission the validation rejects no longer queues the generic error message
of Extbase, "An error occurred while trying to call ...", which named a PHP
method to the visitor wherever a page rendered the queue of the plugin. The
form shows the message of every rejected field, as before.

The items ``-1`` and ``0`` of :guilabel:`Flashmessage creation mode when
persisting new jobs (FE)` are labelled :guilabel:`Use the site or TypoScript
setting` and :guilabel:`Suppress when a redirect page is set` now. Their
values and what they do are unchanged.

Impact
======

*   A form without a redirect page shows the confirmation to the visitor,
    above the form.
*   A copy of :file:`Templates/Job/New.html` in a site package has to render
    the queue ``academic_jobs.new_job_form.confirmation`` with the line above.
    Without it, the visitor who returns to the form sees no confirmation.
*   With the mode :guilabel:`always` and a redirect page, that page still has
    to render ``extbase.flashmessages.tx_academicjobs_newjobform`` in an
    uncached part, as shown above.
*   The mode :guilabel:`never` applies when the visitor returns to the form as
    well.
*   With the mode :guilabel:`Suppress when a redirect page is set` and a
    redirect page to another page, neither the confirmation nor the warning
    about the notification mail is queued, as before.
*   Script code that reads the answer to the submission gets an empty response
    with the status ``303`` and has to follow the redirect.
*   A page that renders the queue of the plugin no longer shows the error
    message of Extbase after a rejected submission.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs New` content element, in
particular one without a redirect page, and one with a copy of
:file:`Templates/Job/New.html`.

.. index:: Frontend, Fluid, ext:academic_jobs
