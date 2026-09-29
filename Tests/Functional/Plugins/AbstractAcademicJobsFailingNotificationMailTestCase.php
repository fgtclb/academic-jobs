<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits a job through the `academicjobs_newjobform` plugin while its notification mail
 * cannot be sent, and reads what the logger of the job controller wrote from a log file
 * of its own.
 *
 * Each frontend request bootstraps again from the `TYPO3_CONF_VARS` of the test instance,
 * so the mail transport and the log writer are the same for every test of a class.
 */
abstract class AbstractAcademicJobsFailingNotificationMailTestCase extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * All properties the `job` validation set marks as required, apart from the title.
     */
    private const REQUIRED_JOB_VALUES = [
        'description' => 'A new job description',
        'companyName' => 'ACME Inc.',
        'employmentStartDate' => '2026-08-01',
        'employmentType' => '1',
        'type' => '1',
    ];

    /**
     * A class that needs another mail transport overrides this.
     *
     * @return array<string, mixed>
     */
    protected function additionalInstanceConfiguration(): array
    {
        return [
            'MAIL' => [
                'transport' => 'null',
            ],
            'LOG' => [
                'FGTCLB' => [
                    'AcademicJobs' => [
                        'Controller' => [
                            'JobController' => [
                                'writerConfiguration' => [
                                    LogLevel::ERROR => [
                                        FileWriter::class => ['logFile' => self::logFile()],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration(
            $this->additionalInstanceConfiguration(),
        );
        parent::setUp();
        // Truncated rather than removed: the writer keeps its file handle open across the
        // requests of a test class, and appends to the end of the file.
        if (is_file(self::logFile())) {
            file_put_contents(self::logFile(), '');
        }
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private static function logFile(): string
    {
        return static::getInstancePath() . '/typo3temp/var/log/job-controller.log';
    }

    /**
     * @param string[] $additionalConstantFiles
     * @param string[] $additionalSetupFiles
     */
    protected function setUpTestCase(array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsFailingNotificationMail/newJobFormPages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * Posts a job with the given title through the form rendered on `$pageUrl`, with the
     * hidden fields the form carries, and returns the response to the post.
     */
    protected function postJob(string $pageUrl, string $title): ResponseInterface
    {
        $document = new \DOMDocument();
        $document->loadHTML(
            '<?xml encoding="UTF-8">' . $this->renderFrontendPage($pageUrl),
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
        foreach (['title' => $title] + self::REQUIRED_JOB_VALUES as $property => $value) {
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

    /**
     * The uid of the one job record the post created.
     */
    protected function createdJobUid(): int
    {
        $uids = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT uid FROM tx_academicjobs_domain_model_job')
            ->fetchFirstColumn();
        $this->assertCount(1, $uids, 'The post did not create exactly one job record.');

        return (int)$uids[0];
    }

    /**
     * @return string[] The entries the job controller logged at level error.
     */
    protected function loggedErrors(): array
    {
        if (!is_file(self::logFile())) {
            return [];
        }

        return array_values(array_filter(
            file(self::logFile(), FILE_IGNORE_NEW_LINES) ?: [],
            static fn(string $line): bool => str_contains($line, '[ERROR]')
                && str_contains($line, 'component="FGTCLB.AcademicJobs.Controller.JobController"'),
        ));
    }

    /**
     * The answer to a saved job is the redirect the plugin returns. TYPO3 v12 and v13 send
     * it with `header()` and answer 200 with the rest of the page, where the empty body of
     * the redirect takes the place of the plugin. A form rendered again would still be
     * there, and a post that ends in an exception fails the test before this.
     */
    protected function assertAnsweredAsSavedJob(ResponseInterface $response): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('academic-jobs-new', (string)$response->getBody());
    }

    protected function assertJobIsSavedHidden(int $uid): void
    {
        $hidden = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT hidden FROM tx_academicjobs_domain_model_job WHERE uid = ?', [$uid])
            ->fetchOne();
        $this->assertSame(1, (int)$hidden);
    }

    protected function assertOneErrorNamesTheJob(int $uid): string
    {
        $errors = $this->loggedErrors();
        $this->assertCount(1, $errors, 'The job controller logged ' . count($errors) . ' errors.');
        $this->assertStringContainsString('The notification mail about the submitted job ' . $uid . ' could not be sent.', $errors[0]);

        return $errors[0];
    }
}
