<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * The additional fields partial of the `academicjobs_newjobform` plugin, which a site
 * overrides to add a field such as a captcha before the submit button.
 *
 * The class extends the notification mail test case for its form submission. The mail a
 * submission sends is not looked at here.
 */
final class AcademicJobsNewJobFormAdditionalFieldsTest extends AbstractAcademicJobsNotificationMailTestCase
{
    private const PARTIAL_OVERRIDE = 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/AdditionalFieldsPartialOverride.typoscript';

    /**
     * The shipped partial renders nothing, neither an element nor text, so the submit
     * button still follows the row of the job properties directly.
     */
    #[Test]
    public function theShippedPartialRendersNothing(): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage('https://www.acme.com/home');
        $previous = $xpath->query(
            self::JOB_FORM . '//button[@type="submit"]'
            . '/preceding-sibling::node()[not(self::text() and normalize-space() = "")][1]'
        );
        $this->assertNotFalse($previous);
        $row = $previous->item(0);
        $this->assertInstanceOf(\DOMElement::class, $row, 'The submit button does not follow an element.');
        $this->assertSame('row', $row->getAttribute('class'), 'The submit button does not follow the job properties.');
    }

    #[Test]
    public function anOverriddenPartialRendersItsFieldBeforeTheSubmitButton(): void
    {
        $this->setUpTestCase([self::PARTIAL_OVERRIDE]);

        $xpath = $this->renderedPage('https://www.acme.com/home');
        $fields = $xpath->query(
            self::JOB_FORM . '//input[@id="job-form-captcha"][@name="tx_academicjobs_newjobform[captcha]"]'
            . '[following::button[@type="submit"]]'
        );
        $this->assertNotFalse($fields);
        $this->assertSame(1, $fields->length, 'The form renders no additional field before its submit button.');
    }

    /**
     * A field named outside the `job` argument does not take part in its property
     * mapping, so the job is created as without it.
     */
    #[Test]
    public function aJobIsCreatedWithAFieldOutsideTheJobArgument(): void
    {
        $this->setUpTestCase([self::PARTIAL_OVERRIDE]);

        $uid = $this->submitJob(
            'https://www.acme.com/home',
            'Research assistant',
            ['tx_academicjobs_newjobform[captcha]' => 'solved'],
        );

        $title = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT title FROM tx_academicjobs_domain_model_job WHERE uid = ?', [$uid])
            ->fetchOne();
        $this->assertSame('Research assistant', $title);
    }
}
