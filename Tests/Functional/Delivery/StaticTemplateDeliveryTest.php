<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Delivery;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Proves that the static templates and the selectable page TSconfig files of this
 * extension deliver what they claim - the half of the delivery that works on every core
 * version this branch supports, and the only half TYPO3 v12 has at all.
 *
 * Both mechanisms fail silently when a path is wrong. A static template pointing at a
 * folder without any of the three files the core looks for contributes nothing, and an
 * unresolved page TSconfig include is not an error either, so a typo produces a site
 * that is configured differently than the integrator expects rather than a message.
 *
 * This extension adds one failure mode a single-component extension does not have. Its
 * three content elements share one `plugin.tx_academicjobs` block, so a component folder
 * holds nothing but an `include_static_file.txt` naming the shared folder. That file is
 * comma separated and is read by the very same code path for a set as for a
 * `sys_template` record, so a component that delivers nothing at all is a plausible
 * outcome of getting it wrong - and an invisible one.
 *
 * The `sys_template` record the probe is imported from carries `clear = 0` on purpose:
 * the backend button "Create a root TypoScript record" writes `clear = 3`, which discards
 * everything a site set contributed, and so does
 * `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class StaticTemplateDeliveryTest extends AbstractAcademicJobsTestCase
{
    use DeliveryProbeTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * Covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries are
     * comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_jobs/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->sharedConstantMarkup(),
            $body,
            'The aggregate static template did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            $this->sharedSetupMarkup(),
            $body,
            'The aggregate static template did not deliver the setup of the shared block.',
        );
        $this->assertStringContainsString(
            $this->sharedSettingMarkup(),
            $body,
            'The shared constants assign a different default for "detailPid" than the site settings declare.',
        );
    }

    /**
     * The point of the layout here: a component folder holds nothing but an
     * `include_static_file.txt` naming the shared folder, and that is what has to arrive
     * when only that component is selected. It is the file this extension would lose its
     * whole plugin configuration to, because an `include_static_file.txt` that does not
     * resolve includes nothing and says nothing.
     *
     * @param string $typoScriptPath The folder the component set points at, which is the
     *        folder registered as a static template with a trailing slash.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentStaticTemplateDeliversTheSharedTypoScriptThroughItsIncludeStaticFile(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
    ): void {
        $this->setUpSite(includeStaticFile: rtrim($typoScriptPath, '/'));

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->sharedConstantMarkup(),
            $body,
            sprintf('The static template "%s" did not deliver the constants of the shared block.', $typoScriptPath),
        );
        $this->assertStringContainsString(
            $this->sharedSetupMarkup(),
            $body,
            sprintf('The static template "%s" did not deliver the setup of the shared block.', $typoScriptPath),
        );
    }

    /**
     * The value installations stored before the configuration was cut per component. It
     * is the shared block itself, and it has to keep delivering it.
     */
    #[Test]
    public function sharedStaticTemplateStillDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_jobs/Configuration/TypoScript');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->sharedConstantMarkup(),
            $body,
            'The static template installations already store did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            $this->sharedSetupMarkup(),
            $body,
            'The static template installations already store did not deliver the setup of the shared block.',
        );
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertions below
     * cannot fail: they check that a content element is absent from `removeItems`, and an
     * empty list satisfies that just as well as a correct one.
     */
    #[Test]
    public function everyContentElementIsHiddenWithoutAPageTsConfigInclude(): void
    {
        $this->setUpSite();

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        foreach (self::componentDataProvider() as $component) {
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The content element "%s" is selectable although no page TSconfig enables it.', $component[1]),
            );
        }
    }

    /**
     * The page TSconfig file of a component re-enables its own content element and
     * nothing else. Without this the whole per-component split is decoration: one page
     * TSconfig file that re-enabled all three would pass every other assertion here.
     *
     * On TYPO3 v12 this is the only mechanism that can re-enable a content element at
     * all - there are no site sets there.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentPageTsConfigReEnablesItsOwnContentElementOnly(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $this->setUpSite(pageTsConfigInclude: $pageTsConfigPath);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The page TSconfig file "%s" did not re-enable "%s".', $pageTsConfigPath, $contentElementType),
        );
        $this->assertArrayHasKey(
            $contentElementType . '.',
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [],
            sprintf('The page TSconfig file "%s" did not deliver the wizard entry.', $pageTsConfigPath),
        );
        foreach (self::componentDataProvider() as $component) {
            if ($component[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The page TSconfig file "%s" also re-enabled "%s".', $pageTsConfigPath, $component[1]),
            );
        }
    }

    /**
     * The wizard element of a component has to arrive in the "show" list as well. The key
     * is read by TYPO3 v12 only, where `NewContentElementController::getWizards()` never
     * adds an element that is not listed in it - so an element definition without it is
     * invisible in the backend of every v12 installation, and nothing on the v13 leg
     * would notice.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentPageTsConfigAddsItsContentElementToTheWizardShowList(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $this->setUpSite(pageTsConfigInclude: $pageTsConfigPath);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);

        $this->assertSame(
            $contentElementType,
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['show'] ?? null,
            sprintf('The page TSconfig file "%s" did not add its content element to "show".', $pageTsConfigPath),
        );
    }

    /**
     * The aggregate page TSconfig file re-enables every component, in one entry.
     */
    #[Test]
    public function aggregatePageTsConfigReEnablesEveryContentElement(): void
    {
        $this->setUpSite(pageTsConfigInclude: 'EXT:academic_jobs/Configuration/TSconfig/Full/page.tsconfig');

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizardElements = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The aggregate page TSconfig did not re-enable the content element "%s".', $component[1]),
            );
            $this->assertArrayHasKey(
                $component[1] . '.',
                $wizardElements,
                sprintf('The aggregate page TSconfig did not deliver the wizard entry of "%s".', $component[1]),
            );
        }
    }
}
