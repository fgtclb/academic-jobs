<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The icons of the job views are frontend icons: registered in
 * `Configuration/FrontendIcons.php` and rendered by `ab:icon` of academic_base. The
 * backend never shows them, so the icon registry of the backend must not know them, or a
 * site that replaces one in `Configuration/Icons.php` sees no effect and no error. The
 * record icon and the plugin icon are the opposite case, backend icons only.
 *
 * Every job property has an identifier of its own, so a site replaces the icon of one
 * property without touching the others, and the drawings are the shared files of
 * academic_base.
 *
 * The identifiers and files are spelled out here rather than read back out of the
 * registration, so a rename has to be made twice instead of silently agreeing with itself.
 */
final class FrontendIconsTest extends AbstractAcademicJobsTestCase
{
    use FrontendIconsAssertionTrait;

    /**
     * The twelve property icons of the job list and the detail view, the two icons of the
     * contact block, and the three no shipped template renders, one per job property, each
     * with the shared file of academic_base it shows.
     *
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function jobIcons(): \Generator
    {
        $files = [
            'tx-academicjobs-info-employment-start-date' => 'calendar.svg',
            'tx-academicjobs-info-company-name' => 'company.svg',
            'tx-academicjobs-info-sector' => 'sector.svg',
            'tx-academicjobs-info-type' => 'employment.svg',
            'tx-academicjobs-info-required-degree' => 'degree.svg',
            'tx-academicjobs-info-contractual-relationship' => 'contract.svg',
            'tx-academicjobs-info-employment-type' => 'employment.svg',
            'tx-academicjobs-info-work-location' => 'location.svg',
            'tx-academicjobs-info-internationals-welcome' => 'international.svg',
            'tx-academicjobs-info-alumni-recommend' => 'recommendation.svg',
            'tx-academicjobs-info-link' => 'link.svg',
            'tx-academicjobs-info-endtime' => 'calendar.svg',
            'tx-academicjobs-info-contact-phone' => 'phone.svg',
            'tx-academicjobs-info-contact-email' => 'email.svg',
            'tx-academicjobs-info-starttime' => 'calendar.svg',
            'tx-academicjobs-info-contact-name' => 'person.svg',
            'tx-academicjobs-info-contact-additional-information' => 'information.svg',
        ];
        foreach ($files as $identifier => $file) {
            yield $identifier => [$identifier, $file];
        }
    }

    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconIsAFrontendIconWithTheSharedFile(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertSame(
            'EXT:academic_base/Resources/Public/Icons/info/' . $file,
            $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier)['options']['source'] ?? null,
        );
    }

    /**
     * The default markup is the inlined drawing, sized by the font of the surrounding text,
     * not an `<img>`.
     */
    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconIsInlined(string $identifier, string $file): void
    {
        $markup = $this->getFrontendIcon($identifier)->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringContainsString('width="1em"', $markup);
        $this->assertStringContainsString('height="1em"', $markup);
        $this->assertStringNotContainsString('<img', $markup);
    }

    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconMarkupFollowsTheTextColour(string $identifier, string $file): void
    {
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('jobIcons')]
    public function renderedJobIconCarriesItsIdentifier(string $identifier, string $file): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier($identifier);
    }

    /**
     * Asked through the icon API of the backend, the way `core:icon` asks, the identifier
     * is unknown and the answer is TYPO3's placeholder.
     */
    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconIsNoBackendIcon(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsNotABackendIcon($identifier);
        $this->assertSame(
            'default-not-found',
            $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL)->getIdentifier(),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function backendIconIdentifiers(): \Generator
    {
        yield from RecordIconsTest::recordIconIdentifiers();
        yield 'tx-academicjobs-plugin-jobs' => ['tx-academicjobs-plugin-jobs'];
    }

    /**
     * The record icon and the plugin icon are shown by the backend only, and stay out of
     * the frontend registry.
     */
    #[Test]
    #[DataProvider('backendIconIdentifiers')]
    public function backendIconIsNoFrontendIcon(string $identifier): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered($identifier));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered($identifier));
    }

    /**
     * The identifiers of 2.x and of the 3.0 development state are removed without an
     * alias, from both registries.
     *
     * @return \Generator<string, array{0: string}>
     */
    public static function removedIdentifiers(): \Generator
    {
        $properties = [
            'starttime', 'endtime', 'employmentStartDate', 'companyName', 'employmentType',
            'workLocation', 'sector', 'type', 'requiredDegree', 'contractualRelationship',
            'internationalsWelcome', 'alumniRecommend', 'link', 'contactName', 'contactEmail',
            'contactPhone', 'contactAdditionalInformation',
        ];
        foreach ($properties as $property) {
            yield 'academic_jobs-' . $property => ['academic_jobs-' . $property];
        }
        yield 'academic_jobs_icon' => ['academic_jobs_icon'];
        yield 'tx_academicjobs_domain_model_job' => ['tx_academicjobs_domain_model_job'];
    }

    #[Test]
    #[DataProvider('removedIdentifiers')]
    public function removedIdentifierIsInNoRegistry(string $identifier): void
    {
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered($identifier));
        $this->assertFalse($this->get(IconRegistry::class)->isRegistered($identifier));
    }
}
