..  _important-job-validation-flags-follow-the-shared-vocabulary:

=================================================================
Important: The job validation flags follow the persons vocabulary
=================================================================

Description
===========

The flags of :file:`Configuration/AcademicJobs/Settings.yaml` are read by the
shared settings classes of :guilabel:`academic_base`, and mean what they mean
in :guilabel:`academic_persons`:

*   Flags are read regardless of case and of spaces around them. A flag
    written :yaml:`Required` made nothing required before and rendered
    :html:`<input type="Required">`. It makes the field required now.
*   A flag outside the shared vocabulary is ignored. Before, the new job form
    rendered the first flag that was not :yaml:`required` as the HTML input
    type, so a typo produced an input such as :html:`<input type="disabledd">`.
*   :yaml:`readonly`, :yaml:`disabled` and :yaml:`frontendreadonly` take effect.
    They cancel :yaml:`required` in the form and in the check of a submitted
    job, and the first two lock the field in the backend. The form keeps
    offering the field.

The shipped settings mark the contact phone with :yaml:`tel` since 2.4, see
:ref:`important-new-job-form-stores-the-chosen-values`. A site package that
copied the whole block of an earlier version still names :yaml:`number` and
has to change it, see :ref:`breaking-job-validation-settings-reach-the-backend`.

A required settings field the job has no property for is left out of the
check of a submitted job. Before, it made every submission fail.

Affected installations
======================

Installations whose site package ships its own settings file with flags not
in lower case, with an unknown flag, or with a field the job has no property
for.

..  index:: Frontend, NotScanned, ext:academic_jobs
