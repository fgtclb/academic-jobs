# Upgrade 2.0

## X.Y.Z

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
