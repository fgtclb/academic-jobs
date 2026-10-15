<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Guards the FlexForm data structure of the plugins against a shape that only
 * works on one of the supported core versions.
 *
 * @see PluginFlexFormDataStructureTrait
 */
final class PluginFlexFormTest extends AbstractAcademicJobsTestCase
{
    use PluginFlexFormDataStructureTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginContentTypeDataProvider(): \Generator
    {
        yield 'New job form' => ['academicjobs_newjobform'];
        yield 'Job list' => ['academicjobs_list'];
    }

    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function pluginFlexFormIsResolvedForContentType(string $cType): void
    {
        $this->assertPluginFlexFormIsResolved($cType);
    }

    /**
     * The existing fields keep the first sheet, so the values stored before the
     * pagination sheet existed stay where the data structure expects them.
     */
    #[Test]
    public function listKeepsItsFieldsOnTheFirstSheet(): void
    {
        $this->assertSame(
            ['settings.job.type', 'settings.showHiddenRecords'],
            $this->fieldNames('academicjobs_list', 'sDEF'),
        );
    }

    #[Test]
    public function listOffersPaginationOnASheetOfItsOwn(): void
    {
        $this->assertPluginFlexFormIsResolved('academicjobs_list', 'pagination');
        $this->assertSame(
            ['settings.paginationEnabled', 'settings.pagination.resultsPerPage'],
            $this->fieldNames('academicjobs_list', 'pagination'),
        );
    }

    /**
     * What an element gets that nobody configured: no pagination, and ten jobs a page
     * once it is switched on. A results per page below one is not accepted.
     */
    #[Test]
    public function paginationFieldsDefaultToOffAndTen(): void
    {
        $fields = $this->resolvePluginFlexFormDataStructure('academicjobs_list')['sheets']['pagination']['ROOT']['el'] ?? [];

        $this->assertSame('0', (string)($fields['settings.paginationEnabled']['config']['default'] ?? null));
        $this->assertSame('10', (string)($fields['settings.pagination.resultsPerPage']['config']['default'] ?? null));
        $this->assertSame('1', (string)($fields['settings.pagination.resultsPerPage']['config']['range']['lower'] ?? null));
    }

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
        $field = $this->resolvePluginFlexFormDataStructure('academicjobs_newjobform')['sheets']['sDEF']['ROOT']['el']['settings.flashMessageCreationMode'] ?? [];
        $languageService = $this->get(LanguageServiceFactory::class)->create($locale);

        $labels = [];
        foreach ($field['config']['items'] ?? [] as $item) {
            $labels[(int)$item['value']] = $languageService->sL((string)$item['label']);
        }

        $this->assertSame($expected, $labels);
    }

    /**
     * @return list<string>
     */
    private function fieldNames(string $cType, string $sheetName): array
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure($cType);

        return array_map(strval(...), array_keys($dataStructure['sheets'][$sheetName]['ROOT']['el'] ?? []));
    }
}
