<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the job image of the job list through the shared image partial of academic_base.
 *
 * The job image is a generic image field, so the list item asks for the `card` preset:
 * four sources and a fallback processed to 690 pixels. Editors do use the field for an
 * employer logo, which is why the vector case matters here as much as in the partner
 * lists - a logo shipped as SVG is passed through unprocessed, whatever the preset.
 *
 * The fixture gives the first job an 800 x 600 photo, the second an SVG logo and neither a
 * crop, so `default` stays free-ratio and nothing is cut.
 */
final class AcademicJobsImageRenderingTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/AcademicJobsImage/';

    private const CARD_SOURCES = 4;
    private const CARD_FALLBACK_WIDTH = 690;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'Files/landscape.jpg', $folder . '/landscape.jpg');
        copy(self::FIXTURES . 'Files/logo.svg', $folder . '/logo.svg');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'jobImagePages.csv');
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
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    /**
     * @return array{0: \DOMElement, 1: \DOMElement} the job with the photo and the one with the
     *         vector logo, in the order the plugin renders them
     */
    private function items(\DOMXPath $xpath): array
    {
        $items = [];
        foreach ($this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-jobs-item ')]") as $item) {
            $this->assertInstanceOf(\DOMElement::class, $item);
            $items[] = $item;
        }
        $this->assertCount(2, $items);

        return [$items[0], $items[1]];
    }

    #[Test]
    public function jobListItemShowsTheImageAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [$withPhoto] = $this->items($xpath);
        $this->assertRendersResponsivePicture(
            $xpath,
            $withPhoto,
            self::CARD_SOURCES,
            self::CARD_FALLBACK_WIDTH,
            'card-img-top img-fluid',
            'The campus of Acme University',
        );
    }

    #[Test]
    public function jobListItemPassesAVectorLogoThrough(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [, $withVectorLogo] = $this->items($xpath);
        $this->assertRendersUnprocessedSvg(
            $xpath,
            $withVectorLogo,
            '/logo.svg',
            'card-img-top img-fluid',
            'The logo of Acme Institute',
        );
    }

    #[Test]
    public function jobListItemWithoutAnImageShowsNone(): void
    {
        $this->setUpTestCase();
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->update('tx_academicjobs_domain_model_job', ['image' => 0], ['uid' => 1]);
        $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->delete('sys_file_reference', ['uid' => 1]);

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [$withoutImage] = $this->items($xpath);
        $this->assertRendersNoImage($xpath, $withoutImage, 'card-img-top img-fluid');
    }

    /**
     * The academic_base partials are registered below the key of the project constant, so a
     * project overrides `Academic/Image.html` the way it overrides any partial of this
     * extension.
     */
    #[Test]
    public function projectOverrideOfTheSharedImagePartialWins(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'jobImagePages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ImagePartialOverride.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(2, substr_count($content, '<span class="project-image-override">card</span>'));
        $this->assertStringNotContainsString('<picture', $content);
    }

    /**
     * This extension registers its partials in `page.10` as well, so a page object that
     * renders `Job/Item` finds that partial - and would then fail on the `Academic/Image`
     * it renders. The academic_base path is therefore registered next to it, with a
     * negative key of its own, because `page.10` belongs to the site package.
     *
     * The rendering fixture of every other test here replaces `page.10` wholesale, which
     * is why this one pins the registration through a probe rather than through a render.
     */
    #[Test]
    public function pageObjectRegistrationKeepsBothPartialRootPaths(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'jobImagePages.csv');
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
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/PageObjectProbe.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString(
            '<div id="shared-partial-path">EXT:academic_base/Resources/Private/Partials/</div>',
            $content,
        );
        $this->assertStringContainsString(
            '<div id="own-partial-path">EXT:academic_jobs/Resources/Private/Partials/</div>',
            $content,
        );
    }
}
