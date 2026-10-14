<?php

declare(strict_types=1);

namespace TESTS\TestJobAfterSaveListener\EventListener;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;

/**
 * Records the response of every action of the new job form plugin, so a test reads the
 * redirect the plugin issued. TYPO3 v14 answers the frontend request with it, while v13
 * passes its headers to `header()`, where a functional test cannot read them. A test
 * empties the record in its `setUp()`.
 */
final class RecordNewJobFormResponse
{
    /**
     * @var list<ResponseInterface>
     */
    public static array $responses = [];

    #[AsEventListener(identifier: 'test-job-after-save-listener/record-new-job-form-response')]
    public function __invoke(AfterRequestDispatchedEvent $event): void
    {
        if ($event->getRequest()->getPluginName() === 'NewJobForm') {
            self::$responses[] = $event->getResponse();
        }
    }
}
