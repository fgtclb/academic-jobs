<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

/**
 * A redirect page the new job form cannot link in the language of the visitor counts as
 * no redirect page: the form answers with a `303` to its own page, the form shows the
 * confirmation as it does without a redirect page, and the job controller logs a warning
 * that names the redirect page and the language. It used to send the visitor to the root
 * of the site, and a queued confirmation waited in the session for a page that might
 * never render it (ACE-932).
 *
 * The site has an English and a German language with strict fallback, as a new site
 * language has by default. `/elsewhere` has no German translation, so the form on the
 * German page `/de/neue-stelle` cannot link it, whichever source names it: the FlexForm
 * of the element, the site setting, a listener of `AfterSaveJobEvent` or the deprecated
 * `listPid`. In the default language a hidden and a deleted page cannot be linked either,
 * and on TYPO3 v13 a folder. TYPO3 v12 links a folder, so the form still sends the visitor
 * there. The element in the default language sends the visitor to `/elsewhere`.
 *
 * The confirmation is decided as for a form without a redirect page. "Suppress when a
 * redirect page is set", the default mode, queues it for the form, "Always" does too,
 * and "Never" still does not. The warning about a notification mail that could not be
 * sent is shown on the form the same way.
 */
final class AcademicJobsNewJobFormUntranslatedRedirectPageTest extends AbstractAcademicJobsNotificationMailTestCase
{
    use NewJobFormSubmissionTrait;

    private const ENGLISH_FORM_PAGE = 'https://www.acme.com/new-job';
    private const GERMAN_FORM_PAGE = 'https://www.acme.com/de/neue-stelle';

    /**
     * The title of the confirmation after a saved job whose notification was sent.
     */
    private const ENGLISH_CONFIRMATION = 'Your job advert has been successfully created';
    private const GERMAN_CONFIRMATION = 'Ihre Stellenanzeige wurde erfolgreich erstellt';

    /**
     * The element in the default language: `/elsewhere`, "Suppress when a redirect page
     * is set".
     */
    private const DEFAULT_FLEXFORM = ['settings.redirectPageId' => '6', 'settings.flashMessageCreationMode' => '0'];

    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        $configuration['LOG']['FGTCLB']['AcademicJobs']['Controller']['JobController']['writerConfiguration'] = [
            LogLevel::WARNING => [
                FileWriter::class => ['logFile' => self::logFile()],
            ],
        ];

