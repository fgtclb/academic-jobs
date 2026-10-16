.. _important-job-list-offers-the-job-types-of-listeners:

=====================================================
Important: Job list offers the job types of listeners
=====================================================

Description
===========

The option :guilabel:`Job Type` of the :guilabel:`Jobs List` plugin offered a
fixed list: :guilabel:`None`, :guilabel:`Job`, :guilabel:`Sidejob` and
:guilabel:`Thesis`. The field :guilabel:`Job Type` of the job record builds its
items with an item provider that dispatches
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`, see
:ref:`feature-1757757620`, so a project could add a type of its own to a job,
but could not choose it for a list.

The option now gets its items from the new item provider
:php:`\FGTCLB\AcademicJobs\Backend\FormEngine\JobListTypeItems`. It keeps
:guilabel:`None` first and appends the types of the job record, built by the
same item provider as for the record. The event is dispatched with the
:php:`table` and the :php:`field` of the record,
``tx_academicjobs_domain_model_job`` and ``type``, so a listener that adds or
removes a type of the record does the same for the list without a change.

Every other parameter is the one TYPO3 passes for the FlexForm field:
:php:`flexParentDatabaseRow` holds the content element, :php:`row` the values
of its FlexForm, and :php:`config` and :php:`TSconfig` belong to the FlexForm
field. Only the list passes :php:`flexParentDatabaseRow`, which is how a
listener tells the two apart. :guilabel:`None` is not among the items a
listener gets, so a listener that replaces the types cannot take it away.

The list already filtered by any type value stored in the option. It filters
by an integer, as the column of the job record stores one, and treats ``0``
and below as :guilabel:`None`.

Impact
======

A type a listener adds to the job record can be chosen for a list, and a type
a listener removes is no longer offered there.

A listener that changes the items of the job type for the record only checks
:php:`flexParentDatabaseRow`:

..  code-block:: php

    if (isset($parameters['flexParentDatabaseRow'])) {
        // The option of the job list, leave it as it is.
        return;
    }

Affected Installations
======================

Installations with a listener of
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` for the field
``type`` of ``tx_academicjobs_domain_model_job``, on TYPO3 v12 and v13.

.. index:: Backend, FlexForm, PHP-API, ext:academic_jobs
