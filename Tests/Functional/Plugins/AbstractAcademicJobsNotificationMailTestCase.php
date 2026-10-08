<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits a job through the `academicjobs_newjobform` plugin and reads the notification
 * mail back from the `mbox` transport of core, which appends every sent mail to one file.
 * What a test reads is therefore what a recipient gets: the headers, and the parts
 * decoded from their transfer encoding.
 *
 * Each frontend request bootstraps again from the `TYPO3_CONF_VARS` of the test instance,
 * so the mail transport is the same for every test of a class.
 */
abstract class AbstractAcademicJobsNotificationMailTestCase extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
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
     * A class that needs more overrides this.
     *
     * @return array<string, mixed>
     */
    protected function additionalInstanceConfiguration(): array
    {
        return [
            'MAIL' => [
                'transport' => 'mbox',
                'transport_mbox_file' => self::mailboxFile(),
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration(
            $this->additionalInstanceConfiguration(),
        );
        parent::setUp();
        // The test instance, and the file with it, is kept for all tests of a class.
        if (is_file(self::mailboxFile())) {
            unlink(self::mailboxFile());
        }
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private static function mailboxFile(): string
    {
        return static::getInstancePath() . '/typo3temp/notification-mails.mbox';
    }

    /**
     * @param string[] $additionalConstantFiles
     * @param string[] $additionalSetupFiles
     */
    protected function setUpTestCase(array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNotificationMail/newJobFormPages.csv');
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
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    /**
     * Posts a job with the given title through the form rendered on `$pageUrl`, with the
     * hidden fields the form carries, and returns the uid of the job record it created.
     */
    protected function submitJob(string $pageUrl, string $title): int
    {
        $this->postJob($pageUrl, $title);

        return $this->createdJobUid();
    }

    /**
     * Posts a job as `submitJob()` does and returns the response to the post.
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
     * The sent mail: how many the mailbox holds, the headers of the first one with their
     * names in lower case, and its plain-text and HTML part, or `null` for a missing part.
     *
     * @return array{count: int, headers: array<string, string>, text: ?string, html: ?string}
     */
    protected function sentMail(): array
    {
        $this->assertFileExists(self::mailboxFile(), 'No mail was sent.');
        $raw = (string)file_get_contents(self::mailboxFile());
        [$headers, $body] = $this->splitEntity($raw);

        $parts = [];
        if (preg_match('@boundary="?([^";]+)"?@i', $headers['content-type'] ?? '', $boundary) === 1) {
            foreach (explode('--' . $boundary[1], $body) as $chunk) {
                // The preamble is empty, and the closing delimiter continues with `--`.
                if (trim($chunk) === '' || str_starts_with($chunk, '--')) {
                    continue;
                }
                [$partHeaders, $partBody] = $this->splitEntity(ltrim($chunk, "\r\n"));
                $parts[$this->mediaType($partHeaders)] = $this->decodeBody($partHeaders, $partBody);
            }
        } else {
            $parts[$this->mediaType($headers)] = $this->decodeBody($headers, $body);
        }

        return [
            'count' => (int)preg_match_all('@^Message-ID: @mi', $raw),
            'headers' => $headers,
            'text' => $parts['text/plain'] ?? null,
            'html' => $parts['text/html'] ?? null,
        ];
    }

    /**
     * @return array{array<string, string>, string}
     */
    private function splitEntity(string $entity): array
    {
        [$headerBlock, $body] = explode("\r\n\r\n", $entity, 2) + [1 => ''];
        $headers = [];
        // Folded header lines continue with white space.
        foreach (explode("\r\n", (string)preg_replace('@\r\n[ \t]+@', ' ', $headerBlock)) as $line) {
            [$name, $value] = explode(':', $line, 2) + [1 => ''];
            $headers[strtolower(trim($name))] ??= trim($value);
        }

        return [$headers, $body];
    }

    /**
     * @param array<string, string> $headers
     */
    private function mediaType(array $headers): string
    {
        return strtolower(trim(explode(';', $headers['content-type'] ?? '')[0]));
    }

    /**
     * @param array<string, string> $headers
     */
    private function decodeBody(array $headers, string $body): string
    {
        $decoded = match (strtolower($headers['content-transfer-encoding'] ?? '')) {
            'quoted-printable' => quoted_printable_decode($body),
            'base64' => (string)base64_decode($body, true),
            default => $body,
        };

        return str_replace("\r\n", "\n", $decoded);
    }
}
