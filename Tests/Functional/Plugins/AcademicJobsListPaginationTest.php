<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pagination of the job list, without `georgringer/numbered-pagination`: the core
 * pagination links every page. The numbered variant is
 * {@see AcademicJobsListNumberedPaginationTest}.
 */
final class AcademicJobsListPaginationTest extends AbstractAcademicJobsListPaginationTestCase
{
    #[Test]
    public function theFirstPageShowsTheFirstJobsAndANavigation(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Alpha Position', 'Bravo Thesis'], $this->renderedJobs($content));
        $this->assertSame(['1', '2', '3', 'next', 'last'], $this->paginationLabels($content));
    }

    #[Test]
    public function theLastPageShowsTheFifthJobOnly(): void
    {
        $lastPage = $this->renderFrontendPage($this->paginationLink($this->renderFrontendPage('https://www.acme.com/home'), '3'));

        $this->assertSame(['Echo Thesis'], $this->renderedJobs($lastPage));
        $this->assertSame(['first', 'previous', '1', '2', '3'], $this->paginationLabels($lastPage));
    }

    /**
     * The page is the only argument a page link carries, and it carries a cHash like the
     * detail links of the list.
     */
    #[Test]
    public function aPageLinkCarriesThePageAndACacheHash(): void
    {
        $link = $this->paginationLink($this->renderFrontendPage('https://www.acme.com/home'), 'next');
        parse_str((string)parse_url($link, PHP_URL_QUERY), $query);

        $this->assertSame('2', $query[self::LIST_NAMESPACE]['currentPage'] ?? null);
        $this->assertArrayHasKey('cHash', $query);
        $this->assertSame(['Charlie Sidejob', 'Delta Thesis'], $this->renderedJobs($this->renderFrontendPage($link)));
    }

    /**
     * @return \Generator<string, array{0: int|string, 1: list<string>}>
     */
    public static function pageOutOfRangeDataProvider(): \Generator
    {
        yield 'page zero shows the first page' => [0, ['Alpha Position', 'Bravo Thesis']];
        yield 'a negative page shows the first page' => [-3, ['Alpha Position', 'Bravo Thesis']];
        yield 'a page beyond the last shows the last page' => [9, ['Echo Thesis']];
        yield 'a page that is no number shows the first page' => ['abc', ['Alpha Position', 'Bravo Thesis']];
        yield 'a page too large for an integer shows the first page' => ['99999999999999999999', ['Alpha Position', 'Bravo Thesis']];
    }

    /**
     * @param list<string> $expectedJobs
     */
    #[Test]
    #[DataProvider('pageOutOfRangeDataProvider')]
    public function aPageOutOfRangeShowsTheNearestPage(int|string $page, array $expectedJobs): void
    {
        $content = $this->renderFrontendPage($this->pageUrl(2, 'home', $page));

        $this->assertSame($expectedJobs, $this->renderedJobs($content));
    }

    /**
     * One page only: no navigation to nowhere.
     */
    #[Test]
    public function aListThatFitsOnOnePageRendersNoNavigation(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/ten-per-page');

        $this->assertSame(self::VISIBLE_JOBS, $this->renderedJobs($content));
        $this->assertNull($this->paginationEntries($content));
    }

    #[Test]
    public function aListWithPaginationSwitchedOffRendersEveryJob(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/switched-off');

        $this->assertSame(self::VISIBLE_JOBS, $this->renderedJobs($content));
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * A list stored before the pagination sheet existed has no value for it at all.
     */
    #[Test]
    public function aListStoredBeforeThePaginationExistedRendersEveryJob(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/stored-before');

        $this->assertSame(self::VISIBLE_JOBS, $this->renderedJobs($content));
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * A page argument means nothing to a list without pagination: it still renders every
     * job.
     */
    #[Test]
    public function aListWithoutPaginationIgnoresAPageArgument(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl(4, 'switched-off', 2));

        $this->assertSame(self::VISIBLE_JOBS, $this->renderedJobs($content));
    }

    /**
     * A POST reaches the list without a cache hash, on every list. A page that is no
     * number must not turn it into a validation error, with the pagination on or off.
     *
     * @return \Generator<string, array{0: string, 1: list<string>}>
     */
    public static function postedPageThatIsNoNumberDataProvider(): \Generator
    {
        yield 'pagination on' => ['https://www.acme.com/home', ['Alpha Position', 'Bravo Thesis']];
        yield 'pagination off' => ['https://www.acme.com/switched-off', self::VISIBLE_JOBS];
    }

    /**
     * @param list<string> $expectedJobs
     */
    #[Test]
    #[DataProvider('postedPageThatIsNoNumberDataProvider')]
    public function aPostedPageThatIsNoNumberRendersTheList(string $url, array $expectedJobs): void
    {
        $content = $this->renderFrontendPage(
            $this->frontendPostRequest($url, [self::LIST_NAMESPACE => ['currentPage' => 'abc']])
        );

        $this->assertSame($expectedJobs, $this->renderedJobs($content));
    }

    /**
     * The paginator throws for less than one job per page. The list takes ten instead,
     * the default of the field - here, all five jobs on one page.
     */
    #[Test]
    public function aResultsPerPageOfZeroFallsBackToTen(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/results-per-page-zero');

        $this->assertSame(self::VISIBLE_JOBS, $this->renderedJobs($content));
        $this->assertNull($this->paginationEntries($content));
    }

    #[Test]
    public function aPagedThesisListShowsThesesOnly(): void
    {
        $firstPage = $this->renderFrontendPage('https://www.acme.com/theses');
        $this->assertSame(['Bravo Thesis', 'Delta Thesis'], $this->renderedJobs($firstPage));
        $this->assertSame(['1', '2', 'next', 'last'], $this->paginationLabels($firstPage));

        $secondPage = $this->renderFrontendPage($this->paginationLink($firstPage, '2'));
        $this->assertSame(['Echo Thesis'], $this->renderedJobs($secondPage));
    }

    #[Test]
    public function aListShowingHiddenJobsPagesThemWithTheOthers(): void
    {
        $firstPage = $this->renderFrontendPage('https://www.acme.com/with-hidden');
        $this->assertSame(['1', '2', '3', 'next', 'last'], $this->paginationLabels($firstPage));

        $lastPage = $this->renderFrontendPage($this->paginationLink($firstPage, 'last'));
        $this->assertSame(['Echo Thesis', 'Foxtrot Hidden Position'], $this->renderedJobs($lastPage));
    }

    /**
     * The core pagination has no limit on the number of page links.
     */
    #[Test]
    public function everyPageIsLinkedWithoutNumberedPagination(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/one-per-page');

        $this->assertSame(['Alpha Position'], $this->renderedJobs($content));
        $this->assertSame(['1', '2', '3', '4', '5', '6', 'next', 'last'], $this->paginationLabels($content));
    }
}
