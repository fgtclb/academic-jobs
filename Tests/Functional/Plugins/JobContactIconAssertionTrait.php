<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

/**
 * Reads the icons of the contact block of a rendered job detail view.
 *
 * An icon is looked up by the `data-identifier` of its wrapper, which is the identifier the
 * template asked for, or `default-not-found` when the registry knows no such icon.
 */
trait JobContactIconAssertionTrait
{
    private function contactBlock(string $content): \DOMElement
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        $blocks = (new \DOMXPath($document))->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-contact ")]'
        );
        $this->assertNotFalse($blocks);
        $this->assertSame(1, $blocks->length, 'The detail view renders not exactly one contact block.');
        $block = $blocks->item(0);
        $this->assertInstanceOf(\DOMElement::class, $block);

        return $block;
    }

    /**
     * The `<img>` of the icon with `$identifier` below `$scope`, or null when there is none.
     */
    private function iconImage(\DOMElement $scope, string $identifier): ?\DOMElement
    {
        $document = $scope->ownerDocument;
        $this->assertInstanceOf(\DOMDocument::class, $document);
        $images = (new \DOMXPath($document))->query(
            sprintf('.//*[@data-identifier="%s"]//img', $identifier),
            $scope,
        );
        $this->assertNotFalse($images);
        $this->assertLessThan(2, $images->length, sprintf('More than one "%s" icon is rendered.', $identifier));
        $image = $images->item(0);

        return $image instanceof \DOMElement ? $image : null;
    }

    /**
     * The identifiers of every icon below `$scope`, in document order.
     *
     * @return list<string>
     */
    private function iconIdentifiers(\DOMElement $scope): array
    {
        $document = $scope->ownerDocument;
        $this->assertInstanceOf(\DOMDocument::class, $document);
        $wrappers = (new \DOMXPath($document))->query('.//*[@data-identifier]', $scope);
        $this->assertNotFalse($wrappers);
        $identifiers = [];
        foreach ($wrappers as $wrapper) {
            if ($wrapper instanceof \DOMElement) {
                $identifiers[] = $wrapper->getAttribute('data-identifier');
            }
        }

        return $identifiers;
    }

    /**
     * The path of the file an icon image shows. TYPO3 v14 appends the modification time of
     * the file as a query string, v13 does not.
     */
    private function iconFilePath(\DOMElement $image): string
    {
        return (string)parse_url($image->getAttribute('src'), PHP_URL_PATH);
    }

    private function assertContactBlockHasNoMissingIcon(\DOMElement $block): void
    {
        $this->assertStringNotContainsString(
            'default-not-found',
            (string)$block->ownerDocument?->saveHTML($block),
            'The contact block renders the icon TYPO3 shows for an identifier it does not know.',
        );
    }
}
