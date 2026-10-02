..  index:: Templates; Override
..  _templates-override:

====================
Overriding templates
====================

EXT:academic_jobs is using Fluid as template engine.

This documentation won't bring you all information about Fluid but only the
most important things you need for using it. You can get
more information in the section :ref:`Fluid templates of the Sitepackage tutorial
<t3sitepackage:fluid-templates>`. A complete reference of Fluid ViewHelpers
provided by TYPO3 can be found in the  :ref:`ViewHelper Reference <t3viewhelper:start>`


..  index:: Templates; TypoScript

Change the templates using TypoScript constants
-----------------------------------------------

As any Extbase based extension, you can find the templates in the directory
:file:`Resources/Private/`.

If you want to change a template, copy the desired files to the directory
where you store the templates.

We suggest that you use a sitepackage extension. Learn how to
:ref:`Create a sitepackage extension <t3sitepackage:start>`.

..  code-block:: typoscript

    # TypoScript constants
    plugin.tx_academicjobs {
        view {
            templateRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Templates/
            partialRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Partials/
            layoutRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Layouts/
        }
    }

..  index:: Templates; Labels

Change the labels of the job properties
---------------------------------------

The job list and the job detail view render the properties of a job through one
loop, in :file:`Resources/Private/Partials/Job/Item.html` respectively
:file:`Resources/Private/Partials/Job/Information.html`. The loop takes every
label from :file:`Resources/Private/Language/locallang.xlf`, which follows three
rules:

*   ``jobs.<property>`` is the row label of a property, for example
    ``jobs.workLocation`` or ``jobs.link``.
*   ``jobs.<property>.<value>`` is the label of a value of a select property:
    ``jobs.type.1`` to ``jobs.type.3`` and ``jobs.employmentType.1`` to
    ``jobs.employmentType.2``.
*   Two properties are rendered without their stored value and therefore carry
    a label that is a full statement: ``jobs.internationalsWelcome`` and
    ``jobs.alumniRecommend``. They are rendered only when the flag is set.

The ``link`` property is the one exception to the first rule: it renders the row
label ``jobs.link`` and, as the text of the anchor, ``jobs.linkText``.

Override any of them the way you override a label of an extension, without
copying the partial:

..  code-block:: typoscript

    # TypoScript setup
    plugin.tx_academicjobs._LOCAL_LANG {
        default.jobs.linkText = To the application form
        de.jobs.linkText = Zum Bewerbungsformular
    }

Every label of the extension, and the path of each content element, is listed
in :ref:`configuration-labels`.

..  index:: Templates; Images

The image of a job
------------------

The job list item renders the job image through the shared partial
:file:`Academic/Image.html` of `EXT:academic_base`, whose arguments and presets
are documented in the `Templates` chapter of that extension.

It asks for the preset `card` with the crop variant `default`. This extension
defines no crop variants, so `default` stays free-ratio and an employer logo is
not cut; an SVG logo is passed through unprocessed whatever the preset says.

Two consequences for an override:

*   Overriding :file:`Job/Item.html` alone changes where the image sits, not
    how it is rendered. To change the markup, the breakpoints or the widths,
    place an :file:`Academic/Image.html` of your own in the partial root path
    above.
*   The partial root path of `EXT:academic_base` is registered below the
    constant above, so the copy wins. A view of your own that renders
    :file:`Job/Item.html` has to list that path itself, or the rendering fails
    on a partial it cannot resolve. Pick a key of your own rather than the one
    this extension uses, and list it under `paths` too if your page object is a
    :typoscript:`PAGEVIEW`, which reads no `partialRootPaths`:

    ..  code-block:: typoscript

        # TypoScript setup
        page.10 {
            partialRootPaths.-1700000001 = EXT:academic_base/Resources/Private/Partials/
            paths.-1700000001 = EXT:academic_base/Resources/Private/
        }

..  index:: Templates; Form fields
..  _templates-override-form-fields:

Change the fields of the new job form
-------------------------------------

Every field of the :guilabel:`New job form` is rendered by a partial below
:file:`Job/Forms/`: :file:`Textfield.html`, :file:`Textarea.html`,
:file:`Select.html`, :file:`Checkbox.html`, :file:`DateTime.html` and
:file:`Upload.html`, each wrapped by :file:`FieldWrapper.html`, and the alert
above the form by :file:`Errors.html`. Override these names. They render the
shared form partials of :guilabel:`EXT:academic_base`, described in its
:guilabel:`Templates` chapter, and pass :file:`Job/Forms/FieldWrapper` on as
the wrapper, so an override of :file:`Job/Forms/FieldWrapper.html` changes the
label and the required mark of every field at once.

..  index:: Templates; New job form
..  _templates-override-new-job-form:

Add a field to the new job form
-------------------------------

The :guilabel:`New job form` renders the partial
:file:`Job/Forms/AdditionalFields.html` between the job properties and the
submit button. The partial the extension ships renders nothing, so a field such
as a captcha is added by placing a :file:`Job/Forms/AdditionalFields.html` of
your own in the partial root path above, without copying
:file:`Templates/Job/New.html` or :file:`Partials/Job/Properties/Job.html`. The
partial receives every variable of the form template.

Name such a field outside the ``job`` argument of the form. The field below is
submitted as ``tx_academicjobs_newjobform[captcha]`` and is not mapped to the
job. A field bound to the job with ``property`` must name a property the job
model has: the property mapping rejects any other one and the submission fails.

..  code-block:: html

    <html
        lang="en"
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >

    <div class="form-textfield-wrap">
        <label class="form-label" for="job-form-captcha">Captcha</label>
        <f:form.textfield name="captcha" id="job-form-captcha" class="form-control" />
    </div>

    </html>

The partial only renders the field. The extension neither checks its value nor
dispatches an event before the job is stored, so a site checks a captcha answer
before the request reaches the plugin, in a middleware for example.
