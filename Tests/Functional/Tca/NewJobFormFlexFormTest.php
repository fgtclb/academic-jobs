<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The FlexForm of the new job form, as the backend resolves it for the content element,
 * from the file of the running core version.
 */
final class NewJobFormFlexFormTest extends AbstractAcademicJobsTestCase
{
    /**
     * The items of the message mode say what they do, in English and German, with the
     * values a stored element holds. The item `0` used to name the list page, which it
     * never looked at, and the German file translated a key the item does not use
     * (ACE-919).
     *
     * @return \Generator<string, array{0: string, 1: array<int, string>}>
     */
    public static function messageModeLabelsDataProvider(): \Generator
    {
        yield 'English' => ['default', [
            -1 => 'Use the site or TypoScript setting',
            1 => 'always',
            2 => 'never',
            0 => 'Suppress when a redirect page is set',
        ]];
        yield 'German' => ['de', [
            -1 => 'Site- oder TypoScript-Einstellung verwenden',
            1 => 'immer',
            2 => 'niemals',
            0 => 'Unterdrücken, wenn eine Weiterleitungsseite gesetzt ist',
        ]];
    }

    /**
     * @param array<int, string> $expected Label by value.
     */
    #[Test]
    #[DataProvider('messageModeLabelsDataProvider')]
    public function theItemsOfTheMessageModeSayWhatTheyDo(string $locale, array $expected): void
    {
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $dataStructure = $flexFormTools->parseDataStructureByIdentifier(
            $flexFormTools->getDataStructureIdentifier(
                $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'],
                'tt_content',
                'pi_flexform',
                ['CType' => 'academicjobs_newjobform', 'list_type' => ''],
            ),
        );
        $field = $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.flashMessageCreationMode'] ?? [];
        $languageService = $this->get(LanguageServiceFactory::class)->create($locale);

        $labels = [];
        foreach ($field['config']['items'] ?? [] as $item) {
            $labels[(int)$item['value']] = $languageService->sL((string)$item['label']);
        }

        $this->assertSame($expected, $labels);
    }
}
