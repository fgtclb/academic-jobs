<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Settings;

use FGTCLB\AcademicBase\Settings\ValidationSet;

/**
 * The normalised `Configuration/AcademicJobs/Settings.yaml` of every active
 * package: one validation set per identifier below `validations`. The new-job
 * form, the job validator and the TCA of the job table read the `job` set.
 *
 * A service of its own, built by {@see AcademicJobsSettingsFactory}.
 *
 * @internal not part of public API.
 */
final class AcademicJobsSettings
{
    /**
     * @param array<string, ValidationSet> $validationSets
     */
    public function __construct(
        public readonly array $validationSets,
    ) {}

    /**
     * @param array{validationSets: array<string, ValidationSet>} $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            validationSets: $array['validationSets'],
        );
    }

    /**
     * The set of `$identifier`, or an empty set carrying it when the settings
     * configure none.
     */
    public function getValidationSet(string $identifier): ValidationSet
    {
        return $this->validationSets[$identifier] ?? new ValidationSet(
            identifier: $identifier,
            validations: [],
        );
    }
}
