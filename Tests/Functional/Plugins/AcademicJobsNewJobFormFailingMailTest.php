<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The notification of a submitted job cannot be built, because the configuration of the
 * site names no recipient, no sender, or a recipient that cannot be parsed. The `null`
 * transport of the instance reports every mail that can be built as sent.
 */
final class AcademicJobsNewJobFormFailingMailTest extends AbstractAcademicJobsFailingNotificationMailTestCase
{
    /**
     * Parts of the two messages the other one does not contain.
     */
    private const SUCCESS_MESSAGE = 'We are automatically notified that you have created a job advert.';
    private const WARNING_MESSAGE = 'Notification email could not be sent';

    #[Test]
    public function aMissingRecipientIsLoggedAndTheJobIsAnsweredAsSaved(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailRecipientEmpty.typoscript',
        ]);

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $uid = $this->createdJobUid();
        $this->assertJobIsSavedHidden($uid);
        $this->assertAnsweredAsSavedJob($response);
        $this->assertOneErrorNamesTheJob($uid);
    }

    #[Test]
    public function aMissingSenderIsLoggedAndTheJobIsAnsweredAsSaved(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailSenderEmpty.typoscript',
        ]);

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $uid = $this->createdJobUid();
        $this->assertJobIsSavedHidden($uid);
        $this->assertAnsweredAsSavedJob($response);
        $this->assertOneErrorNamesTheJob($uid);
    }

    /**
     * The address of a display name without its closing `>` cannot be parsed, which Symfony
     * reports with another exception than an address that breaks the RFC.
     */
    #[Test]
    public function anUnparsableRecipientIsLoggedAndTheJobIsAnsweredAsSaved(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailRecipientUnparsable.typoscript',
        ]);

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $uid = $this->createdJobUid();
        $this->assertJobIsSavedHidden($uid);
        $this->assertAnsweredAsSavedJob($response);
        $error = $this->assertOneErrorNamesTheJob($uid);
        $this->assertStringContainsString('Could not parse', $error);
    }

    /**
     * The form queues its messages in the session of the visitor. A page that shows the
     * queue of the plugin outside the page cache shows them on the next request. TYPO3
     * v12 and v13 answer the post with 200, so the test requests the page the form
     * redirects to itself.
     */
    #[Test]
    public function theVisitorIsToldThatTheNotificationCouldNotBeSent(): void
    {
        $this->setUpMessagesPage([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailRecipientEmpty.typoscript',
        ]);

        $messages = $this->messagesOfTheSession(
            $this->postJob('https://www.acme.com/home', 'Research assistant'),
            'https://www.acme.com/messages',
        );

        $this->assertStringContainsString(self::WARNING_MESSAGE, $messages);
        $this->assertStringNotContainsString(self::SUCCESS_MESSAGE, $messages);
    }

    /**
     * The counterpart of the warning, and the proof that the page shows the queue at all.
     */
    #[Test]
    public function theVisitorIsToldThatTheJobWasCreatedWhenTheNotificationIsSent(): void
    {
        $this->setUpMessagesPage();

        $messages = $this->messagesOfTheSession(
            $this->postJob('https://www.acme.com/home', 'Research assistant'),
            'https://www.acme.com/messages',
        );

        $this->assertStringContainsString(self::SUCCESS_MESSAGE, $messages);
        $this->assertStringNotContainsString(self::WARNING_MESSAGE, $messages);
        $this->assertSame([], $this->loggedErrors());
    }

    /**
     * @param string[] $additionalConstantFiles
     */
    private function setUpMessagesPage(array $additionalConstantFiles = []): void
    {
        $this->setUpTestCase(
            [
                ...$additionalConstantFiles,
                'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/SaveFormMessagesPage.typoscript',
            ],
            [
                'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/NewJobFormMessages.typoscript',
            ],
        );
    }

    /**
     * Requests `$url` in the session the answer to the submitted form started, which holds
     * the queued messages, and returns the page.
     */
    private function messagesOfTheSession(ResponseInterface $answerToThePost, string $url): string
    {
        $this->assertAnsweredAsSavedJob($answerToThePost);
        if (preg_match('@(?:^|,\s*)fe_typo_user=([^;]+)@', $answerToThePost->getHeaderLine('set-cookie'), $cookie) !== 1) {
            $this->fail('The answer to the submitted form starts no session.');
        }

        return $this->renderFrontendPage(
            (new InternalRequest($url))->withCookieParams(['fe_typo_user' => $cookie[1]]),
        );
    }
}
