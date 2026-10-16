<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A listener of `ModifyTcaSelectFieldItemsEvent` that reads `row` without a fallback, run
 * through the job form of the frontend.
 *
 * The form builds the options of its two selects with the item providers of the backend,
 * which dispatch the event. It has no record to pass, and passed no `row` at all, so
 * the listener of the fixture extension `test_job_select_items_listener` raised a warning
 * and then a `TypeError` (ACE-922). The form passes an empty row now, and the listener
 * takes the page of the form for a record that is not stored yet.
 */
final class AcademicJobsNewJobFormSelectItemsListenerTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-job-select-items-listener';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormPlugin/newJobFormPage.csv');
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
    public function bothSelectsOfferTheItemOfAListenerThatReadsTheRow(): void
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML(
            $this->renderFrontendPage('https://www.acme.com/home'),
            LIBXML_NOERROR | LIBXML_NOWARNING,
        ));
        $xpath = new \DOMXPath($document);
        foreach (['employmentType', 'type'] as $identifier) {
            $options = $xpath->query(sprintf('//select[@id="job.%s"]/option[@value="99"]', $identifier));
            $this->assertNotFalse($options);
            $this->assertSame(1, $options->length, sprintf('Select "%s" lacks the item of the listener.', $identifier));
            $this->assertSame('Offered on page 2', $options->item(0)?->textContent);
        }
    }
}
