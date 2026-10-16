<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use Psr\Http\Message\ResponseInterface;

/**
 * What the tests of the redirect after a submitted job share: the `303` the form answers
 * with, the markup of the plugin on the page the visitor lands on, the FlexForm of a form
 * element, and the deprecations a submission raises.
 *
 * The XPath of the plugin is written out here, and a class that needs it as well keeps a
 * constant of its own: a constant in a trait is a fatal error on PHP 8.1, which this
 * branch still supports for TYPO3 v12.
 */
trait NewJobFormSubmissionTrait
{
    /**
     * The answer is a `303` to exactly `$url`, without any argument, and renders no page.
     */
    protected function assertSeeOtherTo(string $url, ResponseInterface $answer): void
    {
        $this->assertSame(
            ['status' => 303, 'location' => $url],
            ['status' => $answer->getStatusCode(), 'location' => $answer->getHeaderLine('Location')],
        );
        $this->assertSame('', (string)$answer->getBody(), 'The answer to the submission rendered a page.');
    }

    /**
     * The markup of the plugin on the page: its wrapper, which holds the confirmation and
     * the form.
     */
    protected function formElement(string $html): string
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $elements = (new \DOMXPath($document))->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-new ")]',
        );
        $this->assertNotFalse($elements);
        $this->assertCount(1, $elements, 'The page does not render the form plugin once.');

        return (string)$document->saveHTML($elements->item(0));
    }

    /**
     * A FlexForm of the plugin with `$settings` in its only sheet.
     *
     * @param array<string, string> $settings
     */
    protected function flexForm(array $settings): string
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
    protected function collectDeprecations(\Closure $callback): array
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
