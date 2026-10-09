..  index:: ! Styling
..  _styling:

=======
Styling
=======

The plugins of this extension render their markup with the class system of the
academic extensions and ship no styling for it. The system, the classes of
Bootstrap it keeps and how a site package styles the markup are described once,
in the chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost elements
======================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin
        -   Content element type
        -   Outermost element
    *   -   Job list
        -   `academicjobs_list`
        -   `academic-jobs-list`
    *   -   Job detail
        -   `academicjobs_detail`
        -   `academic-jobs-detail`
    *   -   New job form
        -   `academicjobs_newjobform`
        -   `academic-jobs-new`

..  _styling-list:

The job list
============

..  code-block:: html

    <div class="academic-jobs-list">
        <!-- the header of the content element, when the plugin renders it -->
        <!-- the flash messages, see below -->
        <div class="ace-content">
            <div class="ace-itemlist">
                <article class="ace-item">
                    <div class="ace-item-content">
                        <header class="ace-header">
                            <h2 class="ace-title"><a href="…">Job title</a></h2>
                        </header>
                        <ul class="ace-list ace-attributes">
                            <li class="ace-list-item ace-attribute">
                                <span class="ace-icon">…</span>
                                <b class="ace-label">Company:</b>
                                <span class="ace-value">…</span>
                            </li>
                        </ul>
                    </div>
                    <!-- the image of the job, see "An image" in academic_base -->
                </article>
            </div>
            <nav aria-label="…">
                <ul class="f3-widget-paginator">
                    <li class="page-item first"><a class="page-link" href="…">First</a></li>
                    <li class="page-item active" aria-current="page"><span class="page-link">2</span></li>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                </ul>
            </nav>
        </div>
    </div>

*   The item list carries no grid, the items follow each other. A grid is a
    rule of the site stylesheet on `.ace-itemlist`.
*   The level of the heading of a job follows the header layout of the content
    element, one level lower when the content element has a subheader.
*   The attributes are the same list the job detail renders. Each has an
    `ace-icon`, an `ace-label` and an `ace-value`. The flags
    :guilabel:`Internationals welcome` and :guilabel:`Alumni recommend` render
    their label only, without a value. A link of a job is an `ace-link` inside
    the value.
*   The image follows the text, after `ace-item-content`, and is only rendered
    when the job has one.
*   The pagination is only rendered with more than one page. Its entries carry
    `first`, `previous`, `next` and `last`, the current page `active` and the
    gaps `disabled`.

..  _styling-detail:

The job detail
==============

..  code-block:: html

    <div class="academic-jobs-detail">
        <!-- the header of the content element, when the plugin renders it -->
        <!-- the flash messages, see below -->
        <a class="ace-link" href="…">Back to the list</a>
        <header class="ace-header">
            <h1 class="ace-title">Job title</h1>
        </header>
        <ul class="ace-list ace-attributes">…</ul>
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="ce-bodytext">…</div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="ace-contact">
                    <h2 class="ace-title">Contact</h2>
                    <b class="ace-name">…</b>
                    <ul class="ace-list">
                        <li class="ace-list-item">
                            <span class="ace-icon">…</span>
                            <a class="ace-link" href="tel:…">…</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

*   The back link is only rendered when the plugin names a list page.
*   The description is a rich text field and therefore a `ce-bodytext`. The
    columns are only rendered for a job with a description or contact data,
    and the description takes the whole width when there is no contact.

..  _styling-flash-messages:

The flash messages
==================

The job list and the job detail render the flash messages of the extension,
the confirmation after a submitted job or the message that it could not be
created. TYPO3 renders them, with the alert classes of Bootstrap and no
speaking class:

..  code-block:: html

    <ul class="typo3-messages">
        <li class="alert alert-success">
            <h4 class="alert-title">…</h4>
            <p class="alert-message">…</p>
        </li>
    </ul>

The severity decides the second class: `alert-success`, `alert-danger` and so
on. The list is only rendered when there is a message.

..  _styling-new:

The new job form
================

..  code-block:: html

    <div class="academic-jobs-new">
        <!-- shown when a field failed validation -->
        <div class="ace-errors">…</div>
        <form class="ace-form" …>
            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="ace-field ace-text-wrap">…</div>
                </div>
                …
            </div>
            <button class="btn btn-primary" type="submit">Submit</button>
        </form>
    </div>

Every field is rendered by the form partials of
:guilabel:`EXT:academic_base`, see `A form field
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html#styling-markup-form>`__
in its chapter :guilabel:`Styling`. The text areas of the description and of
the additional contact information carry `ace-ckeditor`. Unlike every other
script of the academic extensions, the rich text script of the form finds its
elements by this class: it starts the editor on every text area with
`ace-ckeditor`. An override of the form keeps the class on the text areas that
get the editor.

The editor and its script are switched off per site with
`plugin.tx_academicjobs.assets.js`, see :ref:`configuration-javascript`. The
text areas then stay plain text areas, with the class.

The submit button is a Bootstrap button, `btn btn-primary`, without a speaking
class.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-jobs-list .ace-itemlist {
        display: grid;
        gap: 1.5rem;
    }

    .academic-jobs-list .ace-item,
    .academic-jobs-detail .ace-contact {
        padding: 1rem;
        border: 1px solid #dee2e6;
    }

    .academic-jobs-list .ace-attributes,
    .academic-jobs-detail .ace-attributes {
        list-style: none;
        padding-left: 0;
    }

To change the markup itself, override the partials as described in
:ref:`templates`.
