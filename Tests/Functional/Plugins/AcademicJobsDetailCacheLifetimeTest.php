<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The page cache lifetime of the page that shows a job ends with the next start or end
 * time of that job, so the cached page does not show a job after its end. The lifetime is
 * read from the `Cache-Control` header `config.sendCacheHeaders` makes the page send, which
 * carries the lifetime of the page cache entry.
 *
 * TYPO3 v13 limits the lifetime itself to the start and end time of every record Extbase
 * fetches, but only with the feature `frontend.cache.autoTagging`, which it enables for a
 * new installation and leaves off for an updated one. TYPO3 v12 has no such feature. The
 * test instance runs without it, as an updated installation does.
 *
 * The list is on `/home`, the detail on `/job-detail`, both in English and in German below
 * `/de/`. Job 1 ends in two hours, job 2 has neither a start nor an end time. Job 3 ends in
 * six hours, its German translation 13 in one hour.
 */
final class AcademicJobsDetailCacheLifetimeTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const ONE_HOUR = 3600;
    private const TWO_HOURS = 7200;
    private const SIX_HOURS = 21600;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'features' => [
                    'frontend.cache.autoTagging' => false,
                ],
            ],
        ]);
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsDetailCacheLifetime/jobPages.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_academicjobs_domain_model_job');
        $connection->update('tx_academicjobs_domain_model_job', ['endtime' => time() + self::TWO_HOURS], ['uid' => 1]);
        $connection->update('tx_academicjobs_domain_model_job', ['endtime' => time() + self::SIX_HOURS], ['uid' => 3]);
        $connection->update('tx_academicjobs_domain_model_job', ['endtime' => time() + self::ONE_HOUR], ['uid' => 13]);
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
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/SendCacheHeaders.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The end time is written from the clock of the test, the frontend counts from the
     * time of its request, which the test framework may have taken a moment earlier, so
     * a lifetime is expected within a minute of the time left.
     *
     * @return array<string, array{string, int, int}>
     */
    public static function jobsThatEnd(): array
    {
        return [
            'job that ends in two hours' => ['https://www.acme.com/home', 1, self::TWO_HOURS],
            'translation that ends before its default record' => ['https://www.acme.com/de/home', 3, self::ONE_HOUR],
        ];
    }

    #[Test]
    #[DataProvider('jobsThatEnd')]
    public function theDetailPageOfAJobIsCachedUntilTheJobEnds(string $listUrl, int $jobUid, int $timeLeft): void
    {
        $lifetime = $this->cacheLifetimeOfDetailPageOfJob($listUrl, $jobUid);

        $this->assertGreaterThan($timeLeft - 60, $lifetime);
        $this->assertLessThan($timeLeft + 60, $lifetime);
    }

    /**
     * The control: a job without a start or end time leaves the lifetime of the page as
     * the core computes it, which is longer than two hours.
     */
    #[Test]
    public function theDetailPageOfAJobWithoutTimesKeepsTheLifetimeOfThePage(): void
    {
        $this->assertGreaterThan(self::TWO_HOURS + 1, $this->cacheLifetimeOfDetailPageOfJob('https://www.acme.com/home', 2));
    }

    /**
     * Takes the detail link the list rendered for the job, so its cHash is valid, and
     * returns the `max-age` the detail page answers with.
     */
    private function cacheLifetimeOfDetailPageOfJob(string $listUrl, int $jobUid): int
    {
        $listContent = $this->renderFrontendPage($listUrl);
        $pattern = sprintf('#href="(?P<uri>[^"]*tx_academicjobs_detail%%5Bjob%%5D=%d[^"]*)"#', $jobUid);
        $this->assertSame(1, preg_match($pattern, $listContent, $matches), sprintf('The list rendered no link for job %d.', $jobUid));

        $response = $this->requestFrontendPage('https://www.acme.com' . htmlspecialchars_decode($matches['uri']));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            1,
            preg_match('#(?:^|,)\s*max-age=(?P<lifetime>\d+)#', $response->getHeaderLine('Cache-Control'), $header),
            sprintf('The detail page sends no max-age, but "%s".', $response->getHeaderLine('Cache-Control')),
        );

        return (int)$header['lifetime'];
    }
}
