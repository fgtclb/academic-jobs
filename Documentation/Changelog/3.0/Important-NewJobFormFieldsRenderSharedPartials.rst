..  _important-new-job-form-fields-render-shared-partials:

============================================================
Important: The new job form renders the shared form partials
============================================================

Description
===========

The field partials of the :guilabel:`New job form` below
:file:`Resources/Private/Partials/Job/Forms/` render the form partials of
:guilabel:`EXT:academic_base` now, which other frontend forms can share. The
markup of the form is the same.

The partials keep their names, so a project override of one of them keeps
applying. A field is still wrapped by
:file:`Job/Forms/FieldWrapper.html`, so an override of the wrapper still
changes every field of the form. The labels and placeholders are still read
from this extension.

One thing renders differently. The select partial read a `disabled` argument
of its own, which the shipped template never passed, and ignored the
`disabled` key of its element. It reads `element.disabled` now, as every other
field partial does.

Impact
======

Nothing changes for an installation whose templates pass `disabled` to no
select. A copy of :file:`Job/Properties/Job.html` that sets `disabled` on a
select element now renders a disabled select, which submits no value. A copy
that passes `disabled` to :file:`Job/Forms/Select` as an argument of its own,
next to `element`, now renders an editable select.

Affected Installations
======================

An installation with a copy of :file:`Partials/Job/Properties/Job.html` that
sets `disabled` for the employment type or type select, in the element or as an
argument of its own.

Migration
=========

Set `disabled` in the element of a select that is meant to be disabled, and
remove it from the element of one that is meant to stay editable.

..  index:: Fluid, Frontend, ext:academic_jobs
