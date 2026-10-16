..  _developers-event-modify-tca-select-field-items-event:

==============================
ModifyTcaSelectFieldItemsEvent
==============================

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` is owned by
:guilabel:`academic_base`, which describes it for every academic extension in
`its developer chapter
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/Events/ModifyTcaSelectFieldItemsEvent.html>`__.
This page says which items of :guilabel:`academic_jobs` it carries.

..  _developers-event-modify-tca-select-field-items-event-dispatched:

When it is dispatched
=====================

Two fields of the job record build their items with an item provider of this
extension, and each provider dispatches the event once each time it runs. The
option :confval:`Job Type <job-list-job-type>` of the job list offers the job
types of the record behind its own :guilabel:`None`, and dispatches the event
with the table and the field of the record:

..  list-table::
    :header-rows: 1

    *   -   Field
        -   :php:`table` and :php:`field`
        -   Item provider
        -   Shipped items
    *   -   :guilabel:`Job Type`
        -   `tx_academicjobs_domain_model_job`, `type`
        -   :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\TypeItems`
        -   `1` Job, `2` Sidejob, `3` Thesis
    *   -   :guilabel:`Employment Type`
        -   `tx_academicjobs_domain_model_job`, `employment_type`
        -   :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\EmploymentTypeItems`
        -   `1` Full-time, `2` Part-time
    *   -   :guilabel:`Job Type` of the job list
        -   `tx_academicjobs_domain_model_job`, `type`
        -   :php:`\FGTCLB\AcademicJobs\Backend\FormEngine\JobListTypeItems`,
            which calls :php:`TypeItems`
        -   `1` Job, `2` Sidejob, `3` Thesis, after `0` None, which the
            listeners do not get

The providers run in three places:

*   in the backend, when an editor opens the record of a job.
*   in the backend, when an editor opens a :ref:`Job List <integrator-job-list>`
    content element, for its option :guilabel:`Job Type`.
*   in the frontend, each time the :ref:`Job Form <integrator-job-form>`
    renders, to build the choices of its two select fields. The form translates
    the labels after the listeners ran, so a label may be an `LLL:` reference.

..  _developers-event-modify-tca-select-field-items-event-api:

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getParameters()`, :php:`setParameters()`
        -   The parameters of the item provider. :php:`items` holds the items,
            the ones of this extension as arrays with :php:`label` and
            :php:`value`. :php:`table`, :php:`field`, :php:`effectivePid`,
            :php:`site` and :php:`TSconfig` say where they are built. In the
            backend :php:`row` holds the job being edited. In the frontend
            :php:`row` is an empty array, and :php:`effectivePid` is the page
            of the form.

For the job list, :php:`table` and :php:`field` name the field of the job
record, and every other parameter is the one TYPO3 passes for the FlexForm
field: :php:`flexParentDatabaseRow` holds the content element,
:php:`row` the values of its FlexForm, :php:`effectivePid` its page, and
:php:`config` and :php:`TSconfig` belong to the FlexForm field. Only the job
list passes :php:`flexParentDatabaseRow`, so a listener that means the record
or the list alone checks it:

..  code-block:: php

    $isJobList = isset($parameters['flexParentDatabaseRow']);

In the backend form, TYPO3 v13 and v14 hand the static items of a field to the
item provider as :php:`\TYPO3\CMS\Core\Schema\Struct\SelectItem` objects,
and the items a provider adds are arrays. A :php:`SelectItem` implements
:php:`\ArrayAccess`, so a closure typed :php:`array|SelectItem` that reads
:php:`$item['value']` works for both.

The two job fields declare no static items, but a project that adds one in
the TCA gets an object in the backend form. The job form of the frontend hands
every item over as an array. Such an item comes first, and a shipped item with
a value it declares already is left out, see
:ref:`important-job-type-items-keep-declared-items`.

..  _developers-event-modify-tca-select-field-items-event-example:

Example: the job types of an institution
========================================

An institution does not publish side jobs, and advertises doctoral positions
as a type of their own. The listener removes the one type and adds the other,
in the backend and in the job form alike:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AdaptJobTypes.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;

    final class AdaptJobTypes
    {
        private const JOB_TYPE_SIDEJOB = 2;
        private const JOB_TYPE_DOCTORAL_POSITION = 10;

        #[AsEventListener(identifier: 'my-extension/adapt-job-types')]
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            $parameters = $event->getParameters();
            if ($parameters['table'] !== 'tx_academicjobs_domain_model_job'
                || $parameters['field'] !== 'type'
            ) {
                return;
            }
            $parameters['items'] = array_values(array_filter(
                $parameters['items'],
                static fn(array|SelectItem $item): bool => (int)($item['value'] ?? 0) !== self::JOB_TYPE_SIDEJOB,
            ));
            $parameters['items'][] = [
                'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:jobType.doctoralPosition',
                'value' => self::JOB_TYPE_DOCTORAL_POSITION,
            ];
            $event->setParameters($parameters);
        }
    }

The job list and the job detail name the type of a job with the frontend label
`jobs.type.<value>` of this extension, so the new type needs one, see
:ref:`configuration-labels`:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicjobs._LOCAL_LANG.default.jobs.type.10 = Doctoral position

..  _developers-event-modify-tca-select-field-items-event-rules:

Rules and pitfalls
==================

**Check the table and the field first.** The event is dispatched by the item
providers of several academic extensions, for fields of other tables and of
FlexForms, so a listener returns early for every field it does not mean.

**Do not rely on the row.** The job form calls the providers without a record
and passes an empty :php:`row`. The event declares :php:`row` optional, so a
listener reads it with a fallback, :php:`$parameters['row'] ?? []`, and every
field of it as well, for example the page as
:php:`$row['pid'] ?? $parameters['effectivePid']`. For the job list, that is
the page of the content element, because :php:`row` holds the values of its
FlexForm and has no :php:`pid`. The storage folder of the list is a field of
the content element, which :php:`flexParentDatabaseRow` holds.

**Removing an item hides it.** A job that already has the value keeps it, and
the job form does not check a submitted value against the items.

**The job list offers the same types.** A type a listener adds to the field
:php:`type` can be chosen for the job list as well, and one it removes is not
offered there, see :ref:`important-job-list-offers-the-job-types-of-listeners`.
Give a type a positive integer as its value: the column of the job stores an
integer, and the list treats `0` and below as :guilabel:`None`.

..  _developers-event-modify-tca-select-field-items-event-introduced:

Introduced in
=============

*   :ref:`feature-1757757620`: the item providers of the job type and the
    employment type dispatch the event. The example of that entry names
    methods the event does not have, the example above uses the ones it has.
*   :ref:`important-job-list-offers-the-job-types-of-listeners`: the option
    :guilabel:`Job Type` of the job list offers the types of the listeners.
