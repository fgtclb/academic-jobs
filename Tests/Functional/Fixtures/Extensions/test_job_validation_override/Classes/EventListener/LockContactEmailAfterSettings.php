<?php

declare(strict_types=1);

namespace TESTS\TestJobValidationOverride\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * What the changelog tells a site package that has to differ from the settings in
 * the backend: a listener of its own, ordered after the jobs settings. The shipped
 * settings leave the contact e-mail editable, this listener locks it.
 *
 * The core runs a listener with fewer orderings later, and among listeners with as
 * many orderings the one whose identifier sorts first runs first. This one is
 * ordered after `content-blocks-tca` like the jobs listener, and its identifier
 * sorts before that one, so without its ordering after the jobs identifier it
 * would run first and lose the lock to the settings.
 */
final class LockContactEmailAfterSettings
{
    #[AsEventListener(
        identifier: 'academic-fixture/lock-contact-email',
        after: 'content-blocks-tca, academic-jobs/apply-settings-to-tca',
    )]
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['tx_academicjobs_domain_model_job']['columns']['contact_email']['config']['readOnly'] = true;
        $event->setTca($tca);
    }
}
