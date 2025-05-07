<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Upgrades;

use FGTCLB\AcademicJobs\Upgrades\PluginUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

final class PluginUpgradeWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-jobs',
    ];

    #[Test]
    public function updateNecessaryReturnsFalseWhenListTypeRecordsAreAvailable(): void
    {
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    public static function ttContentPluginDataSets(): \Generator
    {
        yield 'only newjobform - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyNewJobForm_notDeletedOrHidden.csv',
        ];
        yield 'only newjobform - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyNewJobForm_notDeletedButHidden.csv',
        ];
        yield 'only newjobform - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyNewJobForm_deletedButNotHidden.csv',
        ];
        yield 'only list - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyList_notDeletedOrHidden.csv',
        ];
        yield 'only list - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyList_notDeletedButHidden.csv',
        ];
        yield 'only list - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyList_deletedButNotHidden.csv',
        ];
        yield 'only detail - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_notDeletedOrHidden.csv',
        ];
        yield 'only detail - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_notDeletedButHidden.csv',
        ];
        yield 'only detail - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_deletedButNotHidden.csv',
        ];
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function updateNecessaryReturnsTrueWhenUpgradablePluginsExists(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function executeUpdateMigratesContentElementsAndReturnsTrue(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
    }
}
