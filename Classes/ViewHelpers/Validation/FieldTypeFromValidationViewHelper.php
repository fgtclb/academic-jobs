<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\ViewHelpers\Validation;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A ViewHelper to set HTML attributes based on validation rules.
 *
 * Example usage:
 * <input type="{j:validation.FieldTypeFromValidation(validations: validations, identifier: identifier)}" name="title" />
 */
class FieldTypeFromValidationViewHelper extends AbstractViewHelper
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
     * Renders the attributes string based on the given validations.
     *
     * @return string
     */
    public function render(): string
    {
        $validations = $this->arguments['validations'];
        $identifier = $this->arguments['identifier'];
        $attributes = [];

        // Check if validations exist for the given identifier
        if (!isset($validations[$identifier])) {
            return 'text';
        }

        foreach ($validations[$identifier] as $validation) {
            if ($validation !== 'required') {
                return $validation;
            }
        }

        return 'text';
    }
}
