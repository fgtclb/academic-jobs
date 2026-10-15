<?php

declare(strict_types=1);

namespace TESTS\TestJobAfterSaveListener\EventListener;

use FGTCLB\AcademicJobs\Domain\Repository\JobRepository;
use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the title of the saved job to the plugin setting `settings.testUpdatedTitle`,
 * which a test sets in TypoScript, and hands the job to the repository the way a project
 * listener would, without persisting it itself. Extbase persists what a request changed
 * once the action ended, so the form has to do that before it ends the request with its
 * redirect. The listener stays inert while the setting is absent.
 */
final readonly class UpdateJobTitle
{
    public function __construct(
        private JobRepository $jobRepository,
    ) {}

    #[AsEventListener(identifier: 'test-job-after-save-listener/update-job-title')]
    public function __invoke(AfterSaveJobEvent $event): void
    {
        $title = $event->getSettings()['testUpdatedTitle'] ?? null;
        if (!is_string($title) || $title === '') {
            return;
        }
        $job = $event->getJob();
        $job->setTitle($title);
        $this->jobRepository->update($job);
    }
}
