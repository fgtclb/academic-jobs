.. include:: /Includes.rst.txt

.. _feature-1757757620:

===========================================================================================
Feature: Dispatch `ModifyTcaSelectFieldItemsEvent` in `TypeItems` and `EmploymentTypeItems`
===========================================================================================

Description
===========

Following provided `itemsProcFunc` handlers now dispatches the new
PSR-14 `\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`:

* :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems`
* :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems`


Impact
======

This allows projects to modify the available select items for the
backend (FormEngine) and also for the frontend using a PSR-14 event
listener:

..  code-block:: php
    :caption: EXT:my_ext/Classes/EventListener/ModifyJobTypeItemsListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExt\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use FGTCLB\AcademicJobs\Domain\Model\JobType;

    final class ModifyJobTypeItemsListener
    {
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            if ($event->getFieldName() === 'type') {
                $items = $event->getItems();
                $items[] = [
                    'Custom Type',
                    JobType::CUSTOM_TYPE,
                ];
                $event->setItems($items);
            }
        }
    }

.. index:: Backend, Frontend, TCA
