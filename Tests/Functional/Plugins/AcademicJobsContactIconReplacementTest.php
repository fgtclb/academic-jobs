<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A site package replaces an icon of the job contact block by registering its own file
 * under the identifier the block renders.
 *
 * The replacement only works from a package that loads after `academic_jobs`, because the
 * icon registrations of all packages are merged in package order. That takes a fixture
 * extension, `test_job_contact_icon`, and a class of its own, since a fixture extension
 * is loaded for every test of the class that names it. The shipped icons are covered by
 * `AcademicJobsListAndDetailPluginTest`, on the same records.
 */
final class AcademicJobsContactIconReplacementTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use JobContactIconAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->testExtensionsToLoad[] = 'tests/test-job-contact-icon';
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsListAndDetailPlugin/jobPages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
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

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function contactBlockRendersThePhoneIconTheSitePackageRegistered(): void
    {
        $block = $this->contactBlock($this->renderDetailPageOfFirstJob());
        $phoneIcon = $this->iconImage($block, 'academic_jobs-contactPhone');
        $this->assertNotNull($phoneIcon, 'The phone row renders no phone icon.');
        $this->assertStringEndsWith('Icons/SitePhone.svg', $this->iconFilePath($phoneIcon));
        $emailIcon = $this->iconImage($block, 'academic_jobs-contactEmail');
        $this->assertNotNull($emailIcon, 'The e-mail row renders no e-mail icon.');
        $this->assertStringEndsWith('Icons/Email.svg', $this->iconFilePath($emailIcon));
        $this->assertContactBlockHasNoMissingIcon($block);
    }

    /**
     * Follows the detail link the list plugin renders for job 1, so the cHash stays valid.
     */
    private function renderDetailPageOfFirstJob(): string
    {
        $pattern = '#href="(?P<uri>[^"]*tx_academicjobs_detail%5Bjob%5D=1[^"]*)"#';
        $listContent = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(1, preg_match($pattern, $listContent, $matches), 'The list plugin rendered no detail link for job 1.');

        return $this->renderFrontendPage('https://www.acme.com' . htmlspecialchars_decode($matches['uri']));
    }
}
