..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships one ready made route enhancer in
:file:`Configuration/Routes/Detail.yaml`. TYPO3 does not read that file on its
own — it is a fragment that has to be imported from the configuration of the
site which shows the plugin.

What the file enhances
----------------------

The file declares a single enhancer of type :yaml:`Extbase` named
:yaml:`AcademicJobsDetailPlugin`, bound to the plugin :yaml:`Detail` of the
extension :yaml:`AcademicJobs`. That pair determines the argument namespace the
enhancer works on — :php:`tx_academicjobs_detail`.

It registers one route for the :php:`show` action of
:php:`FGTCLB\AcademicJobs\Controller\JobController`:

..  code-block:: yaml
    :caption: EXT:academic_jobs/Configuration/Routes/Detail.yaml

    routes:
      - routePath: '/{job_title}'
        _controller: 'Job::show'
        _arguments:
          job_title: 'job'

The path segment is resolved by a :yaml:`PersistedAliasMapper` on
:sql:`tx_academicjobs_domain_model_job` over the field :sql:`slug`, so the
speaking part of the URL is the slug of the job record rather than its uid.

No enhancer is shipped for the other two plugins, and none is needed: the
:yaml:`List` plugin has neither pagination nor a filter, so its action takes no
arguments at all, and the :yaml:`NewJobForm` plugin submits its form by POST.

Importing it into a site configuration
--------------------------------------

Add the resource to the :yaml:`imports` of the site that contains the page with
the job detail plugin:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_jobs/Configuration/Routes/Detail.yaml'

What the URLs look like
-----------------------

Assuming the detail plugin sits on a page with the slug :file:`/jobs/detail`, a
link from the list to a job is built without the enhancer as:

..  code-block:: text

    /jobs/detail?tx_academicjobs_detail%5Bjob%5D=17

and with the enhancer imported as:

..  code-block:: text

    /jobs/detail/research-assistant-biology

Caveats
-------

*   The route needs a slug. :yaml:`PersistedAliasMapper` resolves the path
    segment against the :sql:`slug` field of the job record, so a job whose
    slug is empty cannot be reached through the enhanced URL. A record saved
    in the backend gets one from the TCA :php:`slug` field; a record created by
    the frontend form of the :yaml:`NewJobForm` plugin does not go through the
    :php:`DataHandler` at all and gets one from the event listener
    :php:`FGTCLB\AcademicJobs\EventListener\GenerateJobSlug` instead. Records
    that predate the slug field have to be updated once, for example by
    emptying the field in the backend form so it is generated again.
*   Jobs entered through the frontend form are created hidden, so they only
    become reachable — enhanced URL or not — once they were approved in the
    backend.
*   The list plugin renders its links with the detail plugin name and the page
    from the plugin setting :typoscript:`detailPid`. Enhancing the detail page
    therefore changes the links of an unmodified list template without any
    further configuration.
