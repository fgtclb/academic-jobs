<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicBase\Persistence\HiddenRecordsFetcher;
use FGTCLB\AcademicBase\Persistence\HiddenRecordsOverlayAspect;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * How the job repository fetches hidden records for a translated page (ACE-857), apart
 * from the rendering the plugin tests cover: a paginated list fetches its page only,
 * and the overlay window of `HiddenRecordsFetcher` restores the visibility of the
 * request whatever happens inside it.
 *
 * The fixture is the one of `AcademicJobsShowHiddenRecordsTranslationTest`. In German,
 * with translations only, the list holds jobs 1, 2, 4 and 5 in this order, job 2 is
 * hidden and translated as "Verborgene Mentorenstelle".
 */
final class JobRepositoryHiddenTranslationTest extends AbstractAcademicJobsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Plugins/Fixtures/AcademicJobsShowHiddenRecordsTranslation/jobPages.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/de/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->get(Context::class)->setAspect(
            'language',
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, []),
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * The paginator executes the query of its page on its own. The full result behind it
     * is never iterated by the list and must not be fetched for it.
     */
    #[Test]
    public function aPaginatedListFetchesItsPageOnly(): void
    {
        // The plugin hands the repository its storage folder, a bare repository has none.
        $jobRepository = $this->get(JobRepository::class);
        $jobRepository->setDefaultQuerySettings($this->get(Typo3QuerySettings::class)->setRespectStoragePage(false));
        $jobs = $jobRepository->findAllJobs(true);
        $paginator = new QueryResultPaginator($jobs, 2, 1);
        $page = $paginator->getPaginatedItems();
        $this->assertInstanceOf(QueryResult::class, $page);
        $this->get(HiddenRecordsFetcher::class)->fetch($page);

        $titles = [];
        foreach ($page as $job) {
            $this->assertInstanceOf(Job::class, $job);
            $titles[] = $job->getTitle();
        }
        $this->assertSame(['Verborgene Mentorenstelle'], $titles);
        $this->assertNull(
            (new \ReflectionProperty(QueryResult::class, 'queryResult'))->getValue($jobs),
            'The full result was fetched.',
        );
    }

    /**
     * A fetch that fails after the visibility was lifted, before the listener restores
     * it, leaves the context as it found it.
     */
    #[Test]
    public function aFailingFetchRestoresTheVisibilityAndRemovesTheMarker(): void
    {
        $context = $this->get(Context::class);
        $visibilityAspect = new VisibilityAspect();
        $context->setAspect('visibility', $visibilityAspect);
        $query = $this->hiddenJobsQuery();
        /** @var \ArrayObject<string, bool|int|string> $observed */
        $observed = new \ArrayObject();

        try {
            $this->get(HiddenRecordsFetcher::class)->withinOverlayWindow(
                $query,
                function () use ($query, $context, $observed): void {
                    $this->get(EventDispatcherInterface::class)->dispatch(new ModifyQueryBeforeFetchingObjectDataEvent($query));
                    $observed['lifted'] = (bool)$context->getPropertyFromAspect('visibility', 'includeHiddenContent');
                    throw new \RuntimeException('The fetch failed.', 1791480034);
                },
            );
        } catch (\RuntimeException $exception) {
            $observed['exceptionCode'] = $exception->getCode();
        }

        $this->assertSame(1791480034, $observed['exceptionCode'] ?? null, 'The exception of the fetch was swallowed.');
        $this->assertTrue($observed['lifted'] ?? false, 'The listener did not lift the visibility.');
        $this->assertSame($visibilityAspect, $context->getAspect('visibility'));
        $this->assertFalse($context->hasAspect(HiddenRecordsOverlayAspect::NAME));
    }

    /**
     * Only the query that lifted the visibility restores it, the result event of another
     * query in between leaves it lifted.
     */
    #[Test]
    public function onlyTheQueryThatLiftedRestoresTheVisibility(): void
    {
        $context = $this->get(Context::class);
        $visibilityAspect = new VisibilityAspect();
        $context->setAspect('visibility', $visibilityAspect);
        $query = $this->hiddenJobsQuery();
        $otherQuery = $this->hiddenJobsQuery();
        $eventDispatcher = $this->get(EventDispatcherInterface::class);

        $this->get(HiddenRecordsFetcher::class)->withinOverlayWindow(
            $query,
            function () use ($query, $otherQuery, $context, $visibilityAspect, $eventDispatcher): void {
                $eventDispatcher->dispatch(new ModifyQueryBeforeFetchingObjectDataEvent($query));
                $eventDispatcher->dispatch(new ModifyResultAfterFetchingObjectDataEvent($otherQuery, []));
                $this->assertTrue((bool)$context->getPropertyFromAspect('visibility', 'includeHiddenContent'));
                $eventDispatcher->dispatch(new ModifyResultAfterFetchingObjectDataEvent($query, []));
                $this->assertSame($visibilityAspect, $context->getAspect('visibility'));
            },
        );

        $this->assertSame($visibilityAspect, $context->getAspect('visibility'));
    }

    /**
     * @return QueryInterface<Job>
     */
    private function hiddenJobsQuery(): QueryInterface
    {
        $query = $this->get(JobRepository::class)->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);
        return $query;
    }
}
