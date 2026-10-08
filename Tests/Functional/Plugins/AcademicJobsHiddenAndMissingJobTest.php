<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A job the detail page cannot show: the list does not link a hidden job it shows with
 * "Show hidden records", and the detail page answers a request without a job it can show
 * with `404`.
 *
 * The list is on `/home` with "Show hidden records" switched on, the detail on
 * `/job-detail`. Job 1 is visible, job 2 is hidden.
 */
final class AcademicJobsHiddenAndMissingJobTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsHiddenAndMissingJob/jobPages.csv');
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
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theListLinksAVisibleJobToTheDetailPage(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertMatchesRegularExpression(
            '#<a href="[^"]*tx_academicjobs_detail%5Bjob%5D=1[^"]*"[^>]*>Visible Research Fellowship</a>#',
            $content,
        );
    }

    /**
     * The hidden job is listed, with its title, but not linked: the detail page would
     * answer that link with `404`.
     */
    #[Test]
    public function theListShowsAHiddenJobWithoutALink(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertMatchesRegularExpression('#<h2 class="card-title">\s*Hidden Mentoring Position\s*</h2>#', $content);
        $this->assertStringNotContainsString('tx_academicjobs_detail%5Bjob%5D=2', $content);
    }

    #[Test]
    public function theDetailPageWithoutAJobAnswersNotFound(): void
    {
        $response = $this->requestFrontendPage('https://www.acme.com/job-detail');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('academic-jobs-detail', (string)$response->getBody());
    }

    /**
     * The link of a job that was hidden after the link was rendered, a bookmark or a page
     * of a search engine for example. The link carries a valid cHash, so it is the job, not
     * the link, that the detail page does not find.
     */
    #[Test]
    public function theDetailPageOfAJobHiddenSinceAnswersNotFound(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(1, preg_match('#href="(?P<uri>[^"]*tx_academicjobs_detail%5Bjob%5D=1[^"]*)"#', $content, $matches));
        $uri = 'https://www.acme.com' . htmlspecialchars_decode($matches['uri']);
        $this->assertStringContainsString('Visible Research Fellowship', $this->renderFrontendPage($uri));

        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->update('tx_academicjobs_domain_model_job', ['hidden' => 1], ['uid' => 1]);
        $response = $this->requestFrontendPage($uri);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('Visible Research Fellowship', (string)$response->getBody());
    }
}
