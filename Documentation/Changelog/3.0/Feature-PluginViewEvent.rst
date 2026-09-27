..  _feature-plugin-view-event:

===================================================
Feature: The plugins dispatch the plugin view event
===================================================

Description
===========

The job list, the job detail and the new job form of :guilabel:`academic_jobs`
dispatch :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base`, once each time they render, after assigning their
own variables. A listener adds a variable to their templates without replacing
the controller.

The context of the event names the extension :php:`AcademicJobs` and the plugin
names :php:`List`, :php:`Detail` and :php:`NewJobForm`, which a listener checks
for the plugin it means.

It replaces the view event of the new job form, which is removed, see
:ref:`breaking-removed-job-new-action-view-event`. The validations of the form
are assigned after the event and cannot be replaced by a listener.

The event, with an example listener, is described in the `changelog of
academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

Impact
======

A project adds a variable to the templates of these plugins with an event
listener instead of a subclass or a copy of the controller. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_jobs
