<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
     * @return list<string>
     */
    private function fieldNames(string $cType, string $sheetName): array
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure($cType);

        return array_map(strval(...), array_keys($dataStructure['sheets'][$sheetName]['ROOT']['el'] ?? []));
    }
}
