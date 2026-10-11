.. _important-ace-316-academic-jobs:

=============================================================
Important: The job headings and the contact box are corrected
=============================================================

Description
===========

Three arguments of the job templates were written as quoted text where a
variable was meant, so they never carried the value of the content element or
the job. All three are corrected.

**Job titles in the list.** :file:`Partials/Job/Item.html` passed the text
``'data.subheader'`` as the subheader to :file:`Partials/Job/Header.html`. That
text is never empty, so with the header layouts 1 to 4 every job title of a
:guilabel:`Jobs List` was rendered one heading level lower than without a
subheader, for example an :html:`<h3>` instead of an :html:`<h2>` for layout 1,
whether the content element had a subheader or not.

**Contact heading in the detail view.** :file:`Partials/Job/Contact.html`
passed the texts ``'data.header_layout'`` and ``'data.subheader'``. The layout
matched none of the cases of :file:`Partials/Job/SectionHeader.html`, so the
heading of the contact section was always an :html:`<h2>`, whatever header
layout the content element had.

**Contact box in the detail view.** :file:`Templates/Job/Show.html` decided
whether a job has contact data with an inline condition that Fluid did not
evaluate, and the result was always true. A job without a contact name, email
address and phone number still rendered the grey contact box with its heading
and nothing below it, and the description was narrowed to make room for it.

Impact
======

*   The job titles of a :guilabel:`Jobs List` take the lower heading level only
    when the content element has a subheader.
*   The heading of the contact section of a :guilabel:`Jobs Detail` follows the
    header layout of the content element, like the job titles do.
*   A job without any contact data renders no contact box, and its description
    takes the full width of the row.

TYPO3 v12 and v13 behave alike. Nothing has to be configured or migrated. A
stylesheet that targets a heading level of these elements may need an update.

An installation that overrides :file:`Partials/Job/Item.html`,
:file:`Partials/Job/Contact.html` or :file:`Templates/Job/Show.html` keeps its
own output until it drops the copy or applies the same correction:

..  code-block:: html

    <!-- Partials/Job/Item.html and Partials/Job/Contact.html -->
    layout: data.header_layout,
    subheader: data.subheader,

    <!-- Templates/Job/Show.html -->
    <f:variable name="contact" value="{f:if(condition: '{job.contactName} || {job.contactEmail} || {job.contactPhone}', then: 1, else: 0)}"/>

Affected Installations
======================

Installations that render the job list or the job detail view with the shipped
templates, in particular with a header layout other than :guilabel:`Default`
or with jobs that carry no contact data.

.. index:: Frontend, Fluid, ext:academic_jobs
