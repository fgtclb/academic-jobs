.. _feature-1790666339:

==================================================================
Feature: The notification mail about a new job is a Fluid template
==================================================================

Description
===========

A job submitted through the :guilabel:`Jobs New` content element was announced
with one fixed English sentence and the link to the job in the backend. The
setting :typoscript:`plugin.tx_academicjobs.email.template`, labelled "Email
content", was not read at all.

The mail is now rendered from the Fluid mail template `JobCreated`, with an
HTML and a plain-text part on the core layout :file:`SystemEmail`:

*   The title of the job is the heading of the mail.
*   The message is the text of
    :typoscript:`plugin.tx_academicjobs.email.template`. Its default is now
    empty, and an empty setting renders the default message of the template,
    in the language of the page the form was submitted on:

    ..  code-block:: text

        A new job advert has been submitted. Please review it in the TYPO3 backend.

    ..  code-block:: text

        Eine neue Stellenanzeige wurde eingereicht. Bitte prüfen Sie sie im TYPO3-Backend.

*   A link opens the job in the TYPO3 backend.

The new setting :typoscript:`plugin.tx_academicjobs.email.templateName`, a site
setting of `fgtclb/academic-jobs` and a constant of the static template,
selects the template, `JobCreated` by default. The extension registers its
template path under the key 20 of
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']`, so a site
package replaces the template with a path of its own under any higher key. On
TYPO3 v14, the mail also follows the template paths and the format a site sets
in the core site set `typo3/email`. See
:ref:`configuration-general-notification-mail`.

Recipient, sender and subject come from the same settings as before.

Impact
======

*   Every installation sends a different mail: two parts instead of one, and
    the default message above instead of "A new job has been posted. Please
    check the TYPO3 backend:". The core layout adds the TYPO3 logo to the HTML
    part, and a footer in English naming the site to both parts. A site that
    set
    :typoscript:`plugin.tx_academicjobs.email.template` gets its own text. A
    site that wants the old sentence sets it there.
*   A site that set :typoscript:`plugin.tx_academicjobs.email.template` to the
    old default "A new job application has been submitted. Please check the
    backend." keeps that sentence, now in the mail, and no longer gets the
    translated message.
*   A site whose mail configuration sets
    :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['format']` to `html` or `plain`
    gets that part only.
*   A project that sent its own mail, by replacing the controller or by a
    listener, can drop that code for a template of its own.

.. index:: Frontend, TypoScript, NotScanned
