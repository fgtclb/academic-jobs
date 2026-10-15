..  _developers-event-after-save-job-event:

=================
AfterSaveJobEvent
=================

:php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` is owned by this
extension. It decides what happens to the visitor after a job was submitted
through the :ref:`Job Form <integrator-job-form>`: which page they are sent to,
and whether the confirmation is queued as a flash message.

..  _developers-event-after-save-job-event-dispatched:

When it is dispatched
=====================

The :guilabel:`Job Form` dispatches the event once for every job it saved, in
the action that receives the submission. By then:

*   the submitted values passed the validation. A rejected submission shows the
    form again and dispatches nothing.
*   the job is saved, hidden, and has its uid.
*   the redirect page and the flash message mode are resolved from the options
    of the content element and their site settings, see :confval:`Redirect page
    after job creation <job-form-redirect-page-id>` and :confval:`Flashmessage
    creation mode when persisting new jobs
    <job-form-flash-message-creation-mode>`.

After the event the form uses the deprecated
:typoscript:`plugin.tx_academicjobs.settings.listPid` when the listeners left
no redirect page, sends the notification mail, queues the confirmation as the
mode says and redirects, to the redirect page or back to the form.

..  _developers-event-after-save-job-event-api:

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getJob()`
        -   The saved job, :php:`\FGTCLB\AcademicJobs\Domain\Model\Job`.
    *   -   :php:`getRequest()`
        -   The request that submitted the form.
    *   -   :php:`getCurrentPageId()`
        -   The uid of the page that shows the form.
    *   -   :php:`getSettings()`
        -   The settings of the plugin: the options of the content element over
            the TypoScript of :typoscript:`plugin.tx_academicjobs.settings` and
            :typoscript:`plugin.tx_academicjobs_newjobform.settings`.
    *   -   :php:`getRedirectPageId()`, :php:`setRedirectPageId()`
        -   The uid of the page the visitor is sent to, or :php:`null` for none.
            A page id of `0` or below counts as no page.
    *   -   :php:`getFlashMessageCreationMode()`,
            :php:`setFlashMessageCreationMode()`
        -   Whether the confirmation is queued, a case of
            :php:`\FGTCLB\AcademicBase\Form\FlashMessageCreationMode`:
            :php:`ALWAYS`, :php:`NEVER` or
            :php:`SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE`, which queues it
            unless the visitor is sent to another page than the one of the
            form.

Both setters return the event.

..  _developers-event-after-save-job-event-example:

Example: a confirmation page for theses
=======================================

A site accepts jobs and theses through one form. Companies that offer a thesis
should land on a page that explains how the supervision by a professor is
arranged, everyone else on the general thank-you page of the content element.
The page of its own carries its text, so no confirmation message is queued for
it.

The page is a TypoScript setting of the project, so it is part of the settings
the event hands over:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicjobs.settings.thesisConfirmationPageId = 42

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RedirectSubmittedThesis.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Form\FlashMessageCreationMode;
    use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class RedirectSubmittedThesis
    {
        private const JOB_TYPE_THESIS = 3;

        #[AsEventListener(identifier: 'my-extension/redirect-submitted-thesis')]
        public function __invoke(AfterSaveJobEvent $event): void
        {
            if ($event->getJob()->getType() !== self::JOB_TYPE_THESIS) {
                return;
            }
            $pageId = (int)($event->getSettings()['thesisConfirmationPageId'] ?? 0);
            if ($pageId <= 0) {
                return;
            }
            $event->setRedirectPageId($pageId);
            $event->setFlashMessageCreationMode(FlashMessageCreationMode::NEVER);
        }
    }

..  _developers-event-after-save-job-event-rules:

Rules and pitfalls
==================

**The job is saved already.** A listener cannot stop the save or reject the
job. The checks of a submission belong in the :ref:`validation settings
<configuration-validations>`. A change a listener makes to the job, or to
another record, through a repository is persisted by the form before it
redirects, so the listener does not have to persist it itself.

**A later listener reads what an earlier one set.** :php:`getRedirectPageId()`
returns the value the previous listener handed in, a page id of `0` or below
included. Order listeners with the `before` and `after` arguments of the
attribute when two of them decide the page.

**A page id of 0 or below is no page.** The visitor is then sent back to the
page of the form, exactly as when no page was set, and the deprecated list page
is not used for it, see
:ref:`important-redirect-page-zero-from-a-listener-means-none`. The form shows
the confirmation above itself there, see
:ref:`important-job-form-returns-to-its-page`.

**The mail is sent after the event.** A listener does not learn whether it
could be sent. The confirmation the form queues says so to the visitor.

**The confirmation on a redirect page needs a page that renders it.** The form
shows it for a visitor who returns to the form. For a redirect page, queuing it
is all the mode decides, see :ref:`integrator-job-form-flash-messages`.

..  _developers-event-after-save-job-event-introduced:

Introduced in
=============

The event exists since the first release of the extension. Changed in 2.4:

*   :ref:`important-redirect-page-zero-from-a-listener-means-none`
*   :ref:`important-job-form-returns-to-its-page`

Changed in 3.0:

*   :ref:`deprecation-flash-message-creation-mode-moved`: the enum of the mode
    is :php:`\FGTCLB\AcademicBase\Form\FlashMessageCreationMode` now.
