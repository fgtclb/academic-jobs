<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * Submits the `academicjobs_newjobform` plugin and reads what it stored: a value the
 * visitor did not choose is refused, and the values the visitor chose are stored as
 * chosen.
 *
 * The instance runs in the time zone `Europe/Berlin`, so a day stored at midnight UTC
 * instead of midnight of the server time zone shows as a difference of hours.
 *
 * The class extends the notification mail test case for its form submission. The mail a
 * submission sends is not looked at here.
 */
final class AcademicJobsNewJobFormStoredValuesTest extends AbstractAcademicJobsNotificationMailTestCase
{
    use NewJobFormValidationAssertionTrait;

    private const TIME_ZONE = 'Europe/Berlin';

    /**
     * The bootstrap of the instance sets the time zone of the process, and keeps it for
     * every later test class that configures none.
     */
    private string $previousTimeZone = 'UTC';

    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        $configuration['SYS']['phpTimeZone'] = self::TIME_ZONE;

        return $configuration;
    }

    protected function setUp(): void
    {
        $this->previousTimeZone = date_default_timezone_get();
        parent::setUp();
        $this->setUpTestCase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        date_default_timezone_set($this->previousTimeZone);
    }

    /**
     * Both selects open on "Please choose", which submits `0`. A visitor who leaves either
     * of them there has not chosen a value the settings require, so nothing is stored and
     * the form is shown again with both fields marked.
     */
    #[Test]
    public function aJobWithoutChosenWorkingHoursAndJobTypeIsRefused(): void
    {
        $response = $this->postJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][employmentType]' => '0',
            'tx_academicjobs_newjobform[job][type]' => '0',
        ]);

        $this->assertSame(0, $this->jobCount());
        $this->assertSame(
            ['employmentType', 'type'],
            $this->invalidFields($this->documentXPath((string)$response->getBody())),
        );
    }

    #[Test]
    public function aJobWithoutChosenWorkingHoursIsRefused(): void
    {
        $this->postJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][employmentType]' => '0',
        ]);

        $this->assertSame(0, $this->jobCount());
    }

    #[Test]
    public function aJobWithoutChosenJobTypeIsRefused(): void
    {
        $this->postJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][type]' => '0',
        ]);

        $this->assertSame(0, $this->jobCount());
    }

    /**
     * The control the refusal is about: the same post with both selects chosen is stored.
     */
    #[Test]
    public function aJobWithChosenWorkingHoursAndJobTypeIsStored(): void
    {
        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][employmentType]' => '2',
            'tx_academicjobs_newjobform[job][type]' => '3',
        ]);

        $job = $this->storedJob($uid);
        $this->assertSame(2, (int)$job['employment_type']);
        $this->assertSame(3, (int)$job['type']);
    }

    /**
     * The date inputs submit a day. The job is shown from the start of the first day, and
     * it stays visible through the whole day of the application deadline, both in the time
     * zone of the server, which is the one the backend shows them in.
     */
    #[Test]
    public function startDateAndApplicationDeadlineCoverWholeDays(): void
    {
        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][starttime]' => '2027-03-01',
            'tx_academicjobs_newjobform[job][endtime]' => '2027-03-31',
            'tx_academicjobs_newjobform[job][employmentStartDate]' => '2027-04-01',
        ]);

        $job = $this->storedJob($uid);
        $this->assertSame('2027-03-01 00:00:00', $this->localTime((int)$job['starttime']));
        $this->assertSame('2027-03-31 23:59:59', $this->localTime((int)$job['endtime']));
        $this->assertSame('2027-04-01', $job['employment_start_date']);
    }

    /**
     * An application deadline is not required, and a job without one is not given one.
     */
    #[Test]
    public function aJobWithoutDatesStoresNone(): void
    {
        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $job = $this->storedJob($uid);
        $this->assertSame(0, (int)$job['starttime']);
        $this->assertSame(0, (int)$job['endtime']);
    }

    private function localTime(int $timestamp): string
    {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(self::TIME_ZONE))
            ->format('Y-m-d H:i:s');
    }

    /**
     * @return array<string, mixed>
     */
    private function storedJob(int $uid): array
    {
        $job = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT * FROM tx_academicjobs_domain_model_job WHERE uid = ?', [$uid])
            ->fetchAssociative();
        $this->assertIsArray($job);

        return $job;
    }
}
