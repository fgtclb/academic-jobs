.. _important-notification-mail-link-opens-the-job:

=================================================================
Important: The link of the notification mail opens the job record
=================================================================

Description
===========

The mail that announces a job submitted through the :guilabel:`Jobs New`
content element links to the job in the TYPO3 backend. The link carried the
security token `dummyToken`, because the frontend has no backend session to
create a real one for. The backend rejected that token, so a backend user who
opened the link landed on the dashboard instead of the job. The link also
carried the address of the frontend form as return URL.

The link now carries no token and no return URL. A backend user who opens it
is asked to log in, when not logged in already, and is then taken to the
record editor of the job. This is the link the backend itself offers for
sharing a record. The redirect after the login keeps only the record to edit,
so a return URL would be dropped there anyway.

The text of the mail was English on every site. It is now written in the
language of the page the form was submitted on, English and German are
shipped, and it reads:

..  code-block:: text

    A new job advert has been submitted. Please review it in the TYPO3 backend.

    Open the job advert in the TYPO3 backend:
    https://example.org/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B42%5D=edit

Impact
======

*   Editors open a submitted job directly from the mail.
*   The text can be changed through the labels `email.jobCreated.message` and
    `email.jobCreated.link` of
    :file:`EXT:academic_jobs/Resources/Private/Language/locallang.xlf`. The
    subject is still :typoscript:`plugin.tx_academicjobs.email.subject`, which
    is not translated.
*   Behaviour is identical on TYPO3 v12 and v13.

.. index:: Frontend, ext:academic_jobs
