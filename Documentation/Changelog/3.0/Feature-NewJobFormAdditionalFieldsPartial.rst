.. _feature-new-job-form-additional-fields-partial:

==================================================
Feature: Additional fields partial in new job form
==================================================

Description
===========

The form template :file:`Templates/Job/New.html` of the
:guilabel:`New job form` renders a new partial,
:file:`Partials/Job/Forms/AdditionalFields.html`, between the job properties
and the submit button. The partial the extension ships renders nothing.

A site adds a field to the form, a captcha for example, by overriding that one
partial. Until now it had to copy the form template or the partial of the job
properties to do so. A field in the partial is named outside the ``job``
argument of the form, because a field bound to a property the job model does
not have makes the submission fail. See :ref:`templates-override-new-job-form`.

Impact
======

The form renders as before. A site package that copied
:file:`Templates/Job/New.html` does not render the new partial until it adopts
it in its copy, or drops the copy when it only existed to add a field.

.. index:: Frontend, Fluid, ext:academic_jobs
