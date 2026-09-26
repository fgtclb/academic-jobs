..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicJobs` instead of the extension key :html:`academic_jobs`. TYPO3
v12 and v13 build the TypoScript path of :typoscript:`_LOCAL_LANG` from that
name as it is given, so they read label overrides from
:typoscript:`plugin.tx_academic_jobs`. They now read them from
:typoscript:`plugin.tx_academicjobs` and
:typoscript:`plugin.tx_academicjobs_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

The options of the job type and employment type selects of the form, which the
plugin translates in PHP, follow the same rule. The messages after a job is
saved or not found read these paths already.

An override under :typoscript:`plugin.tx_academicjobs` could reach some labels
on TYPO3 v12 and v13 already, depending on what the page had rendered before
them. It now reaches every label.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_jobs._LOCAL_LANG` no longer has an effect. Move
it to :typoscript:`plugin.tx_academicjobs._LOCAL_LANG`, or to the path of the
one plugin it is meant for:

..  code-block:: typoscript

    plugin.tx_academicjobs._LOCAL_LANG.default.create.job.type.none = Please select
    plugin.tx_academicjobs_newjobform._LOCAL_LANG.default.create.job.type.none = Please select

..  index:: Frontend, Fluid, TypoScript, ext:academic_jobs
