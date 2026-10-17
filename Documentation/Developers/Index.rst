..  _developers:

==============
For developers
==============

The plugins and the backend form of this extension dispatch PSR-14 events. They
are the supported way to decide where a visitor lands after submitting a job,
to hand further variables to the templates and to adapt the job types and
employment types an editor and a visitor choose from, without copying the
controller.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the :ref:`extension points page of
academic_base <fgtclb/academic-base:developers-extension-points>`.

..  _developers-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`AfterSaveJobEvent <developers-event-after-save-job-event>`
        -   by the :guilabel:`Job Form`, after a submitted job is saved and
            before the notification mail is sent
        -   change the page the visitor is redirected to, and whether the
            confirmation is queued
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent
            <developers-event-modify-plugin-view-event>`
        -   by the :guilabel:`Job List`, the :guilabel:`Job Details` and the
            :guilabel:`Job Form`, once each time they render, after they
            assigned their own variables, apart from the validations of the
            form, which the job form assigns after the event
        -   assign further view variables
        -   :guilabel:`academic_base`
    *   -   :ref:`ModifyTcaSelectFieldItemsEvent
            <developers-event-modify-tca-select-field-items-event>`
        -   when the items of the job fields :guilabel:`Job Type` and
            :guilabel:`Employment Type` are built, in the backend form and in
            the :guilabel:`Job Form`
        -   add, remove and reorder the items
        -   :guilabel:`academic_base`

..  toctree::
    :hidden:
    :titlesonly:

    Events/AfterSaveJobEvent
    Events/ModifyPluginViewEvent
    Events/ModifyTcaSelectFieldItemsEvent
