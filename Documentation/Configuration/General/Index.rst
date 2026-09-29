..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

..  _configuration-general-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Jobs List`,
:guilabel:`Jobs Detail` or :guilabel:`Jobs New` content element are rendered by
the content element layout of the site, as for any other content element. The
layouts of :guilabel:`EXT:fluid_styled_content` and of the bootstrap package do
that, and the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicjobs.renderContentElementHeader = 1

On TYPO3 v13, for a site that uses the site set, that is the site setting
:guilabel:`Render the content element header in the plugins` of
`fgtclb/academic-jobs`. The templates then render the header partial of
:guilabel:`EXT:fluid_styled_content` above their output. Do not switch it on
where the layout renders the header: the header then appears twice.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicjobs.settings.defaultHeaderType`, which is
mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.

..  _configuration-general-notification-mail:

The notification mail about a submitted job
===========================================

When a visitor submits a job through the :guilabel:`Jobs New` content element,
the job is saved hidden and one plain-text mail announces it, with a link to
the job in the TYPO3 backend. It is sent through the mail configuration of the
installation.

..  list-table::
    :header-rows: 1

    *   -   Constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicjobs.email.from`
        -   empty
        -   The sender address.
    *   -   :typoscript:`plugin.tx_academicjobs.email.recipientEmail`
        -   empty
        -   The address the mail is sent to.
    *   -   :typoscript:`plugin.tx_academicjobs.email.subject`
        -   `New job application`
        -   The subject of the mail.

On TYPO3 v13, a site that depends on the aggregate set `fgtclb/academic-jobs`
can set them as site settings instead.

The text of the mail is fixed.
:typoscript:`plugin.tx_academicjobs.email.template`, labelled "Email content",
has no effect in 2.x.

..  _configuration-general-notification-mail-failure:

When the mail cannot be sent
----------------------------

The mail is sent after the job is saved. When it cannot be sent, because the
recipient or the sender address is empty or invalid, or because the mail
transport refuses it or cannot deliver it, the job stays saved and the visitor
gets the same redirect as after a sent mail. Where the site shows the messages
of the form, the visitor sees the warning "Notification email could not be
sent" instead of the success message.

The failure is logged at level error, with the uid of the job and the
exception. The logger is the one of
:php:`\FGTCLB\AcademicJobs\Controller\JobController`, so with the default log
configuration of TYPO3 the entry lands in the log file of the installation, in
:file:`var/log/` of a Composer based installation and in
:file:`typo3temp/var/log/` of a classic one. Check it after changing the mail
configuration of a site.

With a spool, the mail is only queued. A transport that fails later, when the
queue is sent, is not reported by this extension, and the visitor has already
seen the success message.

Any other error while the mail is built or sent still ends the request.
