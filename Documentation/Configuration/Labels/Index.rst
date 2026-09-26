..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_jobs/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicjobs._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicjobs_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicjobs._LOCAL_LANG {
      default.jobs.back = All vacancies
      de.jobs.back = Alle Stellen
    }

    # Only in one content element:
    plugin.tx_academicjobs_detail._LOCAL_LANG.default.jobs.back = All vacancies

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`Job list` (:typoscript:`academicjobs_list`)
        - :typoscript:`plugin.tx_academicjobs_list._LOCAL_LANG`
    *   - :guilabel:`Job detail` (:typoscript:`academicjobs_detail`)
        - :typoscript:`plugin.tx_academicjobs_detail._LOCAL_LANG`
    *   - :guilabel:`New job form` (:typoscript:`academicjobs_newjobform`)
        - :typoscript:`plugin.tx_academicjobs_newjobform._LOCAL_LANG`

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on
TYPO3 v13, :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on
TYPO3 v14.

Earlier versions of this extension read these overrides on TYPO3 v13 from
:typoscript:`plugin.tx_academic_jobs` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

Where the labels are shown
==========================

Placeholders in angle brackets stand for a part of the key that the template
or the code fills in, a category type or a field name for example.

..  list-table::
    :header-rows: 1
    :widths: 45 55

    *   - Key
        - Shown by
    *   - :xml:`create.<field>.label`
        - :file:`Partials/Job/Forms/FieldWrapper.html`
    *   - :xml:`create.job.employmentType.none`
        - :file:`Partials/Job/Properties/Job.html`
    *   - :xml:`create.job.incorrectValues`
        - :file:`Partials/Job/Forms/Errors.html`
    *   - :xml:`create.job.type.none`
        - :file:`Partials/Job/Properties/Job.html`
    *   - :xml:`edit.<field>.placeholder`
        - :file:`Partials/Job/Forms/Textarea.html`, :file:`Partials/Job/Forms/Textfield.html`
    *   - :xml:`jobs.<property>`
        - :file:`Partials/Job/Information.html`, :file:`Partials/Job/Item.html`
    *   - :xml:`jobs.<property>.<value>`
        - :file:`Partials/Job/Information.html`, :file:`Partials/Job/Item.html`
    *   - :xml:`jobs.back`
        - :file:`Templates/Job/Show.html`
    *   - :xml:`jobs.contact`
        - :file:`Partials/Job/Contact.html`
    *   - :xml:`jobs.linkText`
        - :file:`Partials/Job/Information.html`, :file:`Partials/Job/Item.html`
    *   - :xml:`jobs.submit`
        - :file:`Templates/Job/New.html`
    *   - :xml:`tx_academicjobs.fe.alert.<alert>.title`, :xml:`tx_academicjobs.fe.alert.<alert>.body`
        - The messages after a job is saved or not found
    *   - :xml:`tx_academicjobs_domain_model_job.jobtype.<type>`, :xml:`tx_academicjobs_domain_model_job.employmenttype.<type>`
        - The options of the job type and employment type selects of the form; the labels live in :file:`locallang_be.xlf`, the override is keyed by their id
