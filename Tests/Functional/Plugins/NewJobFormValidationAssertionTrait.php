<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

/**
 * Reads the effect of the validation settings off a rendered new-job form.
 *
 * @phpstan-require-extends AbstractAcademicJobsNotificationMailTestCase
 */
trait NewJobFormValidationAssertionTrait
{
    /**
     * The fields whose label carries the required mark, sorted by name.
     *
     * @return list<string>
     */
    private function markedFields(\DOMXPath $xpath): array
    {
        $labels = $xpath->query(self::JOB_FORM . '//label[@for][abbr[@title="required"]]');
        $this->assertNotFalse($labels);
        $fields = [];
        foreach ($labels as $label) {
            $this->assertInstanceOf(\DOMElement::class, $label);
            $fields[] = substr($label->getAttribute('for'), strlen('job.'));
        }
        sort($fields);

        return $fields;
    }

    /**
     * The fields the form shows again with an error, sorted by name.
     *
     * @return list<string>
     */
    private function invalidFields(\DOMXPath $xpath): array
    {
        $labels = $xpath->query(
            self::JOB_FORM . '//div[contains(concat(" ", normalize-space(@class), " "), " is-invalid ")]/label[@for]'
        );
        $this->assertNotFalse($labels);
        $fields = [];
        foreach ($labels as $label) {
            $this->assertInstanceOf(\DOMElement::class, $label);
            $fields[] = substr($label->getAttribute('for'), strlen('job.'));
        }
        sort($fields);

        return $fields;
    }

    private function inputType(\DOMXPath $xpath, string $identifier): string
    {
        $inputs = $xpath->query(sprintf('%s//input[@id="job.%s"]', self::JOB_FORM, $identifier));
        $this->assertNotFalse($inputs);
        $this->assertSame(1, $inputs->length, sprintf('The form renders no input for "%s".', $identifier));
        $input = $inputs->item(0);
        $this->assertInstanceOf(\DOMElement::class, $input);

        return $input->getAttribute('type');
    }

    private function jobCount(): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT COUNT(*) FROM tx_academicjobs_domain_model_job')
            ->fetchOne();
    }
}
