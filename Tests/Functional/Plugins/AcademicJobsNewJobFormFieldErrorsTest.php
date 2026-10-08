<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Submits the `academicjobs_newjobform` plugin with values the validation rejects, and
 * reads the form it renders again: each rejected field carries `is-invalid` itself, names
 * its messages with `aria-describedby` and `aria-invalid`, and is followed by them in an
 * `invalid-feedback` element, which is how a Bootstrap theme shows it. A field that passed
 * carries none of it.
 *
 * The class extends the notification mail test case for its form submission. The mail a
 * submission sends is not looked at here.
 */
final class AcademicJobsNewJobFormFieldErrorsTest extends AbstractAcademicJobsNotificationMailTestCase
{
    use NewJobFormValidationAssertionTrait;

    /**
     * Two required fields left empty, an email address and a link the validators reject.
     * The other required fields are filled in by the submission of the test case.
     */
    private const REJECTED_JOB_VALUES = [
        'tx_academicjobs_newjobform[job][description]' => '',
        'tx_academicjobs_newjobform[job][link]' => 'not a url',
        'tx_academicjobs_newjobform[job][contactEmail]' => 'not-an-email',
    ];

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function languages(): array
    {
        return [
            'English' => [
                'https://www.acme.com/home',
                [
                    'title' => 'This field is required.',
                    'description' => 'This field is required.',
                    'link' => 'Please enter a valid URL, for example https://www.example.com.',
                    'contactEmail' => 'Please enter a valid email address.',
                ],
                'required',
            ],
            'German' => [
                'https://www.acme.com/de/home',
                [
                    'title' => 'Dieses Feld ist ein Pflichtfeld.',
                    'description' => 'Dieses Feld ist ein Pflichtfeld.',
                    'link' => 'Bitte geben Sie eine gültige URL ein, zum Beispiel https://www.example.com.',
                    'contactEmail' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
                ],
                'Pflichtfeld',
            ],
        ];
    }

    /**
     * @param array<string, string> $expectedMessages
     */
    #[Test]
    #[DataProvider('languages')]
    public function aRejectedFieldIsMarkedAndFollowedByItsMessage(string $pageUrl, array $expectedMessages, string $requiredTitle): void
    {
        $this->setUpTestCase();

        $xpath = $this->rejectedForm($pageUrl, '', self::REJECTED_JOB_VALUES);

        $this->assertSame(0, $this->jobCount());
        foreach ($expectedMessages as $property => $message) {
            $field = $this->field($xpath, $property);
            $this->assertContains('is-invalid', $this->classes($field), sprintf('The field "%s" is not marked invalid.', $property));
            $this->assertNotContains('f3-form-error', $this->classes($field), $property);
            $this->assertSame('true', $field->getAttribute('aria-invalid'), $property);
            $this->assertSame('job.' . $property . '-error', $field->getAttribute('aria-describedby'), $property);
            $this->assertSame(
                [$message],
                $this->feedback($xpath, $property),
                sprintf('The field "%s" is not followed by its message.', $property),
            );
        }
        $this->assertSame([$requiredTitle], $this->requiredTitles($xpath, 'title'));
    }

    #[Test]
    public function aFieldThatPassedIsNeitherMarkedNorFollowedByAMessage(): void
    {
        $this->setUpTestCase();

        $xpath = $this->rejectedForm('https://www.acme.com/home', '', self::REJECTED_JOB_VALUES);

        foreach (['companyName', 'employmentType', 'type', 'employmentStartDate', 'internationalsWelcome', 'image'] as $property) {
            $field = $this->field($xpath, $property);
            $this->assertNotContains('is-invalid', $this->classes($field), $property);
            $this->assertFalse($field->hasAttribute('aria-invalid'), $property);
            $this->assertFalse($field->hasAttribute('aria-describedby'), $property);
            $this->assertSame([], $this->feedback($xpath, $property), $property);
        }
    }

    /**
     * The form before any submission marks its required fields with a translated title,
     * and renders no message.
     */
    #[Test]
    public function theRequiredMarkIsTranslated(): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage('https://www.acme.com/de/home');

