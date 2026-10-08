<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;

/**
 * Limits the page cache lifetime of a page that shows a job in the detail plugin to the
 * next start or end time of that job, so a job that ends is not shown from the page cache
 * until the cache expires.
 *
 * The core does that for the records a page lists in `config.cache`, by table and storage
 * folder, which a detail page cannot name for the one job it shows. The event names the
 * page only, so the job is read from the arguments of the request the page is rendered
 * for, the global request because the event carries none. A page computed for another
 * page id, the target of a link for example, is left alone. The start and end time of a
 * translation of the job count as well, the page may show it.
 *
 * Registered with the `event.listener` tag in `Configuration/Services.yaml`, the TYPO3
 * attribute does not exist on TYPO3 v12.
 *
 * @internal not part of public API.
 */
final class LimitJobDetailCacheLifetime
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
    {
        $jobUid = $this->requestedJobUid($event->getPageId());
        if ($jobUid === null) {
            return;
        }
        $now = (int)$event->getContext()->getPropertyFromAspect('date', 'timestamp');
        $nextChange = $this->nextChangeOfJob($jobUid, $now);
        if ($nextChange === null) {
            return;
        }
        // One second more, as the core adds for the records of `config.cache`, so the page
        // is rendered again once the job really changed.
        $event->setCacheLifetime(min($event->getCacheLifetime(), $nextChange - $now + 1));
    }

    private function requestedJobUid(int $pageId): ?int
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return null;
        }
        $routing = $request->getAttribute('routing');
        if (!$routing instanceof PageArguments || $routing->getPageId() !== $pageId) {
            return null;
        }
        $job = $routing->getArguments()['tx_academicjobs_detail']['job'] ?? null;
        if (!is_scalar($job) || (int)$job <= 0) {
            return null;
        }

        return (int)$job;
    }

    /**
     * The earliest start or end time after `$now` of the job and its translations, `null`
     * when there is none.
     */
    private function nextChangeOfJob(int $jobUid, int $now): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select('starttime', 'endtime')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($jobUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($jobUid, Connection::PARAM_INT)),
                ),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $times = [];
        foreach ($rows as $row) {
            foreach (['starttime', 'endtime'] as $field) {
                $time = (int)$row[$field];
                if ($time > $now) {
                    $times[] = $time;
                }
            }
        }

        return $times === [] ? null : min($times);
    }
}
