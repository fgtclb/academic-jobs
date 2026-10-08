.. _important-uploaded-job-logo-keeps-existing-files:

===================================================================
Important: An uploaded job logo no longer replaces an existing file
===================================================================

Description
===========

A logo uploaded through the :guilabel:`Jobs New` content element is stored in
the folder of :typoscript:`plugin.tx_academicjobs.jobAvatarImage.uploadFolder`
under the file name it was uploaded with. When a file of that name already
existed in the folder, the upload replaced its content, and every job that
referenced the existing file showed the new logo from then on.

An uploaded logo whose file name is taken is now stored under a new name
instead, :file:`logo_01.png`, :file:`logo_02.png` and so on, and the existing
file stays as it is. Each job keeps the logo it was submitted with.

Impact
======

*   Submitting jobs whose logos share a file name now adds one file per job to
    the upload folder. Nothing else changes for visitors or editors.
*   Jobs submitted before the update may share one logo file whose content is
    the logo of the job submitted last. Installations that accept jobs through
    the form may want to check for jobs that share one logo file, for example
    with this query, and give the affected jobs their own logos in the backend:

    ..  code-block:: sql

        SELECT uid_local, COUNT(*) AS jobs
        FROM sys_file_reference
        WHERE tablenames = 'tx_academicjobs_domain_model_job'
            AND fieldname = 'image'
            AND deleted = 0
        GROUP BY uid_local
        HAVING COUNT(*) > 1;

*   Behaviour is identical on TYPO3 v12 and v13.

.. index:: Frontend, FAL, ext:academic_jobs
