<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The German job list with "Show hidden records" links a job only when the detail page
 * shows it. The detail page finds a job by its default record, so a visible translation
 * of a hidden default record is listed without a link, and every link the list renders
 * answers `200`.
 *
 * The records are those of `AcademicJobsShowHiddenRecordsTranslationTest`, with a detail
 * content element added on `/job-detail`. Job 5 is hidden, its translation 15 is not.
 */
final class AcademicJobsHiddenTranslatedJobLinkTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsShowHiddenRecordsTranslation/jobPages.csv');
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 2,
            'pid' => 3,
            'CType' => 'academicjobs_detail',
            'sys_language_uid' => -1,
        ]);
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function fallbackTypes(): array
    {
        return [
            'strict' => ['strict'],
            'fallback' => ['fallback'],
        ];
    }

    /**
     * @param non-empty-string $fallbackType
     */
    private function writeSite(string $fallbackType): void
    {
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'strict' ? [] : ['EN'],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    /**
     * @return array<string, string|null> The listed titles, with the link of each or null.
     */
    private function listedJobs(string $content): array
    {
        preg_match_all('#<h2 class="card-title">(.*?)</h2>#s', $content, $matches);
        $jobs = [];
        foreach ($matches[1] as $heading) {
            $title = trim(strip_tags($heading));
            $jobs[$title] = preg_match('#href="([^"]+)"#', $heading, $link) === 1 ? htmlspecialchars_decode($link[1]) : null;
        }

        return $jobs;
    }

    /**
     * @param non-empty-string $fallbackType
     */
    #[Test]
    #[DataProvider('fallbackTypes')]
    public function everyLinkOfTheGermanListAnswersWithTheJob(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $jobs = $this->listedJobs($this->renderFrontendPage('https://www.acme.com/de/home'));

        $this->assertNotNull($jobs['Sichtbares Forschungsstipendium'] ?? null, 'The visible job is not linked.');
        foreach (array_filter($jobs) as $title => $link) {
            $response = $this->requestFrontendPage('https://www.acme.com' . $link);
            $this->assertSame(200, $response->getStatusCode(), sprintf('The link of "%s" answers %d.', $title, $response->getStatusCode()));
            $this->assertStringContainsString($title, (string)$response->getBody());
        }
    }

    /**
     * Strict German lists the visible translation of the hidden job 5 on both core versions.
     */
    #[Test]
    public function theVisibleTranslationOfAHiddenJobIsListedWithoutALink(): void
    {
        $this->writeSite('strict');

        $jobs = $this->listedJobs($this->renderFrontendPage('https://www.acme.com/de/home'));

        $this->assertArrayHasKey('Gemischt Sichtbare Uebersetzung', $jobs);
        $this->assertNull($jobs['Gemischt Sichtbare Uebersetzung']);
    }
}
