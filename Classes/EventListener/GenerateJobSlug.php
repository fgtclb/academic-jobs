<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\EventListener;

use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
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
        $connection = $this->connectionPool->getConnectionForTable('tx_academicjobs_domain_model_job');

        $jobRecord = $connection
            ->select(['*'], 'tx_academicjobs_domain_model_job', ['uid' => $uid])
            ->fetchAssociative();

        if ($jobRecord === false) {
            return;
        }

        $slugHelper = $this->getSlugHelperForProfileSlug();
        $jobSlug = $slugHelper->generate($jobRecord, $jobRecord['pid']);

        if (empty($jobRecord)) {
            return;
        }

        $connection->update(
            self::TABLE_NAME,
            [
                'slug' => $jobSlug,
            ],
            [
                'uid' => $uid,
            ]
        );
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
