<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Loader;

use FGTCLB\AcademicJobs\Loader\AcademicJobsSettingsLoader;
use FGTCLB\AcademicJobs\Registry\AcademicJobsSettingsRegistry;
use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Covers `AcademicJobsSettingsLoader`, which is the only producer of
 * `AcademicJobsSettingsRegistry` — `Configuration/Services.yaml` declares the registry
 * with this class as its factory. Everything the new-job form offers and everything
 * `JobValidator` rejects therefore starts here.
 *
 * The loader is exercised against the real `PackageManager` and the real `core` cache of
 * the test instance rather than against doubles: the interesting part is not the
 * branching but that a `Configuration/AcademicJobs/Settings.yaml` shipped by an active
 * package is found at all, and that the cached round trip returns the same thing the YAML
 * round trip returns.
 *
 * The `core` cache is a `PhpFrontend` backed by the file system in a functional test
 * instance — the testing framework nulls `hash`, `imagesizes`, `pages` and `rootline`,
 * but not `core` — and it survives between test methods of this class. Every method
 * therefore starts from a known cache state, and `tearDown()` removes the entry again so
 * a doctored value cannot reach the next test class through the instance.
 */
final class AcademicJobsSettingsLoaderTest extends AbstractAcademicJobsTestCase
{
    private const CACHE_IDENTIFIER = 'AcademicJobs_Settings';

    /**
     * The settings `EXT:academic_jobs` ships in `Configuration/AcademicJobs/Settings.yaml`.
     * It is the only `Settings.yaml` in the mono repository, so it is also the complete
     * expected result of a load.
     *
     * @var array<string, mixed>
     */
    private const SHIPPED_SETTINGS = [
        'validations' => [
            'job' => [
                'title' => ['required'],
                'employmentType' => ['required'],
                'type' => ['required'],
                'companyName' => ['required'],
                'employmentStartDate' => ['required'],
                'description' => ['required'],
                'link' => ['url'],
                'contactEmail' => ['email'],
                'contactPhone' => ['number'],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreCache()->remove(self::CACHE_IDENTIFIER);
    }

    protected function tearDown(): void
    {
        $this->coreCache()->remove(self::CACHE_IDENTIFIER);
        parent::tearDown();
    }

    /**
     * The uncached path is what fills the cache on a cold boot, so its result is the
     * definition of what the extension offers. Asserted as a whole rather than key by key:
     * a property silently dropping out of the YAML is exactly the defect this guards.
     */
    #[Test]
    public function uncachedLoadCollectsTheSettingsYamlOfEveryActivePackage(): void
    {
        $this->assertSame(self::SHIPPED_SETTINGS, $this->subject()->loadUncached());
    }

    #[Test]
    public function loadReturnsARegistryCarryingTheShippedSettings(): void
    {
        $registry = $this->subject()->load();

        $this->assertInstanceOf(AcademicJobsSettingsRegistry::class, $registry);
        $this->assertSame(self::SHIPPED_SETTINGS, $registry->getSettings());
    }

    /**
     * The loader memoizes its registry. That matters because the registry is a mutable
     * object with a public `attach()`: a caller that attaches to the registry it received
     * changes what every later caller of the same loader sees.
     */
    #[Test]
    public function loadReturnsTheSameRegistryInstanceOnEveryCall(): void
    {
        $subject = $this->subject();

        $this->assertSame($subject->load(), $subject->load());
    }

    /**
     * Two loaders are two registries — the memoization above is per instance, not global.
     * The single shared instance applications see comes from the container, not from here.
     */
    #[Test]
    public function twoLoadersProduceTwoRegistries(): void
    {
        $this->assertNotSame($this->subject()->load(), $this->subject()->load());
    }

    /**
     * A cold `load()` has to leave the cache populated, otherwise every request re-reads
     * and re-parses one YAML file per active package.
     */
    #[Test]
    public function loadWritesTheCollectedSettingsToTheCoreCache(): void
    {
        $this->assertFalse($this->coreCache()->has(self::CACHE_IDENTIFIER));

        $this->subject()->load();

        $this->assertTrue($this->coreCache()->has(self::CACHE_IDENTIFIER));
        $this->assertSame(self::SHIPPED_SETTINGS, $this->coreCache()->require(self::CACHE_IDENTIFIER));
    }

    /**
     * The warm path wins unconditionally: the YAML files are not consulted at all when the
     * cache holds an array. That is the intended behaviour, and it is also the reason a
     * changed `Settings.yaml` does not reach a running installation before the core cache
     * is flushed — which is why this is pinned with a value the YAML could never produce.
     */
    #[Test]
    public function cachedSettingsAreUsedInsteadOfTheSettingsYaml(): void
    {
        $cachedSettings = ['validations' => ['job' => ['title' => ['email']]]];
        $this->writeCacheEntry($cachedSettings);

        $this->assertSame($cachedSettings, $this->subject()->load()->getSettings());
    }

    /**
     * `getFromCache()` guards the required value with `is_array()`. A cache entry that
     * returns something else — a leftover from an older format, a `false` from a truncated
     * file — must fall back to the YAML files rather than propagate into the registry.
     */
    #[Test]
    public function aCacheEntryThatIsNotAnArrayIsIgnoredAndTheYamlIsReadAgain(): void
    {
        $this->coreCache()->set(self::CACHE_IDENTIFIER, 'return \'not an array\';');

        $this->assertSame(self::SHIPPED_SETTINGS, $this->subject()->load()->getSettings());
    }

    /**
     * An empty settings array is a legitimate cached state — no active package shipping a
     * `Settings.yaml` produces it. It is an array, so it must be honoured and must not be
     * mistaken for a cache miss.
     */
    #[Test]
    public function anEmptyCachedSettingsArrayIsHonouredAsACacheHit(): void
    {
        $this->writeCacheEntry([]);

        $this->assertSame([], $this->subject()->load()->getSettings());
    }

    /**
     * The registry published by the container is the one the loader built, which is what
     * `Configuration/Services.yaml` expresses with its `factory` entry. Without this the
     * controller and `JobValidator` would receive an empty registry and silently validate
     * nothing.
     */
    #[Test]
    public function containerPublishesTheRegistryProducedByTheLoader(): void
    {
        $registry = $this->get(AcademicJobsSettingsRegistry::class);

        $this->assertInstanceOf(AcademicJobsSettingsRegistry::class, $registry);
        $this->assertSame(self::SHIPPED_SETTINGS, $registry->getSettings());
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function writeCacheEntry(array $settings): void
    {
        $this->coreCache()->set(self::CACHE_IDENTIFIER, 'return ' . var_export($settings, true) . ';');
    }

    private function coreCache(): PhpFrontend
    {
        $cache = $this->get(CacheManager::class)->getCache('core');
        $this->assertInstanceOf(PhpFrontend::class, $cache);
        return $cache;
    }

    private function subject(): AcademicJobsSettingsLoader
    {
        return new AcademicJobsSettingsLoader(
            $this->coreCache(),
            $this->get(PackageManager::class),
        );
    }
}
