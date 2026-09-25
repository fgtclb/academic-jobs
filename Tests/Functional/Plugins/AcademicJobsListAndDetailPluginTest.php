<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicjobs_list` and `academicjobs_detail` plugins in the frontend.
 *
 * Both plugins share one page tree and one class, because the detail plugin is reached
 * through the link the list plugin renders: `showAction()` takes a `job` argument, so its
 * URI carries a cHash and is not something a test should assemble by hand.
 *
 * The class exists since the backport of ACE-596 and covers what that change asserts —
 * the two job flags and the job link — plus a baseline that the two plugins render at
 * all and, since ACE-686, the contact block, and since ACE-729 the content element
 * header: by default the content element layout renders it and the plugins do not, and a
 * site whose layout renders no header switches `renderContentElementHeader` on. It is
 * deliberately narrower than its counterpart on `main`.
 */
final class AcademicJobsListAndDetailPluginTest extends AbstractAcademicJobsTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_CONTENT_ELEMENT = 1;
    private const DETAIL_CONTENT_ELEMENT = 2;

    private const HEADER = 'Open positions';
    private const SUBHEADER = 'Apply by the end of the month';
    private const LIST_WRAPPER = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-list ")]';
    private const DETAIL_WRAPPER = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-detail ")]';
    private const RENDER_HEADER_CONSTANTS = 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript';
    private const LAYOUT_WITHOUT_HEADER_SETUP = 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        // The abstract case of this extension does not load it, and the test
        // page renders its content through `styles.content.get`.
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string $dataSet The fixture below `Fixtures/AcademicJobsListAndDetailPlugin/`,
     *        without its extension. The default carries the jobs the flag and link tests
     *        assert counts against, so a scenario that needs other records brings its own.
     * @param bool $withGermanLanguage Adds a German site language that falls back to the
     *        English records, so a German rendering needs no translated fixtures — what it
     *        exercises is the label file, not the record localization.
     * @param string[] $additionalConstantFiles
     * @param string[] $additionalSetupFiles
     */
    private function setUpTestCase(
        string $dataSet = 'jobPages',
        bool $withGermanLanguage = false,
        array $additionalConstantFiles = [],
        array $additionalSetupFiles = [],
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsListAndDetailPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
                ],
            ],
        );
        $languages = [
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ];
        if ($withGermanLanguage) {
            $languages[] = $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: ['EN'],
                fallbackType: 'fallback',
            );
        }
        $this->writeFrontendPluginTestSite($languages);
    }

    private function renderListPage(string $url = 'https://www.acme.com/home'): string
    {
        return $this->renderFrontendPage($url);
    }

    private function setContentElementHeader(int $uid, int $headerLayout): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout],
                ['uid' => $uid],
            );
    }

    /**
     * The header layouts "Default", 2 and "Hidden", with the number of times the header and
     * the subheader have to render: "Default" is the layout the header partial resolves
     * through a setting, and the one a plugin rendering it without that setting leaves an
     * empty `<header>` for.
     *
     * @return array<string, array{int, int}>
     */
    public static function headerLayouts(): array
    {
        return [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
    }

    /**
     * Takes the detail link the list plugin rendered for a job and requests it. Extracting
     * it instead of building it keeps the cHash valid and makes the list plugin's own link
     * generation part of what the detail tests cover.
     */
    private function renderDetailPageOfJob(string $listContent, int $jobUid): string
    {
        $pattern = sprintf(
            '#href="(?P<uri>[^"]*tx_academicjobs_detail%%5Bjob%%5D=%d[^"]*)"#',
            $jobUid,
        );
        $this->assertMatchesRegularExpression(
            $pattern,
            $listContent,
            sprintf('The list plugin rendered no detail link for job %d.', $jobUid),
        );
        preg_match($pattern, $listContent, $matches);
        $uri = htmlspecialchars_decode($matches['uri']);

        return $this->renderFrontendPage('https://www.acme.com' . $uri);
    }

    #[Test]
    public function listPluginRendersAllVisibleJobs(): void
    {
        $this->setUpTestCase();

        $content = $this->renderListPage();
        $this->assertStringContainsString('academic-jobs-list', $content);
        $this->assertStringContainsString('academic-jobs-itemlist', $content);
        $this->assertStringContainsString('International Research Fellowship', $content);
        $this->assertStringContainsString('Alumni Mentoring Position', $content);
        $this->assertStringContainsString('Plain Assistant Position', $content);
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function listPluginLeavesTheContentElementHeaderToTheLayout(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase();
        $this->setContentElementHeader(self::LIST_CONTENT_ELEMENT, $headerLayout);

        $content = $this->renderListPage();
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeaderElements($content, self::LIST_WRAPPER));
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function listPluginRendersTheContentElementHeaderWhenSwitchedOn(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase(
            additionalConstantFiles: [self::RENDER_HEADER_CONSTANTS],
            additionalSetupFiles: [self::LAYOUT_WITHOUT_HEADER_SETUP],
        );
        $this->setContentElementHeader(self::LIST_CONTENT_ELEMENT, $headerLayout);

        $content = $this->renderListPage();
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, self::LIST_WRAPPER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, self::LIST_WRAPPER));
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function detailPluginLeavesTheContentElementHeaderToTheLayout(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase();
        $this->setContentElementHeader(self::DETAIL_CONTENT_ELEMENT, $headerLayout);

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeaderElements($content, self::DETAIL_WRAPPER));
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function detailPluginRendersTheContentElementHeaderWhenSwitchedOn(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase(
            additionalConstantFiles: [self::RENDER_HEADER_CONSTANTS],
            additionalSetupFiles: [self::LAYOUT_WITHOUT_HEADER_SETUP],
        );
        $this->setContentElementHeader(self::DETAIL_CONTENT_ELEMENT, $headerLayout);

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, self::DETAIL_WRAPPER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, self::DETAIL_WRAPPER));
    }

    #[Test]
    public function detailPluginRendersTheRequestedJob(): void
    {
        $this->setUpTestCase();

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertStringContainsString('academic-jobs-detail', $content);
        $this->assertStringContainsString('International Research Fellowship', $content);
        $this->assertStringContainsString('Join our quantum optics group.', $content);
    }

    #[Test]
    public function detailPluginRendersTheContactPhoneWithADialableLinkTarget(): void
    {
        $this->setUpTestCase('jobPages_contactPhone');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertStringContainsString('academic-jobs-contact', $content);
        $this->assertStringContainsString('Dr. Ada Lovelace', $content);
        // A `tel:` URI carries no spaces, while the stored number is written for a reader —
        // so the link target drops them and the label keeps them.
        $this->assertStringContainsString('href="tel:+49891234">+49 89 1234</a>', $content);
        $this->assertStringContainsString('ada@example.org', $content);
    }

    #[Test]
    public function detailPluginRendersAContactPhoneStoredWithoutSpacesUnchanged(): void
    {
        $this->setUpTestCase('jobPages_contactPhone');

        $this->assertStringContainsString(
            'href="tel:+49891234">+49891234</a>',
            $this->renderDetailPageOfJob($this->renderListPage(), 2),
        );
    }

    #[Test]
    public function detailPluginRendersNoPhoneLinkForAContactWithoutAPhoneNumber(): void
    {
        $this->setUpTestCase('jobPages_contactPhone');

        // The contact block renders for the name and the e-mail; only the phone row is
        // left out.
        $content = $this->renderDetailPageOfJob($this->renderListPage(), 3);
        $this->assertStringContainsString('academic-jobs-contact', $content);
        $this->assertStringContainsString('grace@example.org', $content);
        $this->assertStringNotContainsString('tel:', $content);
    }

    #[Test]
    public function detailPluginRendersJobFlagsAsLabelsWithoutTheirValue(): void
    {
        $this->setUpTestCase();

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        // The two flags carry no unit and no value a visitor could read, so the row is the
        // label alone: the closing `</li>` right behind it is what proves the stored `1`
        // is gone.
        $this->assertMatchesRegularExpression(
            '#<b>International applicants welcome</b>\s*</li>#',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '#<b>Recommended by alumni</b>\s*</li>#',
            $content,
        );
        // An untranslated key renders as an empty label, which is the shape of the defect.
        $this->assertStringNotContainsString('<b>:</b>', $content);
    }

    #[Test]
    public function detailPluginOmitsJobFlagsAndLinkThatAreNotSet(): void
    {
        $this->setUpTestCase();

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 3);
        $this->assertStringContainsString('Plain Assistant Position', $content);
        $this->assertStringNotContainsString('International applicants welcome', $content);
        $this->assertStringNotContainsString('Recommended by alumni', $content);
        $this->assertStringNotContainsString('<b>Link:</b>', $content);
    }

    #[Test]
    public function detailPluginRendersAnExternalJobLinkAsAnchor(): void
    {
        $this->setUpTestCase();

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertStringContainsString('<b>Link:</b>', $content);
        $this->assertMatchesRegularExpression(
            '#<a href="https://jobs\.example\.org/fellowship"[^>]*>\s*To the job posting\s*</a>#',
            $content,
        );
    }

    #[Test]
    public function detailPluginResolvesAPageLinkOfTheJob(): void
    {
        $this->setUpTestCase();

        // `link` is a TCA `link` field, so an editor picking a page stores a `t3://`
        // reference. Printed as text it is useless to a visitor; typolink resolves it.
        $content = $this->renderDetailPageOfJob($this->renderListPage(), 2);
        $this->assertMatchesRegularExpression(
            '#<a href="/application-form"[^>]*>\s*To the job posting\s*</a>#',
            $content,
        );
        $this->assertStringNotContainsString('t3://', $content);
    }

    #[Test]
    public function detailPluginRendersTheGermanFlagAndLinkLabels(): void
    {
        $this->setUpTestCase(withGermanLanguage: true);

        $content = $this->renderDetailPageOfJob(
            $this->renderListPage('https://www.acme.com/de/home'),
            1,
        );
        $this->assertMatchesRegularExpression(
            '#<b>Internationale Bewerbungen willkommen</b>\s*</li>#',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '#<b>Von Alumni empfohlen</b>\s*</li>#',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '#<a href="https://jobs\.example\.org/fellowship"[^>]*>\s*Zur Stellenausschreibung\s*</a>#',
            $content,
        );
    }

    #[Test]
    public function listPluginRendersTheFlagsAndTheLinkOfEachJob(): void
    {
        $this->setUpTestCase();

        // `Partials/Job/Item.html` carries the same loop as `Partials/Job/Information.html`,
        // so the list has to render both of them the same way the detail view does.
        $content = $this->renderListPage();
        $this->assertSame(
            2,
            preg_match_all('#<b>International applicants welcome</b>\s*</li>#', $content),
        );
        $this->assertSame(
            2,
            preg_match_all('#<b>Recommended by alumni</b>\s*</li>#', $content),
        );
        $this->assertStringNotContainsString('<b>:</b>', $content);
        $this->assertMatchesRegularExpression(
            '#<a href="https://jobs\.example\.org/fellowship"[^>]*>\s*To the job posting\s*</a>#',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '#<a href="/application-form"[^>]*>\s*To the job posting\s*</a>#',
            $content,
        );
        $this->assertStringNotContainsString('t3://', $content);
    }
}
