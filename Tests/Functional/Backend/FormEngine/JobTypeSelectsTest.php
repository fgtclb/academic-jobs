<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\TcaHelperMethodsTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The job types as an editor gets them in the backend form, in the field "Job Type" of a
 * job record.
 *
 * The fixture extension `test_job_select_items_listener` adds the type `99` to the job
 * record the way a project does, with a listener of `ModifyTcaSelectFieldItemsEvent` that
 * checks the table and the field of the job.
 *
 * The form is compiled the way FormEngine compiles it when the record is opened, see
 * docs/architecture/backend-select-items.md.
 */
final class JobTypeSelectsTest extends AbstractAcademicJobsTestCase
{
    use TcaHelperMethodsTrait;

    private const JOB_TABLE = 'tx_academicjobs_domain_model_job';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-job-select-items-listener';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/JobTypeSelects/records.csv');
        $this->setUpBackendUser(1);
    }

    protected function tearDown(): void
    {
        $this->restoreTCABackup(false);
        parent::tearDown();
    }

    /**
     * An item a project declares in the TCA of a job field stays in front of the shipped
     * ones. They used to be merged by position, so the first shipped item replaced the
     * declared one, and a declared item with the value of a shipped one is not repeated.
     */
    #[Test]
    public function theJobRecordKeepsTheItemsDeclaredInTheTca(): void
    {
        $this->createTCABackup(false);
        $tca = $GLOBALS['TCA'];
        $tca[self::JOB_TABLE]['columns']['type']['config']['items'] = [
            ['label' => 'Not decided', 'value' => 0],
            ['label' => 'Position', 'value' => 1],
        ];
        $tca[self::JOB_TABLE]['columns']['employment_type']['config']['items'] = [
            ['label' => 'Not decided', 'value' => 0],
        ];
        $this->updateGlobalTCA($tca);

        $columns = $this->compile(self::JOB_TABLE, 1)['processedTca']['columns'];

        $this->assertSame(
            [0 => 'Not decided', 1 => 'Position', 2 => 'Sidejob', 3 => 'Thesis', 99 => 'Offered on page 2'],
            $this->itemLabels($columns['type']['config']['items'] ?? null),
        );
        $this->assertSame(
            [0 => 'Not decided', 1 => 'Full-time', 2 => 'Part-time', 99 => 'Offered on page 2'],
            $this->itemLabels($columns['employment_type']['config']['items'] ?? null),
        );
    }

    /**
     * The items, value to label, in the order offered.
     *
     * @return array<int|string, string>
     */
    private function itemLabels(mixed $items): array
    {
        $this->assertIsArray($items);
        $labels = [];
        foreach ($items as $item) {
            $item = is_array($item) ? $item : $item->toArray();
            $labels[(string)$item['value']] = (string)$item['label'];
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(string $tableName, int $uid): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => $tableName,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
    }
}
