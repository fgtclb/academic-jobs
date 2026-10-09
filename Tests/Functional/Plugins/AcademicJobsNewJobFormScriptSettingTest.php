<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The switch `plugin.tx_academicjobs.assets.js`, which decides whether the new job form
 * loads its scripts: the rich text editor from its content delivery network and the
 * module that configures it.
 *
 * It exists twice under one name, as a site setting of the aggregate set and as a
 * TypoScript constant for a site configured through `sys_template` records, so both
 * delivery mechanisms are covered here.
 */
final class AcademicJobsNewJobFormScriptSettingTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const AGGREGATE_SET = 'fgtclb/academic-jobs';
    private const SETTING = 'plugin.tx_academicjobs.assets.js';

    /**
     * The bare specifier of the module. It reaches the page in the statement that imports
     * it, and nowhere when no module was registered.
     */
    private const MODULE = '@fgtclb/academic-jobs/frontend/rich-text.js';

    private const EDITOR = 'https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormPlugin/newJobFormPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theAggregateSetDeclaresTheSettingSwitchedOn(): void
    {
        $definitions = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::AGGREGATE_SET)?->settingsDefinitions ?? [] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $this->assertArrayHasKey(self::SETTING, $definitions);
        $this->assertSame('bool', $definitions[self::SETTING]->type);
        $this->assertTrue($definitions[self::SETTING]->default);
    }

    #[Test]
    #[DataProvider('deliveryMechanisms')]
    public function theFormLoadsItsScriptsWhenTheSiteConfiguresNothing(bool $siteSet): void
    {
        $siteSet ? $this->setUpSiteSetSite() : $this->setUpStaticTemplateSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString(self::MODULE, $content);
        $this->assertStringContainsString(self::EDITOR, $content);
    }

    public static function deliveryMechanisms(): \Generator
    {
        yield 'site set' => [true];
        yield 'static template' => [false];
    }

    #[Test]
    public function theSiteSettingSwitchesTheScriptsOff(): void
    {
        $this->setUpSiteSetSite([self::SETTING => false]);

        $this->assertFormWithoutScripts($this->renderFrontendPage('https://www.acme.com/home'));
    }

    #[Test]
    public function theTypoScriptConstantSwitchesTheScriptsOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript']);

        $this->assertFormWithoutScripts($this->renderFrontendPage('https://www.acme.com/home'));
    }

    /**
     * Neither script, and the form unchanged: the text areas the editor would replace are
     * there, and a visitor submits them as they are.
     */
    private function assertFormWithoutScripts(string $content): void
    {
        $this->assertStringNotContainsString(self::MODULE, $content);
        $this->assertStringNotContainsString(self::EDITOR, $content);
        $this->assertStringNotContainsString('ckeditor.js', $content);
        $this->assertStringContainsString('<div class="academic-jobs-new">', $content);
        $this->assertMatchesRegularExpression('/<textarea[^>]*class="[^"]*\bace-ckeditor\b/', $content);
    }

    /**
     * @param list<string> $additionalConstants Read after the shipped constants, which is
     *        where an integrator overrides one.
     */
    private function setUpStaticTemplateSite(array $additionalConstants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                    ...$additionalConstants,
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

    /**
     * @param array<string, bool> $settings
     */
    private function setUpSiteSetSite(array $settings = []): void
    {
        // The page object only. "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::AGGREGATE_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
