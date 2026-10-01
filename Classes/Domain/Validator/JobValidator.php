<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Domain\Validator;

use FGTCLB\AcademicBase\Settings\Exception\UnknownValidatorException;
use FGTCLB\AcademicBase\Settings\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Settings\AcademicJobsSettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

final class JobValidator extends AbstractValidator
{
    private AcademicJobsSettings $academicJobsSettings;

    public function injectAcademicJobsSettings(AcademicJobsSettings $academicJobsSettings): void
    {
        $this->academicJobsSettings = $academicJobsSettings;
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
     * Runs the validators of every field of the validation set `$validationsIdentifier`
     * against the property of the same name. A field `$subject` has no property for is
     * left out, so a settings entry without one cannot refuse every submission.
     *
     * @throws UnknownValidatorException
     */
    public function processValidations(object $subject, string $validationsIdentifier): void
    {
        $validationSet = $this->academicJobsSettings->getValidationSet($validationsIdentifier);
        foreach ($validationSet->validations as $property => $validation) {
            if (!ObjectAccess::isPropertyGettable($subject, $property)) {
                continue;
            }
            $value = ObjectAccess::getProperty($subject, $property);
            foreach ($validation->validatorClassNames as $validatorClassName) {
                $validator = GeneralUtility::makeInstance($validatorClassName);
                if (!$validator instanceof ValidatorInterface) {
                    throw new UnknownValidatorException(
                        'Unknown validator',
                        1753702335
                    );
                }
                foreach ($validator->validate($value)->getErrors() as $error) {
                    $this->result->forProperty($property)->addError($error);
                }
            }
        }
    }
}
