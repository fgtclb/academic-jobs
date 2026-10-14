<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TESTS\TestJobAfterSaveListener\EventListener\RecordNewJobFormResponse;

/**
 * A listener of `AfterSaveJobEvent` may hand in a redirect page of 0 or below, for example
 * as the cast of an empty setting of its own. That is no page, so the form behaves as for
 * no redirect page at all: it sends the visitor back to the form page, and the message of
 * the configured mode is shown there. It used to redirect to the link built for the page
 * id the listener set, while the mode had already queued the message for the form page,
 * where the visitor only saw it on the next visit (ACE-435).
 *
 * The fixture extension `test_job_after_save_listener` sets the redirect page from the
 * TypoScript setting `testRedirectPageId` and records the response of the plugin. TYPO3
 * v12 and v13 pass the redirect of the plugin to `header()` and answer the post with the
 * rendered page, so the recorded response is what is asserted. The job is posted as the
 * notification mail tests post it, and an uncached element on `/new-job` shows the
 * messages of the session in the same answer.
 */
final class AcademicJobsNewJobFormRedirectPageTest extends AbstractAcademicJobsNotificationMailTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-job-after-save-listener';
        parent::setUp();
        RecordNewJobFormResponse::$responses = [];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormRedirectPage/jobPages.csv');
    }

    protected function tearDown(): void
    {
        RecordNewJobFormResponse::$responses = [];
        parent::tearDown();
    }

    /**
     * The deprecated `listPid` fallback only applies when the listeners left no redirect
     * page, so a configured list page does not take over a 0 either.
     *
     * @return \Generator<string, array{0: string}>
     */
    public static function redirectPageSetupDataProvider(): \Generator
    {
        yield 'redirect page zero' => [
            'plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 0',
        ];
        yield 'negative redirect page' => [
            'plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = -1',
        ];
        yield 'redirect page zero with a list page configured' => [
            "plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 0\n"
            . 'plugin.tx_academicjobs.settings.listPid = 2',
        ];
    }

    #[DataProvider('redirectPageSetupDataProvider')]
    #[Test]
    public function aRedirectPageThatIsNoPageSendsTheVisitorBackToTheFormWithTheMessage(string $setup): void
    {
        $this->setUpSite($setup);

        $answer = $this->postJob('https://www.acme.com/new-job', 'Research assistant');

        $this->createdJobUid();
        $redirect = $this->createActionResponse();
        $this->assertSame(303, $redirect->getStatusCode());
        $location = $redirect->getHeaderLine('Location');
        $this->assertStringStartsWith('https://www.acme.com/', $location);
        $this->assertSame('/new-job', parse_url($location, PHP_URL_PATH));
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $this->assertNotEmpty($query['cHash'] ?? null, sprintf('"%s" carries no cHash.', $location));
        $this->assertStringContainsString('Your job advert has been successfully created', (string)$answer->getBody());
    }

    /**
     * The response of the plugin to the submission, the last one recorded: the plugin
     * answered the request rendering the form first.
     */
    private function createActionResponse(): ResponseInterface
    {
        $this->assertCount(2, RecordNewJobFormResponse::$responses, 'The form plugin did not answer the form and the submission once each.');

        return RecordNewJobFormResponse::$responses[1];
    }

    private function setUpSite(string $setup): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/NewJobFormMessages.typoscript',
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }
}
