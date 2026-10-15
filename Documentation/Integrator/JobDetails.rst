..  _integrator-job-details:

===========
Job Details
===========

..  image:: /Images/Icons/employment.svg
    :width: 48px
    :alt: Icon of the Job Details content element

:CType:
    ``academicjobs_detail``

:Plugin:
    `AcademicJobs`, plugin `Detail`, action
    :php:`JobController::showAction()`

:Set:
    `fgtclb/academic-jobs-detail`, or the aggregate set `fgtclb/academic-jobs`

:Templates:
    :file:`Templates/Job/Show.html`, with the partials :file:`Job/Header.html`,
    :file:`Job/Information.html` and :file:`Job/Contact.html`, see
    :ref:`templates`

..  figure:: /Images/Screenshots/job-details-overview.png
    :alt: A job offer with its title, a list of properties, the description and a contact box on the right
    :class: with-border with-shadow

    The job detail with its properties, description and contact block.

The job detail shows the one job a link of the :ref:`job list
<integrator-job-list>` names, as `tx_academicjobs_detail[job]` in the URL: its
title, its properties, its description and its contact block. Above the job
it links back to the list page, the site setting
:typoscript:`plugin.tx_academicjobs.listPid`, when that setting names a page.

It is the content element for the page that the site setting
:typoscript:`plugin.tx_academicjobs.detailPid` names. There is one such page
per site, so every job list of a site links to the same detail page. Place the
content element on that page and nowhere else.

What the page does with the job:

*   The title of the job becomes the title of the page, and the job fills the
    meta tags of the page, see :ref:`configuration-general-detail-page-title`.
*   The page is cached until the next start or end time of the job, see
    :ref:`configuration-general-detail-cache-lifetime`.
*   A request without a job, or with a job that is hidden, has ended or does
    not exist, is answered with the "page not found" handling of the site,
    status 404.
*   The route enhancer of the extension turns the job argument into a
    speaking path segment, see :ref:`configuration-route-enhancers`.

..  _integrator-job-details-settings:

Settings
========

The content element has no options of its own. Its tab
:guilabel:`Configuration` does not exist, everything it needs comes from the
site settings and from the link that leads to it.
