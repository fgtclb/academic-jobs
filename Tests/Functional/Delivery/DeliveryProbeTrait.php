<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Delivery;

use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The scaffolding both delivery test classes of this extension need.
 *
 * The site set half of the delivery only exists from TYPO3 v13.1 on, so it is tested in
 * `Tests/Functional/Core13/SiteSet/`, while the static template half is the only
 * mechanism TYPO3 v12 has and is tested for both core versions. That is two classes
 * around one probe, and the probe is what lives here.
 *
 * A class using this trait has to declare its own `LANGUAGE_PRESETS` - which languages a
 * test needs is part of what it tests, and a constant in a trait is a parse time fatal
 * on PHP 8.1, which this branch still supports.
 */
trait DeliveryProbeTrait
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    /**
     * The three content elements of this extension, with the component set that owns each
     * and the two paths that set points at.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function componentDataProvider(): \Generator
    {
        yield 'new job form' => [
            'fgtclb/academic-jobs-new-job-form',
            'academicjobs_newjobform',
            'EXT:academic_jobs/Configuration/TypoScript/NewJobForm/',
            'EXT:academic_jobs/Configuration/TSconfig/NewJobForm/page.tsconfig',
        ];
        yield 'job list' => [
            'fgtclb/academic-jobs-list',
            'academicjobs_list',
            'EXT:academic_jobs/Configuration/TypoScript/List/',
            'EXT:academic_jobs/Configuration/TSconfig/List/page.tsconfig',
        ];
        yield 'job detail' => [
            'fgtclb/academic-jobs-detail',
            'academicjobs_detail',
            'EXT:academic_jobs/Configuration/TypoScript/Detail/',
            'EXT:academic_jobs/Configuration/TSconfig/Detail/page.tsconfig',
        ];
    }

    /**
     * The constant the probe renders, assigned by
     * `Configuration/TypoScript/constants.typoscript` and by nothing else. A constant
     * that `settings.definitions.yaml` also declares would prove nothing here: a site set
     * contributes its settings as constants after the constants of its `typoscript`
     * folder, so such a value renders even when `constants.typoscript` was never read.
     */
    protected function sharedConstantMarkup(): string
    {
        return '<div id="constant">EXT:academic_jobs/Resources/Private/Templates/</div>';
    }

    /**
     * A value the probe copies out of the setup of the shared block, assigned by
     * `Configuration/TypoScript/setup.typoscript`.
     */
    protected function sharedSetupMarkup(): string
    {
        return '<div id="setup">EXT:academic_jobs/Resources/Private/Partials/</div>';
    }

    /**
     * A setting the shared constants assign and `settings.definitions.yaml` declares, with
     * the same default in both. It used to be empty in the constants and `0` in the
     * declaration; the probe renders it so that the reconciliation is asserted where it
     * matters - in what the frontend ends up with, through either mechanism.
     */
    protected function sharedSettingMarkup(): string
    {
        return '<div id="detailPid">0</div>';
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names. Ignored by
     *        TYPO3 v12, which has no site set API at all.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     * @param string $pageTsConfigInclude Page TSconfig file the page record selects, the
     *        only way to reach a component page TSconfig without site sets.
     */
    protected function setUpSite(
        array $dependencies = [],
        string $includeStaticFile = '',
        string $pageTsConfigInclude = '',
    ): void {
        $identifier = 'acme-' . substr(
            md5(implode(',', $dependencies) . '|' . $includeStaticFile . '|' . $pageTsConfigInclude),
            0,
            10,
        );

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        if ($pageTsConfigInclude !== '') {
            $this->getConnectionPool()->getConnectionForTable('pages')->update(
                'pages',
                ['tsconfig_includes' => $pageTsConfigInclude],
                ['uid' => 1],
            );
        }
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed.
                'clear' => 0,
                'title' => 'Probe',
                'constants' => '',
                'config' => '@import \'EXT:academic_jobs/Tests/Functional/Delivery/Fixtures/Probe.typoscript\'',
                'include_static_file' => $includeStaticFile,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: $this->frontendPluginTestBase(),
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    protected function removedContentElementTypes(array $pageTsConfig): array
    {
        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }
}
