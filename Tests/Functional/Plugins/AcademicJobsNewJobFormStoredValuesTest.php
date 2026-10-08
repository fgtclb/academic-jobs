<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits the `academicjobs_newjobform` plugin and reads what it stored: a value the
 * visitor did not choose is refused, and the values the visitor chose are stored as
 * chosen.
 *
 * The instance runs in the time zone `Europe/Berlin`, so a day stored at midnight UTC
 * instead of midnight of the server time zone shows as a difference of hours.
 */
final class AcademicJobsNewJobFormStoredValuesTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const TIME_ZONE = 'Europe/Berlin';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * All properties the `job` validation set marks as required.
     */
    private const REQUIRED_JOB_VALUES = [
        'title' => 'Research assistant',
        'description' => 'A new job description',
        'companyName' => 'ACME Inc.',
        'employmentStartDate' => '2027-04-01',
        'employmentType' => '1',
        'type' => '1',
    ];

    /**
     * The bootstrap of the instance sets the time zone of the process, and keeps it for
     * every later test class that configures none.
     */
    private string $previousTimeZone = 'UTC';

    protected function setUp(): void
    {
        $this->previousTimeZone = date_default_timezone_get();
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'phpTimeZone' => self::TIME_ZONE,
            ],
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
        date_default_timezone_set($this->previousTimeZone);
    }

    /**
     * Both selects open on "Please choose", which submits `0`. A visitor who leaves either
     * of them there has not chosen a value the settings require, so nothing is stored and
     * the form is shown again.
     */
    #[Test]
    public function aJobWithoutChosenWorkingHoursAndJobTypeIsRefused(): void
    {
        $response = $this->postJob(['employmentType' => '0', 'type' => '0'] + self::REQUIRED_JOB_VALUES);

        $this->assertSame([], $this->storedJobs());
        $this->assertStringContainsString('academic-jobs-new', (string)$response->getBody());
    }

    #[Test]
    public function aJobWithoutChosenWorkingHoursIsRefused(): void
    {
        $this->postJob(['employmentType' => '0'] + self::REQUIRED_JOB_VALUES);

        $this->assertSame([], $this->storedJobs());
    }

    #[Test]
    public function aJobWithoutChosenJobTypeIsRefused(): void
    {
        $this->postJob(['type' => '0'] + self::REQUIRED_JOB_VALUES);

        $this->assertSame([], $this->storedJobs());
    }

    /**
     * The control the refusal is about: the same post with both selects chosen is stored.
     */
    #[Test]
    public function aJobWithChosenWorkingHoursAndJobTypeIsStored(): void
    {
        $this->postJob(['employmentType' => '2', 'type' => '3'] + self::REQUIRED_JOB_VALUES);

        $jobs = $this->storedJobs();
        $this->assertCount(1, $jobs);
        $this->assertSame(2, (int)$jobs[0]['employment_type']);
        $this->assertSame(3, (int)$jobs[0]['type']);
    }

    /**
     * A phone number is written with a leading `+` and with spaces, which a number input
     * does not accept. The field is a telephone input, and the number is stored as typed.
     */
    #[Test]
    public function theContactPhoneIsATelephoneInputAndIsStoredAsTyped(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        $inputs = (new \DOMXPath($document))->query('//input[@id="job.contactPhone"]');
        $this->assertNotFalse($inputs);
        $input = $inputs->item(0);
        $this->assertInstanceOf(\DOMElement::class, $input, 'The form renders no contact phone field.');
        $this->assertSame('tel', $input->getAttribute('type'));

        $this->postJob(['contactPhone' => '+49 2166 9404540'] + self::REQUIRED_JOB_VALUES);

        $jobs = $this->storedJobs();
        $this->assertCount(1, $jobs);
        $this->assertSame('+49 2166 9404540', $jobs[0]['contact_phone']);
    }

    /**
     * The date inputs submit a day. The job is shown from the start of the first day, and
     * it stays visible through the whole day of the application deadline, both in the time
     * zone of the server, which is the one the backend shows them in.
     */
    #[Test]
    public function startDateAndApplicationDeadlineCoverWholeDays(): void
    {
        $this->postJob(['starttime' => '2027-03-01', 'endtime' => '2027-03-31'] + self::REQUIRED_JOB_VALUES);

        $jobs = $this->storedJobs();
        $this->assertCount(1, $jobs);
        $this->assertSame('2027-03-01 00:00:00', $this->localTime((int)$jobs[0]['starttime']));
        $this->assertSame('2027-03-31 23:59:59', $this->localTime((int)$jobs[0]['endtime']));
        $this->assertSame('2027-04-01', $jobs[0]['employment_start_date']);
    }

    /**
     * An application deadline is not required, and a job without one is not given one.
     */
    #[Test]
    public function aJobWithoutDatesStoresNone(): void
    {
        $this->postJob(self::REQUIRED_JOB_VALUES);

        $jobs = $this->storedJobs();
        $this->assertCount(1, $jobs);
        $this->assertSame(0, (int)$jobs[0]['starttime']);
        $this->assertSame(0, (int)$jobs[0]['endtime']);
    }

    private function localTime(int $timestamp): string
    {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(self::TIME_ZONE))
            ->format('Y-m-d H:i:s');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function storedJobs(): array
    {
        return array_values($this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT * FROM tx_academicjobs_domain_model_job ORDER BY uid')
            ->fetchAllAssociative());
    }

    /**
     * Posts the given job values through the form rendered on the page, with the hidden
     * fields the form carries, and returns the response to the post.
     *
     * @param array<string, string> $values
     */
    private function postJob(array $values): ResponseInterface
    {
        $document = new \DOMDocument();
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . $this->renderFrontendPage('https://www.acme.com/home'),
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        $xpath = new \DOMXPath($document);
        $forms = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-new ")]//form');
        $this->assertNotFalse($forms);
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form, 'The page renders no job form.');

        $fields = [];
        foreach ($xpath->query('.//input[@type="hidden"][@name]', $form) ?: [] as $input) {
            if ($input instanceof \DOMElement) {
                $fields[] = rawurlencode($input->getAttribute('name')) . '=' . rawurlencode($input->getAttribute('value'));
            }
        }
        foreach ($values as $property => $value) {
            $fields[] = rawurlencode('tx_academicjobs_newjobform[job][' . $property . ']') . '=' . rawurlencode($value);
        }
        $query = implode('&', $fields);
        parse_str($query, $parsedBody);

        $body = new Stream('php://temp', 'rw');
        $body->write($query);
        $body->rewind();
        $action = $form->getAttribute('action');
        $request = (new InternalRequest(str_starts_with($action, '/') ? rtrim($this->frontendPluginTestBase(), '/') . $action : $action))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body)
            ->withParsedBody($parsedBody);

        return $this->requestFrontendPage($request);
    }
}
