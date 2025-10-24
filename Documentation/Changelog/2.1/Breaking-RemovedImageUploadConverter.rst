.. include:: /Includes.rst.txt

.. _breaking-1756116000:

========================================
Breaking: Removed `ImageUploadConverter`
========================================

Description
===========

Custom `ImageUploadConverter` implementation is removed in favour of the shared
`EXT:academic_base/Classes/Extbase/Property/TypeConverter/FileUploadConterter`.
The dropped implementation is considerable an internal implementation, but was
never flagged internal and is therefore mentioned as breaking and added to the
semver breaking exemption.


Impact
======

The change relates usages of the `ImageUploadConverter` implementation.


Affected Installations
======================

All installations using the `ImageUploadConverter`.


Migration
=========

Use the shared :php:`\FGTCLB\AcademicBase\Extbase\Property\TypeConverter\FileUploadConverter`
provided by `EXT:academic_base`.

.. index:: FAL, PHP-API
