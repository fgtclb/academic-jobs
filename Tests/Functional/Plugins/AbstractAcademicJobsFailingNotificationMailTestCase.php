<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

/**
 * Submits a job while its notification mail cannot be sent, and reads what the logger of
 * the job controller wrote from a log file of its own.
 */
abstract class AbstractAcademicJobsFailingNotificationMailTestCase extends AbstractAcademicJobsNotificationMailTestCase
{
    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        $configuration['LOG']['FGTCLB']['AcademicJobs']['Controller']['JobController']['writerConfiguration'] = [
            LogLevel::ERROR => [
                FileWriter::class => ['logFile' => self::logFile()],
            ],
        ];

        return $configuration;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Truncated rather than removed: the writer keeps its file handle open across the
        // requests of a test class, and appends to the end of the file.
        if (is_file(self::logFile())) {
            file_put_contents(self::logFile(), '');
        }
    }

    private static function logFile(): string
    {
        return static::getInstancePath() . '/typo3temp/var/log/job-controller.log';
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
     * The answer to a saved job is the redirect the plugin returns, whose empty body takes
     * the place of the plugin. TYPO3 v14 answers with the redirect, TYPO3 v13 sends it with
     * `header()` and answers 200 with the rest of the page, so the status is not asserted
     * here. A form rendered again would still be there, and a post that ends in an
     * exception fails the test before this.
     */
    protected function assertAnsweredAsSavedJob(ResponseInterface $response): void
    {
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
