..  _important-php-labels-read-the-plugin-override-on-typo3-v14:

=========================================================================
Important: Labels translated in PHP read the plugin override on TYPO3 v14
=========================================================================

Description
===========

The messages after a job is saved or not found, and the options of the job type
and employment type selects of the form, are translated in PHP. On TYPO3 v14
the core reads the :typoscript:`_LOCAL_LANG` override of a plugin,
:typoscript:`plugin.tx_academicjobs_<plugin>`, only from the plugin request a
translation is handed. These translations now hand it on, so the override of
the plugin reaches them on TYPO3 v14 as well. Before, only the override of the
extension did.

The path of the overrides is the one of
:ref:`important-label-overrides-use-the-documented-path`.

Impact
======

An override of these labels under
:typoscript:`plugin.tx_academicjobs_<plugin>._LOCAL_LANG` now has an effect on
TYPO3 v14. Nothing has to be moved.

..  index:: Frontend, TypoScript, ext:academic_jobs
