<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * What the shipped validation settings do to the `academicjobs_newjobform` plugin: which
 * fields carry the required mark, which input type a text field renders, and which
 * submissions are refused.
 *
 * The class extends the notification mail test case for its form submission. The mail a
 * submission sends is not looked at here.
 */
final class AcademicJobsNewJobFormValidationTest extends AbstractAcademicJobsNotificationMailTestCase
{
    use NewJobFormValidationAssertionTrait;

    #[Test]
    public function theShippedSettingsMarkTheRequiredFields(): void
    {
        $this->setUpTestCase();

        $this->assertSame(
            ['companyName', 'description', 'employmentStartDate', 'employmentType', 'title', 'type'],
            $this->markedFields($this->renderedPage('https://www.acme.com/home')),
        );
    }

    #[Test]
    public function theShippedSettingsChooseTheInputTypes(): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage('https://www.acme.com/home');

        $this->assertSame('text', $this->inputType($xpath, 'title'));
        $this->assertSame('text', $this->inputType($xpath, 'companyName'));
        $this->assertSame('url', $this->inputType($xpath, 'link'));
        $this->assertSame('email', $this->inputType($xpath, 'contactEmail'));
        $this->assertSame('tel', $this->inputType($xpath, 'contactPhone'));
    }

    /**
     * A number input refuses a phone number with a country code or spaces in the browser.
     * The server never checked it, so what this pins is that the value arrives unchanged.
     */
    #[Test]
    public function anInternationalPhoneNumberIsStored(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][contactPhone]' => '+49 30 123',
        ]);

        $phone = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT contact_phone FROM tx_academicjobs_domain_model_job WHERE uid = ?', [$uid])
            ->fetchOne();
        $this->assertSame('+49 30 123', $phone);
    }

    #[Test]
    public function anInvalidContactEmailIsRefusedOnItsField(): void
    {
        $this->setUpTestCase();

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][contactEmail]' => 'not-an-address',
        ]);

        $this->assertSame(
            ['contactEmail'],
            $this->invalidFields($this->documentXPath((string)$response->getBody())),
        );
        $this->assertSame(0, $this->jobCount(), 'A job with an invalid contact e-mail was created.');
    }
}
