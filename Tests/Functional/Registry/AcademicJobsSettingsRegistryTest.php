<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Registry;

use FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;

/**
 * Covers the three readers of `AcademicJobsSettingsRegistry` against the registry the
 * container publishes — that is, against the settings `EXT:academic_jobs` really ships in
 * `Configuration/AcademicJobs/Settings.yaml`, not against a hand-built array.
 *
 * That is the point of doing this functionally. The three readers translate one YAML
 * section into three different vocabularies — the Fluid form, the Extbase validator and
 * TCA — and they do not support the same keywords. Feeding them the shipped configuration
 * is what shows which of the nine configured properties actually arrive in each of the
 * three, and where a keyword falls through unnoticed.
 *
 * The pure array mechanics of `attach()` and the `getSettings()` accessor need no
 * container and are covered by the unit suite.
 */
final class AcademicJobsSettingsRegistryTest extends AbstractAcademicJobsTestCase
{
    /**
     * What the new-job form template receives. It is handed out verbatim, so it is the
     * YAML section unchanged — including `number`, which neither of the other two readers
     * understands.
     */
    #[Test]
    public function frontendValidationsForJobAreTheShippedYamlSectionUnchanged(): void
    {
        $this->assertSame(
            [
                'title' => ['required'],
                'employmentType' => ['required'],
                'type' => ['required'],
                'companyName' => ['required'],
                'employmentStartDate' => ['required'],
                'description' => ['required'],
                'link' => ['url'],
                'contactEmail' => ['email'],
                'contactPhone' => ['number'],
            ],
            $this->subject()->getValidationsForFrontend('job'),
        );
    }

    /**
     * `JobController::newAction()` assigns this for every plugin, so an identifier without
     * configuration has to answer with an empty array rather than a missing index notice —
     * the suite fails on notices.
     */
    #[Test]
    public function frontendValidationsForAnUnconfiguredObjectAreEmpty(): void
    {
        $this->assertSame([], $this->subject()->getValidationsForFrontend('unknown'));
    }

    /**
     * The map `JobValidator` walks. Three of the four keywords in use are mapped;
     * `number` is not, so `contactPhone` has no entry here at all and a submitted phone
     * number is never validated by the Extbase side. Asserted as a whole so that the
     * missing property is visible rather than merely unasserted.
     */
    #[Test]
    public function validatorMapForJobResolvesOnlyTheKeywordsItKnows(): void
    {
        $this->assertSame(
            [
                'title' => [NotEmptyValidator::class],
                'employmentType' => [NotEmptyValidator::class],
                'type' => [NotEmptyValidator::class],
                'companyName' => [NotEmptyValidator::class],
                'employmentStartDate' => [NotEmptyValidator::class],
                'description' => [NotEmptyValidator::class],
                'link' => [UrlValidator::class],
                'contactEmail' => [EmailAddressValidator::class],
            ],
            $this->subject()->getValidationsForValidator('job'),
        );
    }

    #[Test]
    public function validatorMapForAnUnconfiguredObjectIsEmpty(): void
    {
        $this->assertSame([], $this->subject()->getValidationsForValidator('unknown'));
    }

    /**
     * The TCA reader is the third vocabulary and it knows a different keyword set again:
     * `required`, `email` and `number` produce configuration, `url` produces none — so
     * `link` is absent here while `contactPhone` is present, the exact inverse of the
     * validator map above.
     *
     * The property names are underscored on the way, because TCA column names are, and the
     * result is nested below a `columns` key ready to be merged into a table definition.
     */
    #[Test]
    public function tcaMapForJobUsesUnderscoredColumnNamesAndItsOwnKeywordSet(): void
    {
        $this->assertSame(
            [
                'columns' => [
                    'title' => ['config' => ['required' => true, 'minitems' => 1]],
                    'employment_type' => ['config' => ['required' => true, 'minitems' => 1]],
                    'type' => ['config' => ['required' => true, 'minitems' => 1]],
                    'company_name' => ['config' => ['required' => true, 'minitems' => 1]],
                    'employment_start_date' => ['config' => ['required' => true, 'minitems' => 1]],
                    'description' => ['config' => ['required' => true, 'minitems' => 1]],
                    'contact_email' => ['config' => ['type' => 'email']],
                    'contact_phone' => ['config' => ['type' => 'number']],
                ],
            ],
            $this->subject()->getValidationsForTca('job'),
        );
    }

    #[Test]
    public function tcaMapForAnUnconfiguredObjectIsEmpty(): void
    {
        $this->assertSame([], $this->subject()->getValidationsForTca('unknown'));
    }

    /**
     * Nothing in this repository consumes `getValidationsForTca()` — it is the only one of
     * the three readers without a caller. It is public API of a published extension
     * nonetheless, and the shape it returns is the part an integrator would merge into
     * `$GLOBALS['TCA']`, so it is pinned rather than left to drift.
     */
    #[Test]
    public function tcaMapIsShapedForMergingIntoATableDefinition(): void
    {
        $tcaMap = $this->subject()->getValidationsForTca('job');

        $this->assertArrayHasKey('columns', $tcaMap);
        $this->assertArrayNotHasKey('link', $tcaMap['columns']);
        $this->assertArrayNotHasKey('ctrl', $tcaMap);
    }

    private function subject(): AcademicJobsSettingsRegistry
    {
        return $this->get(AcademicJobsSettingsRegistry::class);
    }
}
