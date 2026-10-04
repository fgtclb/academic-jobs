<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * A site package replaces an icon of the job views by registering its own file under the
 * identifier the views render, in its own `Configuration/FrontendIcons.php`. A
 * replacement left in `Configuration/Icons.php` does not reach the job views, which show
 * the shipped file.
 *
 * The fixture `tests/job-icons` is that site package: it replaces
 * `academic_jobs-contactPhone` and `academic_jobs-companyName` in the file of the
 * frontend, and `academic_jobs-contactEmail` and `academic_jobs-workLocation` in the file
 * of the backend. The replacement only works from a package that loads after
 * `academic_jobs`, and a TYPO3 v14 test instance orders the packages by their keys, so the
 * first test asserts that order. The shipped icons are covered by
 * `AcademicJobsListAndDetailPluginTest`, on the same records.
 */
final class AcademicJobsIconReplacementTest extends AbstractAcademicJobsTestCase
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
        $this->testExtensionsToLoad[] = 'tests/job-icons';
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
    public function theSitePackageLoadsAfterTheExtension(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());

        $this->assertGreaterThan(
            array_search('academic_jobs', $packageKeys, true),
            array_search('test_job_icons', $packageKeys, true),
        );
    }

    #[Test]
    public function aFrontendIconOfTheSitePackageReachesTheJobList(): void
    {
        $this->assertEveryIconShows($this->renderFrontendPage('https://www.acme.com/home'), 'academic_jobs-companyName', 'Icons/SiteCompany.svg');
    }

    #[Test]
    public function aReplacementInTheBackendFileDoesNotReachTheJobList(): void
    {
        // The extension no longer registers the identifier there, so this is the
        // replacement of the fixture, read by the backend registry.
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered('academic_jobs-workLocation'));
        $this->assertEveryIconShows($this->renderFrontendPage('https://www.acme.com/home'), 'academic_jobs-workLocation', 'Icons/Location.svg');
    }

    #[Test]
    public function aFrontendIconOfTheSitePackageReachesTheDetailView(): void
    {
        $content = $this->renderDetailPageOfFirstJob();

        $this->assertEveryIconShows($content, 'academic_jobs-companyName', 'Icons/SiteCompany.svg');
        $this->assertEveryIconShows($content, 'academic_jobs-workLocation', 'Icons/Location.svg');
    }

    #[Test]
    public function contactBlockRendersThePhoneIconTheSitePackageRegistered(): void
    {
        $block = $this->contactBlock($this->renderDetailPageOfFirstJob());
        $phoneIcon = $this->iconImage($block, 'academic_jobs-contactPhone');
        $this->assertNotNull($phoneIcon, 'The phone row renders no phone icon.');
        $this->assertStringEndsWith('Icons/SitePhone.svg', $this->iconFilePath($phoneIcon));
        // Replaced in `Icons.php` only, so the shipped file.
        $emailIcon = $this->iconImage($block, 'academic_jobs-contactEmail');
        $this->assertNotNull($emailIcon, 'The e-mail row renders no e-mail icon.');
        $this->assertStringEndsWith('Icons/Email.svg', $this->iconFilePath($emailIcon));
        $this->assertContactBlockHasNoMissingIcon($block);
    }

    /**
     * Every icon with the identifier on the page shows the file, and there is at least one.
     *
     * @param non-empty-string $fileSuffix
     */
    private function assertEveryIconShows(string $content, string $identifier, string $fileSuffix): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        $images = (new \DOMXPath($document))->query(sprintf('//*[@data-identifier="%s"]//img', $identifier));
        $this->assertNotFalse($images);
        $this->assertGreaterThan(0, $images->length, sprintf('The page renders no "%s" icon.', $identifier));
        foreach ($images as $image) {
            $this->assertInstanceOf(\DOMElement::class, $image);
            $this->assertStringEndsWith($fileSuffix, $this->iconFilePath($image));
        }
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
