..  _breaking-removed-job-new-action-view-event:

====================================================
Breaking: The view event of the new job form is gone
====================================================

Description
===========

The new job form no longer dispatches
:php:`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent`, and the
class is removed.

Every plugin of this extension, the job list and the job detail included,
dispatches :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base` instead, once each time it renders. It is one event
for every academic plugin and carries the same two things the removed one did:
the plugin action context of :guilabel:`academic_base` and the view. See
:ref:`feature-plugin-view-event` and the `changelog of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

As before, the form assigns the validations after the event, so a listener
cannot replace them.

Impact
======

A listener of the removed event is no longer called. It causes no error: TYPO3
registers the event of a listener as a class name, read from the type of its
parameter or from the :php:`event` argument of the attribute, without loading
the class, and PHP checks the type of a parameter only when the method is
called. The variables the listener assigned are missing from the form.

PHPStan reports the listener, because the class of its parameter does not exist
any more:

..  code-block:: text

    Parameter $event of method MyVendor\MySitepackage\EventListener\AddFormHint::__invoke()
    has invalid type FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent.

Affected installations
======================

Installations with an event listener of
:php:`ModifyJobControllerNewActionViewEvent`, registered with an attribute or in
:file:`Configuration/Services.yaml`. Searching the project code for the class
name finds them.

Migration
=========

Register the listener for :php:`ModifyPluginViewEvent` and return early for
every other plugin:

..  code-block:: php
    :caption: Before

    use FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddFormHint
    {
        #[AsEventListener]
        public function __invoke(ModifyJobControllerNewActionViewEvent $event): void
        {
            $event->getView()->assign('formHintPageId', 42);
        }
    }

..  code-block:: php
    :caption: After

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddFormHint
    {
        #[AsEventListener]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicJobs'
                || $context->getActionName() !== 'new'
            ) {
                return;
            }
            $event->getView()->assign('formHintPageId', 42);
        }
    }

..  index:: Frontend, PHP-API, NotScanned, ext:academic_jobs
