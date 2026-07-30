<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicjobs_list` and `academicjobs_detail` plugins in the frontend.
 *
 * Both plugins share one page tree and one class, because the detail plugin is reached
 * through the link the list plugin renders: `showAction()` takes a `job` argument, so its
 * URI carries a cHash and is not something a test should assemble by hand.
 *
 * Unlike the other extensions of this repository, these templates render the
 * `EXT:fluid_styled_content` `Header/All` partial themselves. On TYPO3 v14 that partial
 * resolves the header through the `record` view variable, which an Extbase view does not
 * assign on its own — `JobController` does it explicitly via
 * `GetCurrentContentRecordMethodTrait`. The header assertions here are what keeps that
 * working.
 */
final class AcademicJobsListAndDetailPluginTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_CONTENT_ELEMENT = 1;
    private const DETAIL_CONTENT_ELEMENT = 2;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsListAndDetailPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderListPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function setContentElementHeader(int $uid, string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => $uid]);
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
        $this->setUpTestCase('jobPages');

        $content = $this->renderListPage();
        $this->assertStringContainsString('academic-jobs-list', $content);
        $this->assertStringContainsString('academic-jobs-itemlist', $content);
        $this->assertStringContainsString('Research Assistant Position', $content);
        $this->assertStringContainsString('Student Assistant Sidejob', $content);
        $this->assertStringContainsString('Master Thesis Topic', $content);
        $this->assertStringNotContainsString('Archived Position', $content);
    }

    #[Test]
    public function listPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('jobPages');
        $this->setContentElementHeader(self::LIST_CONTENT_ELEMENT, 'Open positions');

        $this->assertStringContainsString('Open positions', $this->renderListPage());
    }

    #[Test]
    public function listPluginRendersThePropertiesOfEachJob(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderListPage();
        $this->assertStringContainsString('Organization:', $content);
        $this->assertStringContainsString('Acme University', $content);
        $this->assertStringContainsString('Location:', $content);
        $this->assertStringContainsString('Munich', $content);
        // `type` and `employmentType` render through their own label keys rather than the
        // stored integer.
        $this->assertStringContainsString('Type of job:', $content);
        $this->assertStringContainsString('Working hours:', $content);
        $this->assertStringContainsString('Full-Time', $content);
        $this->assertStringContainsString('Part-Time', $content);
        $this->assertStringNotContainsString('jobs.type.1', $content);
    }

    #[Test]
    public function listPluginRestrictsJobsToTheConfiguredJobType(): void
    {
        $this->setUpTestCase('jobPages_jobTypeFilter');

        $content = $this->renderListPage();
        $this->assertStringContainsString('Student Assistant Sidejob', $content);
        $this->assertStringNotContainsString('Research Assistant Position', $content);
        $this->assertStringNotContainsString('Master Thesis Topic', $content);
    }

    #[Test]
    public function listPluginRendersHiddenJobsWhenConfigured(): void
    {
        $this->setUpTestCase('jobPages_showHiddenRecords');

        $content = $this->renderListPage();
        $this->assertStringContainsString('Research Assistant Position', $content);
        $this->assertStringContainsString('Archived Position', $content);
    }

    #[Test]
    public function listPluginRendersAnEmptyItemListWithoutJobs(): void
    {
        $this->setUpTestCase('jobPages_noJobs');

        $content = $this->renderListPage();
        // This extension has no "nothing found" label — the item list stays empty, and the
        // plugin still has to render rather than fail.
        $this->assertStringContainsString('academic-jobs-itemlist', $content);
        $this->assertStringNotContainsString('academic-jobs-item"', $content);
    }

    #[Test]
    public function listPluginLinksEachJobToTheDetailPage(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderListPage();
        $this->assertStringContainsString('/job-detail?', $content);
        $this->assertStringContainsString('tx_academicjobs_detail%5Bjob%5D=1', $content);
        // A plugin argument only survives the frontend cache when the URI carries a cHash.
        $this->assertStringContainsString('cHash=', $content);
    }

    #[Test]
    public function listPluginRendersItemHeadingLevelAccordingToHeaderLayout(): void
    {
        $this->setUpTestCase('jobPages_headerLayout');

        // Header layout 1 without a subheader is the "one level up" case of the heading
        // partial, so the item title has to be an `h2`.
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*tx_academicjobs_detail[^"]*">Research Assistant Position</a>\s*</h2>#',
            $this->renderListPage(),
        );
    }

    #[Test]
    public function detailPluginRendersTheRequestedJob(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertStringContainsString('academic-jobs-detail', $content);
        $this->assertStringContainsString('Research Assistant Position', $content);
        $this->assertStringContainsString('Join our quantum optics group.', $content);
        // Another job of the same list must not leak into the detail view.
        $this->assertStringNotContainsString('Student Assistant Sidejob', $content);
    }

    #[Test]
    public function detailPluginRendersTheJobTitleAsFirstLevelHeading(): void
    {
        $this->setUpTestCase('jobPages');

        // The detail view passes `detail` to the heading partial, which is the only place
        // rendering an `h1`.
        $this->assertMatchesRegularExpression(
            '#<h1[^>]*>\s*Research Assistant Position\s*</h1>#',
            $this->renderDetailPageOfJob($this->renderListPage(), 1),
        );
    }

    #[Test]
    public function detailPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('jobPages');
        $this->setContentElementHeader(self::DETAIL_CONTENT_ELEMENT, 'Job description');

        $this->assertStringContainsString(
            'Job description',
            $this->renderDetailPageOfJob($this->renderListPage(), 1),
        );
    }

    #[Test]
    public function detailPluginRendersContactInformationOfTheJob(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertStringContainsString('academic-jobs-contact', $content);
        $this->assertStringContainsString('Dr. Ada Lovelace', $content);
        $this->assertStringContainsString('href="tel:+49 89 1234"', $content);
        $this->assertStringContainsString('ada@example.org', $content);
    }

    #[Test]
    public function detailPluginOmitsContactBlockForJobWithoutContact(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 2);
        $this->assertStringContainsString('Student Assistant Sidejob', $content);
        $this->assertStringNotContainsString('academic-jobs-contact', $content);
    }

    #[Test]
    public function detailPluginRendersContactSectionHeadingAccordingToHeaderLayout(): void
    {
        $this->setUpTestCase('jobPages_headerLayout');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        // Header layout 1 without a subheader renders the section heading as `h3` — one
        // level below the `h2` of the default layout.
        $this->assertMatchesRegularExpression(
            '#<h3 class="contact-title">\s*Contact\s*</h3>#',
            $content,
        );
    }

    #[Test]
    public function detailPluginRendersBackLinkToTheConfiguredListPage(): void
    {
        $this->setUpTestCase('jobPages');

        $content = $this->renderDetailPageOfJob($this->renderListPage(), 1);
        $this->assertMatchesRegularExpression(
            '#<a href="/home">\s*Back to job list\s*</a>#',
            $content,
        );
    }

    #[Test]
    public function detailPluginRendersNotFoundMessageWithoutJobArgument(): void
    {
        $this->setUpTestCase('jobPages');

        // Requesting the detail page without the plugin argument is what a stale bookmark
        // looks like: the plugin has to answer with its flash message, not with an error.
        $content = $this->renderFrontendPage('https://www.acme.com/job-detail');
        $this->assertStringContainsString('academic-jobs-detail', $content);
        $this->assertStringContainsString('No job advert could be found.', $content);
    }
}
