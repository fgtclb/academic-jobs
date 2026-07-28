<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Tca;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\DeprecatedCoreLabelsTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see DeprecatedCoreLabelsTrait
 */
final class DeprecatedCoreLabelsTest extends AbstractAcademicJobsTestCase
{
    use DeprecatedCoreLabelsTrait;

    #[Group('not-core-13')]
    #[Test]
    public function tcaDoesNotReferenceCoreLabelsRetiredInV14(): void
    {
        $this->assertTcaHasNoDeprecatedCoreLabelReferences(['tx_academicjobs_']);
    }
}
