<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\Plugins\Fixtures\Mail\FailingTransport;
use PHPUnit\Framework\Attributes\Test;

/**
 * The mail transport of the installation cannot deliver the notification of a submitted
 * job. The transport is part of the instance configuration, so it is the same for every
 * test of this class.
 */
final class AcademicJobsNewJobFormFailingMailTransportTest extends AbstractAcademicJobsFailingNotificationMailTestCase
{
    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        $configuration['MAIL']['transport'] = FailingTransport::class;

        return $configuration;
    }

    #[Test]
    public function aFailingTransportIsLoggedAndTheJobIsAnsweredAsSaved(): void
    {
        $this->setUpTestCase();

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $uid = $this->createdJobUid();
        $this->assertJobIsSavedHidden($uid);
        $this->assertAnsweredAsSavedJob($response);
        $error = $this->assertOneErrorNamesTheJob($uid);
        $this->assertStringContainsString('The test transport refuses every mail.', $error);
    }

    /**
     * Only the errors a mail is expected to fail with are caught. Anything else is a defect
     * that a log entry would hide, and still ends the request.
     */
    #[Test]
    public function anotherErrorOfTheTransportStillEndsTheRequest(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailRecipientUnexpected.typoscript',
        ]);

        $exception = null;
        try {
            $this->postJob('https://www.acme.com/home', 'Research assistant');
        } catch (\RuntimeException $exception) {
            // A failed assertion of PHPUnit is a `RuntimeException` too.
            if ($exception->getCode() !== FailingTransport::UNEXPECTED_ERROR_CODE) {
                throw $exception;
            }
        }

        $this->assertInstanceOf(\RuntimeException::class, $exception, 'The error of the transport did not end the request.');
        $this->assertSame(FailingTransport::UNEXPECTED_ERROR_CODE, $exception->getCode());
        $this->assertJobIsSavedHidden($this->createdJobUid());
        $this->assertSame([], $this->loggedErrors());
    }
}
