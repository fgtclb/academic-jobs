<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

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
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    #[Test]
    public function newJobFormPluginIsRendered(): void
    {
        $this->setUpTestCase();

        $content = $this->renderHomePage();

        $this->assertMatchesRegularExpression('@<form [^>]*enctype="multipart/form-data"@', $content);
        $this->assertStringContainsString('tx_academicjobs_newjobform[job][title]', $content);
    }

    /**
     * Both selects of the form are single valued `int` properties whose item
     * sets start at 1, so without an option for "nothing chosen" the browser
     * selects the first real one and a visitor who never touched the control
     * still submits it. The prepended option carries the value `0`, which is
     * what the column holds for an unset field and what the `int` property
     * accepts - an empty string reaches `IntegerConverter` as `null` and the
     * setter raises a `TypeError` the property mapper turns into an exception.
     */
    #[Test]
    public function bothSelectsOfTheFormOpenOnAnUnchosenOption(): void
    {
        $this->setUpTestCase();

        $content = $this->renderHomePage();

        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        foreach (['employmentType', 'type'] as $identifier) {
            $selects = $xpath->query(sprintf('//select[@id="job.%s"]', $identifier));
            $this->assertNotFalse($selects);
            $this->assertSame(1, $selects->length, sprintf('Missing select for "%s".', $identifier));
            $options = $xpath->query('.//option', $selects->item(0));
            $this->assertNotFalse($options);
            $unchosen = $options->item(0);
            $this->assertInstanceOf(\DOMElement::class, $unchosen);
            $this->assertSame(
                '0',
                $unchosen->getAttribute('value'),
                sprintf('Select "%s" does not open on the unchosen value.', $identifier),
            );
            $this->assertSame(
                'Please choose',
                $unchosen->textContent,
                sprintf('Select "%s" does not label its unchosen option.', $identifier),
            );
            $selected = $xpath->query('.//option[@selected]', $selects->item(0));
            $this->assertNotFalse($selected);
            $this->assertSame(
                0,
                $selected->length,
                sprintf('Select "%s" preselects an option.', $identifier),
            );
        }
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
