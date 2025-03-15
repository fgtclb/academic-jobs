<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Event;

use FGTCLB\AcademicJobs\Domain\Model\Job;
use FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode;
use Psr\Http\Message\ServerRequestInterface;

final class AfterSaveJobEvent
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly Job $job,
        private readonly int $currentPageId,
        private readonly array $settings,
        private FlashMessageCreationMode $flashMessageCreationMode,
        private ?int $redirectPageId,
    ) {}

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getCurrentPageId(): int
    {
        return $this->currentPageId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get page to redirect after persisting new job. NULL redirects to the same page (new form).
     *
     * Currently, global TypoScript `listPid` setting is used as last fallback to redirect after
     * successfully persisting a new job, which is deprecated and will be removed in the future.
     *
     * @return int|null
     */
    public function getRedirectPageId(): ?int
    {
        return $this->redirectPageId;
    }

    /**
     * Set page to redirect after persisting new job. NULL redirects to the same page (new form).
     *
     * Currently, global TypoScript `listPid` setting is used as last fallback to redirect after
     * successfully persisting a new job, which is deprecated and will be removed in the future.
     */
    public function setRedirectPageId(?int $redirectPageId): self
    {
        $this->redirectPageId = $redirectPageId;
        return $this;
    }

    public function getFlashMessageCreationMode(): FlashMessageCreationMode
    {
        return $this->flashMessageCreationMode;
    }

    public function setFlashMessageCreationMode(FlashMessageCreationMode $flashMessageCreationMode): AfterSaveJobEvent
    {
        $this->flashMessageCreationMode = $flashMessageCreationMode;
        return $this;
    }
}
