..  _important-1790323400:

=========================================================================
Important: The job plugins leave the content element header to the layout
=========================================================================

Description
===========

A :guilabel:`Jobs List`, :guilabel:`Jobs Detail` or :guilabel:`Jobs New`
content element renders through the content element layout of the site, like
any other content element. The layouts of EXT:fluid_styled_content
and of the bootstrap package render the header and the subheader an editor
enters, and the templates of the three plugins rendered them a second time:

..  code-block:: html

    <f:render partial="Header/All" arguments="{_all}" />

With an explicit header layout the header and the subheader appeared twice.
With the header layout :guilabel:`Default` the plugin left an empty
:html:`<header></header>` element behind, because the header partial takes the
heading level for that layout from a setting the plugin settings did not carry.

The templates now render the header only when the site switches it on:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicjobs.renderContentElementHeader = 1

On TYPO3 v13 the same is the site setting :guilabel:`Render the content
element header in the plugins` of the set `fgtclb/academic-jobs`. It is off by
default.

The plugin settings now also carry :typoscript:`settings.defaultHeaderType`,
mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
EXT:fluid_styled_content, so with the switch on the header layout
:guilabel:`Default` renders a heading. See
:ref:`configuration-general-content-element-header`.

Impact
======

On a site whose content element layout renders the header, the header of the
three content elements appears once, and the markup of the plugins no longer
contains a :html:`<header>` element. TYPO3 v12 and v13 behave alike here.

Affected Installations
======================

Every installation using one of the three job content elements.

Installations whose content element layout renders **no** header, a site
package that renders the header in its element templates instead, lose the
header of the job content elements.

Migration
=========

On a site whose content element layout renders no header, switch
:typoscript:`plugin.tx_academicjobs.renderContentElementHeader` on, through the
constant or, on TYPO3 v13, the site setting.

Projects that copied :file:`Templates/Job/List.html`,
:file:`Templates/Job/Show.html` or :file:`Templates/Job/New.html` keep
rendering the header in their copy. On a site whose layout renders the header,
drop the :html:`<f:render partial="Header/All" … />` line from the copy. On any
other site, keep it or wrap it in the same condition as the shipped templates:

..  code-block:: html

    <f:if condition="{settings.renderContentElementHeader}">
        <f:render partial="Header/All" arguments="{_all}" />
    </f:if>

.. index:: Frontend, Fluid, TypoScript, ext:academic_jobs
