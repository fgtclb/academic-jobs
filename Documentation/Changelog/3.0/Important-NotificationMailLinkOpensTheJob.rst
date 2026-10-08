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
so a return URL would be dropped there anyway. The link reads:

..  code-block:: text

    https://example.org/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B42%5D=edit

Impact
======

*   Editors open a submitted job directly from the mail.
*   A mail template of a project that renders :html:`{url}` gets the new link
    without a change.
*   Behaviour is identical on TYPO3 v13 and v14.

.. index:: Frontend, ext:academic_jobs
