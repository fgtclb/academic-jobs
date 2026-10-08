..  _important-new-job-form-rich-text-starts-on-typo3-v12:

======================================================
Important: The rich text fields now start on TYPO3 v12
======================================================

Description
===========

The rich text editor of the new job form attaches itself to every field with
the class :html:`rich-text`. The fields :guilabel:`Job description` and
:guilabel:`Additional information` received that class from
:file:`Resources/Private/Partials/Job/Properties/Job.html`, which passed
:html:`richtext: true` to :file:`Partials/Job/Forms/Textarea.html` in a Fluid
array. Fluid 2, which TYPO3 v12 ships, reads an unquoted :html:`true` there as
a variable of that name, which does not exist. On TYPO3 v12 both fields were
therefore rendered as plain text areas, and the editor never started. TYPO3
v13 was not affected.

The partial now passes :html:`richtext: 1`, which every Fluid version reads as
the number one.

Impact
======

An installation that overrides
:file:`Resources/Private/Partials/Job/Properties/Job.html` keeps the plain text
areas on TYPO3 v12 until it replaces :html:`richtext: true` with
:html:`richtext: 1` in its copy.

Affected Installations
======================

Installations on TYPO3 v12 that use the new job form.

..  index:: Frontend, Fluid, ext:academic_jobs
