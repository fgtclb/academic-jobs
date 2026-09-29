<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * An installation registers a mail template path of its own under the key 100 the
 * examples of core use, above the one of `academic_jobs`. The global mail paths are read
 * when the test instance bootstraps, so they are the same for every test of this class.
 */
final class AcademicJobsNewJobFormMailTemplatePathTest extends AbstractAcademicJobsNotificationMailTestCase
{
    protected function additionalInstanceConfiguration(): array
    {
        $configuration = parent::additionalInstanceConfiguration();
        $configuration['MAIL']['templateRootPaths'] = [
            100 => 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/MailTemplates/',
        ];

        return $configuration;
    }

    #[Test]
    public function aTemplateOfTheSameNameInAPathWithAHigherKeyReplacesTheShippedOne(): void
    {
        $this->setUpTestCase();

        $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Project template for Research assistant', $mail[$part]);
            $this->assertStringNotContainsString(self::ENGLISH_MESSAGE, $mail[$part]);
        }
    }

    #[Test]
    public function theTemplateNameSettingSelectsAnotherTemplate(): void
    {
        $this->setUpTestCase([
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/EmailTemplateName.typoscript',
        ]);

        $this->submitJob('https://www.acme.com/home', 'Research assistant');

        $mail = $this->sentMail();
        foreach (['text', 'html'] as $part) {
            $this->assertIsString($mail[$part], sprintf('The mail has no %s part.', $part));
            $this->assertStringContainsString('Review template for Research assistant', $mail[$part]);
            $this->assertStringNotContainsString('Project template', $mail[$part]);
        }
    }
}
