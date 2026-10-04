<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicJobsTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx-academicjobs-record-job',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * The list above pins what is registered, this pins that the TCA names it. A
     * registration nothing points at would pass every assertion below while the record list
     * and the page tree showed another icon for a job.
     */
    #[Test]
    public function jobRecordTypeResolvesToTheRegisteredIcon(): void
    {
        $this->assertSame(
            'tx-academicjobs-record-job',
            $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['ctrl']['typeicon_classes']['default'] ?? null,
        );
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The content elements and the job record draw the briefcase of the shared set of
     * academic_base rather than a copy of it, so the extension ships no icon file of its
     * own apart from Extension.svg.
     */
    #[Test]
    public function pluginAndRecordIconDrawTheSharedBriefcase(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        foreach (['tx-academicjobs-plugin-jobs', 'tx-academicjobs-record-job'] as $identifier) {
            $this->assertSame(
                'EXT:academic_base/Resources/Public/Icons/info/employment.svg',
                $iconRegistry->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? null,
                sprintf('Icon "%s" does not draw the shared briefcase.', $identifier),
            );
        }
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does. It decides
     * by table what belongs to the extension, because the record icon draws a file of
     * academic_base and a walk that attributes icons by their file finds nothing here.
     */
    #[Test]
    public function everyRecordTypeOfThisExtensionNamesAColourSchemeAwareIconOfItsOwn(): void
    {
        $this->assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn('academic_jobs');
    }
}
