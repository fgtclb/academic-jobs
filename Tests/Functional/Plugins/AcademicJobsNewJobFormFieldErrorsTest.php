<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Submits the `academicjobs_newjobform` plugin with values the validation rejects, and
 * reads the form it renders again: each rejected field carries `is-invalid` itself, names
 * its messages with `aria-describedby` and `aria-invalid`, and is followed by them in an
 * `invalid-feedback` element, which is how a Bootstrap theme shows it. A field that passed
 * carries none of it.
 *
 * The site of a test runs in one language only, English or German, as its default language,
 * so the labels follow the locale of the site without a translated page.
 */
final class AcademicJobsNewJobFormFieldErrorsTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 0, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    /**
     * Two required fields left empty, an email address and a link the validators reject,
     * and the remaining required fields filled in.
     */
    private const REJECTED_JOB_VALUES = [
        'title' => '',
        'description' => '',
        'companyName' => 'ACME Inc.',
        'employmentStartDate' => '2027-04-01',
        'employmentType' => '1',
        'type' => '1',
        'link' => 'not a url',
        'contactEmail' => 'not-an-email',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        parent::setUp();
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
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/FieldErrorLabelOverride.typoscript',
                ],
            ],
        );
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return array<string, array{non-empty-string, array<string, string>, string}>
     */
    public static function languages(): array
    {
        return [
            'English' => [
                'EN',
                [
                    'title' => 'This field is required.',
                    'description' => 'This field is required.',
                    'link' => 'Please enter a valid URL, for example https://www.example.com.',
                    'contactEmail' => 'Please enter a valid email address.',
                ],
                'required',
            ],
            'German' => [
                'DE',
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
     * @param non-empty-string $language
     * @param array<string, string> $expectedMessages
     */
    #[Test]
    #[DataProvider('languages')]
    public function aRejectedFieldIsMarkedAndFollowedByItsMessage(string $language, array $expectedMessages, string $requiredTitle): void
    {
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: $language, base: '/'),
        ]);

        $xpath = $this->postJob(self::REJECTED_JOB_VALUES);

        $this->assertStoredJobs(0);
        foreach ($expectedMessages as $property => $message) {
            $field = $this->field($xpath, $property);
            $this->assertContains('is-invalid', $this->classes($field), sprintf('The field "%s" is not marked invalid.', $property));
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);

        $xpath = $this->postJob(self::REJECTED_JOB_VALUES);

        foreach (['companyName', 'employmentType', 'type', 'employmentStartDate'] as $property) {
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'DE', base: '/'),
        ]);

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        foreach (['title', 'employmentType', 'type', 'companyName', 'employmentStartDate', 'description'] as $property) {
            $this->assertSame(['Pflichtfeld'], $this->requiredTitles($xpath, $property), $property);
        }
        $this->assertSame([], $this->requiredTitles($xpath, 'link'));
        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]');
        $this->assertNotFalse($nodes);
        $this->assertSame(0, $nodes->length);
    }

    /**
     * A rejected form is shown with the values the visitor entered, the dates included. A
     * date input takes its value as `Y-m-d` only and drops any other.
     */
    #[Test]
    public function aRejectedFormKeepsTheEnteredDates(): void
    {
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);

        $xpath = $this->postJob(['starttime' => '2027-03-01', 'endtime' => '2027-03-31'] + self::REJECTED_JOB_VALUES);

        $this->assertSame('2027-04-01', $this->field($xpath, 'employmentStartDate')->getAttribute('value'));
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        foreach (['employmentStartDate', 'starttime', 'endtime'] as $property) {
            $this->assertSame('', $this->field($xpath, $property)->getAttribute('value'), $property);
        }
    }

    /**
     * An error without a label of its own shows the message of the part that rejected the
     * value, here the media type check of the upload, which names the type the client sent.
     * That type is visitor input and is shown as text.
     */
    #[Test]
    public function anErrorWithoutALabelShowsItsMessageAsText(): void
    {
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
        $imageType = 'image/x-"evil"<b>bold</b>';

        $xpath = $this->postJob(self::REJECTED_JOB_VALUES, $imageType);

        $this->assertContains('is-invalid', $this->classes($this->field($xpath, 'image')));
        $this->assertSame(
            ['You entered an incorrect media type, "' . $imageType . '" is not allowed for this file. Please refer to the description of this field.'],
            $this->feedback($xpath, 'image'),
        );
        $bold = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]//b');
        $this->assertNotFalse($bold);
        $this->assertSame(0, $bold->length);
    }

    /**
     * A label for the field and the error code gets the arguments of the error, like the
     * label for the code and the message of the validator. They are visitor input and are
     * shown as text. The value that could not be read leaves the required property empty,
     * which is reported as well.
     */
    #[Test]
    public function aLabelForTheFieldAndTheCodeGetsTheArgumentsOfTheError(): void
    {
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);

        $xpath = $this->postJob(['title' => 'Research assistant', 'employmentStartDate' => '<b>soon</b>'] + self::REJECTED_JOB_VALUES);

        $this->assertSame(
            ['"<b>soon</b>" is no date.', 'This field is required.'],
            $this->feedback($xpath, 'employmentStartDate'),
        );
        $bold = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " invalid-feedback ")]//b');
        $this->assertNotFalse($bold);
        $this->assertSame(0, $bold->length);
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
     * @return string[] The texts of the messages that follow the field.
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

    private function assertStoredJobs(int $expected): void
    {
        $count = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicjobs_domain_model_job')
            ->count('*', 'tx_academicjobs_domain_model_job', []);
        $this->assertSame($expected, $count);
    }

    private function xpath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new \DOMXPath($document);
    }

    /**
     * Posts the given job values through the form rendered on the page, with the hidden
     * fields the form carries, and returns the page the post answers with.
     *
     * @param array<string, string> $values
     * @param string|null $imageType The media type the client names for an uploaded image,
     *        none is uploaded when it is null.
     */
    private function postJob(array $values, ?string $imageType = null): \DOMXPath
    {
        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));
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
        foreach ($values as $property => $value) {
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
        if ($imageType !== null) {
            $fixture = __DIR__ . '/Fixtures/Uploads/first-logo.png';
            $request = $request->withUploadedFiles([
                'tx_academicjobs_newjobform' => [
                    'job' => [
                        'image' => new UploadedFile($fixture, (int)filesize($fixture), UPLOAD_ERR_OK, 'logo.png', $imageType),
                    ],
                ],
            ]);
        }

        $response = $this->requestFrontendPage($request);
        $this->assertSame(200, $response->getStatusCode());

        return $this->xpath((string)$response->getBody());
    }
}
