<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Upgrades;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\AcademicJobs\Upgrades\RepairNewJobFormValuesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Install\Updates\RepeatableInterface;

/**
 * The jobs the new job form stored before ACE-837: a start date and an application
 * deadline with the time of day of the submission, and `0` for a select the visitor
 * left on "Please choose".
 *
 * The instance runs in the time zone `Europe/Berlin`, the zone the old form took the
 * time of day in. The form job of the fixture starts before and ends after the change
 * to summer time, so its two values carry the same time of day in Berlin and not in
 * UTC, and its whole days end at different hours of UTC.
 */
final class RepairNewJobFormValuesUpgradeWizardTest extends AbstractAcademicJobsTestCase
{
    private const TIME_ZONE = 'Europe/Berlin';

    /**
     * The bootstrap of the instance sets the time zone of the process, and keeps it for
     * every later test class that configures none.
     */
    private string $previousTimeZone = 'UTC';

    protected function setUp(): void
    {
        $this->previousTimeZone = date_default_timezone_get();
        $this->configurationToUseInTestInstance['SYS']['phpTimeZone'] = self::TIME_ZONE;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        date_default_timezone_set($this->previousTimeZone);
    }

    #[Test]
    public function updateNecessaryReturnsFalseWithoutJobs(): void
    {
        $this->assertFalse($this->subject()->updateNecessary());
    }

    /**
     * A visible, a hidden and an expired job of the form, a translation and a workspace
     * version of one get the whole days the form stores today. The jobs an editor gave
     * other times of day, at midnight, covering whole days or with one date only, a
     * deleted job and a delete placeholder are kept as they are.
     */
    #[Test]
    public function executeUpdateMovesTheDatesOfFormJobsToWholeDays(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_dates.csv');
        $output = new BufferedOutput();
        $subject = $this->subject($output);
        $this->assertTrue($subject->updateNecessary());

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/newJobForm_dates.csv');
        $this->assertSame(
            'Moved the start date and the application deadline of 5 job record(s) to whole days in the time zone Europe/Berlin.' . PHP_EOL,
            $output->fetch(),
        );
    }

    /**
     * A repaired job no longer carries the same time of day on both dates, so the wizard
     * has nothing left to do, and running it again changes nothing.
     */
    #[Test]
    public function aSecondRunChangesNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_dates.csv');
        $subject = $this->subject();
        $subject->executeUpdate();

        $this->assertFalse($subject->updateNecessary());
        $output = new BufferedOutput();
        $this->subject($output)->executeUpdate();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/newJobForm_dates.csv');
        $this->assertStringContainsString(' of 0 job record(s) ', $output->fetch());
    }

    /**
     * None of these jobs carries the fingerprint of the form, so none of them makes the
     * wizard necessary, and running it anyway keeps every value.
     */
    #[Test]
    public function jobsWithoutTheFingerprintOfTheFormAreKept(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_datesToKeep.csv');
        $subject = $this->subject();

        $this->assertFalse($subject->updateNecessary());
        $subject->executeUpdate();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_datesToKeep.csv');
    }

    /**
     * A job without a chosen "Employment Type" or "Job Type" is listed with uid, page and
     * title, a translation and a workspace version saying so, and none of their values is
     * changed. A deleted job and a delete placeholder are not listed. The dates of the
     * hidden job carry the fingerprint of the form and are repaired all the same.
     */
    #[Test]
    public function jobsWithoutAChosenValueAreListedAndKept(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_unchosenValues.csv');
        $output = new BufferedOutput();
        $subject = $this->subject($output);
        $this->assertTrue($subject->updateNecessary());

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/newJobForm_unchosenValues.csv');
        $this->assertSame(
            [
                'Moved the start date and the application deadline of 1 job record(s) to whole days in the time zone Europe/Berlin.',
                '4 job record(s) have no "Employment Type" or "Job Type" chosen. Choose them in the backend:',
                '  - uid 21, page 2: "Lab technician" (no Job Type)',
                '  - uid 22, page 2, language 1: "Lab technician, translated" (no Employment Type)',
                '  - uid 23, page 3: "Hidden job without both values" (no Employment Type, no Job Type)',
                '  - uid 24, page 2, workspace 1: "Workspace version of a job without a type" (no Job Type)',
            ],
            explode(PHP_EOL, rtrim($output->fetch(), PHP_EOL)),
        );
    }

    /**
     * A job with a `0` alone makes the wizard necessary, so an installation without a
     * single date to repair still gets the list. Once an editor has chosen the values,
     * there is nothing left.
     */
    #[Test]
    public function updateNecessaryReturnsTrueForAJobWithoutAChosenValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/newJobForm_unchosenValues.csv');
        $subject = $this->subject();
        // The run repairs the dates of the hidden job, so only the zeros are left.
        $subject->executeUpdate();

        $this->assertTrue($subject->updateNecessary());

        // Every row, which Connection::update() cannot express with Doctrine DBAL 3 of
        // TYPO3 v12: an empty identifier renders an incomplete WHERE there.
        $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_academicjobs_domain_model_job')
            ->update('tx_academicjobs_domain_model_job')
            ->set('type', 1)
            ->set('employment_type', 1)
            ->executeStatement();

        $this->assertFalse($subject->updateNecessary());
    }

    /**
     * The description names the time zone of the process before anything is written, the
     * one the wizard reads and writes in. It is computed when asked for, so a zone set
     * after the bootstrap is the one named.
     */
    #[Test]
    public function theDescriptionNamesTheTimeZoneOfTheProcess(): void
    {
        date_default_timezone_set('America/New_York');

        $this->assertStringContainsString(
            'the time zone America/New_York,',
            $this->subject()->getDescription(),
        );
    }

    /**
     * The list is shown in one run, then the core marks the wizard as done. A repeatable
     * wizard would stay necessary while one listed job is left, an expired one nobody
     * edits included, and the reports module would show an incomplete update for good.
     */
    #[Test]
    public function theWizardIsNotRepeatable(): void
    {
        $this->assertNotInstanceOf(RepeatableInterface::class, $this->subject());
    }

    /**
     * The wizard keeps the output the core sets, so every retrieval gets an instance of
     * its own.
     */
    #[Test]
    public function everyRetrievalGetsAnInstanceOfItsOwn(): void
    {
        $this->assertNotSame($this->subject(), $this->subject());
    }

    private function subject(?BufferedOutput $output = null): RepairNewJobFormValuesUpgradeWizard
    {
        $subject = $this->get(RepairNewJobFormValuesUpgradeWizard::class);
        $this->assertInstanceOf(RepairNewJobFormValuesUpgradeWizard::class, $subject);
        if ($output !== null) {
            $subject->setOutput($output);
        }
        return $subject;
    }
}
