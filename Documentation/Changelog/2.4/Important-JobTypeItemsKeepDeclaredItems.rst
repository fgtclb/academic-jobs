.. _important-job-type-items-keep-declared-items:

=================================================
Important: Job type items keep the declared items
=================================================

Description
===========

The item providers :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems`
and :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems` of the
fields :guilabel:`Job Type` and :guilabel:`Employment Type` merged their items
into the items the field declares in the TCA by position. Both lists start at
``0``, so the first shipped item replaced the first declared one, the second
the second, and so on. The two fields declare no items, but an item a project
declared, for example one for "not decided", was lost in the backend form and
in the :guilabel:`Jobs New` form.

Both providers now append their items after the declared ones and leave out a
shipped item whose value is declared already, so a declared item with the
value of a shipped one keeps its label.

Impact
======

An item a project declares in the TCA of either field is offered again, in
front of the shipped items and of the items a listener of
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` adds. The
listeners get it among the items. Installations without declared items see no
change.

Affected Installations
======================

Installations that declare :php:`items` for the column ``type`` or
``employment_type`` of ``tx_academicjobs_domain_model_job`` in the TCA, on
TYPO3 v12 and v13.

.. index:: Backend, TCA, ext:academic_jobs