        return $configuration;
    }

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-job-after-save-listener';
        parent::setUp();
        // Truncated rather than removed: the writer keeps its file handle open across the
        // requests of a test class, and appends to the end of the file.
        if (is_file(self::logFile())) {
            file_put_contents(self::logFile(), '');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormUntranslatedRedirectPage/jobPages.csv');
    }

    /**
     * Every source that can name `/elsewhere` for the German form, each with the default
     * mode "Suppress when a redirect page is set".
     *
     * @return \Generator<string, array{0: string, 1: array<string, string>}>
     */
    public static function untranslatedRedirectPageDataProvider(): \Generator
    {
        yield 'the content element' => ['', ['settings.redirectPageId' => '6', 'settings.flashMessageCreationMode' => '0']];
        yield 'the site setting' => ['plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId = 6', []];
        yield 'a listener' => ['plugin.tx_academicjobs_newjobform.settings.testRedirectPageId = 6', []];
    }

    /**
     * @param array<string, string> $overlayFlexFormSettings
     */
    #[DataProvider('untranslatedRedirectPageDataProvider')]
    #[Test]
    public function anUntranslatedRedirectPageReturnsTheVisitorToTheForm(string $setup, array $overlayFlexFormSettings): void
    {
        $this->setUpSite($setup, $overlayFlexFormSettings);

        $answer = $this->sendJob(self::GERMAN_FORM_PAGE);

        $this->createdJobUid();
        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringContainsString(self::GERMAN_CONFIRMATION, $formElement);
        $this->assertStringNotContainsString(self::ENGLISH_CONFIRMATION, $formElement);
        $this->assertOneWarningNames(6, 1, 'Deutsch', 5);
    }

    /**
     * The deprecated list page, the redirect page of last resort, is checked as well.
     */
    #[Test]
    public function anUntranslatedDeprecatedListPageReturnsTheVisitorToTheForm(): void
    {
        $this->setUpSite('plugin.tx_academicjobs.settings.listPid = 6', []);

        $answer = null;
        $deprecations = $this->collectDeprecations(function () use (&$answer): void {
            $answer = $this->sendJob(self::GERMAN_FORM_PAGE);
        });

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('plugin.tx_academicjobs.settings.listPid', $deprecations[0]);
        $this->assertInstanceOf(ResponseInterface::class, $answer);
        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $this->assertStringContainsString(self::GERMAN_CONFIRMATION, $this->formElement($this->followSeeOther($answer)));
        $this->assertOneWarningNames(6, 1, 'Deutsch', 5);
    }

    /**
     * The mode "Never" queues no confirmation on the form page either.
     */
    #[Test]
    public function theModeNeverShowsNoConfirmationOnTheForm(): void
    {
        $this->setUpSite('', ['settings.redirectPageId' => '6', 'settings.flashMessageCreationMode' => '2']);

        $answer = $this->sendJob(self::GERMAN_FORM_PAGE);

        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringNotContainsString(self::GERMAN_CONFIRMATION, $formElement);
        $this->assertOneWarningNames(6, 1, 'Deutsch', 5);
    }

    /**
     * The mode "Always" queues the confirmation for the form as well, not for the plugin,
     * and the form shows it once: the form page requested without the session of the
     * submission shows none.
     */
    #[Test]
    public function theModeAlwaysShowsTheConfirmationOnTheFormOnce(): void
    {
        $this->setUpSite('', ['settings.redirectPageId' => '6', 'settings.flashMessageCreationMode' => '1']);

        $answer = $this->sendJob(self::GERMAN_FORM_PAGE);

        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $this->assertNotNull($this->frontendSessionCookie($answer), 'The confirmation was not stored in a session.');
        $this->assertStringContainsString(self::GERMAN_CONFIRMATION, $this->formElement($this->followSeeOther($answer)));
        $withoutSession = $this->formElement($this->renderFrontendPage(self::GERMAN_FORM_PAGE));
        $this->assertStringContainsString('<form', $withoutSession);
        $this->assertStringNotContainsString(self::GERMAN_CONFIRMATION, $withoutSession);
        $this->assertOneWarningNames(6, 1, 'Deutsch', 5);
    }

    /**
     * A notification mail that cannot be sent, here for an empty recipient, puts its
     * warning on the form in place of the confirmation, in the conditional mode as well.
     */
    #[Test]
    public function aMailThatCannotBeSentIsReportedOnTheForm(): void
    {
        $this->setUpSite(
            'plugin.tx_academicjobs.settings.email.recipientEmail =',
            ['settings.redirectPageId' => '6', 'settings.flashMessageCreationMode' => '0'],
        );

        $answer = $this->sendJob(self::GERMAN_FORM_PAGE);

        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('Benachrichtigungs-E-Mail konnte nicht gesendet werden', $formElement);
        // The body of the confirmation is checked, since the body of the warning begins
        // with the title of the confirmation.
        $this->assertStringNotContainsString('Nach einer Überprüfung schalten wir Ihre Stellenanzeige frei.', $formElement);
        $this->assertOneWarningNames(6, 1, 'Deutsch', 5);
    }

    /**
     * Pages the form cannot link in the default language either, on TYPO3 v12 and v13.
     *
     * @return \Generator<string, array{0: int}>
     */
    public static function unlinkableRedirectPageDataProvider(): \Generator
    {
        yield 'a hidden page' => [7];
        yield 'a deleted page' => [8];
    }

    #[DataProvider('unlinkableRedirectPageDataProvider')]
    #[Test]
    public function aRedirectPageThatCannotBeLinkedReturnsTheVisitorToTheForm(int $redirectPageId): void
    {
        $this->assertRedirectPageReturnsTheVisitorToTheEnglishForm($redirectPageId);
    }

    /**
     * TYPO3 v13 links no folder.
     */
    #[Group('not-core-12')]
    #[Test]
    public function aFolderAsTheRedirectPageReturnsTheVisitorToTheForm(): void
    {
        $this->assertRedirectPageReturnsTheVisitorToTheEnglishForm(100);
    }

    /**
     * TYPO3 v12 links a folder as any other page, so the form sends the visitor there, as
     * it did before, and logs nothing.
     */
    #[Group('not-core-13')]
    #[Test]
    public function aFolderAsTheRedirectPageReceivesTheVisitorOnTypo3V12(): void
    {
        $this->setUpSite('', [], ['settings.redirectPageId' => '100', 'settings.flashMessageCreationMode' => '0']);

        $answer = $this->sendJob(self::ENGLISH_FORM_PAGE);

        $this->assertSeeOtherTo('https://www.acme.com/sysfolder-records', $answer);
        $this->assertSame([], $this->loggedWarnings());
    }

    /**
     * The page the German form cannot link is linked in the default language, and nothing
     * is logged.
     */
    #[Test]
    public function theEnglishFormStillSendsTheVisitorToTheRedirectPage(): void
    {
        $this->setUpSite('', []);

        $answer = $this->sendJob(self::ENGLISH_FORM_PAGE);

        $this->assertSeeOtherTo('https://www.acme.com/elsewhere', $answer);
        $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        $this->assertSame([], $this->loggedWarnings());
    }

    private function assertRedirectPageReturnsTheVisitorToTheEnglishForm(int $redirectPageId): void
    {
        $this->setUpSite('', [], ['settings.redirectPageId' => (string)$redirectPageId, 'settings.flashMessageCreationMode' => '0']);

        $answer = $this->sendJob(self::ENGLISH_FORM_PAGE);

        $this->assertSeeOtherTo(self::ENGLISH_FORM_PAGE, $answer);
        $this->assertStringContainsString(self::ENGLISH_CONFIRMATION, $this->formElement($this->followSeeOther($answer)));
        $this->assertOneWarningNames($redirectPageId, 0, 'English', 5);
    }

    private function sendJob(string $formPage): ResponseInterface
    {
        return $this->postJob($formPage, 'Research assistant');
    }

    private function assertOneWarningNames(int $redirectPageId, int $languageId, string $languageTitle, int $formPageId): void
    {
        $warnings = $this->loggedWarnings();
        $this->assertCount(1, $warnings, 'The job controller logged ' . count($warnings) . ' warnings.');
        $this->assertStringContainsString(
            sprintf(
                'The redirect page %d of the job form on page %d cannot be linked in language %d (%s),'
                . ' it is missing, hidden, access restricted, not translated or of a type that cannot be linked.'
                . ' The visitor returns to the form.',
                $redirectPageId,
                $formPageId,
                $languageId,
                $languageTitle,
            ),
            $warnings[0],
        );
    }

    private static function logFile(): string
    {
        return static::getInstancePath() . '/typo3temp/var/log/job-controller.log';
    }

    /**
     * @return string[] The entries the job controller logged at level warning.
     */
    private function loggedWarnings(): array
    {
        if (!is_file(self::logFile())) {
            return [];
        }

        return array_values(array_filter(
            file(self::logFile(), FILE_IGNORE_NEW_LINES) ?: [],
            static fn(string $line): bool => str_contains($line, '[WARNING]')
                && str_contains($line, 'component="FGTCLB.AcademicJobs.Controller.JobController"'),
        ));
    }

    /**
     * Writes the TypoScript, the FlexForm of both elements and the site with an English
     * and a German language with strict fallback.
     *
     * @param array<string, string> $overlayFlexFormSettings Values of the FlexForm of the
     *        German overlay.
     * @param array<string, string> $defaultFlexFormSettings Values of the FlexForm of the
     *        element in the default language.
     */
    private function setUpSite(string $setup, array $overlayFlexFormSettings, array $defaultFlexFormSettings = self::DEFAULT_FLEXFORM): void
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
                ],
            ],
        );
        $templateConnection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $templateConnection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $templateConnection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $contentConnection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $contentConnection->update('tt_content', ['pi_flexform' => $this->flexForm($defaultFlexFormSettings)], ['uid' => 3]);
        $contentConnection->update('tt_content', ['pi_flexform' => $this->flexForm($overlayFlexFormSettings)], ['uid' => 13]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }
}
