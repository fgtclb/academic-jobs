<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins the values an installation stores in "sys_template.include_static_file" and
 * in "pages.tsconfig_includes".
 *
 * They are not implementation detail: they are written into records, so renaming a
 * registered folder silently empties the configuration of every installation that
 * selected it. Whenever an expectation here changes, the extension needs a Breaking
 * changelog entry naming the old and the new value.
 */
final class StaticRegistrationTest extends AbstractAcademicJobsTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function staticTemplateIsRegisteredDataProvider(): \Generator
    {
        yield 'new job form' => [
            'EXT:academic_jobs/Configuration/TypoScript/NewJobForm',
            'Academic Jobs: Jobs New (academic_jobs)',
        ];
        yield 'job list' => [
            'EXT:academic_jobs/Configuration/TypoScript/List',
            'Academic Jobs: Jobs List (academic_jobs)',
        ];
        yield 'job detail' => [
            'EXT:academic_jobs/Configuration/TypoScript/Detail',
            'Academic Jobs: Jobs Detail (academic_jobs)',
        ];
        yield 'all components' => [
            'EXT:academic_jobs/Configuration/TypoScript/Full',
            'Academic Jobs: All components (academic_jobs)',
        ];
        // The value installations stored before the configuration was cut per component.
        // It is the shared plugin block, and it keeps its folder for exactly that reason.
        yield 'shared plugin settings' => [
            'EXT:academic_jobs/Configuration/TypoScript',
            'Academic Jobs: Shared plugin settings (academic_jobs)',
        ];
    }

    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function staticTemplateIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'] ?? [],
        );
    }

    /**
     * The registration above is a string, so it stays green when the folder it names
     * is renamed or removed - which is the failure this test class exists for. A
     * static template that points at a folder without any of the three files the core
     * looks for is not an error either, it simply contributes nothing, so the folder
     * and its content have to be asserted separately.
     */
    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function registeredStaticTemplateFolderExistsAndCarriesTypoScript(string $value, string $label): void
    {
        $path = GeneralUtility::getFileAbsFileName($value);

        $this->assertDirectoryExists(
            $path,
            sprintf('The folder registered as "%s" does not exist.', $label),
        );

        $carriedFiles = array_values(array_filter(
            ['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'],
            static fn(string $fileName): bool => file_exists($path . '/' . $fileName),
        ));

        $this->assertNotSame(
            [],
            $carriedFiles,
            sprintf(
                'The folder registered as "%s" holds none of "constants.typoscript", "setup.typoscript" or'
                    . ' "include_static_file.txt", so the static template delivers nothing.',
                $label,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pageTsConfigFileIsRegisteredDataProvider(): \Generator
    {
        yield 'new job form' => [
            'EXT:academic_jobs/Configuration/TSconfig/NewJobForm/page.tsconfig',
            'Academic Jobs: Jobs New (academic_jobs)',
        ];
        yield 'job list' => [
            'EXT:academic_jobs/Configuration/TSconfig/List/page.tsconfig',
            'Academic Jobs: Jobs List (academic_jobs)',
        ];
        yield 'job detail' => [
            'EXT:academic_jobs/Configuration/TSconfig/Detail/page.tsconfig',
            'Academic Jobs: Jobs Detail (academic_jobs)',
        ];
        yield 'all components' => [
            'EXT:academic_jobs/Configuration/TSconfig/Full/page.tsconfig',
            'Academic Jobs: All components (academic_jobs)',
        ];
    }

    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function pageTsConfigFileIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'] ?? [],
        );
    }

    /**
     * As above, and worse: an unresolved page TSconfig include is silent, so a
     * registration that names a file which is not there configures nothing and reports
     * nothing.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function registeredPageTsConfigFileExists(string $value, string $label): void
    {
        $this->assertFileExists(
            GeneralUtility::getFileAbsFileName($value),
            sprintf('The file registered as "%s" does not exist.', $label),
        );
    }
}
