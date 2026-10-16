<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * The FlexForm of a new-job form element, what the answer to a submission and the
 * rendered form are read with, and the deprecations a submission raises, shared by the
 * tests of where the form sends the visitor.
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

    /**
     * Only the deprecations raised in the classes of this extension are taken. The core
     * raises its own while it renders a page, and those are left to the handler of
     * PHPUnit, as they are in every other test.
     *
     * @return list<string> The messages of the `E_USER_DEPRECATED` errors the classes of
     *         this extension raised while `$callback` ran.
     */
    private function collectDeprecations(\Closure $callback): array
    {
        $deprecations = [];
        $previousHandler = null;
        $previousHandler = set_error_handler(
            static function (int $level, string $message, string $file = '', int $line = 0) use (&$deprecations, &$previousHandler): bool {
                if ($level === E_USER_DEPRECATED && str_contains($file, '/academic-jobs/Classes/')) {
                    $deprecations[] = $message;
                    return true;
                }
                return is_callable($previousHandler) && (bool)$previousHandler($level, $message, $file, $line);
            },
        );
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
