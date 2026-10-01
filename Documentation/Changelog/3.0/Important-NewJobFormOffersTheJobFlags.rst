.. _important-new-job-form-offers-the-job-flags:

============================================
Important: New job form offers the job flags
============================================

Description
===========

The :guilabel:`New job form` offered neither of the two job flags
:guilabel:`Internationals welcome` and :guilabel:`Alumni recommends`, although
the job list and the job detail view render both. The form now offers them as
two optional checkboxes, labelled as in those views:
:guilabel:`International applicants welcome` and
:guilabel:`Recommended by alumni`.

A site that added a flag through the shipped checkbox partial
:file:`Partials/Job/Forms/Checkbox.html` could not submit the form with that
checkbox unchecked: the checkbox submits an empty value, which Extbase converts
to :php:`null`, and the job model only accepts an integer. The create action
now stores an unchecked flag as ``0``.

The label unit ``create.job.alumniRecommend.label`` read
:guilabel:`Created by Alumni` (:guilabel:`Erstellt von Alumni`), which is not
what the flag means. It now reads :guilabel:`Recommended by alumni`
(:guilabel:`Von Alumni empfohlen`). The label unit
``create.job.internationalsWelcome.label`` is new, in English and in German.

Impact
======

The form shows two more checkboxes. A job submitted through it stores
:sql:`internationals_welcome` and :sql:`alumni_recommend` as the visitor
checked them, where both were always ``0`` before.

A site package that copied :file:`Partials/Job/Properties/Job.html` keeps its
own version and does not show the checkboxes. It adopts the two new fields in
its copy.

A site that overrides ``create.job.alumniRecommend.label`` keeps its own text.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
