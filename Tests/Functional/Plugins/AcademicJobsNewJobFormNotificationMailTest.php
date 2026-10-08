<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * The mail an editor receives for a job submitted through the `academicjobs_newjobform`
 * plugin.
 */
final class AcademicJobsNewJobFormNotificationMailTest extends AbstractAcademicJobsNotificationMailTestCase
{
    /**
     * The frontend has no backend session to create a token for. A link with the token
     * it creates instead is rejected by the backend, which shows a logged-in user the
     * dashboard. A link without a token goes through the login, which redirects to the
     * record and keeps no other argument, so the link carries none.
     */
    #[Test]
    public function theMailLinksTheJobInTheBackendWithoutAToken(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        $this->assertSame(1, $mail['count']);
        $this->assertIsString($mail['text'], 'The mail has no plain-text part.');
        $this->assertStringNotContainsString('token=', $mail['text']);
        $this->assertStringNotContainsString('returnUrl', $mail['text']);
        $this->assertSame(
            'https://www.acme.com/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B' . $uid . '%5D=edit',
            $this->lastLine($mail['text']),
        );
    }

    #[Test]
    public function theMailIsWrittenInEnglishForTheDefaultLanguage(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        $this->assertSame('recipient@example.com', $mail['headers']['to'] ?? null);
        $this->assertSame('sender@example.com', $mail['headers']['from'] ?? null);
        $this->assertSame(
            'A new job advert has been submitted. Please review it in the TYPO3 backend.' . "\n"
            . "\n"
            . 'Open the job advert in the TYPO3 backend:' . "\n"
            . 'https://www.acme.com/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B' . $uid . '%5D=edit',
            trim((string)$mail['text']),
        );
    }

    #[Test]
    public function theMailFollowsAGermanSiteLanguage(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/de/home', 'Wissenschaftliche Hilfskraft');

        $mail = $this->sentMail();
        $this->assertSame(
            'Eine neue Stellenanzeige wurde eingereicht. Bitte prüfen Sie sie im TYPO3-Backend.' . "\n"
            . "\n"
            . 'Stellenanzeige im TYPO3-Backend öffnen:' . "\n"
            . 'https://www.acme.com/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B' . $uid . '%5D=edit',
            trim((string)$mail['text']),
        );
    }

    private function lastLine(string $text): string
    {
        $lines = explode("\n", trim($text));

        return (string)end($lines);
    }
}
