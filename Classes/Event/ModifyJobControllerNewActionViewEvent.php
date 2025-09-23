<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicJobs\Controller\JobController;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see JobController::newAction()} to allow assigning additional values to the view.
 */
final class ModifyJobControllerNewActionViewEvent
{
    public function __construct(
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
        private readonly FluidViewInterface|CoreViewInterface $view,
    ) {}

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }
}
