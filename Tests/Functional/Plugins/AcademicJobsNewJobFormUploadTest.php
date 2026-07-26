<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Submits the `academicjobs_newjobform` plugin with a file upload and verifies the
 * native Extbase file upload handling introduced with the migration of this plugin.
 *
 * Modelled after the TYPO3 core test
 * `extbase/Tests/Functional/Mvc/Controller/FileUploadControllerTest.php`.
 *
 * TYPO3 v13 is excluded on purpose: `ResourceStorage::assureFileUploadPermissions()`
 * calls `is_uploaded_file()` unconditionally there, which can never be true in a CLI
 * test run. TYPO3 v14 performs that check only for the legacy string path argument and
 * skips it for an `UploadedFile`, which is what the Extbase file handling service
 * passes. Core added its own upload test on the v14 branch for the same reason.
 *
 * @todo Drop the group once TYPO3 v13 support ends.
 */
#[Group('not-core-13')]
final class AcademicJobsNewJobFormUploadTest extends AbstractAcademicJobsTestCase
{
    use SiteBasedTestTrait;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'MAIL' => [
            'transport' => 'null',
        ],
        'FE' => [
            'debug' => false,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
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
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    /**
     * Renders the form and returns its action URI together with its hidden fields. Both
     * carry request bound values — the action selects the controller and action, the
     * hidden fields carry hashes — and can therefore not be hardcoded.
     *
     * @return array{action: string, fields: array<string, string>}
     */
    private function renderFormAndExtractSubmitData(): array
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://www.acme.com/home'),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();

        $this->assertSame(1, preg_match('@<form [^>]*action="([^"]+)"@', $content, $actionMatch));

        $fields = [];
        preg_match_all(
            '@<input[^>]+type="hidden"[^>]+name="([^"]+)"[^>]+value="([^"]*)"@',
            $content,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $match) {
            $fields[html_entity_decode($match[1])] = html_entity_decode($match[2]);
        }
        $this->assertNotEmpty($fields, 'Job form contains no hidden fields.');

        return [
            'action' => html_entity_decode($actionMatch[1]),
            'fields' => $fields,
        ];
    }

    /**
     * @param array<string, string> $hiddenFields
     */
    private function submitJobFormWithUpload(string $action, array $hiddenFields): ResponseInterface
    {
        $parsedBody = $this->pluginArgumentsOfFormAction($action);
        foreach ($hiddenFields as $name => $value) {
            $this->addFormValue($parsedBody, $name, $value);
        }
        // All properties the `job` validation set marks as required.
        $values = [
            'title' => 'A new job',
            'description' => 'A new job description',
            'companyName' => 'ACME Inc.',
            'employmentStartDate' => '2026-08-01',
            'employmentType' => '1',
            'type' => '1',
        ];
        foreach ($values as $property => $value) {
            $this->addFormValue($parsedBody, 'tx_academicjobs_newjobform[job][' . $property . ']', $value);
        }

        // A successful upload consumes the file, therefore a copy is handed in.
        $uploadFixture = __DIR__ . '/Fixtures/Uploads/job-logo.png';
        $temporaryFile = $this->instancePath . '/typo3temp/' . basename($uploadFixture) . '.upload';
        copy($uploadFixture, $temporaryFile);

        // The body is provided explicitly. The testing framework otherwise serialises the
        // parsed body with `GuzzleHttp\Psr7\Query::build()`, which cannot handle the nested
        // plugin arguments and emits an "Array to string conversion" warning.
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        $request = (new InternalRequest('https://www.acme.com/home'))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body)
            ->withParsedBody($parsedBody)
            ->withUploadedFiles([
                'tx_academicjobs_newjobform' => [
                    'job' => [
                        'image' => new UploadedFile(
                            $temporaryFile,
                            (int)filesize($temporaryFile),
                            UPLOAD_ERR_OK,
                            basename($uploadFixture),
                            // Deliberately wrong: the client supplied type must not be trusted.
                            'application/octet-stream',
                        ),
                    ],
                ],
            ]);

        return $this->executeFrontendSubRequest($request, new InternalRequestContext());
    }

    /**
     * Extracts the plugin arguments (controller, action, ...) the form encodes into its
     * action URI, so they can be submitted through the request body. Keeping them out of
     * the request URI avoids rebuilding a nested query array, which emits a PHP warning.
     *
     * @return array<string, mixed>
     */
    private function pluginArgumentsOfFormAction(string $action): array
    {
        $query = parse_url($action, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }
        $parsed = [];
        parse_str($query, $parsed);

        $arguments = [];
        foreach ($parsed as $name => $value) {
            $arguments[(string)$name] = $value;
        }

        return $arguments;
    }

    /**
     * Turns `a[b][c]` notation into the nested array the request expects.
     *
     * @param array<string, mixed> $target
     */
    private function addFormValue(array &$target, string $name, string $value): void
    {
        $position = strpos($name, '[');
        if ($position === false) {
            $target[$name] = $value;
            return;
        }
        preg_match_all('@\[([^]]*)]@', $name, $matches);
        $keys = array_merge([substr($name, 0, $position)], $matches[1]);
        $current = &$target;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    #[Test]
    public function uploadedJobImageIsStoredAndReferenced(): void
    {
        $this->setUpTestCase();
        $submitData = $this->renderFormAndExtractSubmitData();
        $response = $this->submitJobFormWithUpload($submitData['action'], $submitData['fields']);

        $this->assertSame(303, $response->getStatusCode(), 'Job creation did not redirect.');

        $job = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT uid, title FROM tx_academicjobs_domain_model_job')
            ->fetchAssociative();
        $this->assertIsArray($job, 'No job record was created.');
        $this->assertSame('A new job', $job['title']);

        $file = $this->getConnectionPool()
            ->getConnectionForTable('sys_file')
            ->executeQuery('SELECT identifier, mime_type FROM sys_file')
            ->fetchAssociative();
        $this->assertIsArray($file, 'No file was stored.');
        // The mime type is detected from the file content, not from the client header.
        $this->assertSame('image/png', $file['mime_type']);
        // The native handling adds a random suffix instead of keeping the client name.
        $this->assertMatchesRegularExpression(
            '@^/job-logos/job-logo-[0-9a-f]{16}\.png$@',
            (string)$file['identifier']
        );

        $reference = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->executeQuery('SELECT tablenames, fieldname, uid_foreign FROM sys_file_reference')
            ->fetchAssociative();
        $this->assertIsArray($reference, 'No file reference was created.');
        $this->assertSame('tx_academicjobs_domain_model_job', $reference['tablenames']);
        $this->assertSame('image', $reference['fieldname']);
        $this->assertSame((int)$job['uid'], (int)$reference['uid_foreign']);
    }
}
