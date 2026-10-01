..  index:: Configuration; Validations
..  _configuration-validations:

===================
Validation settings
===================

:file:`Configuration/AcademicJobs/Settings.yaml` describes which fields of a job
are required, how they are rendered in the public *new job* form, and what the
backend record editor of a job demands.

The file is read by the same settings classes of :guilabel:`academic_base` that
:guilabel:`academic_persons` uses, so the flags mean the same in both
extensions.

The file
========

The shipped configuration defines a single set, :yaml:`job`. Its keys are
**property names** in camel case, each carrying a list of flags:

..  code-block:: yaml

    validations:
      job:
        title:
          - required
        employmentType:
          - required
        type:
          - required
        companyName:
          - required
        employmentStartDate:
          - required
        description:
          - required
        link:
          - url
        contactEmail:
          - email
        contactPhone:
          - tel

A property that is not listed is neither required nor specially rendered.
Flags are read regardless of case and of spaces around them. The flags are
the ones of :guilabel:`academic_persons`, and a flag outside that vocabulary is
ignored.

Where the flags take effect
===========================

Every flag is read by the new job form, by the check of a submitted job, and
by the backend record editor of a job:

..  list-table::
    :header-rows: 1

    *   -   Flag
        -   New job form
        -   Submitted job
        -   Backend record editor
    *   -   :yaml:`required`
        -   Marks the field with an asterisk
        -   Value must not be empty
        -   Required
    *   -   :yaml:`readonly`, :yaml:`disabled`
        -   Shown, not marked
        -   Not required
        -   Read only, not required
    *   -   :yaml:`frontendreadonly`
        -   Shown, not marked
        -   Not required
        -   As the other flags say
    *   -   :yaml:`email`
        -   Renders :html:`<input type="email">`
        -   Value must be an e-mail address
        -   An e-mail field
    *   -   :yaml:`url`
        -   Renders :html:`<input type="url">`
        -   Value must be a URL
        -   Unchanged
    *   -   :yaml:`number`
        -   Renders :html:`<input type="number">`
        -   **Not checked**
        -   A number field
    *   -   :yaml:`tel`
        -   Renders :html:`<input type="tel">`
        -   Not checked
        -   Unchanged
    *   -   :yaml:`date`
        -   Renders :html:`<input type="date">` on a text field
        -   Not checked
        -   Unchanged

:yaml:`textarea` and :yaml:`html` belong to the profile editor of
:guilabel:`academic_persons`. The new job form chooses between a text field
and a text area per field itself, so do not use them here: on a text field
they render an invalid input type.

The backend half applies to the fields the job table has a column for, with
the column name derived from the property name: :yaml:`companyName` is the
column :sql:`company_name`. For every configured field the settings decide
whether it is required and read only, also when they say *no*: a field listed
with :yaml:`url` alone is not required in the backend, whatever the TCA of the
job table or a TCA override of a site package says.

A field the job has no property for is left out of the check of a submitted
job, and a field the job table has no column for is left out of the backend.

..  _configuration-validations-limits:

Points to be aware of
=====================

*   :yaml:`readonly` and :yaml:`disabled` do **not** lock a field in the new job
    form. The form keeps offering it and stores what is submitted. They lock
    the field for backend editors, so what a visitor entered stays as it is.
*   :yaml:`number` sets the type of the backend field as well, and saving a
    record then stores the value as a whole number: :code:`+49 30 123` becomes
    :code:`49`. Do not use it for a field holding text such as a phone number,
    :yaml:`tel` is the flag for that. The settings of 2.x marked the contact
    phone with :yaml:`number`, so check a copy of them.
*   :yaml:`required` cannot detect an unselected value for the job type and
    employment type fields in the form, because those are stored as numbers
    and an unset selection is indistinguishable from a valid zero.

Overriding the settings
=======================

Settings are collected from **all installed extensions**. Every package that
contains :file:`Configuration/AcademicJobs/Settings.yaml` contributes, in
package loading order, and a later package changes only the fields it names.

To change them for an installation:

#.  Add :file:`Configuration/AcademicJobs/Settings.yaml` to your site package
    with the fields you change, and nothing else.
#.  Make the site package **depend on** :guilabel:`academic_jobs` in its
    :file:`composer.json` or :file:`ext_emconf.php`, so that it is loaded after
    it.
#.  Flush the TYPO3 caches. The settings and the compiled TCA are cached, and
    a changed file has no effect until the cache is cleared.

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicJobs/Settings.yaml

    validations:
      job:
        # The flags of a field are replaced as a whole.
        companyName:
          - required
          - readonly
        # An empty list drops every flag: optional everywhere.
        description: []
        # `~` removes the field: the backend keeps what the TCA says.
        link: ~

There is no TypoScript and no site set equivalent for these settings.

..  _configuration-validations-backend-listener:

Changing the backend after the settings
=======================================

The flags reach the backend through a listener of
:php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent` with the
identifier ``academic-jobs/apply-settings-to-tca``. It runs after every
:file:`Configuration/TCA/Overrides` file, so a TCA override of :php:`required`
or :php:`readOnly` on a configured field does not survive it.

A site package that has to differ there either changes the settings file, or,
for what the flags cannot say, orders a listener of its own after that
identifier. A field that is required in the form and optional in the backend
is such a case:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/CompanyNameOptionalInTheBackend.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

    /**
     * The new job form demands a company name, backend editors may leave it
     * empty, for a job an editor enters for the institution itself.
     */
    final class CompanyNameOptionalInTheBackend
    {
        #[AsEventListener(
            identifier: 'my-sitepackage/company-name-optional-in-the-backend',
            after: 'academic-jobs/apply-settings-to-tca',
        )]
        public function __invoke(AfterTcaCompilationEvent $event): void
        {
            $tca = $event->getTca();
            $tca['tx_academicjobs_domain_model_job']['columns']['company_name']['config']['required'] = false;
            $event->setTca($tca);
        }
    }

The identifier is public API, the listener class is not.
