<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The notification of a submitted job cannot be sent, because the configuration of the
 * site names no recipient or sender, a recipient that cannot be parsed, or a mail
 * template that does not exist. The mail transport works, and a mail that can be built
 * and rendered is delivered.
 *
 * A post that ends in an exception fails the test, so a test that gets a response knows
 * that no error left the plugin.
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
        $this->assertFileDoesNotExist(static::getInstancePath() . '/typo3temp/notification-mails.mbox');
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
        $this->assertFileDoesNotExist(static::getInstancePath() . '/typo3temp/notification-mails.mbox');
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
        $this->assertFileDoesNotExist(static::getInstancePath() . '/typo3temp/notification-mails.mbox');
    }

    #[Test]
    public function aMissingMailTemplateIsLoggedAndTheJobIsAnsweredAsSaved(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailTemplateNameMissing.typoscript',
        ]);

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $uid = $this->createdJobUid();
        $this->assertJobIsSavedHidden($uid);
        $this->assertAnsweredAsSavedJob($response);
        $error = $this->assertOneErrorNamesTheJob($uid);
        $this->assertStringContainsString('DoesNotExist', $error);
    }

    /**
     * The form queues its messages in the session of the visitor. A page that shows the
     * queue of the plugin outside the page cache shows them on the next request.
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
     * A failing mail changes nothing about the redirect of a saved job. Only TYPO3 v14
     * answers with it: TYPO3 v13 sends the redirect a plugin action returns with
     * `header()` and answers 200 with the rest of the page.
     */
    #[Test]
    #[Group('not-core-13')]
    public function theVisitorIsRedirectedToTheConfiguredPageAndToldThatNobodyWasNotified(): void
    {
        $this->setUpMessagesPage([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailRecipientEmpty.typoscript',
        ]);

        $response = $this->postJob('https://www.acme.com/home', 'Research assistant');

        $this->assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('location');
        $this->assertSame('/messages', parse_url($location, PHP_URL_PATH));
        $messages = $this->messagesOfTheSession($response, $location);
        $this->assertStringContainsString(self::WARNING_MESSAGE, $messages);
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNotificationMail/messagesPage.csv');
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
