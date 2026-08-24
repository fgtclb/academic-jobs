<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Core13\SiteSet;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\AcademicJobs\Tests\Functional\SiteSet\DeliveryProbeTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Proves that the site sets of this extension deliver what their `config.yaml` claims.
 *
 * Both keys of a set are strings that the core resolves at runtime, and both fail
 * silently when they are wrong: `SysTemplateTreeBuilder::handleSetInclude()` and
 * `TsConfigTreeBuilder::getSitePageTsConfigTree()` `file_exists()`-guard the files they
 * read and simply continue when one is missing. A typo in `typoscript:` or in `pagets:`
 * therefore produces no error anywhere, only a site that is configured differently than
 * the integrator expects - which is the whole reason this restructuring exists.
 *
 * TYPO3 v12 has no site set API at all, which is why this class sits in a `Core13`
 * folder rather than only carrying the group attribute: `SetRegistry` and `SetDefinition`
 * do not exist there, and the group excludes the class from PHPUnit but not from PHPStan,
 * which analyses the sources against the installed core. The static template half of the
 * same delivery is tested for both core versions in
 * `Tests/Functional/SiteSet/StaticTemplateDeliveryTest.php`.
 */
#[Group('not-core-12')]
final class SiteSetDeliveryTest extends AbstractAcademicJobsTestCase
{
    use DeliveryProbeTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const AGGREGATE_SET = 'fgtclb/academic-jobs';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function siteSetDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->sharedConstantMarkup(),
            $body,
            'The site set did not deliver "constants.typoscript" of the shared block.',
        );
        $this->assertStringContainsString(
            $this->sharedSetupMarkup(),
            $body,
            'The site set did not deliver "setup.typoscript" of the shared block.',
        );
        $this->assertStringContainsString(
            $this->sharedSettingMarkup(),
            $body,
            'The site set delivered a different default for "detailPid" than the shared constants assign.',
        );
    }

    /**
     * The point of the layout here: a component folder holds nothing but an
     * `include_static_file.txt` naming the shared folder, and that is what has to arrive
     * when a site depends on a single component.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetDeliversTheSharedTypoScriptThroughItsIncludeStaticFile(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->sharedConstantMarkup(),
            $body,
            sprintf('The set "%s" did not deliver the constants of the shared block.', $set),
        );
        $this->assertStringContainsString(
            $this->sharedSetupMarkup(),
            $body,
            sprintf('The set "%s" did not deliver the setup of the shared block.', $set),
        );
    }

    /**
     * The other half of the delivery: the content elements are hidden for the whole
     * installation, and naming a set in the site configuration is one of the two ways to
     * bring one back. No page carries a `tsconfig_includes` entry here, so the set is the
     * only thing that can do it.
     */
    #[Test]
    public function siteSetDeliversThePageTsConfigOfEveryComponent(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizardElements = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The aggregate set did not re-enable the content element "%s".', $component[1]),
            );
            $this->assertArrayHasKey(
                $component[1] . '.',
                $wizardElements,
                sprintf('The aggregate set did not deliver the wizard entry of "%s".', $component[1]),
            );
        }
    }

    /**
     * A component set re-enables its own content element and nothing else. Without this
     * the whole per-component split is decoration: one page TSconfig file that re-enabled
     * all three would pass every other assertion here.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetReEnablesItsOwnContentElementOnly(string $set, string $contentElementType): void
    {
        $this->setUpSite(dependencies: [$set]);

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The set "%s" did not re-enable "%s".', $set, $contentElementType),
        );
        foreach (self::componentDataProvider() as $component) {
            if ($component[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The set "%s" also re-enabled "%s".', $set, $component[1]),
            );
        }
    }

    /**
     * Pins the two strings the tests above depend on, and the files they point at.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetPointsAtTheFilesTheStaticRegistrationUses(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $component = $this->setRegistry()->getSet($set);

        $this->assertNotNull($component, sprintf('The set "%s" is not registered.', $set));
        $this->assertSame($typoScriptPath, $component->typoscript);
        $this->assertSame($pageTsConfigPath, $component->pagets);
        $this->assertDirectoryExists(GeneralUtility::getFileAbsFileName((string)$component->typoscript));
        $this->assertFileExists(GeneralUtility::getFileAbsFileName((string)$component->pagets));
    }

    /**
     * The aggregate carries no payload of its own on purpose: it delivers through the
     * component sets, and a `typoscript:` of its own would parse the same files twice.
     * The name is the one this extension published before the split, so a site
     * configuration that depends on it needs no change.
     */
    #[Test]
    public function aggregateSetDependsOnEveryComponentAndCarriesNoPayload(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);

        $this->assertNotNull($aggregate, sprintf('The set "%s" is not registered.', self::AGGREGATE_SET));
        foreach (self::componentDataProvider() as $component) {
            $this->assertContains($component[0], $aggregate->dependencies);
        }
        $this->assertSetCarriesNoPayload($aggregate);
    }

    /**
     * The settings of this extension belong to the shared block and are declared with the
     * aggregate set, once. Every default has to stay identical to what
     * `constants.typoscript` assigns for the same path, so that a site using both delivery
     * mechanisms does not have its configuration reset by the second parse.
     */
    #[Test]
    public function settingsAreDeclaredWithTheAggregateSetOnly(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);
        $this->assertNotNull($aggregate);

        $definitions = [];
        foreach ($aggregate->settingsDefinitions as $definition) {
            $definitions[$definition->key] = $definition->default;
        }

        $this->assertSame(
            [
                'plugin.tx_academicjobs.persistence.storagePid' => 0,
                'plugin.tx_academicjobs.detailPid' => 0,
                'plugin.tx_academicjobs.listPid' => 0,
                'plugin.tx_academicjobs.email.from' => '',
                'plugin.tx_academicjobs.email.recipientEmail' => '',
                'plugin.tx_academicjobs.email.subject' => 'New job application',
                'plugin.tx_academicjobs.email.template' => 'A new job application has been submitted. Please check the backend.',
                'plugin.tx_academicjobs.saveForm.fallbackRedirectPageId' => 0,
                'plugin.tx_academicjobs.saveForm.fallbackFlashMessageCreationMode' => 0,
                'plugin.tx_academicjobs.jobAvatarImage.uploadFolder' => '1:/global-content/jobs/logos/',
                'plugin.tx_academicjobs.jobAvatarImage.validation.fileSize.maximum' => '2M',
                'plugin.tx_academicjobs.jobAvatarImage.validation.mimeType.allowedMimeTypes' => 'image/jpeg,image/png,image/webp,image/svg+xml',
            ],
            $definitions,
        );

        foreach (self::componentDataProvider() as $component) {
            $set = $this->setRegistry()->getSet($component[0]);
            $this->assertNotNull($set);
            $this->assertSame(
                [],
                $set->settingsDefinitions,
                sprintf('The set "%s" declares settings of its own.', $component[0]),
            );
        }
    }

    private function setRegistry(): SetRegistry
    {
        $setRegistry = $this->get(SetRegistry::class);
        $this->assertInstanceOf(SetRegistry::class, $setRegistry);

        return $setRegistry;
    }

    /**
     * A set that declares neither key does not get `null`: the core defaults both to the
     * set folder itself (`YamlSetDefinitionProvider::createDefinition()`), and reads
     * whatever it finds there. "Carries no payload" therefore means the set folder holds
     * none of the four files the two mechanisms look for.
     */
    private function assertSetCarriesNoPayload(SetDefinition $set): void
    {
        $typoScriptPath = rtrim(GeneralUtility::getFileAbsFileName((string)$set->typoscript), '/') . '/';
        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $fileName) {
            $this->assertFileDoesNotExist(
                $typoScriptPath . $fileName,
                sprintf('The set "%s" carries a payload of its own: %s', $set->name, $fileName),
            );
        }
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$set->pagets),
            sprintf('The set "%s" carries a page TSconfig of its own.', $set->name),
        );
    }
}
