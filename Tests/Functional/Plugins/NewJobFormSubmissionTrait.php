<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * The FlexForm of a new-job form element, and what the answer to a submission and the
 * rendered form are read with, shared by the tests of where the form sends the visitor.
 *
 * @phpstan-require-extends AbstractAcademicJobsTestCase
 */
trait NewJobFormSubmissionTrait
{
    /**
     * The wrapper of the plugin, which holds the confirmation and the form.
     */
    private const FORM_ELEMENT = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-new ")]';

    /**
     * The answer is a `303` to exactly `$url`, without any argument, and renders no page.
     */
    private function assertSeeOtherTo(string $url, ResponseInterface $answer): void
    {
        $this->assertSame(
            ['status' => 303, 'location' => $url],
            ['status' => $answer->getStatusCode(), 'location' => $answer->getHeaderLine('Location')],
        );
        $this->assertSame('', (string)$answer->getBody(), 'The answer to the submission rendered a page.');
    }

    /**
     * The markup of the plugin on the page.
     */
    private function formElement(string $html): string
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $elements = (new \DOMXPath($document))->query(self::FORM_ELEMENT);
        $this->assertNotFalse($elements);
        $this->assertCount(1, $elements, 'The page does not render the form plugin once.');

        return (string)$document->saveHTML($elements->item(0));
    }

    /**
     * @param array<string, string> $settings
     */
    private function flexForm(array $settings): string
    {
        $fields = '';
        foreach ($settings as $name => $value) {
            $fields .= sprintf(
                '<field index="%s"><value index="vDEF">%s</value></field>',
                htmlspecialchars($name),
                htmlspecialchars($value),
            );
        }

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . $fields
            . '</language></sheet></data></T3FlexForms>';
    }
}
