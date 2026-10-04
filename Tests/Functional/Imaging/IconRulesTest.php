<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The icon rules of docs/architecture/icons.md, checked for every icon of this extension:
 * its content element and record icons and the property icons of the job views. All of
 * them draw files of the shared set of academic_base, so the extension ships no icon file
 * apart from `Extension.svg` and has no licence notice of its own. The other icon tests
 * spell out the identifiers that exist today. These checks read the registration files,
 * the registries, the TCA and the icon directory instead, so an icon added later is held
 * to the same rules without a test naming it, and an icon file added later has to be
 * registered.
 */
final class IconRulesTest extends AbstractAcademicJobsTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;
    use IconFilesAssertionTrait;

    #[Test]
    public function backendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_jobs', ['plugin', 'record']);
    }

    #[Test]
    public function frontendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertFrontendIconIdentifiersFollowTheNamingScheme('academic_jobs', ['info']);
    }

    #[Test]
    public function everyBackendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('academic_jobs');
    }

    #[Test]
    public function everyFrontendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat('academic_jobs');
    }

    /**
     * Every type of a table of this extension, and every content element named here, names
     * an icon of this extension in the group of its kind, drawn for the colour scheme. A
     * table added later is covered as it is. A content element added later has to be added
     * to the list.
     */
    #[Test]
    public function everyTypeOfTheExtensionNamesAnIconOfItsOwn(): void
    {
        $this->assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn(
            'academic_jobs',
            contentTypes: [
                'academicjobs_newjobform',
                'academicjobs_list',
                'academicjobs_detail',
            ],
        );
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_jobs');
    }
}
