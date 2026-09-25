..  _important-1790336613:

=========================================================
Important: Dependencies are named by their extension keys
=========================================================

Description
===========

:file:`ext_emconf.php` named two dependencies by a name that is no extension
key: `rte-ckeditor` and `fluid-styled-content`. They are now
`rte_ckeditor` and `fluid_styled_content`, the keys :file:`composer.json`
requires through `typo3/cms-rte-ckeditor` and
`typo3/cms-fluid-styled-content`.

Impact
======

A classic, non-Composer installation resolves the dependencies of an extension
from :file:`ext_emconf.php`. It saw two dependencies on packages that do not
exist: the Extension Manager reported them as missing, and sorting the active
packages failed with "The package "academic_jobs" depends on "rte-ckeditor"
which is not present in the system". It now depends on the Rich Text Editor
and on Fluid Styled Content, which it needs.

Composer installations are not affected, and neither is TYPO3 v14: the
:file:`composer.json` of this extension declares its version and the packages
it provides, so TYPO3 v14 reads the dependencies from there.

Affected Installations
======================

Classic, non-Composer installations of this extension on TYPO3 v12 or v13.

.. index:: Backend, ext:academic_jobs