        foreach (['title', 'employmentType', 'type', 'companyName', 'employmentStartDate', 'description'] as $property) {
            $this->assertSame(['Pflichtfeld'], $this->requiredTitles($xpath, $property), $property);
        }
        $this->assertSame([], $this->requiredTitles($xpath, 'link'));
        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]');
        $this->assertNotFalse($nodes);
        $this->assertSame(0, $nodes->length);
    }

    /**
     * A date that cannot be read is rejected by the conversion of the value. The extension
     * ships a label for its code.
     */
    #[Test]
    public function aDateThatCannotBeReadIsRejectedWithTheShippedLabel(): void
    {
        $this->setUpTestCase();

        $xpath = $this->rejectedForm('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][employmentStartDate]' => 'next spring',
        ]);

        // The value that could not be read leaves the required property empty, which is
        // reported as well.
        $this->assertSame(
            ['Please enter a valid date.', 'This field is required.'],
            $this->feedback($xpath, 'employmentStartDate'),
        );
    }

    /**
     * A label for the field and the error code gets the arguments of the error, like the
     * label for the code and the message of the validator. They are visitor input and are
     * shown as text.
     */
    #[Test]
    public function aLabelForTheFieldAndTheCodeGetsTheArgumentsOfTheError(): void
    {
        $this->setUpTestCase([], [
            'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/FieldErrorLabelOverride.typoscript',
        ]);

        $xpath = $this->rejectedForm('https://www.acme.com/home', 'Research assistant', [
            'tx_academicjobs_newjobform[job][employmentStartDate]' => '<b>soon</b>',
        ]);

        $this->assertSame(
            ['"<b>soon</b>" is no date.', 'This field is required.'],
            $this->feedback($xpath, 'employmentStartDate'),
        );
        $bold = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]//b');
        $this->assertNotFalse($bold);
        $this->assertSame(0, $bold->length);
    }

    /**
     * A rejected form is shown with the values the visitor entered, the dates included. A
     * date input takes its value as `Y-m-d` only and drops any other.
     */
    #[Test]
    public function aRejectedFormKeepsTheEnteredDates(): void
    {
        $this->setUpTestCase();

        $xpath = $this->rejectedForm('https://www.acme.com/home', '', [
            'tx_academicjobs_newjobform[job][starttime]' => '2027-03-01',
            'tx_academicjobs_newjobform[job][endtime]' => '2027-03-31',
        ] + self::REJECTED_JOB_VALUES);

        $this->assertSame('2026-08-01', $this->field($xpath, 'employmentStartDate')->getAttribute('value'));
        $this->assertSame('2027-03-01', $this->field($xpath, 'starttime')->getAttribute('value'));
        $this->assertSame('2027-03-31', $this->field($xpath, 'endtime')->getAttribute('value'));
    }

    /**
     * The form before any submission leaves the dates empty, it does not show the current
     * day, which is what a date format applied to no date gives.
     */
    #[Test]
    public function theDatesOfAFreshFormAreEmpty(): void
    {
        $this->setUpTestCase();

        $xpath = $this->renderedPage('https://www.acme.com/home');

        foreach (['employmentStartDate', 'starttime', 'endtime'] as $property) {
            $this->assertSame('', $this->field($xpath, $property)->getAttribute('value'), $property);
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function rejectedForm(string $pageUrl, string $title, array $values): \DOMXPath
    {
        $response = $this->postJob($pageUrl, $title, $values);
        $this->assertSame(200, $response->getStatusCode());

        return $this->documentXPath((string)$response->getBody());
    }

    private function field(\DOMXPath $xpath, string $property): \DOMElement
    {
        $nodes = $xpath->query(sprintf('//*[@id="job.%s"][self::input or self::select or self::textarea]', $property));
        $this->assertNotFalse($nodes);
        $field = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $field, sprintf('The form renders no field "%s".', $property));

        return $field;
    }

    /**
     * @return string[]
     */
    private function classes(\DOMElement $element): array
    {
        return preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
    }

    /**
     * @return string[] The texts of the messages in the element the field names, which
     *                  follows the field.
     */
    private function feedback(\DOMXPath $xpath, string $property): array
    {
        $nodes = $xpath->query(sprintf(
            '//*[@id="job.%1$s"]/following-sibling::*[@id="job.%1$s-error"][contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]/div',
            $property,
        ));
        $this->assertNotFalse($nodes);
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim((string)preg_replace('/\s+/', ' ', $node->textContent));
        }

        return $texts;
    }

    /**
     * @return string[] The titles of the required marks in the label of the field.
     */
    private function requiredTitles(\DOMXPath $xpath, string $property): array
    {
        $nodes = $xpath->query(sprintf('//label[@for="job.%s"]/abbr', $property));
        $this->assertNotFalse($nodes);
        $titles = [];
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $titles[] = $node->getAttribute('title');
            }
        }

        return $titles;
    }
}
