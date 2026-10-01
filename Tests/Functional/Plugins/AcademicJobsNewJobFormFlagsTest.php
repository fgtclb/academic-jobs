<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The two job flags of the `academicjobs_newjobform` plugin.
 *
 * A checkbox the `f:form.checkbox` view helper renders comes with a hidden field of the
 * same name and an empty value, so an unchecked flag submits `''`. Extbase converts that
 * to `null` for an `int` property, which the setters of the job do not accept, and the
 * submission fails in property mapping unless the create action turns it into `0` first.
 *
 * The class extends the notification mail test case for its form submission and its two
 * site languages. The mail a submission sends is not looked at here.
 */
final class AcademicJobsNewJobFormFlagsTest extends AbstractAcademicJobsNotificationMailTestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function flagLabels(): array
    {
        return [
            'English' => ['https://www.acme.com/home', 'International applicants welcome', 'Recommended by alumni'],
            'German' => ['https://www.acme.com/de/home', 'Internationale Bewerbungen willkommen', 'Von Alumni empfohlen'],
        ];
    }

    #[Test]
    #[DataProvider('flagLabels')]
    public function theFormOffersBothFlagsAsUncheckedCheckboxes(string $pageUrl, string $internationalsLabel, string $alumniLabel): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage($pageUrl);
        $labels = ['internationalsWelcome' => $internationalsLabel, 'alumniRecommend' => $alumniLabel];
        foreach ($labels as $identifier => $label) {
            $checkbox = $this->flagCheckbox($xpath, $identifier);
            $this->assertFalse($checkbox->hasAttribute('checked'), sprintf('The checkbox "%s" is checked.', $identifier));
            $this->assertFalse($checkbox->hasAttribute('disabled'), sprintf('The checkbox "%s" is disabled.', $identifier));

            $labelElements = $xpath->query(sprintf('%s//label[@for="job.%s"]', self::JOB_FORM, $identifier));
            $this->assertNotFalse($labelElements);
            $this->assertSame(1, $labelElements->length, sprintf('The checkbox "%s" has no label.', $identifier));
            $this->assertSame($label, trim((string)$labelElements->item(0)?->textContent));
        }
    }

    /**
     * @return array<string, array{list<string>, int, int}>
     */
    public static function flagSubmissions(): array
    {
        return [
            'both flags unchecked' => [[], 0, 0],
            'internationals welcome checked' => [['internationalsWelcome'], 1, 0],
            'recommended by alumni checked' => [['alumniRecommend'], 0, 1],
            'both flags checked' => [['internationalsWelcome', 'alumniRecommend'], 1, 1],
        ];
    }

    /**
     * @param list<string> $checkedFlags
     */
    #[Test]
    #[DataProvider('flagSubmissions')]
    public function aSubmittedJobStoresWhichFlagsWereChecked(array $checkedFlags, int $internationalsWelcome, int $alumniRecommend): void
    {
        $this->setUpTestCase();

        $uid = $this->submitJob('https://www.acme.com/home', 'Research assistant', $this->checkedFlagValues($checkedFlags));

        $job = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery(
                'SELECT internationals_welcome, alumni_recommend FROM tx_academicjobs_domain_model_job WHERE uid = ?',
                [$uid],
            )
            ->fetchAssociative();
        $this->assertIsArray($job);
        $this->assertSame($internationalsWelcome, (int)$job['internationals_welcome']);
        $this->assertSame($alumniRecommend, (int)$job['alumni_recommend']);
    }

    /**
     * A submission that fails validation shows the form again, rendered from what the
     * visitor submitted rather than from the values the create action mapped.
     */
    #[Test]
    public function aFormReturnedWithAnErrorKeepsTheCheckedFlags(): void
    {
        $this->setUpTestCase();

        $response = $this->postJob('https://www.acme.com/home', '', $this->checkedFlagValues(['internationalsWelcome']));

        $xpath = $this->documentXPath((string)$response->getBody());
        $invalidTitles = $xpath->query(
            self::JOB_FORM . '//div[contains(concat(" ", normalize-space(@class), " "), " is-invalid ")]//input[@id="job.title"]'
        );
        $this->assertNotFalse($invalidTitles);
        $this->assertSame(1, $invalidTitles->length, 'The form is not shown again with the error of the title.');
        $this->assertTrue($this->flagCheckbox($xpath, 'internationalsWelcome')->hasAttribute('checked'));
        $this->assertFalse($this->flagCheckbox($xpath, 'alumniRecommend')->hasAttribute('checked'));
        $jobs = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->executeQuery('SELECT COUNT(*) FROM tx_academicjobs_domain_model_job')
            ->fetchOne();
        $this->assertSame(0, (int)$jobs, 'A job without a title was created.');
    }

    private function flagCheckbox(\DOMXPath $xpath, string $identifier): \DOMElement
    {
        $checkboxes = $xpath->query(sprintf(
            '%s//input[@type="checkbox"][@id="job.%s"][@name="tx_academicjobs_newjobform[job][%s]"][@value="1"]',
            self::JOB_FORM,
            $identifier,
            $identifier,
        ));
        $this->assertNotFalse($checkboxes);
        $this->assertSame(1, $checkboxes->length, sprintf('The form renders no checkbox for "%s".', $identifier));
        $checkbox = $checkboxes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $checkbox);

        return $checkbox;
    }

    /**
     * @param list<string> $flags
     * @return array<string, string>
     */
    private function checkedFlagValues(array $flags): array
    {
        $values = [];
        foreach ($flags as $flag) {
            $values['tx_academicjobs_newjobform[job][' . $flag . ']'] = '1';
        }

        return $values;
    }
}
