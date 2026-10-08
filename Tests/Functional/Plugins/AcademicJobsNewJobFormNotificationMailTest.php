<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * The mail an editor receives for a job submitted through the `academicjobs_newjobform`
 * plugin, rendered from the `JobCreated` mail template the extension ships.
 */
final class AcademicJobsNewJobFormNotificationMailTest extends AbstractAcademicJobsNotificationMailTestCase
{
    #[Test]
    public function aSubmittedJobIsAnnouncedWithAnHtmlAndAPlainTextPart(): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        $this->assertSame(1, $mail['count']);
        $this->assertSame('recipient@example.com', $mail['headers']['to'] ?? null);
        $this->assertSame('sender@example.com', $mail['headers']['from'] ?? null);
        $this->assertSame('New job application', $mail['headers']['subject'] ?? null);
        $editLink = 'edit%5Btx_academicjobs_domain_model_job%5D%5B' . $uid . '%5D=edit';
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Research assistant', $mail[$part]);
            $this->assertStringContainsString(self::ENGLISH_MESSAGE, $mail[$part]);
            $this->assertStringContainsString('https://www.acme.com/typo3/record/edit?', $mail[$part]);
            $this->assertStringContainsString($editLink, $mail[$part]);
        }
        $this->assertStringContainsString('<a href="https://www.acme.com/typo3/record/edit?', (string)$mail['html']);
    }

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

        $link = 'https://www.acme.com/typo3/record/edit?edit%5Btx_academicjobs_domain_model_job%5D%5B' . $uid . '%5D=edit';
        $mail = $this->sentMail();
        preg_match_all('@https://www\.acme\.com/typo3/\S+@', (string)$mail['text'], $textLinks);
        $this->assertSame([$link], $textLinks[0]);
        preg_match_all('@href="(https://www\.acme\.com/typo3/[^"]+)"@', (string)$mail['html'], $htmlLinks);
        $this->assertSame([htmlspecialchars($link)], $htmlLinks[1]);
    }

    /**
     * The title is what a visitor typed. The HTML part escapes it, the plain-text part has
     * no markup to protect and shows it as typed.
     */
    #[Test]
    public function theTitleIsEscapedInTheHtmlPartOnly(): void
    {
        $this->setUpTestCase();

        $this->submitJob('https://www.acme.com/home', 'R&D <b>assistant</b>');

        $mail = $this->sentMail();
        $this->assertStringContainsString('R&amp;D &lt;b&gt;assistant&lt;/b&gt;', (string)$mail['html']);
        $this->assertStringNotContainsString('<b>assistant</b>', (string)$mail['html']);
        $this->assertStringContainsString('R&D <b>assistant</b>', (string)$mail['text']);
    }

    #[Test]
    public function aConfiguredMessageReplacesTheDefaultMessage(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailMessage.typoscript',
        ]);

        $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Please review the new job offer.', $mail[$part]);
            $this->assertStringNotContainsString(self::ENGLISH_MESSAGE, $mail[$part]);
        }
    }

    #[Test]
    public function theDefaultMessageFollowsAGermanSiteLanguage(): void
    {
        $this->setUpTestCase();

        $this->submitJob('https://www.acme.com/de/home', 'Wissenschaftliche Hilfskraft');

        $mail = $this->sentMail();
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Wissenschaftliche Hilfskraft', $mail[$part]);
            $this->assertStringContainsString(self::GERMAN_MESSAGE, $mail[$part]);
            $this->assertStringNotContainsString(self::ENGLISH_MESSAGE, $mail[$part]);
        }
    }

    /**
     * TYPO3 v14 adds the mail template paths a site lists in the site set `typo3/email` on
     * top of the global ones, for every mail created through `TemplatedEmailFactory`. A
     * list without keys is appended above the highest global key.
     */
    #[Test]
    #[Group('not-core-13')]
    public function aMailTemplatePathOfTheSiteReplacesTheShippedTemplate(): void
    {
        $this->setUpTestCase();
        $this->mergeSiteConfiguration('acme', [
            'dependencies' => ['typo3/email'],
            'settings' => [
                'email' => [
                    'templateRootPaths' => [
                        'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/SiteMailTemplates/',
                    ],
                ],
            ],
        ]);

        $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Site template for Research assistant', $mail[$part]);
            $this->assertStringNotContainsString(self::ENGLISH_MESSAGE, $mail[$part]);
        }
    }
}
