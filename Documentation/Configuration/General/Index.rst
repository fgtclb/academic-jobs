..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

..  _configuration-general-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Jobs List`,
:guilabel:`Jobs Detail` or :guilabel:`Jobs New` content element are rendered by
the content element layout of the site, as for any other content element. The
layouts of :guilabel:`EXT:fluid_styled_content` and of the bootstrap package do
that, and the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicjobs.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting
:guilabel:`Render the content element header in the plugins` of
`fgtclb/academic-jobs`. The templates then render the header partial of
:guilabel:`EXT:fluid_styled_content` above their output. Do not switch it on
where the layout renders the header: the header then appears twice.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicjobs.settings.defaultHeaderType`, which is
mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.
