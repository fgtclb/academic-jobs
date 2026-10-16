..  _integrator-job-list:

========
Job List
========

..  image:: /Images/Icons/employment.svg
    :width: 48px
    :alt: Icon of the Job List content element

:CType:
    ``academicjobs_list``

:Plugin:
    `AcademicJobs`, plugin `List`, action
    :php:`JobController::listAction()`

:Set:
    `fgtclb/academic-jobs-list`, or the aggregate set `fgtclb/academic-jobs`

:Templates:
    :file:`Templates/Job/List.html`, with the partials :file:`Job/Item.html`
    and :file:`Job/Pagination.html`, see :ref:`templates`

..  figure:: /Images/Screenshots/job-list-overview.png
    :alt: A list of three job offers, each with title, properties and logo, and a page navigation below
    :class: with-border with-shadow

    The job list with three jobs per page and the navigation below them.

The job list shows the jobs of the storage folder of the site, the site
setting :typoscript:`plugin.tx_academicjobs.persistence.storagePid`. Each job
shows its title, its properties and its image. The title links to the job on
the detail page, the site setting
:typoscript:`plugin.tx_academicjobs.detailPid`.
The jobs are ordered by their start time, the earliest first. A job without a
start time comes before the others, and jobs with the same start time keep the
order they were created in.

It is the content element for the page that lists the open positions of an
institution, all of them or those of one job type, for example a page of
theses next to a page of jobs. The list is not cached with the page, so a job
appears as soon as an editor publishes it and disappears when its end time has
passed.

It works with:

*   a :ref:`Job Details <integrator-job-details>` content element on the
    detail page, which the titles link to. Without a detail page the titles link
    to the page of the list itself, which then shows no job.
*   a :ref:`Job Form <integrator-job-form>` content element, whose submitted
    jobs this list shows once an editor published them.

The list also renders the flash messages queued for the job list. The
confirmation of the job form is queued for the form, not for the list, see
:ref:`integrator-job-form-flash-messages`.

..  _integrator-job-list-settings:

Settings
========

..  confval:: Job Type
    :name: job-list-job-type
    :type: select
    :Default: None

    Which jobs the list shows. :guilabel:`None` lists every job of the storage
    folder. Every other choice lists only the jobs of that type, which the
    editor chooses in the field :guilabel:`Job Type` of the job record.

    After :guilabel:`None` come the types of the job record:
    :guilabel:`Job`, :guilabel:`Sidejob` and :guilabel:`Thesis`, and every type
    a project adds or removes with an event listener, see
    :ref:`developers-event-modify-tca-select-field-items-event`.

    Page TSconfig of the record field does not reach this option. A type added
    or removed with :typoscript:`addItems` or :typoscript:`removeItems` below
    :typoscript:`TCEFORM.tx_academicjobs_domain_model_job.type` needs the same
    lines for the FlexForm field:

    ..  code-block:: typoscript
        :caption: Page TSconfig

        TCEFORM.tx_academicjobs_domain_model_job.type.removeItems = 3
        TCEFORM.tt_content.pi_flexform.academicjobs_list.sDEF.settings\.job\.type.removeItems = 3

    Use one list per type when a site presents them on pages of their own, and
    :guilabel:`None` for a single page of every open position.

..  confval:: Show hidden records
    :name: job-list-show-hidden-records
    :type: checkbox
    :Default: off

    On, the list also shows the jobs that are hidden, independent of the
    preview settings of the backend. That includes every job a visitor
    submitted through the :ref:`job form <integrator-job-form>` and that no
    editor has published yet, because the form saves a job hidden.

    A hidden job is listed without a link, because the detail page does not
    show it. The same applies to a translated job whose record in the default
    language is hidden. Start time, end time and access restrictions still
    apply to hidden jobs.

    Use it for a page that lists the submitted jobs waiting for review, and
    restrict the access to that page. Off, the setting for a public list,
    shows only published jobs.

..  _integrator-job-list-pagination:

Pagination
==========

..  confval:: Enable pagination
    :name: job-list-pagination-enabled
    :type: checkbox
    :Default: off

    Off, the list shows every job on one page. On, it shows :confval:`Results
    per page <job-list-results-per-page>` jobs and a navigation to the other
    pages below them. A list that fits on one page shows no navigation.

    The navigation links at most
    :typoscript:`plugin.tx_academicjobs.pagination.numberOfLinks` page numbers
    around the current page when `georgringer/numbered-pagination` is
    installed, see :ref:`configuration-list-pagination`. Without it, the core
    pagination links every page.

    A page link carries the page as `tx_academicjobs_list[currentPage]` and
    nothing else. The job type and the switch for hidden jobs come from the
    content element, so every page keeps them. A page beyond the last one shows
    the last page, a page below one, or a value that is no number, the first.

    Two job lists on the same page read the same `currentPage` argument, so
    they turn their pages together. Put a second paginated list on a page of
    its own.

..  figure:: /Images/Screenshots/job-list-pagination.png
    :alt: Page navigation with the page numbers 1 to 3, the first one current, and the links next and last
    :class: with-border with-shadow

    The navigation of a paginated job list.

..  confval:: Results per page
    :name: job-list-results-per-page
    :type: number
    :Default: ``10``

    The number of jobs on one page of the list. The field is shown and stored
    whether the pagination is enabled or not, and only read while it is. A
    value below 1 counts as 10.
