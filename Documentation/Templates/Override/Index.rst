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
