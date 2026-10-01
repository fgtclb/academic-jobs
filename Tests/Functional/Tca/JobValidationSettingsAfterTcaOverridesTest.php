<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The jobs settings reach the backend record editor after every TCA override and after
 * the TCA listener of EXT:content_blocks. The fixture extension
 * `test_job_validation_override` locks the company name, removes the description,
 * drops the flags of the start date, disables the required degree, and turns the work
 * location into a number and the contact name into an e-mail column in its settings,
 * which also name `salary`, a field without a column. Its
 * TCA override makes the link required, a listener registered under the identifier of
 * the content_blocks listener locks the link, and a listener ordered after the settings
 * locks the contact e-mail.
 */
final class JobValidationSettingsAfterTcaOverridesTest extends AbstractAcademicJobsTestCase
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/job-validation-override';
        parent::setUp();
    }

    /**
     * `readonly` locks the column and cancels `required`, in the backend as in the form.
     */
    #[Test]
    public function aLockedFieldIsReadOnlyInTheBackend(): void
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns']['company_name']['config'];

        $this->assertTrue($config['readOnly']);
        $this->assertFalse($config['required']);
    }

    /**
     * `disabled` locks the column as `readonly` does, and cancels `required`.
     */
    #[Test]
    public function aDisabledFieldIsReadOnlyInTheBackend(): void
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns']['required_degree']['config'];

        $this->assertTrue($config['readOnly']);
        $this->assertFalse($config['required']);
    }

    /**
     * An empty list drops every flag, so the start date is optional in the backend as
     * well, although the TCA of the job table requires it.
     */
    #[Test]
    public function anEmptyListMakesTheFieldOptionalInTheBackend(): void
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns']['employment_start_date']['config'];

        $this->assertFalse($config['required']);
        $this->assertFalse($config['readOnly']);
        $this->assertSame('datetime', $config['type']);
    }

    #[Test]
    public function theNumberFlagMakesANumberColumn(): void
    {
        $this->assertSame('number', $GLOBALS['TCA'][self::TABLE]['columns']['work_location']['config']['type']);
    }

    /**
     * A field removed with `~` is not configured at all, so the column stays as the TCA
     * declares it.
     */
    #[Test]
    public function aRemovedFieldLeavesItsColumnAlone(): void
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns']['description']['config'];

        $this->assertArrayNotHasKey('required', $config);
        $this->assertArrayNotHasKey('readOnly', $config);
    }

    #[Test]
    public function aFieldRequiredInCapitalsIsRequiredInTheBackend(): void
    {
        $this->assertTrue($GLOBALS['TCA'][self::TABLE]['columns']['sector']['config']['required']);
    }

    #[Test]
    public function theSettingsWinOverAnOverrideOfTheirKeys(): void
    {
        $this->assertFalse($GLOBALS['TCA'][self::TABLE]['columns']['link']['config']['required']);
    }

    #[Test]
    public function theSettingsAreAppliedAfterTheContentBlocksListener(): void
    {
        $this->assertFalse($GLOBALS['TCA'][self::TABLE]['columns']['link']['config']['readOnly']);
    }

    /**
     * The way out the changelog names for a site package that has to differ in the
     * backend: a listener ordered after the settings.
     */
    #[Test]
    public function aListenerOrderedAfterTheSettingsKeepsItsChange(): void
    {
        $this->assertTrue($GLOBALS['TCA'][self::TABLE]['columns']['contact_email']['config']['readOnly']);
    }

    /**
     * The core adds the soft reference to every `email` column while it prepares the TCA.
     * The contact name becomes one through the `email` flag of the settings, which are
     * applied after that preparation.
     */
    #[Test]
    public function aColumnTheEmailFlagCreatesGetsItsSoftReference(): void
    {
        $config = $GLOBALS['TCA'][self::TABLE]['columns']['contact_name']['config'];

        $this->assertSame('email', $config['type']);
        $this->assertSame('email[subst]', $config['softref']);
    }

    /**
     * The settings describe columns, they do not create them. A fragment for a column the
     * TCA does not have would be a column without a type.
     */
    #[Test]
    public function aFieldWithoutAColumnAddsNoColumn(): void
    {
        $this->assertArrayNotHasKey('salary', $GLOBALS['TCA'][self::TABLE]['columns']);
    }
}
