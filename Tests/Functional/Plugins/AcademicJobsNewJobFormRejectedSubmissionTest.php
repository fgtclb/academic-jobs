<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * A submission the validation rejects shows the form again with the message of each
 * rejected field, and nothing else. Extbase queued a generic error message of its own for
 * the plugin as well ("An error occurred while trying to call ..."), which named a PHP
 * method to the visitor wherever a page rendered the queue of the plugin. TYPO3 v13 kept
 * it in the session, so it showed up next to the confirmation of the job the visitor sent
 * afterwards (ACE-919).
 *
 * The pages are those of `AcademicJobsNewJobFormRedirectPageTest`: the form on
 * `/new-job`, and `/thanks`, which renders the queue of the plugin in an uncached element.
 */
final class AcademicJobsNewJobFormRejectedSubmissionTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const FORM_PAGE = 'https://www.acme.com/new-job';

    private const JOB_FORM = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-jobs-new ")]//form';

    /**
     * The start of the error message of Extbase.
     */
    private const EXTBASE_ERROR = 'An error occurred while trying to call';

    private const CONFIRMATION = 'Your job advert has been successfully created';

    /**
     * Renders the queue of the plugin on the form page as well, as a site does that
     * renders it on every page.
     */
    private const QUEUE_ON_THE_FORM_PAGE = <<<'TYPOSCRIPT'
        [traverse(page, "uid") == 5]
            page.20 = COA_INT
            page.20.10 = FLUIDTEMPLATE
            page.20.10.file = EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/Templates/NewJobFormMessages.html
        [END]
        TYPOSCRIPT;

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'MAIL' => [
                'transport' => 'null',
            ],
        ]);
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsNewJobFormRedirectPage/jobPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function aRejectedSubmissionShowsTheFieldErrorsAndNoErrorMessageOfExtbase(): void
    {
        $this->setUpSite(self::QUEUE_ON_THE_FORM_PAGE);

        $page = (string)$this->submitJob('')->getBody();

        $this->assertSame(0, $this->jobCount());
        $this->assertStringContainsString('aria-invalid="true"', $page, 'The form is not shown again with the error of the title.');
        $this->assertStringNotContainsString(self::EXTBASE_ERROR, $page);
    }

    #[Test]
    public function theConfirmationAfterARejectedSubmissionComesWithoutAnErrorMessageOfExtbase(): void
    {
        $this->setUpSite(
            "plugin.tx_academicjobs.settings.saveForm.fallbackRedirectPageId = 4\n"
            . 'plugin.tx_academicjobs.settings.saveForm.fallbackFlashMessageCreationMode = 1',
        );

        $rejected = $this->submitJob('');
        $this->assertSame(0, $this->jobCount());
        $sessionCookie = $this->frontendSessionCookie($rejected);
        $saved = $this->submitJob('Research assistant', $sessionCookie);
        $this->assertSame(1, $this->jobCount());
        $sessionCookie = $this->frontendSessionCookie($saved) ?? $sessionCookie;
        $this->assertNotNull($sessionCookie, 'The confirmation was not stored in a session.');

        // The page is requested rather than reached through the redirect, so the messages of
        // the session are read the same way whatever the answer to the submission was.
        $thanks = $this->renderFrontendPage(
            (new InternalRequest('https://www.acme.com/thanks'))->withCookieParams(['fe_typo_user' => $sessionCookie]),
        );
        $this->assertStringContainsString(self::CONFIRMATION, $thanks);
        $this->assertStringNotContainsString(self::EXTBASE_ERROR, $thanks);
    }

    private function submitJob(string $title, ?string $sessionCookie = null): ResponseInterface
    {
        return $this->submitFrontendForm(
            self::FORM_PAGE,
            self::JOB_FORM,
            [
                'tx_academicjobs_newjobform' => [
                    'job' => [
                        'title' => $title,
                        'description' => 'A new job description',
                        'companyName' => 'ACME Inc.',
                        'employmentStartDate' => '2027-04-01',
                        'employmentType' => '1',
                        'type' => '1',
                    ],
                ],
            ],
            $sessionCookie !== null ? ['fe_typo_user' => $sessionCookie] : [],
        );
    }

    /**
     * A job sent through the form is saved hidden, so the restrictions are removed.
     */
    private function jobCount(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_academicjobs_domain_model_job');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder->count('uid')->from('tx_academicjobs_domain_model_job')->executeQuery()->fetchOne();
    }

    private function setUpSite(string $setup): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    // Renders the queue of the plugin on `/thanks`.
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/NewJobFormMessages.typoscript',
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }
}
