<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Unit\Event;

use FGTCLB\AcademicBase\Form\FlashMessageCreationMode;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * A redirect page of 0 or below counts as none only when the plugin decides
 * where to send the visitor (ACE-435). The event keeps what a listener set, so
 * a later listener reads the value of an earlier one, not a normalised one.
 */
final class AfterSaveJobEventTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{int}>
     */
    public static function redirectPagesThatAreNoPage(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
    }

    #[DataProvider('redirectPagesThatAreNoPage')]
    #[Test]
    public function aListenerReadsTheRedirectPageAnEarlierListenerSet(int $redirectPageId): void
    {
        $event = new AfterSaveJobEvent(
            request: new ServerRequest(),
            job: new Job(),
            currentPageId: 5,
            settings: [],
            flashMessageCreationMode: FlashMessageCreationMode::default(),
            redirectPageId: null,
        );

        $event->setRedirectPageId($redirectPageId);

        $this->assertSame($redirectPageId, $event->getRedirectPageId());
    }
}
