<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\TcaHelperMethodsTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The job types as an editor gets them in the backend form: in the field "Job Type" of a
 * job record and in the option "Job Type" of the job list, which filters the list by it.
 *
 * The fixture extension `test_job_select_items_listener` adds the type `99` to the job
 * record the way a project does, with a listener of `ModifyTcaSelectFieldItemsEvent` that
 * checks the table and the field of the job. The job list offered a fixed list of the
 * three shipped types, so such a type could be stored on a job but not chosen for a list
 * (ACE-923).
 *
 * The forms are compiled the way FormEngine compiles them when the record is opened, see
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

    #[Test]
    public function theJobListOffersNoneTheShippedTypesAndTheTypeOfAListener(): void
    {
        $this->assertSame(
            [0 => 'None', 1 => 'Job', 2 => 'Sidejob', 3 => 'Thesis', 99 => 'Offered on page 1'],
            $this->jobListTypeItems(1),
        );
    }

    /**
     * A single select drops a stored value that is not among its items and shows an
     * item for an invalid value instead, so the next save of the element would have
     * lost the type.
     */
    #[Test]
    public function theJobListKeepsAStoredTypeOfAListenerSelected(): void
    {
        $result = $this->compile('tt_content', 2);

        $this->assertSame(
            ['99'],
            $result['databaseRow']['pi_flexform']['data']['sDEF']['lDEF']['settings.job.type']['vDEF'] ?? null,
        );
        $this->assertSame(
            [0 => 'None', 1 => 'Job', 2 => 'Sidejob', 3 => 'Thesis', 99 => 'Offered on page 1'],
            $this->itemLabels($this->jobListTypeField($result)['config']['items'] ?? null),
        );
    }

    /**
     * The job list hands a listener the table and the field of the job record, so a
     * listener written for the record changes both selects. It tells the two apart by
     * `flexParentDatabaseRow`, which only the job list passes.
     */
    #[Test]
    public function aListenerGetsTheJobRecordFieldAndTellsTheJobListApart(): void
    {
        $calls = [];
        $this->addListener(static function (ModifyTcaSelectFieldItemsEvent $event) use (&$calls): void {
            $parameters = $event->getParameters();
            if ($parameters['table'] === self::JOB_TABLE && $parameters['field'] === 'type') {
                $calls[] = [
                    'content element' => $parameters['flexParentDatabaseRow']['uid'] ?? null,
                    'values' => array_map(
                        static fn(mixed $item): string => (string)($item['value'] ?? ''),
                        $parameters['items'],
                    ),
                ];
            }
        });

        $this->compile('tt_content', 1);
        $this->compile(self::JOB_TABLE, 1);

        $this->assertSame(
            [
                ['content element' => 1, 'values' => ['1', '2', '3', '99']],
                ['content element' => null, 'values' => ['1', '2', '3', '99']],
            ],
            $calls,
        );
    }

    /**
     * "None" is the item of the job list, not a job type: listeners never see it, so a
     * listener that replaces the types cannot take it away.
     */
    #[Test]
    public function theJobListKeepsNoneWhenAListenerReplacesTheTypes(): void
    {
        $this->addListener(static function (ModifyTcaSelectFieldItemsEvent $event): void {
            $parameters = $event->getParameters();
            if ($parameters['table'] === self::JOB_TABLE && $parameters['field'] === 'type') {
                $parameters['items'] = [['label' => 'Internship', 'value' => 10]];
                $event->setParameters($parameters);
            }
        });

        $this->assertSame([0 => 'None', 10 => 'Internship'], $this->jobListTypeItems(1));
    }

    /**
     * A type of a listener with the value of "None" is offered by the record, but not by
     * the job list, where `0` is the choice of no filter already.
     */
    #[Test]
    public function theJobListLeavesOutATypeWithTheValueOfNone(): void
    {
        $this->addListener(static function (ModifyTcaSelectFieldItemsEvent $event): void {
            $parameters = $event->getParameters();
            if ($parameters['table'] === self::JOB_TABLE && $parameters['field'] === 'type') {
                $parameters['items'][] = ['label' => 'Any', 'value' => 0];
                $event->setParameters($parameters);
            }
        });

        $this->assertSame(
            [0 => 'None', 1 => 'Job', 2 => 'Sidejob', 3 => 'Thesis', 99 => 'Offered on page 1'],
            $this->jobListTypeItems(1),
        );
    }

    /**
     * Page TSconfig of the record field does not reach the job list, which needs the same
     * lines below its FlexForm field, as its manual says.
     */
    #[Test]
    public function theJobListTakesPageTsConfigOfItsOwnField(): void
    {
        $this->setPageTsConfig('TCEFORM.tx_academicjobs_domain_model_job.type.removeItems = 3');
        $this->assertSame(
            [1 => 'Job', 2 => 'Sidejob', 99 => 'Offered on page 2'],
            $this->itemLabels($this->compile(self::JOB_TABLE, 1)['processedTca']['columns']['type']['config']['items'] ?? null),
        );
        $this->assertSame(
            [0 => 'None', 1 => 'Job', 2 => 'Sidejob', 3 => 'Thesis', 99 => 'Offered on page 1'],
            $this->jobListTypeItems(1),
        );

        $this->setPageTsConfig(
            "TCEFORM.tx_academicjobs_domain_model_job.type.removeItems = 3\n"
            . 'TCEFORM.tt_content.pi_flexform.academicjobs_list.sDEF.settings\\.job\\.type.removeItems = 3',
        );
        $this->assertSame(
            [0 => 'None', 1 => 'Job', 2 => 'Sidejob', 99 => 'Offered on page 1'],
            $this->jobListTypeItems(1),
        );
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
     * @return array<int|string, string>
     */
    private function jobListTypeItems(int $contentUid): array
    {
        return $this->itemLabels($this->jobListTypeField($this->compile('tt_content', $contentUid))['config']['items'] ?? null);
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function jobListTypeField(array $result): array
    {
        $field = $result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el']['settings.job.type'] ?? null;
        $this->assertIsArray($field);

        return $field;
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

    private function setPageTsConfig(string $tsConfig): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['TSconfig' => $tsConfig], ['uid' => 1]);
        $this->get(CacheManager::class)->flushCaches();
    }

    private function addListener(\Closure $listener): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('job-type-selects-test-listener', $listener);
        $this->get(ListenerProvider::class)->addListener(ModifyTcaSelectFieldItemsEvent::class, 'job-type-selects-test-listener');
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
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
