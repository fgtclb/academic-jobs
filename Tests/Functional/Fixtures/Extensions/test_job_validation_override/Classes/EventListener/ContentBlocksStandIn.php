<?php

declare(strict_types=1);

namespace TESTS\TestJobValidationOverride\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * Stands in for the TCA listener of EXT:content_blocks, under its identifier. The
 * jobs settings have to be applied after it, so its lock of the link does not
 * survive them.
 */
final class ContentBlocksStandIn
{
    #[AsEventListener(identifier: 'content-blocks-tca')]
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['tx_academicjobs_domain_model_job']['columns']['link']['config']['readOnly'] = true;
        $event->setTca($tca);
    }
}
