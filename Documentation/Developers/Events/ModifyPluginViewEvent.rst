..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` is owned by
:guilabel:`academic_base`, which describes it for the seven academic
extensions whose plugins dispatch it in :ref:`its developer chapter
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page says what the plugins of :guilabel:`academic_jobs` hand to it.

..  _developers-event-modify-plugin-view-event-dispatched:

When it is dispatched
=====================

Each of the three content elements dispatches the event once each time it
renders, after its action assigned its own variables. Submitting the form
renders nothing and dispatches no view event, the
:ref:`AfterSaveJobEvent <developers-event-after-save-job-event>` serves it.

..  list-table::
    :header-rows: 1
    :widths: 20 15 15 50

    *   -   Content element
        -   Plugin name
        -   Action
        -   Variables assigned before the event
    *   -   :ref:`Job List <integrator-job-list>`
        -   `List`
        -   `list`
        -   :html:`{jobs}`, :html:`{data}`, :html:`{record}`, and while the
            pagination is enabled :html:`{paginator}` and
            :html:`{pagination}`. :html:`{jobsWithHiddenDefaultRecord}` maps
            the uid of every listed job whose record in the default language
            is hidden to `true`.
    *   -   :ref:`Job Details <integrator-job-details>`
        -   `Detail`
        -   `show`
        -   :html:`{job}`, :html:`{data}`, :html:`{record}`
    *   -   :ref:`Job Form <integrator-job-form>`
        -   `NewJobForm`
        -   `new`
        -   :html:`{typeOptions}`, :html:`{employmentTypeOptions}`,
            :html:`{data}`, :html:`{record}`

The extension name of the context is `AcademicJobs`.

..  _developers-event-modify-plugin-view-event-api:

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering plugin: the request, the site and its
            language, the content object, the plugin and action name and the
            settings of the plugin, the options of the content element over
            the TypoScript of :typoscript:`plugin.tx_academicjobs.settings`.
    *   -   :php:`getView()`
        -   The view the action renders. A variable a listener assigns to it
            reaches the template and its partials.

..  _developers-event-modify-plugin-view-event-example:

Example: a privacy page next to the job form
============================================

A site links its privacy statement above the submit button of the job form.
The page differs per site, so the site package declares a site setting for it,
and a listener hands the page to the form. A template override of
:file:`Job/Forms/AdditionalFields.html` renders the link with
:html:`{privacyPageId}`, see :ref:`templates-override-new-job-form`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddPrivacyPageToJobForm.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddPrivacyPageToJobForm
    {
        #[AsEventListener(identifier: 'my-extension/add-privacy-page-to-job-form')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicJobs'
                || $context->getPluginName() !== 'NewJobForm'
            ) {
                return;
            }
            $privacyPageId = (int)($context->getSite()?->getSettings()->get('mySitepackage.privacyPageId') ?? 0);
            $event->getView()->assign('privacyPageId', $privacyPageId);
        }
    }

..  _developers-event-modify-plugin-view-event-rules:

Rules and pitfalls
==================

**The validations of the form cannot be replaced.** The job form assigns
:html:`{validations}` after the event on purpose, so the required marks of the
form always match what the validation checks.

**Replacing the jobs of a paginated list changes nothing.** The paginator is
built from the jobs before the event, and a paginated list renders the page of
the paginator. Without the pagination, the list renders :html:`{jobs}`, and a
listener that replaces it changes what is listed. Which jobs a list shows is
better decided by its options.

**The detail page is cached.** The listener runs when the page is rendered for
the cache, not for every visitor. The job list and the job form are not cached
and run it on every request.

..  _developers-event-modify-plugin-view-event-introduced:

Introduced in
=============

*   :ref:`feature-plugin-view-event`
*   :ref:`breaking-removed-job-new-action-view-event`: the event replaces the
    view event of the job form.
*   :ref:`feature-1790660966`: the variables of the pagination.
