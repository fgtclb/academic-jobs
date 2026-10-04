<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The icons of the job views are frontend icons: registered in
 * `Configuration/FrontendIcons.php` and rendered by `ab:icon` of academic_base. The
 * backend never shows them, so the icon registry of the backend must not know them, or a
 * site that replaces one in `Configuration/Icons.php` sees no effect and no error. The
 * record icon and the plugin icon are the opposite case, backend icons only.
 *
 * The identifiers and files are spelled out here rather than read back out of the
 * registration, so a rename has to be made twice instead of silently agreeing with itself.
 */
final class FrontendIconsTest extends AbstractAcademicJobsTestCase
{
    use FrontendIconsAssertionTrait;

    /**
     * The twelve property icons of the job list and the detail view, the two icons of the
     * contact block, and the three no shipped template renders.
     *
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function jobIcons(): \Generator
    {
        $files = [
            'academic_jobs-employmentStartDate' => 'Calendar.svg',
            'academic_jobs-companyName' => 'Company.svg',
            'academic_jobs-sector' => 'Industry.svg',
            'academic_jobs-type' => 'jobs_icon.svg',
            'academic_jobs-requiredDegree' => 'School.svg',
            'academic_jobs-contractualRelationship' => 'Contract.svg',
            'academic_jobs-employmentType' => 'Work.svg',
            'academic_jobs-workLocation' => 'Location.svg',
            'academic_jobs-internationalsWelcome' => 'Public.svg',
            'academic_jobs-alumniRecommend' => 'Star.svg',
            'academic_jobs-link' => 'Link.svg',
            'academic_jobs-endtime' => 'Calendar.svg',
            'academic_jobs-contactPhone' => 'Phone.svg',
            'academic_jobs-contactEmail' => 'Email.svg',
            'academic_jobs-starttime' => 'Calendar.svg',
            'academic_jobs-contactName' => 'Person.svg',
            'academic_jobs-contactAdditionalInformation' => 'Info.svg',
        ];
        foreach ($files as $identifier => $file) {
            yield $identifier => [$identifier, $file];
        }
    }

    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconIsAFrontendIconWithTheShippedFile(string $identifier, string $file): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, SvgIconProvider::class);
        $this->assertSame(
            'EXT:academic_jobs/Resources/Public/Icons/' . $file,
            $this->get(FrontendIconRegistry::class)->getIconConfiguration($identifier)['options']['source'] ?? null,
        );
    }

    /**
     * The default markup stays an `<img>`, the way the job views have always shown these
     * icons.
     */
    #[Test]
    #[DataProvider('jobIcons')]
    public function jobIconRendersAsAnImage(string $identifier, string $file): void
    {
        $markup = $this->getFrontendIcon($identifier)->getMarkup();

        $this->assertStringStartsWith('<img', $markup);
        $this->assertStringContainsString('/Icons/' . $file, $markup);
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
        yield 'academic_jobs_icon' => ['academic_jobs_icon'];
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
}
