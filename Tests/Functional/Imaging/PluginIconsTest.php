<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * The three content elements of this extension share one icon, and it has to be the same
 * in the page module (TCA `typeicon_classes`, written by the `icon` of the select item) and
 * in the new content element wizard (page TSconfig `iconIdentifier`). The two are
 * configured in different files, so nothing but this test keeps them in step.
 *
 * The icon is drawn in `currentColor` like the record icon, so it is asserted with the same
 * colour scheme checks.
 */
final class PluginIconsTest extends AbstractAcademicJobsTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const PLUGIN_ICON = 'tx-academicjobs-plugin-jobs';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function contentElementTypeDataProvider(): \Generator
    {
        yield 'new job form' => ['academicjobs_newjobform'];
        yield 'job list' => ['academicjobs_list'];
        yield 'job detail' => ['academicjobs_detail'];
    }

    #[Test]
    #[DataProvider('contentElementTypeDataProvider')]
    public function contentElementIsShownWithThePluginIcon(string $cType): void
    {
        $this->assertSame(
            self::PLUGIN_ICON,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null,
        );
    }

    #[Test]
    #[DataProvider('contentElementTypeDataProvider')]
    public function wizardItemIsShownWithTheIconOfTheContentElement(string $cType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PluginIcons/pages.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        $wizardIcon = $elements[$cType . '.']['iconIdentifier'] ?? null;
        $this->assertSame(self::PLUGIN_ICON, $wizardIcon);
        $this->assertSame(
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null,
            $wizardIcon,
            'The wizard shows another icon than the page module.',
        );
    }

    #[Test]
    public function pluginIconIsRegisteredWithTheColourSchemeAwareProvider(): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider(self::PLUGIN_ICON);
    }

    #[Test]
    public function pluginIconIsInlinedInBothMarkups(): void
    {
        $this->assertIconIsInlinedInBothMarkups(self::PLUGIN_ICON);
    }

    #[Test]
    public function pluginIconMarkupFollowsTheTextColour(): void
    {
        $this->assertIconMarkupFollowsTheTextColour(self::PLUGIN_ICON);
    }

    #[Test]
    public function renderedPluginIconCarriesItsIdentifier(): void
    {
        $this->assertRenderedIconCarriesItsIdentifier(self::PLUGIN_ICON);
    }
}
