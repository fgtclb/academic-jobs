<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The new job form submitted through a translated content element, as the development
 * seed has it on `/de/`: the element in the default language and its German overlay
 * (`l18n_parent` set) each carry a FlexForm of their own, the site has an English and
 * a German language with strict fallback, and the redirect page is translated.
 *
 * On the German form page the FlexForm of the overlay decides the redirect page and the
 * mode, the redirect leads to the German address of the redirect page, a form without a
 * redirect page returns to the German form page without any argument of a plugin, and
 * the confirmation is the German one. The job itself is stored in the default language:
 * the controller sets no language, and Extbase writes `0` for a new object without one,
 * on TYPO3 v13 and v14 alike.
 *
 * The element in the default language sends the visitor to `/elsewhere` without a
 * confirmation, the German overlay to `/de/danke` with the confirmation queued for the
 * plugin, which `/thanks` renders in an uncached element. A test that read the FlexForm
 * of the wrong element would land on the wrong page.
 */
final class AcademicJobsNewJobFormTranslatedElementTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use NewJobFormSubmissionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

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

    /**
     * The German overlay: `/de/danke`, "Always".
     */
    private const OVERLAY_FLEXFORM = ['settings.redirectPageId' => '4', 'settings.flashMessageCreationMode' => '1'];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormTranslatedElement/jobPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The redirect page and the mode of the element of each language. Only the mode
     * "Always" of the overlay queues the confirmation and starts a session, the mode of
     * the element in the default language queues nothing for its redirect page.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: bool}>
     */
    public static function redirectPageOfTheElementDataProvider(): \Generator
    {
        yield 'English, the element in the default language' => [self::ENGLISH_FORM_PAGE, 'https://www.acme.com/elsewhere', false];
        yield 'German, the overlay' => [self::GERMAN_FORM_PAGE, 'https://www.acme.com/de/danke', true];
    }

    #[DataProvider('redirectPageOfTheElementDataProvider')]
    #[Test]
    public function eachLanguageFollowsTheFlexFormOfItsOwnElement(string $formPage, string $redirectPage, bool $queuesConfirmation): void
    {
        $this->setUpSite(self::OVERLAY_FLEXFORM);

        $answer = $this->submitJob($formPage);

        $this->assertSeeOtherTo($redirectPage, $answer);
        if ($queuesConfirmation) {
            $this->assertNotNull($this->frontendSessionCookie($answer), 'The confirmation was not stored in a session.');
        } else {
            $this->assertNull($this->frontendSessionCookie($answer), 'A message was stored in the session.');
        }
    }

    /**
     * The overlay queues the confirmation for the redirect page, in German. The element
     * in the default language would have queued nothing.
     */
    #[Test]
    public function theGermanRedirectPageShowsTheGermanConfirmation(): void
    {
        $this->setUpSite(self::OVERLAY_FLEXFORM);

        $answer = $this->submitJob(self::GERMAN_FORM_PAGE);

        $this->assertSeeOtherTo('https://www.acme.com/de/danke', $answer);
        $this->assertNotNull($this->frontendSessionCookie($answer), 'The confirmation was not stored in a session.');
        $page = $this->followSeeOther($answer);
        $this->assertStringContainsString(self::GERMAN_CONFIRMATION, $page);
        $this->assertStringNotContainsString(self::ENGLISH_CONFIRMATION, $page);
    }

    /**
     * An overlay without a redirect page returns the visitor to the German form page,
     * without any argument of a plugin, and the form shows the German confirmation. The
     * redirect page of the element in the default language does not apply.
     */
    #[Test]
    public function withoutARedirectPageTheGermanFormShowsTheGermanConfirmation(): void
    {
        $this->setUpSite([]);

        $answer = $this->submitJob(self::GERMAN_FORM_PAGE);

        $this->assertSeeOtherTo(self::GERMAN_FORM_PAGE, $answer);
        $formElement = $this->formElement($this->followSeeOther($answer));
        $this->assertStringContainsString('<form', $formElement);
        $this->assertStringContainsString(self::GERMAN_CONFIRMATION, $formElement);
        $this->assertStringNotContainsString(self::ENGLISH_CONFIRMATION, $formElement);
    }

    /**
     * Pins the language of a job submitted on the German form page: the default
     * language, as for a job submitted on the English one.
     */
    #[Test]
    public function aJobSubmittedInGermanIsStoredInTheDefaultLanguage(): void
    {
        $this->setUpSite(self::OVERLAY_FLEXFORM);

        $this->assertSeeOtherTo('https://www.acme.com/de/danke', $this->submitJob(self::GERMAN_FORM_PAGE));

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder->getRestrictions()->removeAll();
        $this->assertSame(
            [['title' => 'Research assistant', 'sys_language_uid' => 0, 'l10n_parent' => 0]],
            array_map(
                static fn(array $row): array => [
                    'title' => $row['title'],
                    'sys_language_uid' => (int)$row['sys_language_uid'],
                    'l10n_parent' => (int)$row['l10n_parent'],
                ],
                $queryBuilder->select('title', 'sys_language_uid', 'l10n_parent')
                    ->from('tx_academicjobs_domain_model_job')
                    ->orderBy('uid')
                    ->executeQuery()
                    ->fetchAllAssociative(),
            ),
        );
    }

    private function submitJob(string $formPage): ResponseInterface
    {
        return $this->submitFrontendForm(
            $formPage,
            self::FORM_ELEMENT . '//form',
            [
                'tx_academicjobs_newjobform' => [
                    'job' => [
                        'title' => 'Research assistant',
                        'description' => 'A new job description',
                        'companyName' => 'ACME Inc.',
                        'employmentStartDate' => '2027-04-01',
                        'employmentType' => '1',
                        'type' => '1',
                    ],
                ],
            ],
        );
    }

    /**
     * Writes the FlexForm of both elements and the site with an English and a German
     * language.
     *
     * @param array<string, string> $overlayFlexFormSettings Values of the FlexForm of the
     *        German overlay. The element in the default language always has
     *        `DEFAULT_FLEXFORM`.
     */
    private function setUpSite(array $overlayFlexFormSettings): void
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
                    // Renders the queue of the plugin on `/thanks` and `/de/danke`.
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/NewJobFormMessages.typoscript',
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->update('tt_content', ['pi_flexform' => $this->flexForm(self::DEFAULT_FLEXFORM)], ['uid' => 3]);
        $connection->update('tt_content', ['pi_flexform' => $this->flexForm($overlayFlexFormSettings)], ['uid' => 13]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }
}
