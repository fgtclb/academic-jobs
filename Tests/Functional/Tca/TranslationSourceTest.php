<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The job table records where a translation was made from.
 *
 * `l10n_parent` and `l10n_source` answer two different questions. The first says
 * which record a translation belongs to, the second which record it was written
 * from - the same thing only when the translation was made from the default
 * language. Without `translationSource` the second question has no answer at
 * all, and this was the only academic table missing it (ACE-463).
 *
 * Nothing has to be migrated for it. The column is derived from TCA by
 * `DefaultTcaSchema` as `int unsigned NOT NULL DEFAULT 0`, and `0` is both the
 * value every existing row gets and the correct value for a default language
 * record. On a translation that predates this it means "not recorded", which
 * every reader in the core guards for with `> 0`.
 */
final class TranslationSourceTest extends AbstractAcademicJobsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    #[Test]
    public function theTableHasATranslationSourceColumn(): void
    {
        $columns = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->createSchemaManager()
            ->listTableColumns('tx_academicjobs_domain_model_job');

        $this->assertArrayHasKey(
            'l10n_source',
            array_change_key_case($columns),
            'The job table has no "l10n_source" column, so no translation can record its source.',
        );
    }

    #[Test]
    public function localizingAJobRecordsTheRecordItWasWrittenFrom(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationSource/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationSource/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationSource/tx_academicjobs_domain_model_job.csv');
        // DataHandler refuses a "localize" into a language the page's site does not
        // declare - "Language ID 1 not found for page 1" - so the site comes first.
        $this->writeSiteConfiguration('acme', $this->buildSiteConfiguration(1, 'https://www.acme.com/'), [
            $this->buildDefaultLanguageConfiguration('EN', '/'),
            $this->buildLanguageConfiguration('DE', '/de/'),
        ]);
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_academicjobs_domain_model_job' => [
                1 => ['localize' => 1],
            ],
        ]);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);

        $translation = $this->translationOf(1);
        $this->assertNotSame([], $translation, 'The job was not translated.');
        $this->assertSame(1, (int)$translation['l10n_parent'], 'The translation belongs to the wrong record.');
        $this->assertSame(
            1,
            (int)$translation['l10n_source'],
            'The translation does not record which record it was written from.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function translationOf(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('uid', 'l10n_parent', 'l10n_source', 'sys_language_uid')
            ->from('tx_academicjobs_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($uid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? [] : $row;
    }
}
