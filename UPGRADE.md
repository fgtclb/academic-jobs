# Upgrade 2.0

## X.Y.Z

### FEATURE: Introduce `ModifyJobControllerNewActionViewEvent` in `JobController::newAction()`

`JobController::newAction()` dispatches now the newly introduced PSR-14 event
`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent` providing
following methods:

* `getPluginControllerActionContext(): PluginControllerActionAcontext` to
  extbase controller action plugin context data, which can be used to make
  decisions using the event.
* `getView(): FluidViewInterface|CoreViewInterface` to retrieve the view
  instance, which can be used to assign additional data, but disallowing
  to replace the instance.

Implementing a event listener for this event allows attaching additional
variables to the view for the `JobController::newAction()`, for example
to provide additional select field options in case fields are changed,
from text to a select field for example and avoid the need to implement
a custom ViewHelper to retrieve the "select options" within the modifed
views.

**Example event listener**

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExt\EventListener;

use FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent;

final class ModifyJobControllerNewActionViewEventListener
{
    public function __invoke(
        ModifyJobControllerNewActionViewEvent $event,
    ): void {
        $settings = $event
            ->getPluginControllerActionContext()
            ->getSettings();

        // Assign additional variables to the new action view.
        $event->getView()->assignMultiple(
            [
                'additionalViewVariable' => 1234,
            ],
        );
    }
}
```

### FEATURE: Dispatch `ModifyTcaSelectFieldItemsEvent` in `TypeItems` and `EmploymentTypeItems`

Following provided `itemsProcFunc` handlers now dispatches the new
PSR-14 `\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`:

* `\FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems`
* `\FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems`

This allows projects to modify the available select items for the
backend (FormEngine) and also for the frontend using a PSR-14 event
listener:

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExt\EventListener;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener(identifier: 'project/modify-academic-jobs-tca-select-items')]
final public ModifyTcaSelectFieldItemsEventListener
{
    public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
    {
        $tableName = $event->getParameters()['table'];
        $fieldName = $event->getParameters()['field'];
        if ($tableName !== 'tx_academicjobs_domain_model_job') {
            // Not the table we want to handle. Skip
            return;
        }
        if ($fieldName === 'type') {
            $this->modifyJobsTypeSelectItems($event);
        }
        if ($fieldName === 'employment_type') {
            $this->modifyJobsEmploymentTypeSelectItems($event);
        }
    }

    private function modifyJobsTypeSelectItems(
        ModifyTcaSelectFieldItemsEvent $event,
    ): void {
        $parameters = $event->getParameters();
        $parameters['items'][] = [
            'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.jobtype.custom_type',
            'value' => 10
        ];
        $event->setParameters($parameters);
    }

    private function modifyJobsEmploymentTypeSelectItems(
        ModifyTcaSelectFieldItemsEvent $event,
    ): void {
        $parameters = $event->getParameters();
        $parameters['items'][] = [
            'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang_be.xlf:tx_academicjobs_domain_model_job.employment_type.custom_type',
            'value' => 10
        ];
        $event->setParameters($parameters);
    }
}
```

### BREAKING: Removed `ImageUploadConverter`

Custom `ImageUploadConverter` implementation is removed in favour of the shared
`EXT:academic_base/Classes/Extbase/Property/TypeConverter/FileUploadConterter`.
The dropped implementation is considerable an internal implementation, but was
never flagged internal and is therefore mentioned as breaking and added to the
semver breaking exemption.

Projects using the dropped implementation should implement their own or switch
to the `internal` implementation of `EXT:academic_base` with all the danger it
comes with.

### BREAKING: Removed `tx_academicjobs_domain_model_contact`

As the relation between `tx_academicjobs_domain_model_job` and `tx_academicjobs_domain_model_contact`
is 1:1 and there is no reuse of contact records, it does not make sense to have a separate table
and record for these contacts. Therefore the relation was resolved and an upgrade wizard
handles the migration to the new fields in the `tx_academicjobs_domain_model_job`.

### BREAKING: Migrated extbase plugins from `list_type` to `CType`

TYPO3 v13 deprecated the `tt_content` sub-type feature, only used for `CType=list` sub-typing also known
as `list_type` and mostly used based on old times for extbase based plugins. It has been possible since
the very beginning to register Extbase Plugins directly as `CType` instead of `CType=list` sub-type, which
has now done.

Technically this is a breaking change, and instances upgrading from `1.x` version of the plugin needs to
update corresponding `tt_content` records in the database and eventually adopt addition, adjustments or
overrides requiring to use the correct CType.

Relates to following plugins:

* academicjobs_newjobform
* academicjobs_list'
* academicjobs_detail

> [!NOTE]
> An TYPO3 UpgradeWizard `academicJobs_pluginUpgradeWizard` is provided to migrate
> plugins from `CType=list` to dedicated `CTypes` matching the new registration.

## 2.0.1

## 2.0.0
