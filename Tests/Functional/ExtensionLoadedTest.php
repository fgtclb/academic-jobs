<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicJobsTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-jobs',
        // extension keys
        'academic_base',
        'academic_jobs',
    ];
}
