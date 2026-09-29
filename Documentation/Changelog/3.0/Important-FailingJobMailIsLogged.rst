.. _important-1790675015:

===========================================================================
Important: A notification mail that cannot be sent no longer fails the form
===========================================================================

Description
===========

A job submitted through the :guilabel:`Jobs New` content element is saved
before the mail that announces it is sent. When the mail could not be sent, the
error ended the request although the job was saved. The visitor got an error
in place of the form instead of the confirmation, and reloading the page
submitted the form again.

The mail cannot be sent when

*   the recipient or the sender address is empty or invalid. The defaults of
    :typoscript:`plugin.tx_academicjobs.email.recipientEmail` and
    :typoscript:`plugin.tx_academicjobs.email.from` are empty, so a site that
    never set them met the error after every submitted job,
*   the mail transport of the installation refuses the mail or cannot deliver
    it,
*   the mail template cannot be found or rendered, for example because
    :typoscript:`plugin.tx_academicjobs.email.templateName` names a template
    that no mail template path holds.

In each of these cases the job stays saved, and the visitor now gets the same
redirect as after a sent mail. Where the site shows the messages of the form,
the visitor sees the warning "Notification email could not be sent" instead of
the success message. The warning existed before, but it could never show.

The failure is logged at level error with the uid of the job and the
exception, by the logger of
:php:`\FGTCLB\AcademicJobs\Controller\JobController`. With the default log
configuration of TYPO3 it lands in the log file of the installation, in
:file:`var/log/` of a Composer based installation and in
:file:`typo3temp/var/log/` of a classic one. See
:ref:`configuration-general-notification-mail-failure`.

Impact
======

*   A visitor who got an error before now gets the confirmation redirect, and
    nobody gets a mail about the job. Check the log after changing the
    mail configuration of a site.
*   Any other error while the mail is built or sent still ends the request, as
    before, because it points to a defect a log entry would hide.
*   With a spool, the mail is only queued. A transport that fails later, when
    the queue is sent, is not reported by this extension, and the visitor has
    already seen the success message.

.. index:: Frontend, NotScanned
