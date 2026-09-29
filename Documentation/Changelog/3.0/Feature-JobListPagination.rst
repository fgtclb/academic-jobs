.. _feature-1790660966:

======================================
Feature: The job list can be paginated
======================================

Description
===========

The :guilabel:`Jobs List` content element rendered every job of its job type on
one page. It now has a :guilabel:`Pagination` tab with two fields:

*   :guilabel:`Enable pagination`, off by default. Off, the list renders every
    job, as before.
*   :guilabel:`Results per page`, 10 by default.

With the pagination enabled, the list shows one page of jobs and a navigation
to the other pages below it. When `georgringer/numbered-pagination` is
installed, the navigation links at most
:typoscript:`plugin.tx_academicjobs.pagination.numberOfLinks` page numbers
around the current one, 5 by default, and marks the pages it leaves out with an
ellipsis. Without it, the core pagination links every page. The number is a
site setting of the set `fgtclb/academic-jobs` and a constant of the static
template, see :ref:`configuration-list-pagination`.

*   A page link carries the page as :php:`tx_academicjobs_list[currentPage]`
    and nothing else. The job type and the switch for hidden jobs come from the
    content element, so every page keeps them.
*   A page beyond the last one shows the last page. A page below one, or a
    value that is no number, shows the first.
*   A list that fits on one page renders no navigation.

Impact
======

*   Nothing changes until an editor enables the pagination on a content
    element. The existing fields of the content element stay on its first tab,
    so their stored values keep working.
*   The template :file:`Templates/Job/List.html` renders one page of jobs and
    the new partial :file:`Job/Pagination.html`. The variables
    :html:`{paginator}` and :html:`{pagination}` are assigned only while the
    pagination is enabled, and :html:`{jobs}` is still every job of the list. A
    project that overrides :file:`Templates/Job/List.html` renders every job and
    no navigation, even with the pagination enabled, until it takes over the
    change.
*   A listener of :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`
    that replaces :html:`{jobs}` changes what the list renders only while the
    pagination is off. With it enabled, the list renders the page of the
    paginator, which is built from the jobs before the event.
*   A project that paginates the list itself, with a FlexForm field of its own
    or a copy of the list template, should remove it with this update. Editors
    would otherwise see two pagination switches, and the values stored in the
    project's own field are not taken over: enable the pagination again on each
    content element that used it.

.. index:: Backend, Frontend, FlexForm, NotScanned
