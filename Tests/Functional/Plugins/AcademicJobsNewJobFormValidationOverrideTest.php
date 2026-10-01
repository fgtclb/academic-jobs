<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * The new-job form with the settings file of a site package loaded after
 * `academic_jobs`. The fixture extension `test_job_validation_override` names only the
 * fields it changes: an unknown flag on the title, a locked company name, the sector
 * required in capitals with spaces around it, the description removed with `~`, the
 * start date without flags, a number input for the work location, a required and
 * disabled degree, and `salary`, which the job has no property for.
 */
final class AcademicJobsNewJobFormValidationOverrideTest extends AbstractAcademicJobsNotificationMailTestCase
{
    use NewJobFormValidationAssertionTrait;

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/job-validation-override';
        parent::setUp();
    }

    /**
     * Every field the site package does not name keeps the flags `academic_jobs` ships,
     * and the ones it names change as it says.
     */
    #[Test]
    public function theSitePackageChangesOnlyTheFieldsItNames(): void
    {
        $this->setUpTestCase();

        $this->assertSame(
            ['employmentType', 'sector', 'title', 'type'],
            $this->markedFields($this->renderedPage('https://www.acme.com/home')),
        );
    }

    #[Test]
    public function anUnknownFlagIsNotRenderedAsTheInputType(): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage('https://www.acme.com/home');

        $this->assertSame('text', $this->inputType($xpath, 'title'));
        $this->assertSame('text', $this->inputType($xpath, 'sector'));
        $this->assertSame('tel', $this->inputType($xpath, 'contactPhone'));
        $this->assertSame('number', $this->inputType($xpath, 'workLocation'));
        $this->assertSame('text', $this->inputType($xpath, 'requiredDegree'));
    }

    /**
     * A locked field stays in the form and is not demanded of the visitor, and so does a
     * disabled one. A field whose flags were dropped or removed is optional.
     */
    #[Test]
    public function aJobWithoutTheLockedAndTheRemovedFieldIsCreated(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][sector]' => 'Research',
            'tx_academicjobs_newjobform[job][companyName]' => '',
            'tx_academicjobs_newjobform[job][description]' => '',
            'tx_academicjobs_newjobform[job][employmentStartDate]' => '',
            'tx_academicjobs_newjobform[job][requiredDegree]' => '',
        ]);

        $this->assertGreaterThan(0, $uid);
        $this->assertSame('text', $this->inputType($this->renderedPage('https://www.acme.com/home'), 'companyName'));
    }

    #[Test]
    public function aFieldRequiredInCapitalsIsRefusedWhenEmpty(): void
    {
        $this->setUpTestCase();

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $this->assertSame(['sector'], $this->invalidFields($this->documentXPath((string)$response->getBody())));
        $this->assertSame(0, $this->jobCount(), 'A job without a sector was created.');
    }
}
