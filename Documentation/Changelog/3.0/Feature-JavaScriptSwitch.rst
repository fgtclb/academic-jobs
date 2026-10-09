..  _feature-1791566501:

============================================================
Feature: The scripts of the new job form can be switched off
============================================================

Description
===========

The :guilabel:`Jobs New` content element registers two scripts from inside
its template: CKEditor 4 from its content delivery network, and the module
:js:`@fgtclb/academic-jobs/frontend/rich-text.js` that configures it. An
installation that brings its own editor, or wants none, had to override the
whole template to leave them out.

Both are switchable now, together, per site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
    *   -   :yaml:`plugin.tx_academicjobs.assets.js`
        -   :yaml:`true`

It is declared by the site set `fgtclb/academic-jobs`, with every other setting
of this extension:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicjobs.assets.js: false

An installation that configures its frontend through :sql:`sys_template`
records sets the TypoScript constant of the very same name, in
:guilabel:`Constants`:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicjobs.assets.js = 0

Every academic extension that loads a script has this switch, named
:typoscript:`assets.js` below its own plugin namespace and switched on by
default, after the switch of the study plan content element.

Impact
======

Nothing changes for an installation that configures nothing: the form loads
both scripts exactly as before.

Switched off, the page loads neither, and the markup is unchanged. The form
keeps working: the description fields are plain text areas with the class
:html:`ace-ckeditor`, and a visitor submits the text as it is. See
:ref:`configuration-javascript`.

A project that overrides :file:`Templates/Job/New.html` keeps whatever its copy
loads. The switch reaches it once it wraps its own :html:`<f:asset.script>` and
:html:`<f:asset.module>` in :html:`<f:if condition="{settings.assets.js}">`.

.. index:: Frontend, Fluid, TypoScript, ext:academic_jobs
