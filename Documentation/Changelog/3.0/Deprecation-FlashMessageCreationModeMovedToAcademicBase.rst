..  _deprecation-flash-message-creation-mode-moved:

================================================================
Deprecation: FlashMessageCreationMode moved to EXT:academic_base
================================================================

Description
===========

The enum :php:`\FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode` moved to
:guilabel:`EXT:academic_base` as
:php:`\FGTCLB\AcademicBase\Form\FlashMessageCreationMode`, so another frontend
form can share it. Its cases and their values are unchanged.

The old name is a class alias of the new one. Both name the same enum, so a
case taken by the old name is the case of the new one, and
:php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent` accepts it.

Impact
======

Nothing is logged. A listener of :php:`AfterSaveJobEvent` that uses the old
name keeps working. The alias is removed in version 4.0.

Affected Installations
======================

An installation with code that names
:php:`\FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode`, usually a
listener of :php:`AfterSaveJobEvent` that changes the flash message mode.

Migration
=========

Import the new name:

..  code-block:: php

    use FGTCLB\AcademicBase\Form\FlashMessageCreationMode;

..  index:: PHP-API, ext:academic_jobs
