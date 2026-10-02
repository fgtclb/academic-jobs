<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * A project override of a field partial of the `academicjobs_newjobform` plugin.
 *
 * The field partials render the shared form partials of EXT:academic_base, while the
 * names below `Job/Forms/` stay the ones a project overrides. Each fixture is a copy of
 * the shipped partial with one marker attribute added, which is how a project starts an
 * override. The field wrapper matters most: every field partial renders it, so an
 * override of it has to reach every field, not only the text fields.
 *
 * The class extends the notification mail test case for its rendered form page.
 */
final class AcademicJobsNewJobFormPartialOverrideTest extends AbstractAcademicJobsNotificationMailTestCase
{
    private const TEXTFIELD_OVERRIDE = 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/TextfieldPartialOverride.typoscript';
    private const FIELD_WRAPPER_OVERRIDE = 'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/FieldWrapperPartialOverride.typoscript';

    /**
     * Every visible control the form binds to the job: the text fields, the selects,
     * the text areas, the date fields, the checkboxes and the image upload.
     */
    private const JOB_CONTROLS = self::JOB_FORM
        . '//*[self::input or self::select or self::textarea]'
        . '[starts-with(@name, "tx_academicjobs_newjobform[job]")][not(@type="hidden")]';

    #[Test]
    public function anOverriddenTextfieldPartialRendersEveryTextField(): void
    {
        $this->setUpTestCase([self::TEXTFIELD_OVERRIDE]);

        $xpath = $this->renderedPage('https://www.acme.com/home');
        $this->assertSame(10, $this->countMatches($xpath, self::JOB_CONTROLS . '[@data-project="textfield"]'));
        $this->assertSame(
            1,
            $this->countMatches($xpath, self::JOB_CONTROLS . '[@id="job.title"][@type="text"][@data-project="textfield"]'),
            'The title is not rendered by the overriding partial.',
        );
    }

    #[Test]
    public function anOverriddenFieldWrapperPartialWrapsEveryField(): void
    {
        $this->setUpTestCase([self::FIELD_WRAPPER_OVERRIDE]);

        $xpath = $this->renderedPage('https://www.acme.com/home');
        $this->assertSame(20, $this->countMatches($xpath, self::JOB_CONTROLS));
        $this->assertSame(20, $this->countMatches($xpath, self::JOB_FORM . '//div[@data-project="field-wrapper"]'));
        $this->assertSame(
            0,
            $this->countMatches($xpath, self::JOB_CONTROLS . '[not(ancestor::div[@data-project="field-wrapper"])]'),
            'A field of the form is not wrapped by the overriding partial.',
        );
        $this->assertSame(
            1,
            $this->countMatches($xpath, self::JOB_FORM . '//div[@data-project="field-wrapper"]/label[@for="job.title"][abbr]'),
            'The overriding wrapper does not mark the required title.',
        );
    }

    private function countMatches(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);
        $this->assertNotFalse($nodes, sprintf('The query "%s" is invalid.', $query));
        return $nodes->length;
    }
}
