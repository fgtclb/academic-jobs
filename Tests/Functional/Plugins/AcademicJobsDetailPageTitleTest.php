<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The head of the page the `academicjobs_detail` plugin shows a job on: the title of the
 * job is the title of the page, and the meta tags describe the job without an empty one.
 * The site of the test frontend has the title "Home", which the core puts before the
 * title of the page.
 *
 * The list is on `/home`, the detail on `/job-detail`. Job 1 has a description, job 2 has
 * none, job 3 has one with entities and two paragraphs, job 4 an empty paragraph.
 */
final class AcademicJobsDetailPageTitleTest extends AbstractAcademicJobsTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsDetailPageTitle/jobPages.csv');
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
    public function theTitleOfTheJobIsTheTitleOfThePage(): void
    {
        $xpath = $this->renderDetailPageOfJob(1);

        $this->assertSame(['Home: International Research Fellowship'], $this->texts($xpath, '//head/title'));
        $this->assertSame([], $this->texts($xpath, $this->meta('title')));
        $this->assertSame(['International Research Fellowship'], $this->texts($xpath, $this->meta('og:title')));
        $this->assertSame(['International Research Fellowship'], $this->texts($xpath, $this->meta('twitter:title')));
    }

    #[Test]
    public function aJobWithADescriptionIsDescribedByIt(): void
    {
        $xpath = $this->renderDetailPageOfJob(1);

        $this->assertSame(['Join our quantum optics group.'], $this->texts($xpath, $this->meta('description')));
        $this->assertSame(['Join our quantum optics group.'], $this->texts($xpath, $this->meta('og:description')));
        $this->assertSame(['Join our quantum optics group.'], $this->texts($xpath, $this->meta('twitter:description')));
    }

    #[Test]
    public function aJobWithoutADescriptionGetsNoEmptyDescription(): void
    {
        $xpath = $this->renderDetailPageOfJob(2);

        $this->assertSame(['Home: Plain Assistant Position'], $this->texts($xpath, '//head/title'));
        $this->assertSame([], $this->texts($xpath, $this->meta('description')));
        $this->assertSame([], $this->texts($xpath, $this->meta('og:description')));
        $this->assertSame([], $this->texts($xpath, $this->meta('twitter:description')));
    }

    /**
     * The description is rich text: its entities are decoded once, the meta tag escapes the
     * text itself, and the paragraphs are joined with single spaces.
     */
    #[Test]
    public function theDescriptionOfAJobIsItsTextWithoutMarkupAndEntities(): void
    {
        $xpath = $this->renderDetailPageOfJob(3);

        $this->assertSame(['Café & "Bar" Second paragraph'], $this->texts($xpath, $this->meta('description')));
        $this->assertSame(['Café & "Bar" Second paragraph'], $this->texts($xpath, $this->meta('og:description')));
    }

    /**
     * An empty paragraph of the editor is no description.
     */
    #[Test]
    public function aJobWithAnEmptyParagraphGetsNoEmptyDescription(): void
    {
        $xpath = $this->renderDetailPageOfJob(4);

        $this->assertSame([], $this->texts($xpath, $this->meta('description')));
        $this->assertSame([], $this->texts($xpath, $this->meta('og:description')));
        $this->assertSame([], $this->texts($xpath, $this->meta('twitter:description')));
    }

    /**
     * The list of the same site shows no job, so the provider has no title to give and the
     * page keeps its own.
     */
    #[Test]
    public function aPageWithoutAJobKeepsItsOwnTitle(): void
    {
        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(['Home: Home (EN)'], $this->texts($xpath, '//head/title'));
    }

    /**
     * Takes the detail link the list rendered for the job, so its cHash is valid.
     */
    private function renderDetailPageOfJob(int $jobUid): \DOMXPath
    {
        $listContent = $this->renderFrontendPage('https://www.acme.com/home');
        $pattern = sprintf('#href="(?P<uri>[^"]*tx_academicjobs_detail%%5Bjob%%5D=%d[^"]*)"#', $jobUid);
        $this->assertSame(1, preg_match($pattern, $listContent, $matches), sprintf('The list rendered no link for job %d.', $jobUid));

        return $this->xpath($this->renderFrontendPage('https://www.acme.com' . htmlspecialchars_decode($matches['uri'])));
    }

    /**
     * The query for the content of a meta tag. Without EXT:seo every meta tag is written
     * with `name`, the Open Graph ones included.
     */
    private function meta(string $name): string
    {
        return sprintf('//head/meta[@name="%1$s" or @property="%1$s"]/@content', $name);
    }

    private function xpath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new \DOMXPath($document);
    }

    /**
     * @return string[]
     */
    private function texts(\DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        $this->assertNotFalse($nodes);
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }
}
