<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation the three plugins of
 * this extension render, on TYPO3 v12 and v13: a label comes from `locallang.xlf`, an
 * override of the extension (`plugin.tx_academicjobs`) replaces it, and an override of the
 * plugin (`plugin.tx_academicjobs_<plugin>`) replaces both.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_jobs` instead.
 *
 * They also keep the labels of a language file, overrides included, for the rest of the
 * request: once one translation has read the right path, the ones after it show its
 * overrides whatever name they pass. Before the change, the job item and the job
 * information translated the job type and the employment type without an extension name,
 * which took the name of the plugin request and so read the right path; the only job of the
 * fixture therefore has neither. The controller translates its messages in PHP with the
 * right name too, but only for a job that is not found or a form that is sent - the one case
 * that asserts such a message renders nothing else of the file before it. That message was
 * translated with the right name before these tests existed, so it passes either way; it is
 * here so that it keeps doing so.
 *
 * Apart from those, no translation of these files had the right name before the change, so
 * every override case failed on its own. Since then, a case that is not the first
 * translation of its file in the request would pass even if its own call lost the name
 * again; the check of the extension names of all translations holds those calls.
 *
 * The list is on `/home`, the detail on `/job-detail`, the form on `/new-job`.
 */
final class AcademicJobsLabelOverrideTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsLabelOverride/jobPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpSite(string $setup): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function renderPluginPage(string $page, string $setup, ?string $path = null): string
    {
        $this->setUpSite($setup);
        $content = $this->renderFrontendPage('https://www.acme.com' . ($path ?? '/home'));
        if ($path !== null) {
            // Rendered as given.
        } elseif ($page === 'detail') {
            // The detail link carries a cHash, so it is taken from the list rather than built.
            $this->assertSame(1, preg_match('#href="(?P<uri>[^"]*tx_academicjobs_detail%5Bjob%5D=1[^"]*)"#', $content, $matches));
            $content = $this->renderFrontendPage('https://www.acme.com' . htmlspecialchars_decode($matches['uri']));
        } elseif ($page === 'newjobform') {
            $content = $this->renderFrontendPage('https://www.acme.com/new-job');
        }

        return (string)preg_replace('/\s+/', ' ', $content);
    }

    /**
     * @param array<string, string> $overrides TypoScript path => label
     */
    private function localLang(string $key, array $overrides): string
    {
        $setup = '';
        foreach ($overrides as $path => $label) {
            $setup .= $path . '._LOCAL_LANG.default.' . $key . ' = ' . $label . LF;
        }
        return $setup;
    }

    /**
     * Every kind of translation the plugins render: a label whose key is built from a
     * variable, a plain one, one translated inside the argument of a partial, one kept in a
     * variable for the option of a select, and the options themselves, which the controller
     * translates from the full `LLL:` references of their TCA items.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string, 4?: string}>
     */
    public static function pluginTranslationDataProvider(): \Generator
    {
        yield 'list, property of a job, key from a variable' => [
            'list', 'jobs.companyName', 'Organization', '<b>%s:</b> <span> Acme University </span>',
        ];
        yield 'list, text of the job link' => [
            'list', 'jobs.linkText', 'To the job posting', '<a href="https://jobs.example.org/research-assistant"> %s </a>',
        ];
        yield 'detail, back link' => [
            'detail', 'jobs.back', 'Back to job list', '<a href="/home"> %s </a>',
        ];
        yield 'detail, contact heading, translated in the argument of a partial' => [
            'detail', 'jobs.contact', 'Contact', '<h2 class="contact-title"> %s </h2>',
        ];
        yield 'form, field label, key from a variable' => [
            'newjobform', 'create.job.title.label', 'Job title / Thesis title', '<label class="form-label" for="job.title"> %s ',
        ];
        yield 'form, empty option of a select, kept in a variable' => [
            'newjobform', 'create.job.type.none', 'Please choose', '<option value="0">%s</option>',
        ];
        yield 'form, option of a select, translated in PHP from the item label of TCA' => [
            'newjobform', 'tx_academicjobs_domain_model_job.jobtype.job', 'Job', '<option value="1">%s</option>',
        ];
        yield 'detail without a job, message translated in PHP' => [
            'detail', 'tx_academicjobs.fe.alert.job_not_found.body', 'No job advert could be found.', '<div class="academic-jobs-detail"> [ERROR] %s <a href="/home">', '/job-detail',
        ];
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheLanguageFile(string $plugin, string $key, string $label, string $markup, ?string $path = null): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderPluginPage($plugin, '', $path));
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheExtension(string $plugin, string $key, string $label, string $markup, ?string $path = null): void
    {
        $content = $this->renderPluginPage($plugin, $this->localLang($key, [
            'plugin.tx_academicjobs' => 'Extension label',
        ]), $path);

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfThePlugin(string $plugin, string $key, string $label, string $markup, ?string $path = null): void
    {
        $content = $this->renderPluginPage($plugin, $this->localLang($key, [
            'plugin.tx_academicjobs_' . $plugin => 'Plugin label',
        ]), $path);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $plugin, string $key, string $label, string $markup, ?string $path = null): void
    {
        $content = $this->renderPluginPage($plugin, $this->localLang($key, [
            'plugin.tx_academicjobs' => 'Extension label',
            'plugin.tx_academicjobs_' . $plugin => 'Plugin label',
        ]), $path);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }

    /**
     * The placeholder of a text field has no label in `locallang.xlf`; a site that wants one
     * sets it, and it lands in the attribute.
     *
     * @return \Generator<string, array{0: array<string, string>, 1: string}>
     */
    public static function placeholderDataProvider(): \Generator
    {
        yield 'extension' => [['plugin.tx_academicjobs' => 'Extension label'], 'Extension label'];
        yield 'plugin' => [['plugin.tx_academicjobs_newjobform' => 'Plugin label'], 'Plugin label'];
        yield 'plugin over extension' => [
            ['plugin.tx_academicjobs' => 'Extension label', 'plugin.tx_academicjobs_newjobform' => 'Plugin label'],
            'Plugin label',
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('placeholderDataProvider')]
    #[Test]
    public function theFormRendersAPlaceholderSetThroughLocalLang(array $overrides, string $expected): void
    {
        $content = $this->renderPluginPage('newjobform', $this->localLang('edit.job.title.placeholder', $overrides));

        // The order of the attributes is up to Fluid, and differs between its versions.
        $this->assertSame(1, preg_match('#<input [^>]*id="job\.title"[^>]*>#', $content, $matches));
        $this->assertStringContainsString('placeholder="' . $expected . '"', $matches[0]);
    }
}
