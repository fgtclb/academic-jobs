<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Domain\Validator;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\Domain\Validator\JobValidator;
use FGTCLB\AcademicJobs\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * Covers `JobValidator`, the validator `JobController::initializeCreateAction()` adds to
 * the `job` argument of the public new-job form. What it rejects is what a visitor cannot
 * submit, so both directions matter: a job that must be refused, and a job that must not
 * be refused for the wrong reason.
 *
 * The validator is built the way the controller builds it —
 * `ValidatorResolver::createValidator()`, i.e. `GeneralUtility::makeInstance()` through
 * the container — because `injectSettingsRegistry()` is a dependency injection setter. A
 * validator constructed with `new` has no registry and fails on an uninitialized typed
 * property, so the container round trip is a load-bearing part of what is asserted here,
 * not a convenience.
 *
 * The rules themselves come from the shipped
 * `Configuration/AcademicJobs/Settings.yaml` via `AcademicJobsSettingsRegistry` — see
 * `Tests/Functional/Registry/AcademicJobsSettingsRegistryTest` for the mapping from
 * keyword to validator class.
 */
final class JobValidatorTest extends AbstractAcademicJobsTestCase
{
    /**
     * A job carrying every value the shipped settings mark as required passes. Without
     * this the "rejects everything" mistake would be indistinguishable from a working
     * validator.
     */
    #[Test]
    public function aFullyFilledJobIsAccepted(): void
    {
        $result = $this->subject()->validate($this->createValidJob());

        $this->assertFalse($result->hasErrors());
        $this->assertSame([], $result->getFlattenedErrors());
    }

    /**
     * The four string and date properties that are really empty on a fresh model are
     * reported, each on its own property path so the Fluid form can show the message at
     * the field.
     *
     * Note what is *not* in this list: `type` and `employmentType` are configured
     * `required` too, but they are `int` properties defaulting to `0`, and
     * `NotEmptyValidator` treats `0` as a value. An unselected job type therefore passes
     * this validator — the assertion is written as a whole list so that the gap is stated
     * rather than merely unnoticed.
     */
    #[Test]
    public function anEmptyJobReportsEveryRequiredPropertyThatIsActuallyEmpty(): void
    {
        $result = $this->subject()->validate(new Job());

        $this->assertTrue($result->hasErrors());
        $this->assertSame(
            ['title', 'companyName', 'employmentStartDate', 'description'],
            array_keys($result->getFlattenedErrors()),
        );
    }

    /**
     * `NotEmptyValidator` distinguishes a `null` from an empty string by error code. Pinning
     * the codes proves the errors come from the mapped validator and not from some other
     * part of the chain, and that the unset date really arrives as `null`.
     */
    #[Test]
    public function requiredPropertiesReportTheNotEmptyValidatorCodes(): void
    {
        $result = $this->subject()->validate(new Job());

        $this->assertSame(1221560718, $this->firstErrorCode($result, 'title'));
        $this->assertSame(1221560910, $this->firstErrorCode($result, 'employmentStartDate'));
    }

    /**
     * A zeroed integer is not empty. Setting both integer properties to a real value must
     * therefore change nothing — which is the same statement as above, made from the other
     * side, and it is what pins the behaviour if the mapping for those two ever changes.
     */
    #[Test]
    public function selectingAJobTypeChangesNothingBecauseZeroWasAlreadyAccepted(): void
    {
        $job = new Job();
        $job->setType(3);
        $job->setEmploymentType(2);

        $result = $this->subject()->validate($job);

        $this->assertSame(
            ['title', 'companyName', 'employmentStartDate', 'description'],
            array_keys($result->getFlattenedErrors()),
        );
    }

    #[Test]
    public function anInvalidContactEmailIsReportedOnItsOwnProperty(): void
    {
        $job = $this->createValidJob();
        $job->setContactEmail('not-an-email');

        $result = $this->subject()->validate($job);

        $this->assertSame(['contactEmail'], array_keys($result->getFlattenedErrors()));
        $this->assertSame(1221559976, $this->firstErrorCode($result, 'contactEmail'));
    }

    /**
     * `contactEmail` is configured `email`, not `required`, and `EmailAddressValidator`
     * accepts empty values. Leaving the field blank must stay possible — the new-job form
     * offers it as optional.
     */
    #[Test]
    public function anEmptyContactEmailIsAccepted(): void
    {
        $job = $this->createValidJob();
        $job->setContactEmail('');

        $this->assertFalse($this->subject()->validate($job)->hasErrors());
    }

    #[Test]
    public function anInvalidLinkIsReportedOnItsOwnProperty(): void
    {
        $job = $this->createValidJob();
        $job->setLink('not a url');

        $result = $this->subject()->validate($job);

        $this->assertSame(['link'], array_keys($result->getFlattenedErrors()));
        $this->assertSame(1238108078, $this->firstErrorCode($result, 'link'));
    }

