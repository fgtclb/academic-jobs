<?php

declare(strict_types=1);

namespace TESTS\TestJobAfterSaveListener\EventListener;

use FGTCLB\AcademicJobs\Event\AfterSaveJobEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Hands in the redirect page a project listener would, taken from the plugin setting
 * `settings.testRedirectPageId`, which a test sets in TypoScript. The value is passed on
 * as an integer without any check, a page id of 0 or below included. The listener stays
 * inert while the setting is absent.
 */
final class SetRedirectPage
{
    #[AsEventListener(identifier: 'test-job-after-save-listener/set-redirect-page')]
    public function __invoke(AfterSaveJobEvent $event): void
    {
        $redirectPageId = $event->getSettings()['testRedirectPageId'] ?? null;
        if (is_numeric($redirectPageId)) {
            $event->setRedirectPageId((int)$redirectPageId);
        }
    }
}
