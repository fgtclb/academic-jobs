..  _breaking-ckeditor-configuration-is-a-module:

==================================================
Breaking: The editor configuration is an ES module
==================================================

Description
===========

Version 2.4 moved the script configuring the CKEditor instances of the frontend
form to :file:`frontend/ckeditor.js` and kept it a classic script, see
:ref:`breaking-ckeditor-configuration-moved`. It is an **ES module** now, under
another name:

..  code-block:: text

    EXT:academic_jobs/Resources/Public/JavaScript/frontend/ckeditor.js
    ->  EXT:academic_jobs/Resources/Public/JavaScript/frontend/rich-text.js

It is registered in :file:`Configuration/JavaScriptModules.php` and addressed by
the bare specifier :code:`@fgtclb/academic-jobs/frontend/rich-text.js`.

The name avoids :file:`ckeditor.js` on purpose. CKEditor 4 derives its own
installation directory from the first script tag of the document whose source
ends in that name, and an import mapped module is rendered before the plain
script assets of a page. A module of that name would send the editor looking for
its skin and its language files below this extension.

CKEditor itself is still loaded from a content delivery network by the same
template, unchanged.

Impact
======

An installation that uses the shipped template needs to do nothing.

An installation that loads :file:`frontend/ckeditor.js` loads a file that no
longer exists, and the rich text fields fall back to plain textareas.

Affected Installations
======================

Installations that override :file:`Templates/Job/New.html` or reference the
script from their own site package.

Migration
=========

In an overridden template, replace the reference:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-jobs/frontend/rich-text.js" />

:html:`<f:asset.script>` cannot be used any more, because a classic script tag
does not execute an ES module. The :code:`frontend-editor` identifier and the
:html:`async`/:html:`defer` attributes are gone with it: a module is deferred by
definition.

..  index:: Frontend, JavaScript, Fluid, ext:academic_jobs
