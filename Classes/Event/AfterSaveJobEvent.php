<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Event;

use FGTCLB\AcademicJobs\Controller\JobController;
use FGTCLB\AcademicJobs\Domain\Model\Job;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Event is fired after {@see JobController::newJobFormAction()} job form has been
 * processed and persisted in {@see JobController::saveJobAction()} and can be used
 * as pure notification to process additional tasks or to control if the default
 * job saved mail should be sent or not {@see self::setSendMail()}.
 */
final class AfterSaveJobEvent
{
    private bool $sendMail = true;

    /**
     * @param array<string, mixed> $pluginSettings
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly Job $job,
        private readonly array $pluginSettings = [],
        private ?int $listPid = null,
    ) {
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * Current request, extbase request in TYPO3 v11 (decorated)
     * and the enriched direct request object in TYPO3 v12 and
     * newer. Consuming code needs to take care of this on their
     * own.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Return the plugin settings (pi_flexform, ...) as array.
     *
     * @return array<string, mixed>
     */
    public function getPluginSettings(): array
    {
        return $this->pluginSettings;
    }

    /**
     * Short circuit access to listPid plugin settings.
     */
    public function getListPid(): ?int
    {
        return $this->listPid;
    }

    /**
     * Set pageId of page to redirect (list page).
     *
     * Can be set to `null` to redirect to form page.
     */
    public function setListPid(?int $listPid): self
    {
        $this->listPid = $listPid;
        return $this;
    }

    /**
     * States if default job saved email should be sent or not.
     */
    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    /**
     * Set if default job saved email should be sent or not.
     */
    public function setSendMail(bool $sendMail): AfterSaveJobEvent
    {
        $this->sendMail = $sendMail;
        return $this;
    }
}
