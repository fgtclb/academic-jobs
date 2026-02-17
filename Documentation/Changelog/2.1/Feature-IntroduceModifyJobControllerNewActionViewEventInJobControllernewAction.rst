.. include:: /Includes.rst.txt

.. _feature-1757757630:

==========================================================================================
Feature: Introduce `ModifyJobControllerNewActionViewEvent` in `JobController::newAction()`
==========================================================================================

Description
===========

`JobController::newAction()` dispatches now the newly introduced PSR-14 event
`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent` providing
following methods:

* :php:`function getPluginControllerActionContext(): PluginControllerActionAcontext`
  to extbase controller action plugin context data, which can be used to make
  decisions using the event.
* :php:`function getView(): FluidViewInterface|CoreViewInterface` to retrieve the
  view instance, which can be used to assign additional data, but disallowing to
  replace the instance.

Impact
======

Implementing a event listener for this event allows attaching additional
variables to the view for the `JobController::newAction()`, for example
to provide additional select field options in case fields are changed,
from text to a select field for example and avoid the need to implement
a custom ViewHelper to retrieve the "select options" within the modified
views.

Example event listener
----------------------

..  code-block:: php
    :caption: EXT:my_ext/Classes/EventListener/ModifyJobControllerNewActionViewListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExt\EventListener;

    use FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent;
    use TYPO3\CMS\Fluid\View\FluidViewInterface;

    final class ModifyJobControllerNewActionViewListener
    {
        public function __invoke(ModifyJobControllerNewActionViewEvent $event): void
        {
            $view = $event->getView();
            if ($view instanceof FluidViewInterface) {
                // Assign additional variable to the view
                $view->assign('myCustomVariable', 'My Custom Value');
            }
        }
    }

.. index:: Frontend
