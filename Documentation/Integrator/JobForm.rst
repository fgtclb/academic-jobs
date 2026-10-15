..  _integrator-job-form:

========
Job Form
========

..  image:: /Images/Icons/employment.svg
    :width: 48px
    :alt: Icon of the Job Form content element

:CType:
    ``academicjobs_newjobform``

:Plugin:
    `AcademicJobs`, plugin `NewJobForm`, actions
    :php:`JobController::newAction()` and
    :php:`JobController::createAction()`

:Set:
    `fgtclb/academic-jobs-new-job-form`, or the aggregate set
    `fgtclb/academic-jobs`

:Templates:
    :file:`Templates/Job/New.html`, with the partial
    :file:`Job/Properties/Job.html` and the field partials below
    :file:`Job/Forms/`, see :ref:`templates-override-form-fields` and
    :ref:`templates-override-new-job-form`

..  figure:: /Images/Screenshots/job-form-overview.png
    :alt: A form with fields for the title, type, description and dates of a job offer
    :class: with-border with-shadow

    The job form as a visitor sees it.

The job form lets a visitor enter a job offer: its title, type, description,
dates, location, an image and the contact details. Which fields are required,
and how they are rendered, is set in the :ref:`validation settings
<configuration-validations>`. The image is uploaded into the folder and within
the limits of the site settings
:typoscript:`plugin.tx_academicjobs.jobAvatarImage.*`. The page that shows the
form loads a rich text editor for the description fields, which a site can
switch off, see :ref:`configuration-javascript`.

It is the content element for a site that accepts job offers from outside,
from companies that advertise a thesis or a job for students, for example.
Nothing a visitor submits is published right away:

#.  The form checks the submitted values. A rejected field shows the form
    again, with its messages.
#.  The job is saved hidden in the storage folder of the site, the site setting
    :typoscript:`plugin.tx_academicjobs.persistence.storagePid`. Its
    application deadline is kept until the end of the chosen day.
#.  One notification mail announces the job, see
    :ref:`configuration-general-notification-mail`.
#.  The visitor is redirected, and a confirmation message is queued, as the
    two options below decide.

An editor then reviews the job in the backend and publishes it. Until then a
:ref:`job list <integrator-job-list>` shows it only with
:confval:`Show hidden records <job-list-show-hidden-records>`. The form is not
cached with the page.

..  _integrator-job-form-settings:

Settings
========

..  confval:: Redirect page after job creation
    :name: job-form-redirect-page-id
    :type: page
    :Default: empty

    The page the visitor is sent to after the job is saved, usually a page
    that thanks for the submission and says what happens next.

    A page chosen here decides alone. Empty, the site setting
    :typoscript:`plugin.tx_academicjobs.saveForm.fallbackRedirectPageId`
    applies, :guilabel:`Default redirect page PID, can be overruled in
    plugin`, which is `0` and therefore no page by default. One content
    element thus overrides the page every other form of the site uses.

    When neither names a page, the deprecated setting
    :typoscript:`plugin.tx_academicjobs.settings.listPid` is used instead, the
    site setting :guilabel:`Default list page PID` that the detail page links
    back to, and a deprecation is logged. Set a redirect page rather than
    relying on it, the fallback is removed in a future version.

    An event listener can replace the page this option resolved to, see
    :ref:`developers-event-after-save-job-event`.

    ..  important::

        Set a redirect page, here or in the site setting. Without any, the
        form sends the visitor back to its own page with an action the
        content element does not offer, and the content element shows an
        error in place of the form and of the confirmation.

..  confval:: Flashmessage creation mode when persisting new jobs (FE)
    :name: job-form-flash-message-creation-mode
    :type: select
    :Default: Fallback - Use TypoScript settings

    Whether the confirmation is queued as a flash message after the job is
    saved. The confirmation says that the job was created and will be reviewed,
    or, when the notification mail could not be sent, that it was created and
    the visitor should get in touch.

    :guilabel:`Fallback - Use TypoScript settings`
        The site setting
        :typoscript:`plugin.tx_academicjobs.saveForm.fallbackFlashMessageCreationMode`
        decides, :guilabel:`Flash message creation mode`. Its values are those
        of the three items below, `1`, `2` and `0`, and its default is `0`.

    :guilabel:`always`
        The confirmation is queued after every saved job.

    :guilabel:`never`
        No confirmation is queued. Choose it when the redirect page carries a
        confirmation text of its own.

    :guilabel:`suppress when list pid has been configured`
        The confirmation is queued unless the visitor is redirected to another
        page than the one of the form. Despite its label, the item looks at the
        redirect page, the one of the option above, of the site setting, of a
        listener or of the deprecated list page, and not at the list page.

    An event listener can replace the mode, see
    :ref:`developers-event-after-save-job-event`.

..  _integrator-job-form-flash-messages:

Where the confirmation is shown
-------------------------------

The form queues its confirmation for itself, in the queue
`extbase.flashmessages.tx_academicjobs_newjobform`. None of the shipped
templates renders that queue: the form template renders no flash message, and
the job list and the job detail render only the messages queued for them. A
queued confirmation stays in the session of the visitor until a page renders
its queue.

To show it, render the queue on the page the visitor is redirected to, in a
part of that page that is not cached, for example with a
:typoscript:`COA_INT` that renders a Fluid template:

..  code-block:: html
    :caption: The Fluid template rendered on the redirect page

    <f:flashMessages queueIdentifier="extbase.flashmessages.tx_academicjobs_newjobform" />

A site that shows a confirmation page of its own instead chooses
:guilabel:`never`, so no message waits in the session for a later page.
