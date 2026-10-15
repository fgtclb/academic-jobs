..  _integrator:

===============
For integrators
===============

This extension ships three content elements: a list of the jobs, the detail of
one job and a form a visitor submits a job with. An integrator adds them to a
site with the sets or the static templates, see :ref:`configuration`, and sets
up each content element in its tab :guilabel:`Configuration`. The pages below
explain every option of that tab, what the visitor sees and when to choose
which value.

The options of a content element meet the TypoScript of the plugin, the block
:typoscript:`plugin.tx_academicjobs.settings`, in one settings array. A value
stored in the content element replaces the TypoScript value of the same name,
an empty one included, because this extension names no key in
:typoscript:`ignoreFlexFormSettingsIfEmpty`. None of the options of the list
has a TypoScript value to fall back to, so that rule changes nothing there.

The two options of the job form fall back in the code instead: an empty
:confval:`Redirect page after job creation <job-form-redirect-page-id>` uses
the site setting
:typoscript:`plugin.tx_academicjobs.saveForm.fallbackRedirectPageId`, and the
value :guilabel:`Fallback - Use TypoScript settings` of
:confval:`Flashmessage creation mode when persisting new jobs
<job-form-flash-message-creation-mode>` uses the site setting
:typoscript:`plugin.tx_academicjobs.saveForm.fallbackFlashMessageCreationMode`.

Everything that is set once per site rather than per content element, the
storage folder, the detail and list pages, the notification mail and the
pagination links among others, is a site setting, see
:ref:`configuration-components`.

..  list-table::
    :header-rows: 1
    :widths: 10 25 25 40

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  image:: /Images/Icons/employment.svg
                :width: 32px
                :alt: Icon of the Job List content element
        -   :ref:`Job List <integrator-job-list>`
        -   ``academicjobs_list``
        -   The jobs of the storage folder, all of them or those of one job
            type, on one page or split into pages.
    *   -   ..  image:: /Images/Icons/employment.svg
                :width: 32px
                :alt: Icon of the Job Details content element
        -   :ref:`Job Details <integrator-job-details>`
        -   ``academicjobs_detail``
        -   The job a link of the job list points to.
    *   -   ..  image:: /Images/Icons/employment.svg
                :width: 32px
                :alt: Icon of the Job Form content element
        -   :ref:`Job Form <integrator-job-form>`
        -   ``academicjobs_newjobform``
        -   A form a visitor enters a job offer with. The job is saved hidden
            for an editor to review.

All three content elements show the same icon.

..  toctree::
    :hidden:
    :titlesonly:

    JobList
    JobDetails
    JobForm
