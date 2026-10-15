<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Where the new job form sends the visitor after a saved job, and where the confirmation
 * is shown, on TYPO3 v12 and v13 alike.
 *
 * Without a redirect page, or with the form page as the redirect page, the form answers
 * the submission with a `303` to the form page, without any argument of a plugin, and the
 * form shows the confirmation above itself. It used to redirect to an action of the list
 * plugin on the form page, which rendered an empty form, and the confirmation waited in
 * the session for a page that rendered the queue of the plugin (ACE-912, ACE-919). A
 * redirect page of 0 or below, which only a listener of `AfterSaveJobEvent` can hand in,
 * is no page and counts as none (ACE-435).
 *
 * With a redirect page, the form answers with a `303` to that page, and the confirmation
 * goes to the queue of the plugin, which `/thanks` renders in an uncached element and
 * `/elsewhere` does not. The fixture extension `test_job_after_save_listener` sets the
 * redirect page from the TypoScript setting `testRedirectPageId`, and changes the title
 * of the saved job to `testUpdatedTitle`.
 *
 * The answer to the submission is the redirect itself on both core versions. A returned
 * redirect reached the browser on TYPO3 v12 and v13 only through `header()`, and the page
 * rendered for nothing. The job is posted as the notification mail tests post it. The
 * class runs with the database backend of the page cache, so the pages are cached as on
 * a site, and the form, which is not cached, has to keep the confirmation of one visitor
 * from the next one.
 */
final class AcademicJobsNewJobFormRedirectPageTest extends AbstractAcademicJobsNotificationMailTestCase
{
    private const FORM_PAGE = 'https://www.acme.com/new-job';

    /**
     * The wrapper of the plugin, which holds the confirmation and the form.
     */
    private const FORM_ELEMENT = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-new ")]';

    /**
     * The title of the confirmation after a saved job whose notification was sent.
     */
    private const CONFIRMATION = 'Your job advert has been successfully created';

    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        // The testing framework replaces the page cache by a NullBackend. The database
        // backend is restored, so the pages are cached as on a site, the uncached form
        // among them.
        $configuration['SYS']['caching']['cacheConfigurations']['pages']['backend'] = Typo3DatabaseBackend::class;

