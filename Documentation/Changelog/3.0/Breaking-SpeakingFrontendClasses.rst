..  _breaking-jobs-speaking-frontend-classes:

==============================================
Breaking: Job templates carry speaking classes
==============================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, form controls, spacing and display
utilities - next to block and element classes of the extension. They carry
speaking classes now, named after what an element is and shared by all academic
extensions: `ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`,
`ace-link` and so on. The outermost element of a plugin keeps the class of the
plugin. The classes of Bootstrap that a site builds on stay on purpose where
the templates render them: the grid classes `row` and `col-*`, the button
classes `btn` and `btn-*` and `visually-hidden`. No other theme class is
rendered any more, and the extension ships no styles for the new classes.

The header partials take the class of the heading as the argument `class`,
in place of `positionClass`. An override that renders one of them with
`positionClass` renders the heading without that class.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   List
        -   `academic-jobs-list`
        -   `academic-jobs-list`, the jobs and the pagination in an `ace-content`
    *   -   Item list
        -   `academic-jobs-itemlist`
        -   `ace-itemlist`
    *   -   Item
        -   `card flex-column-reverse academic-jobs-item` with `card-content`, `card-body` and `card-img-top img-fluid` on the image
        -   `<article class="ace-item">` with `ace-item-content`, `ace-image` on the image
    *   -   Attributes
        -   `list-group list-group-flush` with `list-group-item` entries, a plain list in the detail
        -   `ace-list ace-attributes` with `ace-list-item ace-attribute` entries, `ace-icon`, `ace-label` and `ace-value`
    *   -   Heading of an item
        -   the class passed as `positionClass`
        -   `<header class="ace-header">` around a heading with `ace-title` and the class passed as `class`
    *   -   Contact block
        -   `academic-jobs-contact bg-grey`
        -   `ace-contact` with `ace-name`, an `ace-list` of `ace-list-item` entries, `ace-icon` and `ace-link`, the additional contact information in a `ce-bodytext`
    *   -   Description of the detail
        -   the grid column only
        -   `ce-bodytext`
    *   -   Back link of the detail
        -   no class
        -   `ace-link`
    *   -   Pagination
        -   `academic-jobs-list__pagination` around `pagination justify-content-center mt-4`
        -   `f3-widget-paginator`, the entries keep `page-item` and `page-link`

Impact
======

*   A site stylesheet that styles the job list, the job detail or the contact
    block through the classes of the column *Before* no longer matches.
*   The cards of a Bootstrap theme no longer apply: a job item renders without
    border, padding and image ratio until the site styles `ace-item`.
*   The new job form renders the form partials of `EXT:academic_base`, whose
    classes changed as well, see the changelog of that extension.

Affected Installations
======================

Every installation that renders the job list, the job detail or the new job
form.

Migration
=========

#.  Move the selectors of the site stylesheet from the classes in the
    column *Before* to those in the column *After*. A site that styled the
    plugins through the classes of its Bootstrap theme alone gives the new
    classes the look it wants, for example with :css:`@extend` in SCSS or
    with rules of its own.
#.  A template override is compared with the shipped template of 3.0 and
    adopts its markup. An override keeps working as it is, but renders its
    own classes, and nothing in the extension relies on them.
#.  Replace `positionClass` with `class` where an override renders
    :file:`Job/Header.html` or :file:`Job/SectionHeader.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_jobs
