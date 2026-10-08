<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\PageTitle;

use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

/**
 * The title of the page that shows a job, registered as `academicJobs` in
 * `config.pageTitleProviders`.
 *
 * {@see JobController::showAction()} sets the title of the job it shows. The page title
 * API of the core keeps the title in the provider until the page renders its `<title>`,
 * which is why this class holds state. The core shares the one instance, a provider is a
 * singleton.
 */
final class JobTitleProvider extends AbstractPageTitleProvider
{
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
