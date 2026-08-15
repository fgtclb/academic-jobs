..  _breaking-ckeditor-configuration-is-a-module:

==================================================
Breaking: The editor configuration is an ES module
==================================================

Description
===========

The script configuring the CKEditor instances of the frontend form is now
compiled from a TypeScript source and is an **ES module**:

..  code-block:: text

    EXT:academic_jobs/Resources/Public/JavaScript/CKEditor.js
    ->  EXT:academic_jobs/Resources/Public/JavaScript/frontend/ckeditor.js

It is registered in :file:`Configuration/JavaScriptModules.php` and addressed by
the bare specifier :code:`@fgtclb/academic-jobs/frontend/ckeditor.js`.

CKEditor itself is still loaded from a content delivery network by the same
template, unchanged.

Impact
======

An installation that uses the shipped template needs to do nothing.

An installation that references the old path loads a file that no longer
exists, and the rich text fields fall back to plain textareas.

Affected installations
======================

Installations that override :file:`Templates/Job/New.html` or reference
:file:`CKEditor.js` from their own site package.

Migration
=========

In an overridden template, replace the reference:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-jobs/frontend/ckeditor.js" />

:html:`<f:asset.script>` cannot be used any more, because a classic script tag
does not execute an ES module. The :code:`frontend-editor` identifier and the
:html:`async`/:html:`defer` attributes are gone with it: a module is deferred by
definition.
