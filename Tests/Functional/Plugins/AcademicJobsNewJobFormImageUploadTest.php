<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\AcademicJobs\Tests\Functional\Plugins\Fixtures\Classes\SimulatedUploadResourceStorage;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits the `academicjobs_newjobform` plugin with an image, the way an anonymous
 * visitor does.
 *
 * The form is open to everybody, so the name a visitor gives the file must never decide
 * which stored file is written: a second image with the name of an existing one is
 * stored as a file of its own, and the job that referenced the first one keeps it.
 *
 * The storage refuses files that were not posted to a web server, see
 * `SimulatedUploadResourceStorage`, which is registered as XCLASS for this class.
 */
final class AcademicJobsNewJobFormImageUploadTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const UPLOAD_NAME = 'logo.png';

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

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
            'SYS' => [
                'Objects' => [
                    ResourceStorage::class => [
                        'className' => SimulatedUploadResourceStorage::class,
                    ],
                ],
            ],
        ]);
        // `makeInstance()` keeps the class it resolved for the storage for the whole
        // process, so a test class that ran before would hide the XCLASS, and this one
        // would hand it on to the next. Flushed before the instance is set up as well,
        // because setting it up may resolve the storage already.
        GeneralUtility::flushInternalRuntimeCaches();
        parent::setUp();
        GeneralUtility::flushInternalRuntimeCaches();
    }

    protected function tearDown(): void
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $this->removeWrittenSiteConfiguration();
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * Posts a job with the given title and the given image through the form, with the
     * hidden fields the form carries, and returns the response to the post. The image is
     * always called `logo.png`, whatever fixture it is made from.
     */
    private function postJobWithImage(string $title, string $imageFixture): ResponseInterface
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
        $uploadFields = $xpath->query('.//input[@type="file"][@name="tx_academicjobs_newjobform[job][image]"]', $form);
        $this->assertNotFalse($uploadFields);
        $this->assertSame(1, $uploadFields->length, 'The job form renders no image upload.');

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

        // A stored upload is moved away, so the storage gets a copy of the fixture.
        $uploadDirectory = SimulatedUploadResourceStorage::uploadDirectory();
        GeneralUtility::mkdir_deep($uploadDirectory);
        $temporaryFile = $uploadDirectory . bin2hex(random_bytes(8));
        copy(__DIR__ . '/Fixtures/Uploads/' . $imageFixture, $temporaryFile);

        $body = new Stream('php://temp', 'rw');
        $body->write($query);
        $body->rewind();
        $action = $form->getAttribute('action');
        $request = (new InternalRequest(str_starts_with($action, '/') ? rtrim($this->frontendPluginTestBase(), '/') . $action : $action))
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
                            self::UPLOAD_NAME,
                            'image/png',
                        ),
                    ],
                ],
            ]);

        return $this->requestFrontendPage($request);
    }

    /**
     * The answer to a saved job is the redirect the plugin returns. TYPO3 v12 and v13 send
     * it with `header()` and answer 200 with the rest of the page, where the empty body of
     * the redirect takes the place of the plugin.
     */
    private function assertAnsweredAsSavedJob(ResponseInterface $response): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('academic-jobs-new', (string)$response->getBody());
    }

    /**
     * @return array{identifier: string, content: string} The file the image of the job
     *         with the given title references, read from the storage folder.
     */
    private function imageOfJob(string $title): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('sys_file')
            ->executeQuery(
                'SELECT sys_file.identifier FROM sys_file'
                . ' INNER JOIN sys_file_reference ON sys_file_reference.uid_local = sys_file.uid'
                . ' INNER JOIN tx_academicjobs_domain_model_job ON tx_academicjobs_domain_model_job.uid = sys_file_reference.uid_foreign'
                . ' WHERE sys_file_reference.tablenames = ? AND sys_file_reference.fieldname = ? AND sys_file_reference.deleted = 0'
                . ' AND tx_academicjobs_domain_model_job.title = ?',
                ['tx_academicjobs_domain_model_job', 'image', $title],
            )
            ->fetchFirstColumn();
        $this->assertCount(1, $rows, sprintf('The job "%s" does not reference exactly one image.', $title));
        $identifier = (string)$rows[0];
        $path = $this->instancePath . '/fileadmin' . $identifier;
        $this->assertFileExists($path);

        return [
            'identifier' => $identifier,
            'content' => (string)file_get_contents($path),
        ];
    }

    #[Test]
    public function anImageWithTheNameOfAnExistingOneIsStoredAsAFileOfItsOwn(): void
    {
        $this->setUpTestCase();
        $firstContent = (string)file_get_contents(__DIR__ . '/Fixtures/Uploads/first-logo.png');
        $secondContent = (string)file_get_contents(__DIR__ . '/Fixtures/Uploads/second-logo.png');
        $this->assertNotSame($firstContent, $secondContent, 'The two upload fixtures are the same image.');

        $this->assertAnsweredAsSavedJob($this->postJobWithImage('First job', 'first-logo.png'));
        $this->assertAnsweredAsSavedJob($this->postJobWithImage('Second job', 'second-logo.png'));

        $firstImage = $this->imageOfJob('First job');
        $secondImage = $this->imageOfJob('Second job');

        $this->assertSame('/job-logos/' . self::UPLOAD_NAME, $firstImage['identifier']);
        $this->assertNotSame(
            $firstImage['identifier'],
            $secondImage['identifier'],
            'Both jobs reference the same file.',
        );
        $this->assertStringStartsWith('/job-logos/logo', $secondImage['identifier']);
        $this->assertSame($firstContent, $firstImage['content'], 'The image of the first job was overwritten.');
        $this->assertSame($secondContent, $secondImage['content'], 'The second job does not show its own image.');

        $storedFiles = $this->getConnectionPool()
            ->getConnectionForTable('sys_file')
            ->executeQuery('SELECT COUNT(*) FROM sys_file WHERE identifier LIKE ?', ['/job-logos/%'])
            ->fetchOne();
        $this->assertSame(2, (int)$storedFiles, 'The two uploads were not stored as two files.');
    }
}
