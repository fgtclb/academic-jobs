..  _breaking-jobs-images-render-as-picture:

===================================================
Breaking: Job images render as a responsive picture
===================================================

Description
===========

The job list item renders the job image through the responsive image partial
of `EXT:academic_base`, :file:`Partials/Academic/Image.html`, from 3.0 on. A
raster image is therefore a :html:`<picture>` with WebP sources and a fallback
:html:`<img>` that carries the classes it carried before, and an SVG file -
the shape an employer logo often has - is rendered as one :html:`<img>` of the
original file without processing.

The item asks for the preset `card` with the crop variant `default`. This
extension defines no crop variants, so `default` stays free-ratio and a logo
is not cut. A project that wants its job images cropped like a partner logo
overrides :file:`Job/Item.html` or the preset section of the shared partial.

The views register :file:`EXT:academic_base/Resources/Private/Partials/`: the
plugin view with the partial root path key `-1`, below the keys `0`, `10` and
`20` it uses already, and :typoscript:`page.10` with the key `-1758484804`,
next to the partials this extension registers there.

Impact
======

*   CSS that selects the image as a direct child of the card no longer
    matches: it is wrapped in a :html:`<picture>`.
*   The fallback image is requested with `loading="lazy"`, which it was not
    before, and its `width` and `height` are those of the preset rather than
    those of the original file.
*   A project that replaces the partial root paths of the plugin completely
    fails with an exception on the partial `Academic/Image` that the view
    cannot resolve. So does a :typoscript:`PAGEVIEW` page object that renders
    `Job/Item`: this extension registers the path under
    :typoscript:`page.10.partialRootPaths`, which a `PAGEVIEW` page object does
    not read - it derives its partial root paths from `paths`, and this
    extension writes no `paths` entry, because it ships no page template of its
    own.

Affected Installations
======================

Every installation that renders a job list with job images.

Migration
=========

#.  Adjust CSS that addresses the job image.
#.  A plugin view whose partial root paths were replaced needs the path of
    `EXT:academic_base` below the others:

    ..  code-block:: typoscript

        plugin.tx_academicjobs.view.partialRootPaths {
            -1 = EXT:academic_base/Resources/Private/Partials/
        }

#.  If a page object of the project renders `Job/Item` itself, list the path
    there with a key of your own - under `partialRootPaths` for a
    :typoscript:`FLUIDTEMPLATE` page object, under `paths` for a
    :typoscript:`PAGEVIEW` one:

    ..  code-block:: typoscript

        page.10 {
            partialRootPaths.-1700000001 = EXT:academic_base/Resources/Private/Partials/
            paths.-1700000001 = EXT:academic_base/Resources/Private/
        }

#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, TypoScript, ext:academic_jobs
