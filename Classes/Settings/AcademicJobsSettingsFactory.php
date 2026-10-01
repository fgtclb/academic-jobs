<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Builds {@see AcademicJobsSettings} from the settings file of every active
 * package, merged and cached by the shared loader of academic_base.
 *
 * The file keeps the shape it had before the shared classes: one flag list
 * per property below `validations.<identifier>`.
 *
 * Public for the functional test that builds the settings through it. The
 * service definition of the settings object reaches it either way.
 *
 * @internal not part of public API.
 */
#[Autoconfigure(public: true)]
final readonly class AcademicJobsSettingsFactory
{
    public const SETTINGS_FILE = 'Configuration/AcademicJobs/Settings.yaml';
    public const CACHE_IDENTIFIER = 'AcademicJobs_Settings_v3';

    public function __construct(
        private SettingsFileLoader $settingsFileLoader,
        private ValidationNormalizer $validationNormalizer,
    ) {}

    public function get(): AcademicJobsSettings
    {
        return $this->settingsFileLoader->load(
            self::SETTINGS_FILE,
            self::CACHE_IDENTIFIER,
            AcademicJobsSettings::class,
            fn(array $settings): AcademicJobsSettings => $this->normalize($settings),
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function normalize(array $settings): AcademicJobsSettings
    {
        $validationSets = [];
        foreach (is_array($settings['validations'] ?? null) ? $settings['validations'] : [] as $identifier => $properties) {
            if (!is_array($properties)) {
                continue;
            }
            $validationSets[(string)$identifier] = array_filter($properties, is_array(...));
        }

        /** @var array<string, array<string, list<string>>> $validationSets */
        return new AcademicJobsSettings(
            validationSets: $this->validationNormalizer->normalizeValidationSets($validationSets),
        );
    }
}
