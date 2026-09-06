..  _important-jobs-record-icons-follow-the-colour-scheme:

========================================================
Important: The job record icon follows the colour scheme
========================================================

Description
===========

The job table pointed at its icon file directly, through the TCA
:php:`ctrl.iconfile` option. That bypasses the icon registry altogether: the
file gets the core provider, which renders the default markup as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said, and the dark drawing stayed dark
on the dark cards of the record list.

The icon is now registered in :file:`Configuration/Icons.php` under the
identifier :php:`tx_academicjobs_domain_model_job` with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
and the table points at that identifier through :php:`ctrl.typeicon_classes`.
The file is drawn in `currentColor`.

Impact
======

The job record icon takes the text colour of the backend, so it stays legible
in a dark colour scheme. It is also addressable by identifier now, which it was
not before - :html:`<core:icon identifier="tx_academicjobs_domain_model_job" />`
resolves.

The seventeen :php:`academic_jobs-*` field icons of the job detail view keep the
core provider and are unchanged.

Affected Installations
======================

Every installation of this extension.

.. index:: Backend, TCA, ext:academic_jobs