        return $configuration;
    }

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-job-after-save-listener';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormRedirectPage/jobPages.csv');
    }

    /**
     * Every way the form is left without a page to send the visitor to. The deprecated
     * `listPid` fallback only applies when the listeners left no redirect page, so a
     * configured list page does not take over a 0 either.
     *
     * @return \Generator<string, array{0: string, 1: array<string, string>}>
     */
    public static function noRedirectPageDataProvider(): \Generator
    {
        yield 'no redirect page' => ['', []];
        yield 'a listener sets page 0' => [
            'plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 0',
            [],
        ];
        yield 'a listener sets a negative page' => [
            'plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = -1',
            [],
        ];
        yield 'a listener sets page 0 with a list page configured' => [
            "plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 0\n"
            . 'plugin.tx_academicjobs.settings.listPid = 4',
            [],
        ];
        yield 'the content element names the form page' => ['', ['settings.redirectPageId' => '5']];
        yield 'the site setting names the form page' => [
            'plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId = 5',
            [],
        ];
    }

    /**
     * @param array<string, string> $flexFormSettings
     */
    #[DataProvider('noRedirectPageDataProvider')]
    #[Test]
    public function withoutARedirectPageTheFormShowsTheConfirmation(string $setup, array $flexFormSettings): void
    {
        $this->setUpSite($setup, $flexFormSettings);

        $answer = $this->sendJob();

        $this->createdJobUid();
        $this->assertSeeOtherTo(self::FORM_PAGE, $answer);
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringContainsString(self::CONFIRMATION, $formElement);
    }

    /**
     * The confirmation is taken from the session when the form shows it. A reload shows
     * the empty form, and so does a visitor without the session: the form is not cached
     * with the page, so the confirmation of one visitor never reaches another one.
     */
    #[Test]
    public function theFormShowsTheConfirmationOnce(): void
    {
        $this->setUpSite('');

        $answer = $this->sendJob();
        $sessionCookie = $this->frontendSessionCookie($answer);
        $this->assertNotNull($sessionCookie, 'The confirmation was not stored in a session.');
        $this->assertStringContainsString(self::CONFIRMATION, $this->formElement($this->followSeeOther($answer)));

        $reload = $this->formElement($this->renderFrontendPage(
            (new InternalRequest(self::FORM_PAGE))->withCookieParams(['fe_typo_user' => $sessionCookie]),
        ));
        $this->assertStringContainsString('<form', $reload);
        $this->assertStringNotContainsString(self::CONFIRMATION, $reload);
        $this->assertStringNotContainsString(self::CONFIRMATION, $this->renderFrontendPage(self::FORM_PAGE));
    }

    /**
     * The mode "never" applies on the form page as well.
     */
    #[Test]
    public function theModeNeverShowsNoConfirmationOnTheForm(): void
    {
        $this->setUpSite('plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 2');

        $answer = $this->sendJob();

        $this->createdJobUid();
        $this->assertSeeOtherTo(self::FORM_PAGE, $answer);
        $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringNotContainsString(self::CONFIRMATION, $formElement);
    }

    /**
     * Every source of a redirect page sends the visitor there, with the mode "always" so
     * that the confirmation is queued for the plugin.
     *
     * @return \Generator<string, array{0: string, 1: array<string, string>}>
     */
    public static function redirectPageDataProvider(): \Generator
    {
        yield 'the content element' => [
            '',
            ['settings.redirectPageId' => '4', 'settings.flashMessageCreationMode' => '1'],
        ];
        yield 'the site setting' => [
            "plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId = 4\n"
            . 'plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 1',
            [],
        ];
        yield 'a listener' => [
            "plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 4\n"
            . 'plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 1',
            [],
        ];
    }

    /**
     * @param array<string, string> $flexFormSettings
     */
    #[DataProvider('redirectPageDataProvider')]
    #[Test]
    public function aRedirectPageReceivesTheVisitorWithTheConfirmationOfThePlugin(string $setup, array $flexFormSettings): void
    {
        $this->setUpSite($setup, $flexFormSettings);

        $answer = $this->sendJob();

        $this->createdJobUid();
        $this->assertSeeOtherTo('https://www.acme.com/thanks', $answer);
        $this->assertStringContainsString(self::CONFIRMATION, $this->followSeeOther($answer));
    }

    /**
     * The conditional mode, the default, queues no confirmation for a redirect to another
     * page, so no session is started.
     */
    #[Test]
    public function theConditionalModeQueuesNoConfirmationForARedirectPage(): void
    {
        $this->setUpSite('', ['settings.redirectPageId' => '4']);

        $answer = $this->sendJob();

        $this->assertSeeOtherTo('https://www.acme.com/thanks', $answer);
        $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        $this->assertStringNotContainsString(self::CONFIRMATION, $this->followSeeOther($answer));
    }

    /**
     * The deprecated list page is the redirect page of last resort, and using it is
     * reported as a deprecation.
     */
    #[Test]
    public function theDeprecatedListPageReceivesTheVisitor(): void
    {
        $this->setUpSite(
            "plugin.tx_academicjobs.settings.listPid = 4\n"
            . 'plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 1',
        );

        $answer = null;
        $deprecations = $this->collectDeprecations(function () use (&$answer): void {
            $answer = $this->sendJob();
        });

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('plugin.tx_academicjobs.settings.listPid', $deprecations[0]);
        $this->assertInstanceOf(ResponseInterface::class, $answer);
        $this->assertSeeOtherTo('https://www.acme.com/thanks', $answer);
        $this->assertStringContainsString(self::CONFIRMATION, $this->followSeeOther($answer));
    }

    /**
     * With the mode "always", a redirect page that does not render the queue of the plugin
     * leaves the confirmation in the session. The form renders a queue of its own, so the
     * visitor does not meet the confirmation of an earlier job when coming back to it.
     */
    #[Test]
    public function aConfirmationLeftInTheSessionIsNotShownOnTheForm(): void
    {
        $this->setUpSite(
            "plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId = 6\n"
            . 'plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 1',
        );

        $answer = $this->sendJob();
        $sessionCookie = $this->frontendSessionCookie($answer);
        $this->assertNotNull($sessionCookie, 'The confirmation was not stored in a session.');
        $this->assertSeeOtherTo('https://www.acme.com/elsewhere', $answer);
        $this->assertStringNotContainsString(self::CONFIRMATION, $this->followSeeOther($answer));

        $formElement = $this->formElement($this->renderFrontendPage(
            (new InternalRequest(self::FORM_PAGE))->withCookieParams(['fe_typo_user' => $sessionCookie]),
        ));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringNotContainsString(self::CONFIRMATION, $formElement);
    }

    /**
     * The redirect ends the request before Extbase clears the page caches the saved job
     * registered, so the form clears them itself: among them the pages the storage folder
     * names in `TCEMAIN.clearCacheCmd`.
     */
    #[Test]
    public function theSavedJobClearsThePageCachesItsStorageFolderNames(): void
    {
        $this->setUpSite('');
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['TSconfig' => 'TCEMAIN.clearCacheCmd = 2'],
            ['uid' => 100],
        );
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');
        $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(1, $this->pageCacheEntriesTagged('pageId_2'), 'The page was not cached.');

        $this->assertSeeOtherTo(self::FORM_PAGE, $this->sendJob());

        $this->assertSame(0, $this->pageCacheEntriesTagged('pageId_2'));
    }

    /**
     * The redirect ends the request before Extbase persists what is left, so the form
     * persists itself before it redirects: a listener of `AfterSaveJobEvent` that changes
     * the job and hands it to the repository without persisting it would lose the change
     * otherwise.
     */
    #[Test]
    public function aChangeOfAListenerToTheJobIsStored(): void
    {
        $this->setUpSite('plugin.tx_academicjobs_newjobform.settings.testUpdatedTitle = Changed by a listener');

        $this->assertSeeOtherTo(self::FORM_PAGE, $this->sendJob());

        // A job sent through the form is saved hidden, so the query has no restrictions.
        $title = $this->getConnectionPool()->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT title FROM tx_academicjobs_domain_model_job WHERE uid = ?', [$this->createdJobUid()])
            ->fetchOne();
        $this->assertSame('Changed by a listener', $title);
    }

    /**
     * A request to the action that saves a job, without a job, saves nothing and returns to
     * the form as a saved job does, without a message and without rendering a page.
     */
    #[Test]
    public function aRequestWithoutAJobReturnsToTheForm(): void
    {
        $this->setUpSite('');
        $forms = $this->documentXPath($this->renderFrontendPage(self::FORM_PAGE))->query(self::FORM_ELEMENT . '//form');
        $this->assertNotFalse($forms);
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form, 'The page renders no job form.');
        $action = $form->getAttribute('action');

        $answer = $this->requestFrontendPage(
            (new InternalRequest(str_starts_with($action, '/') ? 'https://www.acme.com' . $action : $action))
                ->withMethod('POST')
                ->withParsedBody([]),
        );

        $this->assertSeeOtherTo(self::FORM_PAGE, $answer);
        $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        $this->assertSame(
            0,
            (int)$this->getConnectionPool()->getConnectionForTable('tx_academicjobs_domain_model_job')
                ->executeQuery('SELECT COUNT(*) FROM tx_academicjobs_domain_model_job')
                ->fetchOne(),
        );
    }

    private function pageCacheEntriesTagged(string $tag): int
    {
        return (int)$this->getConnectionPool()->getConnectionForTable('cache_pages_tags')
            ->count('*', 'cache_pages_tags', ['tag' => $tag]);
    }

    private function sendJob(): ResponseInterface
    {
        return $this->postJob(self::FORM_PAGE, 'Research assistant');
    }

    /**
     * The answer is a `303` to exactly `$url`, without any argument, and renders no page.
     */
    private function assertSeeOtherTo(string $url, ResponseInterface $answer): void
    {
        $this->assertSame(
            ['status' => 303, 'location' => $url],
            ['status' => $answer->getStatusCode(), 'location' => $answer->getHeaderLine('Location')],
        );
        $this->assertSame('', (string)$answer->getBody(), 'The answer to the submission rendered a page.');
    }

    /**
     * The markup of the plugin on the page.
     */
    private function formElement(string $html): string
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $elements = (new \DOMXPath($document))->query(self::FORM_ELEMENT);
        $this->assertNotFalse($elements);
        $this->assertCount(1, $elements, 'The page does not render the form plugin once.');

        return (string)$document->saveHTML($elements->item(0));
    }

    /**
     * Only the deprecations raised in the classes of this extension are taken. The core
     * raises its own while it renders a page, and those are left to the handler of
     * PHPUnit, as they are in every other test.
     *
     * @return list<string> The messages of the `E_USER_DEPRECATED` errors the classes of
     *         this extension raised while `$callback` ran.
     */
    private function collectDeprecations(\Closure $callback): array
    {
        $deprecations = [];
        $previousHandler = null;
        $previousHandler = set_error_handler(
            static function (int $level, string $message, string $file = '', int $line = 0) use (&$deprecations, &$previousHandler): bool {
                if ($level === E_USER_DEPRECATED && str_contains($file, '/academic-jobs/Classes/')) {
                    $deprecations[] = $message;
                    return true;
                }
                return is_callable($previousHandler) && (bool)$previousHandler($level, $message, $file, $line);
            },
        );
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    /**
     * @param array<string, string> $flexFormSettings Values of the FlexForm of the plugin.
     */
    private function setUpSite(string $setup, array $flexFormSettings = []): void
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
                    // Renders the queue of the plugin on `/thanks`.
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/NewJobFormMessages.typoscript',
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        if ($flexFormSettings !== []) {
            $this->getConnectionPool()->getConnectionForTable('tt_content')->update(
                'tt_content',
                ['pi_flexform' => $this->flexForm($flexFormSettings)],
                ['uid' => 3],
            );
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @param array<string, string> $settings
     */
    private function flexForm(array $settings): string
    {
        $fields = '';
        foreach ($settings as $name => $value) {
            $fields .= sprintf(
                '<field index="%s"><value index="vDEF">%s</value></field>',
                htmlspecialchars($name),
                htmlspecialchars($value),
            );
        }

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . $fields
            . '</language></sheet></data></T3FlexForms>';
    }
}