    #[Test]
    public function anAbsoluteLinkIsAccepted(): void
    {
        $job = $this->createValidJob();
        $job->setLink('https://www.acme.com/jobs/42');

        $this->assertFalse($this->subject()->validate($job)->hasErrors());
    }

    #[Test]
    public function anEmptyLinkIsAccepted(): void
    {
        $job = $this->createValidJob();
        $job->setLink('');

        $this->assertFalse($this->subject()->validate($job)->hasErrors());
    }

    /**
     * `contactPhone` is configured `number` in the shipped settings, and the registry maps
     * no validator class for that keyword. Nothing therefore checks the value here, no
     * matter what is submitted. The frontend reader hands `number` to the template, so the
     * form may well mark the field — the server side does not.
     */
    #[Test]
    public function contactPhoneIsNotValidatedAtAll(): void
    {
        $job = $this->createValidJob();
        $job->setContactPhone('definitely not a number');

        $this->assertFalse($this->subject()->validate($job)->hasErrors());
    }

    /**
     * Every configured property is checked in one pass, not up to the first failure — the
     * form has to be able to mark all offending fields at once.
     */
    #[Test]
    public function allViolationsOfOneSubmissionAreCollected(): void
    {
        $job = new Job();
        $job->setLink('not a url');
        $job->setContactEmail('not-an-email');

        $result = $this->subject()->validate($job);

        $this->assertSame(
            ['title', 'companyName', 'employmentStartDate', 'description', 'link', 'contactEmail'],
            array_keys($result->getFlattenedErrors()),
        );
    }

    /**
     * `AbstractValidator::validate()` starts a fresh `Result` on every call. Reusing one
     * validator instance for a second submission must not carry the first one's errors
     * over — the controller keeps the validator on the argument across a re-displayed
     * form.
     */
    #[Test]
    public function aSecondValidationDoesNotInheritTheErrorsOfTheFirst(): void
    {
        $subject = $this->subject();
        $subject->validate(new Job());

        $this->assertFalse($subject->validate($this->createValidJob())->hasErrors());
    }

    /**
     * The type guard. It is a hard failure rather than a validation error, because a
     * non-`Job` argument on the `job` argument is a programming mistake, not visitor input.
     */
    #[Test]
    public function validatingSomethingThatIsNotAJobThrows(): void
    {
        $this->expectException(UnsuitableValidatorException::class);
        $this->expectExceptionCode(1753702412);

        $this->subject()->validate(new \stdClass());
    }

    /**
     * The guard is only reached for values `AbstractValidator` considers non-empty, and
     * `null` is not one of them: `isValid()` is never called, so a `null` job is reported
     * as valid rather than as unsuitable. That is core behaviour, and it is the reason
     * `JobController::createAction()` still has to handle `$job === null` itself.
     */
    #[Test]
    public function validatingNullIsAcceptedWithoutReachingTheTypeGuard(): void
    {
        $result = $this->subject()->validate(null);

        $this->assertFalse($result->hasErrors());
    }

    /**
     * The same for the empty string — while any other string does reach the guard and
     * throws. Both are pinned together because the pair is what makes the asymmetry
     * visible.
     */
    #[Test]
    public function validatingAnEmptyStringIsAcceptedWhileAnyOtherStringThrows(): void
    {
        $this->assertFalse($this->subject()->validate('')->hasErrors());

        $this->expectException(UnsuitableValidatorException::class);
        $this->subject()->validate('some string');
    }

    /**
     * `processValidations()` is public and takes the identifier, so it is usable for
     * something other than a job. An identifier the settings do not configure validates
     * anything without complaint instead of failing.
     *
     * Note that this only holds for an identifier without configuration. The method writes
     * to `$this->result`, which `AbstractValidator::validate()` creates — calling it
     * standalone with a configured identifier fatals on the uninitialized property, so it
     * is public but not usable on its own.
     */
    #[Test]
    public function processValidationsForAnUnconfiguredIdentifierReportsNothing(): void
    {
        $subject = $this->subject();
        $subject->processValidations(new Job(), 'unknown');

        $this->assertFalse($subject->validate($this->createValidJob())->hasErrors());
    }

    private function createValidJob(): Job
    {
        $job = new Job();
        $job->setTitle('Research Assistant');
        $job->setCompanyName('ACME University');
        $job->setDescription('<p>A description of the position.</p>');
        $job->setEmploymentStartDate(new \DateTime('2026-10-01'));
        $job->setType(1);
        $job->setEmploymentType(1);
        $job->setLink('https://www.acme.com/jobs/1');
        $job->setContactEmail('editor@acme.com');
        $job->setContactPhone('+49 123 456789');

        return $job;
    }

    private function firstErrorCode(Result $result, string $propertyPath): int
    {
        $errors = $result->forProperty($propertyPath)->getErrors();
        $this->assertNotSame([], $errors);

        return $errors[0]->getCode();
    }

    private function subject(): JobValidator
    {
        $validator = $this->get(ValidatorResolver::class)->createValidator(JobValidator::class);
        $this->assertInstanceOf(JobValidator::class, $validator);

        return $validator;
    }
}
