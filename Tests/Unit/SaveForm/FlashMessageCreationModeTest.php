<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Unit\SaveForm;

use FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The single decision behind `Controller\JobController::createAction()`: whether the
 * "job created" confirmation is put into the Extbase flash message queue at all.
 *
 * It exists because the queue is keyed per plugin and lives in the session. A message
 * created on the form page and then redirected away is not consumed by the target page,
 * so it resurfaces the next time a visitor opens the form - a confirmation for a job
 * somebody else submitted. Getting this wrong is not a missing message, it is a message
 * shown to the wrong person at the wrong time.
 *
 * The mode is integrator configurable (plugin FlexForm, TypoScript fallback) and a
 * listener of `Event\AfterSaveJobEvent` may replace both the mode and the redirect page
 * before the decision is taken, so every combination below is reachable in production.
 */
final class FlashMessageCreationModeTest extends UnitTestCase
{
    /**
     * The backing values are persisted: they are what a plugin FlexForm and the TypoScript
     * setting `saveForm.fallbackFlashMessageCreationMode` store, and `resolveFlashMessage
     * CreationMode()` casts that stored string through `from()`. Reordering the cases would
     * silently reinterpret every existing installation's configuration, which is why they
     * are pinned here rather than treated as an implementation detail.
     */
    #[Test]
    public function theBackingValuesAreStable(): void
    {
        $this->assertSame(FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, FlashMessageCreationMode::from(0));
        $this->assertSame(FlashMessageCreationMode::ALWAYS, FlashMessageCreationMode::from(1));
        $this->assertSame(FlashMessageCreationMode::NEVER, FlashMessageCreationMode::from(2));
        $this->assertNull(FlashMessageCreationMode::tryFrom(3));
    }

    /**
     * An installation that configures nothing gets the conditional mode, not `ALWAYS`.
     * `resolveFlashMessageCreationMode()` falls through to this for every unset, non
     * numeric or out-of-range setting, so it is the behaviour the majority of sites run.
     */
    #[Test]
    public function theDefaultIsTheConditionalMode(): void
    {
        $this->assertSame(
            FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE,
            FlashMessageCreationMode::default(),
        );
    }

    #[Test]
    #[DataProvider('creationDecisions')]
    public function theDecisionFollowsTheModeAndTheRedirectTarget(
        FlashMessageCreationMode $mode,
        int $currentPageId,
        ?int $redirectPageId,
        bool $expected,
    ): void {
        $this->assertSame($expected, $mode->shouldBeCreated($currentPageId, $redirectPageId));
    }

    /**
     * `ALWAYS` and `NEVER` are unconditional - they must not start consulting the redirect
     * target, because an integrator picking them has accepted the consequence (a stale
     * message, or none at all) deliberately.
     *
     * `SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE` suppresses on exactly one condition: a
     * redirect that actually leaves the current page. All three parts of that condition
     * are covered separately below - a missing target, a target that is not a usable page
     * id, and a target equal to the page the form sits on. Each of them means "the visitor
     * stays here", and staying here is what makes the message consumable.
     *
     * @return \Generator<string, array{0: FlashMessageCreationMode, 1: int, 2: int|null, 3: bool}>
     */
    public static function creationDecisions(): \Generator
    {
        yield 'always, no redirect page' => [FlashMessageCreationMode::ALWAYS, 10, null, true];
        yield 'always, redirect page zero' => [FlashMessageCreationMode::ALWAYS, 10, 0, true];
        yield 'always, negative redirect page' => [FlashMessageCreationMode::ALWAYS, 10, -1, true];
        yield 'always, redirect page is the current page' => [FlashMessageCreationMode::ALWAYS, 10, 10, true];
        yield 'always, redirect to another page' => [FlashMessageCreationMode::ALWAYS, 10, 11, true];

        yield 'never, no redirect page' => [FlashMessageCreationMode::NEVER, 10, null, false];
        yield 'never, redirect page zero' => [FlashMessageCreationMode::NEVER, 10, 0, false];
        yield 'never, negative redirect page' => [FlashMessageCreationMode::NEVER, 10, -1, false];
        yield 'never, redirect page is the current page' => [FlashMessageCreationMode::NEVER, 10, 10, false];
        yield 'never, redirect to another page' => [FlashMessageCreationMode::NEVER, 10, 11, false];

        // The visitor stays on the form page, so the queue is consumed by the very render
        // that follows and the confirmation is shown where it was produced.
        yield 'conditional, no redirect page configured' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 10, null, true];
        yield 'conditional, redirect page is the current page' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 10, 10, true];
        // Zero and negative are not page ids anything can be rendered from. They are
        // treated as "nothing configured" rather than as a redirect - see the note below.
        yield 'conditional, redirect page zero' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 10, 0, true];
        yield 'conditional, negative redirect page' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 10, -1, true];
        // The only combination that suppresses.
        yield 'conditional, redirect to another page' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 10, 11, false];

        // `determineCurrentPageId()` returns an int and is not guaranteed to be positive in
        // every context, so the two page ids being equal at zero must not be mistaken for a
        // redirect - the `> 0` guard decides this row before the equality does.
        yield 'conditional, current page zero and redirect page zero' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 0, 0, true];
        yield 'conditional, current page zero and a real redirect page' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, 0, 11, false];
    }

    /**
     * The redirect page is optional on the signature. Omitting it has to mean the same as
     * passing `null`, because that is the shape any future caller that only knows the
     * current page will use.
     */
    #[Test]
    #[DataProvider('modesWithoutARedirectPage')]
    public function omittingTheRedirectPageMatchesPassingNull(FlashMessageCreationMode $mode, bool $expected): void
    {
        $this->assertSame($expected, $mode->shouldBeCreated(10));
        $this->assertSame($mode->shouldBeCreated(10, null), $mode->shouldBeCreated(10));
    }

    /**
     * @return \Generator<string, array{0: FlashMessageCreationMode, 1: bool}>
     */
    public static function modesWithoutARedirectPage(): \Generator
    {
        yield 'always' => [FlashMessageCreationMode::ALWAYS, true];
        yield 'never' => [FlashMessageCreationMode::NEVER, false];
        yield 'conditional' => [FlashMessageCreationMode::SUPPRESS_WITH_CONFIGURED_REDIRECT_PAGE, true];
    }

    /**
     * Every case has to answer the question - a case added without an arm of the `match`
     * throws `\UnhandledMatchError` at the point a job was already persisted, turning a
     * successful submission into a 500. Iterating `cases()` is what makes that a test
     * failure at the time the case is added instead.
     */
    #[Test]
    public function everyModeAnswersForEveryTarget(): void
    {
        $answers = [];
        foreach (FlashMessageCreationMode::cases() as $mode) {
            foreach ([null, -1, 0, 10, 11] as $redirectPageId) {
                $answers[] = $mode->shouldBeCreated(10, $redirectPageId);
            }
        }

        $this->assertCount(15, $answers);
        $this->assertContainsOnly('bool', $answers);
    }
}
