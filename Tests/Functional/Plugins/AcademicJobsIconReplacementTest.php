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
 * the shipped glyph.
 *
 * The fixture `tests/job-icons` is that site package: it replaces
 * `tx-academicjobs-info-contact-phone` and `tx-academicjobs-info-company-name` in the
 * file of the frontend, and `tx-academicjobs-info-contact-email` and
 * `tx-academicjobs-info-work-location` in the file of the backend. It registers its files
 * with the core provider, so they render as an `<img>`, while the shipped glyphs are
 * inlined. The replacement only works from a package that loads after `academic_jobs`,
 * and a TYPO3 v14 test instance orders the packages by their keys, so the first test
 * asserts that order. The shipped icons are covered by
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
        $this->assertEveryIconShowsTheImage($this->renderFrontendPage('https://www.acme.com/home'), 'tx-academicjobs-info-company-name', 'Icons/SiteCompany.svg');
    }

    #[Test]
    public function aReplacementInTheBackendFileDoesNotReachTheJobList(): void
    {
        // The extension no longer registers the identifier there, so this is the
        // replacement of the fixture, read by the backend registry.
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered('tx-academicjobs-info-work-location'));
        $this->assertEveryIconShowsTheSharedGlyph($this->renderFrontendPage('https://www.acme.com/home'), 'tx-academicjobs-info-work-location', 'location.svg');
    }

    #[Test]
    public function aFrontendIconOfTheSitePackageReachesTheDetailView(): void
    {
        $content = $this->renderDetailPageOfFirstJob();

        $this->assertEveryIconShowsTheImage($content, 'tx-academicjobs-info-company-name', 'Icons/SiteCompany.svg');
        $this->assertEveryIconShowsTheSharedGlyph($content, 'tx-academicjobs-info-work-location', 'location.svg');
    }

    #[Test]
    public function contactBlockRendersThePhoneIconTheSitePackageRegistered(): void
    {
        $block = $this->contactBlock($this->renderDetailPageOfFirstJob());
        $this->assertIconShowsTheImage($this->iconDrawing($block, 'tx-academicjobs-info-contact-phone'), 'Icons/SitePhone.svg');
        // Replaced in `Icons.php` only, so the shipped glyph.
        $this->assertIconShowsTheSharedGlyph($this->iconDrawing($block, 'tx-academicjobs-info-contact-email'), 'email.svg');
        $this->assertContactBlockHasNoMissingIcon($block);
    }

    /**
     * Every icon with the identifier on the page is an image of the file, and there is at
     * least one.
     *
     * @param non-empty-string $fileSuffix
     */
    private function assertEveryIconShowsTheImage(string $content, string $identifier, string $fileSuffix): void
    {
        foreach ($this->iconDrawingsOnPage($content, $identifier) as $drawing) {
            $this->assertIconShowsTheImage($drawing, $fileSuffix);
        }
    }

    /**
     * Every icon with the identifier on the page is the inlined shared glyph, and there is
     * at least one.
     */
    private function assertEveryIconShowsTheSharedGlyph(string $content, string $identifier, string $file): void
    {
        foreach ($this->iconDrawingsOnPage($content, $identifier) as $drawing) {
            $this->assertIconShowsTheSharedGlyph($drawing, $file);
        }
    }

    /**
     * @return non-empty-list<\DOMElement>
     */
    private function iconDrawingsOnPage(string $content, string $identifier): array
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        $nodes = (new \DOMXPath($document))->query(sprintf('//*[@data-identifier="%s"]//*[self::svg or self::img]', $identifier));
        $this->assertNotFalse($nodes);
        $drawings = [];
        foreach ($nodes as $node) {
            $this->assertInstanceOf(\DOMElement::class, $node);
            $drawings[] = $node;
        }
        $this->assertNotSame([], $drawings, sprintf('The page renders no "%s" icon.', $identifier));

        return $drawings;
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
