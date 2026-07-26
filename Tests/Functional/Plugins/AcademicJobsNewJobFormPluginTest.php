<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Renders the `academicjobs_newjobform` plugin in the frontend.
 *
 * This guards the `record` view variable: TYPO3 v14 renders the header of the
 * `EXT:fluid_styled_content` `Header/All` partial with `{record -> f:render.text(...)}`,
 * which raises an exception when no record object is available. Extbase plugin views
 * assign only `data`, so without the record the whole plugin fails to render on v14,
 * while it still renders on v13 whose partial reads `data`.
 */
final class AcademicJobsNewJobFormPluginTest extends AbstractAcademicJobsTestCase
{
    use SiteBasedTestTrait;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'MAIL' => [
            'transport' => 'null',
        ],
        'FE' => [
            'debug' => false,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpFrontendRootPageForTestCase(): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormPlugin/newJobFormPage.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    private function renderHomePage(): string
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://www.acme.com/home'),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    #[Test]
    public function newJobFormPluginIsRendered(): void
    {
        $this->setUpTestCase();

        $content = $this->renderHomePage();

        $this->assertMatchesRegularExpression('@<form [^>]*enctype="multipart/form-data"@', $content);
        $this->assertStringContainsString('tx_academicjobs_newjobform[job][title]', $content);
    }

    #[Test]
    public function newJobFormPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase();
        // Rendering a header is what requires the `record` view variable on TYPO3 v14.
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => 'Post a new job'], ['uid' => 1]);

        $this->assertStringContainsString('Post a new job', $this->renderHomePage());
    }
}
