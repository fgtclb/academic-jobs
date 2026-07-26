..  _important-migrated-job-image-upload-to-native-extbase-file-upload-handling:

===============================================================
Important: Job image upload uses native Extbase upload handling
===============================================================

Description
===========

The job avatar image upload of the `academicjobs_newjobform` plugin was handled
by the custom type converter
`FGTCLB\\AcademicBase\\Extbase\\Property\\TypeConverter\\FileUploadConverter`
(`EXT:academic_base`). It has been replaced with the native Extbase file upload
handling introduced in TYPO3 v13.3 (`FileUploadConfiguration`, see TYPO3 feature
:issue:`103511`).

The TypoScript configuration is unchanged. `settings.jobAvatarImage.uploadFolder`,
`settings.jobAvatarImage.validation.fileSize.maximum` and
`settings.jobAvatarImage.validation.mimeType.allowedMimeTypes` keep their names
and meaning and are now mapped onto the core `FileSizeValidator` and
`MimeTypeValidator`. The form template, the `Job` domain model and the plugin
itself are untouched, so no integration or template change is required.

Impact
======

The upload behaves differently in three ways:

*   **Stored file names change.** The custom converter stored the file under the
    name supplied by the client and replaced an existing file of the same name.
    The native handling appends a random suffix and renames on conflict instead,
    so two visitors uploading `logo.png` no longer overwrite each other in the
    shared upload folder.

*   **The mime type is detected from the file content.** The custom converter
    trusted the media type sent by the browser, which can be spoofed. The core
    `MimeTypeValidator` inspects the uploaded file itself and additionally
    cross-checks the file extension. An upload whose real content does not match
    an allowed mime type is now rejected, even if the browser announced an
    allowed one. Uploads that only passed because of a faked header stop working
    — this is intended.

*   **The file is only stored once the whole form validates.** Previously the
    file was imported into FAL while mapping the request, so a job that failed
    validation afterwards left an unreferenced file behind in the upload folder.
    The file is now imported after successful validation, which avoids those
    orphaned files but requires the visitor to select the file again when the
    form is redisplayed with validation errors.

An empty `allowedMimeTypes` setting continues to mean "no mime type
restriction".

Affected Installations
======================

Installations using the job creation form (`academicjobs_newjobform`) with image
uploads. Installations that rely on the uploaded file keeping its original name
— for example when referencing those files by a fixed path outside of FAL — need
to review that assumption.

Migration
=========

No configuration change is required. Files uploaded before this change keep
their existing names and references.

.. index:: Fluid, TypoScript, ext_localconf, NotScanned
