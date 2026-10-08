.. _important-new-job-form-marks-rejected-fields:

=================================================
Important: The new job form marks rejected fields
=================================================

Description
===========

A submission of the new job form that the validation rejected came back with
the general alert above the form only. No field said what was wrong with it,
the class ``is-invalid`` sat on the element around the field, which a Bootstrap
theme does not style, assistive technology learned nothing about the field,
and the title of the mark of a required field was the English word "required"
on every page.

Now a rejected field carries ``is-invalid`` itself, in place of the class
``f3-form-error`` the form field ViewHelpers set by default, names its messages
with ``aria-describedby`` and ``aria-invalid``, and is followed by them:

..  code-block:: html

    <input type="email" class="form-control is-invalid" id="job.contactEmail"
        aria-invalid="true" aria-describedby="job.contactEmail-error" ... />
    <div id="job.contactEmail-error" class="invalid-feedback">
        <div>Please enter a valid email address.</div>
    </div>

The element around the field keeps its ``is-invalid``. A rich text field gets
its message, but no red border: CKEditor hides the textarea that carries the
class and shows an editor of its own instead. The labels for the errors of the
shipped validation settings, and the title of the required mark, are new and
are available in English and German:

*   ``create.required``
*   ``create.error.1221560910`` and ``create.error.1221560718``, a required
    field left empty
*   ``create.error.1221559976``, an invalid email address
*   ``create.error.1238108078``, an invalid URL
*   ``create.error.1307719788``, a date that cannot be read

A label for a field and an error code, such as
``create.job.employmentStartDate.error.1307719788``, takes precedence and is
given the arguments of the error. See :ref:`configuration-labels-field-messages`.

Impact
======

The general alert above the form is unchanged. A date field that a listener of
the plugin view event fills from an assigned job now gets its date as
``Y-m-d``, the only format a date input accepts. It was given ``d.m.Y`` and
stayed empty. The dates a visitor entered were and are kept when the form is
shown again. TYPO3 v13 and v14 behave alike here.

An installation that styles ``f3-form-error`` styles ``is-invalid`` instead.
One that overrides a partial below :file:`Partials/Job/Forms/` in its own site
package keeps its own output until it adopts the changes.

Affected Installations
======================

Every installation rendering the :guilabel:`Jobs New` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
