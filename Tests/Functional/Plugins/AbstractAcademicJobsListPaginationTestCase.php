<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * The job list with pagination, rendered through the templates the extension ships.
 *
 * Five visible jobs, in the order of the list: Alpha Position, Bravo Thesis, Charlie
 * Sidejob, Delta Thesis and Echo Thesis. Foxtrot Hidden Position is hidden and comes last.
 *
 * - `/home`: pagination on, two jobs per page - three pages.
 * - `/switched-off`: pagination stored as off.
 * - `/stored-before`: a list stored before the pagination sheet existed.
 * - `/ten-per-page`: pagination on, ten jobs per page - one page.
 * - `/theses`: theses only, two per page.
 * - `/with-hidden`: hidden jobs shown, two per page.
 * - `/results-per-page-zero`: pagination on, with zero jobs per page.
 * - `/one-per-page`: hidden jobs shown, pagination on, one job per page - six pages.
 *
 * `numberOfLinks` is 2, so numbered pagination leaves page three out of its page links
 * where the core pagination lists every page.
 */
abstract class AbstractAcademicJobsListPaginationTestCase extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LIST_NAMESPACE = 'tx_academicjobs_list';
    protected const JOBS = [
        'Alpha Position',
        'Bravo Thesis',
        'Charlie Sidejob',
        'Delta Thesis',
        'Echo Thesis',
        'Foxtrot Hidden Position',
    ];
    protected const VISIBLE_JOBS = [
        'Alpha Position',
        'Bravo Thesis',
        'Charlie Sidejob',
        'Delta Thesis',
        'Echo Thesis',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsListPagination/jobListPages.csv');
        $this->setUpPaginationSite();
    }

    /**
     * A site configured through a TypoScript record, with the constants of the extension.
     */
    protected function setUpPaginationSite(): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PaginationTwoLinks.typoscript',
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

    /**
     * @return list<string> The jobs the page renders, in the order it renders them.
     */
    protected function renderedJobs(string $content): array
    {
        $positions = [];
        foreach (self::JOBS as $job) {
            // A hidden job is listed without a link, its title stands in the heading with
            // the white space of the template around it.
            if (preg_match('#>\s*' . preg_quote($job, '#') . '\s*<#', $content, $match, PREG_OFFSET_CAPTURE) === 1) {
                $positions[$job] = $match[0][1];
            }
        }
        asort($positions);

        return array_keys($positions);
    }

    /**
     * The entries of the pagination navigation, `null` when the page renders none. An
     * entry without a link is the current page or an ellipsis.
     *
     * @return list<array{label: string, href: string|null}>|null
     */
    protected function paginationEntries(string $content): ?array
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $navigations = (new \DOMXPath($document))->query(
            '//nav[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-list__pagination ")]'
        );
        if ($navigations === false || $navigations->length === 0) {
            return null;
        }
        $this->assertSame(1, $navigations->length, 'The page renders more than one pagination.');

        $entries = [];
        foreach ((new \DOMXPath($document))->query('.//li', $navigations->item(0)) ?: [] as $item) {
            $link = $item instanceof \DOMElement ? $item->getElementsByTagName('a')->item(0) : null;
            $entries[] = [
                'label' => trim((string)$item->textContent),
                'href' => $link?->getAttribute('href'),
            ];
        }

        return $entries;
    }

    /**
     * @return list<string> The labels of the navigation, in order.
     */
    protected function paginationLabels(string $content): array
    {
        return array_column($this->paginationEntries($content) ?? [], 'label');
    }

    /**
     * The URL of the entry of the navigation labelled `$label` - a page number, or
     * `next`, `last` and so on.
     */
    protected function paginationLink(string $content, string $label): string
    {
        foreach ($this->paginationEntries($content) ?? [] as $entry) {
            if ($entry['label'] === $label && $entry['href'] !== null) {
                return 'https://www.acme.com' . $entry['href'];
            }
        }
        $this->fail(sprintf('The pagination has no link "%s": %s', $label, implode(' | ', $this->paginationLabels($content))));
    }

    /**
     * A URL of the list on page `$pageId` that asks for `$page`, the way a stale link does,
     * with the cHash the frontend asks for with it.
     */
    protected function pageUrl(int $pageId, string $slug, int|string $page): string
    {
        $query = http_build_query([self::LIST_NAMESPACE => ['currentPage' => $page]]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return 'https://www.acme.com/' . $slug . '?' . $query . '&cHash=' . $cacheHash;
    }
}
