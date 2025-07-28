<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Validator;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Exception\UnknownValidatorException;
use FGTCLB\AcademicJobs\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry as SettingsRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class JobValidator extends AbstractValidator
{
    private SettingsRegistry $settingsRegistry;

    public function injectSettingsRegistry(SettingsRegistry $settingsRegistry): void
    {
        $this->settingsRegistry = $settingsRegistry;
    }

    /**
     * @param object $job
     * @throws UnsuitableValidatorException
     */
    protected function isValid(mixed $job): void
    {
        if (!$job instanceof Job) {
            throw new UnsuitableValidatorException(
                'Not a valid job object.',
                1753702412
            );
        }

        $this->processValidations($job, 'job');
    }

    /**
     * @param object $subject
     * @param string $validationsIdentifier
     * @throws UnknownValidatorException
     */
    public function processValidations(object $subject, string $validationsIdentifier): void
    {
        $validations = $this->settingsRegistry->getValidationsForValidator($validationsIdentifier);
        foreach ($validations as $property => $validators) {
            foreach ($validators as $validator) {
                $value = ObjectAccess::getPropertyPath($subject, $property);
                $validator = GeneralUtility::makeInstance($validator);
                if (method_exists($validator, 'validate')) {
                    $validationResult = $validator->validate($value);
                    if ($validationResult->hasErrors()) {
                        foreach ($validationResult->getErrors() as $error) {
                            $this->result->forProperty($property)->addError($error);
                        }
                    }
                } else {
                    throw new UnknownValidatorException(
                        'Unknown validator',
                        1753702335
                    );
                }
            }
        }
    }
}
