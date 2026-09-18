.. _important-job-views-label-flags-and-render-the-link:

====================================================
Important: Job views label flags and render the link
====================================================

Description
===========

The job detail view and the job list render every job property through one loop
in :file:`Resources/Private/Partials/Job/Information.html` respectively
:file:`Resources/Private/Partials/Job/Item.html`. Two of the properties were not
handled by that loop.

The flags :guilabel:`Internationals welcome` and :guilabel:`Alumni recommends`
had no frontend label, so a set flag rendered as an empty label followed by the
stored ``1``. They now render their label alone — a flag carries no value a
visitor can read, and an unset flag is still not rendered at all.

The property :guilabel:`Link` was printed as text. For a link to a page, a file
or a record that is the stored ``t3://`` reference, which is of no use in the
frontend. It is now rendered with :html:`<f:link.typolink>`, so every link type
resolves, and the anchor carries its own label instead of the URL. The row keeps
its label :guilabel:`Link` in front of the anchor.

Three label units are new and available for translation, in English and in
German: ``jobs.internationalsWelcome``, ``jobs.alumniRecommend`` and
``jobs.linkText``.

Impact
======

The link row of a job shows the text :guilabel:`To the job posting` where it
showed the URL before. An installation that wants the URL as the link text
overrides ``jobs.linkText`` or the partial.

Both partials changed, so a site package that copied either of them keeps its
own version and does not receive the fix. Sites in that position adopt the two
new branches of the loop.

Affected Installations
======================

Every installation rendering the :guilabel:`Job list` or the
:guilabel:`Job detail` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
