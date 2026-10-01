..  _breaking-job-validation-settings-classes-removed:

==============================================================
Breaking: The validation settings classes of jobs were removed
==============================================================

Description
===========

The job validation settings are read by the shared settings classes of
:guilabel:`academic_base`, as the persons settings are. The classes and
ViewHelpers :guilabel:`academic_jobs` had for them are removed:

*   :php:`\FGTCLB\AcademicJobs\Loader\AcademicJobsSettingsLoader`
*   :php:`\FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry`
*   :php:`\FGTCLB\AcademicJobs\ViewHelpers\Validation\FieldTypeFromValidationViewHelper`,
    in a template :html:`j:validation.fieldTypeFromValidation`
*   :php:`\FGTCLB\AcademicJobs\ViewHelpers\Validation\RequiredFromValidationViewHelper`,
    in a template :html:`j:validation.requiredFromValidation`
*   :php:`\FGTCLB\AcademicJobs\Exception\UnknownValidatorException` and
    :php:`\FGTCLB\AcademicJobs\Exception\UnsuitableValidatorException`. The
    job validator throws the classes of the same name in the namespace
    :php:`\FGTCLB\AcademicBase\Settings\Exception`, with the same codes.
*   :php:`\FGTCLB\AcademicJobs\ServiceProvider`, which TYPO3 never loaded.

The view variable :html:`{validations}` of the new job form changed with
them. It held a list of flags per property, and it holds one validation
object per property now, which carries :html:`required`, :html:`readOnly`,
:html:`disabled` and :html:`inputType`.

Impact
======

A template that calls one of the two ViewHelpers fails to render with an
error about an unknown ViewHelper. A template that reads a flag from
:html:`{validations}` directly reads nothing. PHP code using one of the
classes fails with a class that is not found.

Affected installations
======================

Installations that override a partial of the new job form calling
:html:`j:validation.` (the shipped :file:`Job/Forms/FieldWrapper.html` and
:file:`Job/Forms/Textfield.html` did), and installations with PHP code using
one of the classes. Searching the site package for :html:`j:validation`,
:php:`AcademicJobsSettings` and :php:`AcademicJobs\Exception` finds them.

Migration
=========

Resolve the validation of a field with the ViewHelper
:html:`validationEnsure` of :guilabel:`academic_base`, and read its
properties:

..  code-block:: html
    :caption: Before

    <html xmlns:j="http://typo3.org/ns/FGTCLB/AcademicJobs/ViewHelpers" data-namespace-typo3-fluid="true">
    <f:if condition="{j:validation.RequiredFromValidation(validations: element.validations, identifier: element.identifier)}">
        <abbr title="required">*</abbr>
    </f:if>
    <f:form.textfield
        type="{j:validation.fieldTypeFromValidation(validations: element.validations, identifier: element.identifier)}"
        property="{element.identifier}"
    />

..  code-block:: html
    :caption: After

    <html xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers" data-namespace-typo3-fluid="true">
    <f:variable name="validation" value="{p:validationEnsure(validations: element.validations, identifier: element.identifier)}"/>
    <f:if condition="{validation.required}">
        <abbr title="required">*</abbr>
    </f:if>
    <f:form.textfield
        type="{validation.inputType}"
        property="{element.identifier}"
    />

For a field the settings do not configure, :html:`validationEnsure` returns a
validation that is not required and renders a text input.

..  index:: Frontend, Fluid, PHP-API, NotScanned, ext:academic_jobs
