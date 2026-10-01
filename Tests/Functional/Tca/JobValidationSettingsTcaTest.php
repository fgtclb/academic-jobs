<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Schema\SchemaCollection;

/**
 * The shipped jobs settings reach the backend record editor of a job. Title, job type,
 * employment type and start date are required by the TCA of the job table itself,
 * company name and description only by the settings.
 */
final class JobValidationSettingsTcaTest extends AbstractAcademicJobsTestCase
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    #[Test]
    public function everyRequiredFieldOfTheSettingsIsRequiredInTheBackend(): void
    {
        $columns = $GLOBALS['TCA'][self::TABLE]['columns'];

        foreach (['title', 'type', 'employment_type', 'company_name', 'employment_start_date', 'description'] as $column) {
            $this->assertTrue($columns[$column]['config']['required'] ?? null, sprintf('"%s" is not required.', $column));
            $this->assertFalse($columns[$column]['config']['readOnly'] ?? null, sprintf('"%s" is read only.', $column));
        }
        foreach (['link', 'contact_email', 'contact_phone'] as $column) {
            $this->assertFalse($columns[$column]['config']['required'] ?? null, sprintf('"%s" is required.', $column));
        }
    }

    /**
     * The settings set the type of a column for `email` and `number` only. A phone number
     * is not a number, and the `link` column keeps its own type under `url`.
     */
    #[Test]
    public function theColumnTypesStayWhatTheTcaDeclares(): void
    {
        $columns = $GLOBALS['TCA'][self::TABLE]['columns'];

        $this->assertSame('input', $columns['contact_phone']['config']['type']);
        $this->assertSame('email', $columns['contact_email']['config']['type']);
        $this->assertSame('email[subst]', $columns['contact_email']['config']['softref']);
        $this->assertSame('link', $columns['link']['config']['type']);
        $this->assertTrue($columns['description']['config']['enableRichtext']);
    }

    /**
     * The TCA a request works with comes from the cache the bootstrap wrote, not from a
     * compilation of its own.
     */
    #[Test]
    public function theCachedTcaCarriesTheSettings(): void
    {
        $tca = $this->get('cache.core')->require($this->cacheIdentifier('tca_base'));
        $this->assertIsArray($tca, 'The bootstrap wrote no TCA cache.');
        $columns = $tca['tca'][self::TABLE]['columns'];

        $this->assertTrue($columns['company_name']['config']['required'] ?? null);
        $this->assertTrue($columns['description']['config']['required'] ?? null);
    }

    /**
     * The schema API reads its own cache. TYPO3 v13 caches an array of schemata, v14 a
     * schema collection.
     */
    #[Test]
    public function theCachedSchemaCarriesTheSettings(): void
    {
        $schemata = $this->get('cache.core')->require($this->cacheIdentifier('TcaSchema'));
        $this->assertTrue(
            is_array($schemata) || $schemata instanceof SchemaCollection,
            'The bootstrap wrote no schema cache.',
        );

        $this->assertTrue($schemata[self::TABLE]->getField('company_name')->isRequired());
        $this->assertTrue($schemata[self::TABLE]->getField('description')->isRequired());
    }

    private function cacheIdentifier(string $prefix): string
    {
        return (new PackageDependentCacheIdentifier($this->get(PackageManager::class)))
            ->withPrefix($prefix)
            ->toString();
    }
}
