<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\ViewHelpers\Validation;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A ViewHelper to check if a field is required based on validation rules.
 *
 * <f:if condition="{j:validation.RequiredFromValidation(validations: validations, identifier: identifier)}">
 * <abbr title="required">*</abbr>
 * </f:if>
 */
class RequiredFromValidationViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments for the ViewHelper.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('validations', 'array', 'The array of validation rules.', true);
        $this->registerArgument('identifier', 'string', 'The name of the field to get validations for.', true);
    }

    /**
     * Renders a boolean indicating if the field is required.
     *
     * @return bool
     */
    public function render(): bool
    {
        $validations = $this->arguments['validations'];
        $identifier = $this->arguments['identifier'];

        // Check if validations exist for the given identifier
        if (!isset($validations[$identifier])) {
            return false;
        }

        return in_array('required', $validations[$identifier], true);
    }
}
