.. _important-ace-267-academic-jobs:

=======================================================
Important: An empty mime type list accepts every upload
=======================================================

Description
===========

The logo upload of the new job form checks the media type of the uploaded file
against a comma separated list:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes = image/jpeg,image/png,image/webp,image/svg+xml

On TYPO3 v13 the same value is the site setting
:yaml:`plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes`
of the set :yaml:`fgtclb/academic-jobs`.

An empty value was read as a list holding one empty entry, which no file
matches. Every upload was rejected with the message *You entered an incorrect
media type, "image/png" is not allowed for this file*, whatever the file was.
An empty list now means that the media type is not restricted.

Impact
======

With the shipped value nothing changes.

A site that set the value to an empty string accepts a logo of any media type
now, where it rejected every logo before. Files with a PHP file extension are
still rejected, as before. The size limit
:typoscript:`plugin.tx_academicjobs.jobAvatarImage.validation.fileSize.maximum`
is not affected.

Affected Installations
======================

Installations that set
:typoscript:`plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes`
to an empty value. To keep restricting the logo, list the accepted media types
instead.

.. index:: Frontend, TypoScript, ext:academic_jobs
