<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Settings;

use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicJobs\Settings\AcademicJobsSettings;
use FGTCLB\AcademicJobs\Settings\AcademicJobsSettingsFactory;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;

/**
 * Covers how the jobs settings are read: the `Configuration/AcademicJobs/Settings.yaml`
 * of every active package, merged and normalised by the shared classes of
 * `academic_base`, and cached in the `core` cache.
 *
 * The `core` cache is a `PhpFrontend` backed by the file system in a functional test
 * instance and survives between the test methods of this class. Every method therefore
 * starts from a known cache state, and `tearDown()` removes the entry again so a doctored
 * value cannot reach the next test class through the instance.
 */
final class AcademicJobsSettingsFactoryTest extends AbstractAcademicJobsTestCase
{
    /**
     * The flags `academic_jobs` ships, per property.
     */
    private const SHIPPED_FLAGS = [
        'title' => ['required'],
        'employmentType' => ['required'],
        'type' => ['required'],
        'companyName' => ['required'],
        'employmentStartDate' => ['required'],
        'description' => ['required'],
        'link' => ['url'],
        'contactEmail' => ['email'],
        'contactPhone' => ['tel'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreCache()->remove(AcademicJobsSettingsFactory::CACHE_IDENTIFIER);
    }

    protected function tearDown(): void
    {
        $this->coreCache()->remove(AcademicJobsSettingsFactory::CACHE_IDENTIFIER);
        parent::tearDown();
    }

    /**
     * Asserted as a whole: a field silently dropping out of the file is exactly the defect
     * this guards.
     */
    #[Test]
    public function theJobSetCarriesTheShippedFlags(): void
    {
        $this->assertSame(self::SHIPPED_FLAGS, $this->flags($this->subject()->get()->getValidationSet('job')));
    }

    #[Test]
    public function theFlagsChooseTheValidators(): void
    {
        $validations = $this->subject()->get()->getValidationSet('job')->validations;

        $this->assertSame([NotEmptyValidator::class], $validations['title']->validatorClassNames);
        $this->assertSame([UrlValidator::class], $validations['link']->validatorClassNames);
        $this->assertSame([EmailAddressValidator::class], $validations['contactEmail']->validatorClassNames);
        $this->assertSame([], $validations['contactPhone']->validatorClassNames);
        $this->assertSame('tel', $validations['contactPhone']->inputType);
        $this->assertSame('contact_phone', $validations['contactPhone']->fieldName);
    }

    #[Test]
    public function anUnknownSetIsEmptyAndKeepsItsIdentifier(): void
    {
        $validationSet = $this->subject()->get()->getValidationSet('apartment');

        $this->assertSame('apartment', $validationSet->identifier);
        $this->assertSame([], $validationSet->validations);
    }

    /**
     * The settings object the container publishes is the one the factory builds. Without
     * it the controller, the validator and the TCA listener would read nothing.
     */
    #[Test]
    public function theContainerPublishesTheSettingsOfTheFactory(): void
    {
        $this->assertSame(
            self::SHIPPED_FLAGS,
            $this->flags($this->get(AcademicJobsSettings::class)->getValidationSet('job')),
        );
    }

    /**
     * A cold read has to leave the cache filled, otherwise every request parses one file
     * per active package again.
     */
    #[Test]
    public function theSettingsAreWrittenToTheCoreCache(): void
    {
        $this->assertFalse($this->coreCache()->has(AcademicJobsSettingsFactory::CACHE_IDENTIFIER));

        $settings = $this->subject()->get();

        $this->assertEquals($settings, $this->coreCache()->require(AcademicJobsSettingsFactory::CACHE_IDENTIFIER));
    }

    /**
     * The cache wins over the files, which is also why a changed file does not reach a
     * running installation before the core cache is flushed. Pinned with a value the
     * shipped file could never produce.
     */
    #[Test]
    public function theCachedSettingsAreUsedInsteadOfTheFiles(): void
    {
        $cached = new AcademicJobsSettings(['job' => new ValidationSet('job', [])]);
        $this->coreCache()->set(
            AcademicJobsSettingsFactory::CACHE_IDENTIFIER,
            'return ' . var_export($cached, true) . ';',
        );

        $this->assertEquals($cached, $this->subject()->get());
    }

    /**
     * An entry of another shape, such as the raw array an older release cached, is read
     * as a miss.
     */
    #[Test]
    public function aCacheEntryOfAnotherShapeIsIgnored(): void
    {
        $this->coreCache()->set(
            AcademicJobsSettingsFactory::CACHE_IDENTIFIER,
            'return ' . var_export(['validations' => ['job' => ['title' => ['email']]]], true) . ';',
        );

        $this->assertSame(self::SHIPPED_FLAGS, $this->flags($this->subject()->get()->getValidationSet('job')));
    }

    /**
     * @return array<string, list<string>>
     */
    private function flags(ValidationSet $validationSet): array
    {
        $flags = [];
        foreach ($validationSet->validations as $identifier => $validation) {
            $flags[$identifier] = $validation->flags;
        }

        return $flags;
    }

    private function subject(): AcademicJobsSettingsFactory
    {
        return $this->get(AcademicJobsSettingsFactory::class);
    }

    private function coreCache(): PhpFrontend
    {
        $cache = $this->get(CacheManager::class)->getCache('core');
        $this->assertInstanceOf(PhpFrontend::class, $cache);

        return $cache;
    }
}
