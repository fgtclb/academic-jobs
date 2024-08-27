<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\EventListener;

use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenerateJobSlug
{
    public const TABLE_NAME = 'tx_academicjobs_domain_model_job';

    private ConnectionPool $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function __invoke(AfterSaveJobEvent $event): void
    {
        $uid = $event->getJob()->getUid();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder->getRestrictions()->removeAll();

        $jobRecord = $queryBuilder
            ->select('*')
            ->from('tx_academicjobs_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($jobRecord === false) {
            return;
        }

        $slugHelper = $this->getSlugHelperForProfileSlug();
        $jobSlug = $slugHelper->generate($jobRecord, $jobRecord['pid']);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder
            ->update('tx_academicjobs_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->set('slug', $jobSlug)
            ->executeStatement();
    }

    private function getSlugHelperForProfileSlug(): SlugHelper
    {
        return GeneralUtility::makeInstance(
            SlugHelper::class,
            self::TABLE_NAME,
            'slug',
            $GLOBALS['TCA'][self::TABLE_NAME]['columns']['slug']['config']
        );
    }
}
