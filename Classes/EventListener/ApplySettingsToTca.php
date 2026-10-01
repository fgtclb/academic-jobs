<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\EventListener;

use FGTCLB\AcademicBase\Settings\TcaValidationMerger;
use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicJobs\Settings\AcademicJobsSettings;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * Applies the `job` validation set of the jobs settings to the TCA of the job
 * table, so the backend record editor requires and locks what the new-job form
 * and the job validator do.
 *
 * It runs once the TCA is compiled, after every `Configuration/TCA/Overrides`
 * file, so an override that replaces a column keeps the flags of the settings,
 * and before the compiled TCA is cached. It is ordered after the TCA listener of
 * EXT:content_blocks, which runs in `BeforeTcaOverridesEvent` today and is
 * earlier anyway. Should that extension move it to this event, `after` keeps
 * the settings last.
 *
 * A site package that has to change one of these keys after the settings orders
 * a listener of its own after `academic-jobs/apply-settings-to-tca`. That
 * identifier is public API, the class is not.
 *
 * @internal not part of public API.
 */
#[AsEventListener(
    identifier: 'academic-jobs/apply-settings-to-tca',
    after: 'content-blocks-tca',
)]
final readonly class ApplySettingsToTca
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    public function __construct(
        private AcademicJobsSettings $academicJobsSettings,
        private TcaValidationMerger $tcaValidationMerger,
    ) {}

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        if (!is_array($tca[self::TABLE]['columns'] ?? null)) {
            return;
        }
        $tca[self::TABLE] = $this->tcaValidationMerger->merge(
            $tca[self::TABLE],
            $this->withExistingColumns(
                $this->academicJobsSettings->getValidationSet('job'),
                $tca[self::TABLE]['columns'],
            ),
        );
        $tca[self::TABLE] = $this->addEmailSoftReferences($tca[self::TABLE]);
        $event->setTca($tca);
    }

    /**
     * A fragment for a column the TCA does not have would be a column without a type.
     *
     * @param array<string, mixed> $columns
     */
    private function withExistingColumns(ValidationSet $validationSet, array $columns): ValidationSet
    {
        return new ValidationSet(
            identifier: $validationSet->identifier,
            validations: array_filter(
                $validationSet->validations,
                static fn(Validation $validation): bool => isset($columns[$validation->fieldName]),
            ),
        );
    }

    /**
     * The core sets the soft reference of every `email` column while it prepares the
     * TCA, before this event. A column the `email` flag turns into one gets it here.
     *
     * @param array<string, mixed> $tableTca
     * @return array<string, mixed>
     */
    private function addEmailSoftReferences(array $tableTca): array
    {
        foreach ($tableTca['columns'] as $column => $configuration) {
            if (($configuration['config']['type'] ?? null) === 'email') {
                $tableTca['columns'][$column]['config']['softref'] = 'email[subst]';
            }
        }
        return $tableTca;
    }
}
