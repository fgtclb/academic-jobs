..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

..  _configuration-general-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Job List`,
:guilabel:`Job Details` or :guilabel:`Job Form` content element are rendered by
the content element layout of the site, as for any other content element. The
layouts of :guilabel:`EXT:fluid_styled_content` and of the bootstrap package do
that, and the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicjobs.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting
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

..  _configuration-general-detail-page-title:

The title of the job detail page
================================

A :guilabel:`Job Details` content element that shows a job makes the title of
the job the title of the page, through the page title API of TYPO3. Its
provider is registered as :typoscript:`academicJobs` and is asked before the
providers :typoscript:`record` and :typoscript:`seo` of the core:

..  code-block:: typoscript
    :caption: EXT:academic_jobs/Configuration/TypoScript/setup.typoscript

    config.pageTitleProviders {
      academicJobs {
        provider = FGTCLB\AcademicJobs\PageTitle\JobTitleProvider
        before = record,seo
      }
    }

The static templates and the site sets of the extension both deliver it. A site
changes the order there, or removes the provider with
:typoscript:`config.pageTitleProviders.academicJobs >`. A page without a job
keeps its own title. The detail also writes the meta tags ``og:title`` and
``twitter:title``, and ``description``, ``og:description`` and
``twitter:description`` from the text of the description of the job, the
latter three only when the job has one. A page without them keeps its own
description, the one :guilabel:`EXT:seo` writes for example.

..  _configuration-general-detail-cache-lifetime:

The page cache of the job detail page
=====================================

The job list is rendered outside the page cache, the job detail is cached with
its page. The cache entry of a page that shows a job ends with the next start
or end time of that job, or of a translation of it, so a job that ends is no
longer shown from the page cache. TYPO3 v13 and v14 do that themselves for the
records Extbase fetches when the feature :php:`frontend.cache.autoTagging` is
enabled, which they do for a new installation and not for an updated one. The
extension limits the lifetime with or without it.

..  _configuration-general-notification-mail:

The notification mail about a submitted job
===========================================

When a visitor submits a job through the :guilabel:`Job Form` content element,
the job is saved hidden and one mail announces it. The mail is rendered from a
Fluid mail template into an HTML and a plain-text part, on the core layout
:file:`SystemEmail` that the mails of TYPO3 itself use, and it is sent through
the mail configuration of the installation.

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
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
    *   -   :typoscript:`plugin.tx_academicjobs.email.template`
        -   empty
        -   The message of the mail. When it is empty, the template renders
            its default message in the language of the page the form was
            submitted on. English and German are shipped.
    *   -   :typoscript:`plugin.tx_academicjobs.email.templateName`
        -   `JobCreated`
        -   The name of the mail template. Empty falls back to `JobCreated`.

The site settings are declared by the aggregate set `fgtclb/academic-jobs`. A
site configured through static templates sets the constants instead.

..  _configuration-general-notification-mail-template:

Changing the mail template
--------------------------

The extension registers its template path as
:php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][20]`, a low key
on purpose. A site package replaces the shipped template with a path of its
own under any higher key, holding a :file:`JobCreated.html` and a
:file:`JobCreated.txt`:

..  code-block:: php
    :caption: EXT:my_sitepackage/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][100]
        = 'EXT:my_sitepackage/Resources/Private/Templates/Email/';

A template of another name in any mail template path is selected with
:typoscript:`plugin.tx_academicjobs.email.templateName`.

On TYPO3 v14, a site that uses the core site set `typo3/email` can list the
path in its site setting :yaml:`email.templateRootPaths` instead. A list is
added above every global path. The mail then also follows the site setting
:yaml:`email.format` of that set, as every mail TYPO3 sends for the site
does. TYPO3 v13 has no such site set.

The template uses the core layout :file:`SystemEmail`, which adds the TYPO3
logo, loaded from the site URL, and an English footer naming the site. A
template of a project can use a layout of its own.

The template receives these variables, next to those every Fluid mail of the
core gets, such as :html:`{normalizedParams}`:

..  list-table::
    :header-rows: 1

    *   -   Variable
        -   Content
    *   -   :html:`{job}`
        -   The submitted job, :php:`\FGTCLB\AcademicJobs\Domain\Model\Job`.
    *   -   :html:`{url}`
        -   The link to the edit form of the job in the TYPO3 backend.
    *   -   :html:`{settings}`
        -   The settings of the plugin.
    *   -   :html:`{emailText}`
        -   The value of :typoscript:`plugin.tx_academicjobs.email.template`,
            empty when it is not set.

The link in :html:`{url}` opens the job in the record editor of the backend.
It carries no security token, because the frontend has no backend session to
create one for. A backend user who opens it is asked to log in, when not
logged in already, and is then taken to the job. The link uses the host of the
request the form was submitted with.

Fluid escapes values in the plain-text template as well. The shipped
:file:`JobCreated.txt` therefore passes them through :html:`f:format.raw()`, and
a template of a project should do the same.

..  _configuration-general-notification-mail-failure:

When the mail cannot be sent
----------------------------

The mail is sent after the job is saved. When it cannot be sent, because the
recipient or the sender address is empty or invalid, because the mail transport
refuses it or cannot deliver it, or because the template cannot be found or
rendered, the job stays saved and the visitor gets the same redirect as after
a sent mail. Where the site shows the messages of the form, the visitor sees
the warning "Notification email could not be sent" instead of the success
message.

The failure is logged at level error, with the uid of the job and the
exception, whose message names for example the missing template and the paths
looked in. The logger is the one of
:php:`\FGTCLB\AcademicJobs\Controller\JobController`, so with the default log
configuration of TYPO3 the entry lands in the log file of the installation, in
:file:`var/log/` of a Composer based installation and in
:file:`typo3temp/var/log/` of a classic one. Check it after changing the mail
configuration of a site.

With a spool, the mail is only queued. A transport that fails later, when the
queue is sent, is not reported by this extension, and the visitor has already
seen the success message.

Any other error while the mail is built or sent still ends the request.
