<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reads the icons of the contact block of a rendered job detail view.
 *
 * An icon is looked up by the `data-identifier` of its wrapper, which is the identifier the
 * template asked for, or `default-not-found` when the registry knows no such icon. The
 * shipped icons are inlined `<svg>` drawings in `currentColor`, an icon a site package
 * registers with the core provider is an `<img>`.
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
     * The drawing of the icon with `$identifier` below `$scope`, the inlined `<svg>` or the
     * `<img>` a provider renders instead, or null when there is none.
     */
    private function iconDrawing(\DOMElement $scope, string $identifier): ?\DOMElement
    {
        $document = $scope->ownerDocument;
        $this->assertInstanceOf(\DOMDocument::class, $document);
        $drawings = (new \DOMXPath($document))->query(
            sprintf('.//*[@data-identifier="%s"]//*[self::svg or self::img]', $identifier),
            $scope,
        );
        $this->assertNotFalse($drawings);
        $this->assertLessThan(2, $drawings->length, sprintf('More than one "%s" icon is rendered.', $identifier));
        $drawing = $drawings->item(0);

        return $drawing instanceof \DOMElement ? $drawing : null;
    }

    /**
     * The icon is the shared glyph `$file` of academic_base, inlined and drawn in the
     * colour of the surrounding text at the size of its font. An inlined drawing names no
     * file, so the shape is compared with the one in the file.
     */
    private function assertIconShowsTheSharedGlyph(?\DOMElement $drawing, string $file): void
    {
        $this->assertNotNull($drawing, sprintf('No icon is rendered where the shared glyph "%s" belongs.', $file));
        $this->assertSame('svg', $drawing->nodeName, 'The icon is not inlined.');
        $this->assertSame('currentColor', $drawing->getAttribute('fill'));
        $this->assertSame('1em', $drawing->getAttribute('width'));
        $this->assertSame('1em', $drawing->getAttribute('height'));
        $this->assertSame($this->sharedGlyphShape($file), $this->drawnShape($drawing));
    }

    /**
     * The icon is an image of the file whose path ends with `$fileSuffix`, the way a site
     * package that registers its own file with the core provider renders it.
     *
     * @param non-empty-string $fileSuffix
     */
    private function assertIconShowsTheImage(?\DOMElement $drawing, string $fileSuffix): void
    {
        $this->assertNotNull($drawing, sprintf('No icon is rendered where "%s" belongs.', $fileSuffix));
        $this->assertSame('img', $drawing->nodeName);
        $this->assertStringEndsWith($fileSuffix, $this->iconFilePath($drawing));
    }

    private function drawnShape(\DOMElement $svg): string
    {
        $paths = $svg->getElementsByTagName('path');
        $this->assertSame(1, $paths->length, 'The inlined icon does not hold exactly one shape.');
        $path = $paths->item(0);
        $this->assertInstanceOf(\DOMElement::class, $path);

        return $path->getAttribute('d');
    }

    private function sharedGlyphShape(string $file): string
    {
        $content = file_get_contents(
            GeneralUtility::getFileAbsFileName('EXT:academic_base/Resources/Public/Icons/info/' . $file)
        );
        $this->assertIsString($content);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($content));
        $this->assertInstanceOf(\DOMElement::class, $document->documentElement);

        return $this->drawnShape($document->documentElement);
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
