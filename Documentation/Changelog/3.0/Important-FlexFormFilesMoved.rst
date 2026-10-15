..  _important-job-flexform-files-moved:

=================================================================
Important: The FlexForm files moved to `Configuration/FlexForms/`
=================================================================

Description
===========

Version 2.4 shipped the data structures of the job list and the new job form
once per TYPO3 version, as :file:`PluginList.xml` and
:file:`Plugin_NewJobForm.xml` in :file:`Configuration/Flexforms/Core12/` and in
:file:`Configuration/Flexforms/Core13/`. Version 3.0 ships each of them once,
for TYPO3 v13 and v14 alike:

*   :file:`Configuration/FlexForms/PluginList.xml`
*   :file:`Configuration/FlexForms/Plugin_NewJobForm.xml`

The directory is spelled :file:`FlexForms` now, with a capital ``F``. The
extension registers the new files itself.

Impact
======

TCA of a project that names one of the old files as the data structure of a
content type, for example
``FILE:EXT:academic_jobs/Configuration/Flexforms/Core13/PluginList.xml``,
points at a file that no longer exists. TYPO3 throws an exception with the
code 1478105826 as soon as it resolves that data structure, for example when
an editor opens the content element.

Affected Installations
======================

Installations whose own TCA names a FlexForm file of this extension by its
path. A project that registers a copy of its own is not affected by the move,
but compares the copy with the shipped file of 3.0.

Migration
=========

Replace the path with the one of the new file, for example
``FILE:EXT:academic_jobs/Configuration/FlexForms/PluginList.xml``. The same
file serves TYPO3 v13 and v14.

..  index:: FlexForm, TCA, NotScanned, ext:academic_jobs
