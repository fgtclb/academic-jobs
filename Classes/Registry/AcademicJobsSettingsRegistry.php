<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Registry;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

class AcademicJobsSettingsRegistry
{
    /**
     * @var array<string, mixed>
     */
    protected array $registry = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function attach(array $settings): void
    {
        if ($settings === []) {
            return;
        }

        $this->registry = array_merge($this->registry, $settings);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->registry;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationsForFrontend(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            $validations = $this->registry['validations'][$object];
        }
        return $validations;
    }

    /**
     * @return array<string, array<class-string<ValidatorInterface>>>
     */
    public function getValidationsForValidator(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            foreach ($this->registry['validations'][$object] as $property => $validators) {
                foreach ($validators as $validator) {
                    $validatorClass = null;
                    switch ($validator) {
                        case 'email':
                            $validatorClass = EmailAddressValidator::class;
                            break;
                        case 'required':
                            $validatorClass = NotEmptyValidator::class;
                            break;
                        case 'url':
                            $validatorClass = UrlValidator::class;
                            break;
                    }
                    if ($validatorClass !== null) {
                        $validations[$property][] = $validatorClass;
                    }
                }
            }
        }
        return $validations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationsForTca(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            foreach ($this->registry['validations'][$object] as $property => $validators) {
                $property = GeneralUtility::camelCaseToLowerCaseUnderscored($property);
                $tcaConfig = [];
                foreach ($validators as $validator) {
                    switch ($validator) {
                        case 'email':
                            $tcaConfig['type'] = 'email';
                            break;
                        case 'number':
                            $tcaConfig['type'] = 'number';
                            break;
                        case 'required':
                            $tcaConfig['required'] = true;
                            $tcaConfig['minitems'] = 1;
                            break;
                    }
                }
                if ($tcaConfig !== []) {
                    $validations['columns'][$property]['config'] = $tcaConfig;
                }
            }
        }
        return $validations;
    }
}
