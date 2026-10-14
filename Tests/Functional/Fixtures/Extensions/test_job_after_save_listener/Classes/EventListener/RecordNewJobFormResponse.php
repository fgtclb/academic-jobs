<?php

declare(strict_types=1);

namespace TESTS\TestJobAfterSaveListener\EventListener;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;

/**
 * Records the response of every action of the new job form plugin, so a test reads the
 * redirect the plugin issued. TYPO3 v12 and v13 pass the headers of an Extbase redirect
 * to `header()`, where a functional test cannot read them, and answer the frontend
 * request with the rendered page. A test empties the record in its `setUp()`.
 */
final class RecordNewJobFormResponse
{
    /**
     * @var list<ResponseInterface>
     */
    public static array $responses = [];

    public function __invoke(AfterRequestDispatchedEvent $event): void
    {
        if ($event->getRequest()->getPluginName() === 'NewJobForm') {
            self::$responses[] = $event->getResponse();
        }
    }
}
