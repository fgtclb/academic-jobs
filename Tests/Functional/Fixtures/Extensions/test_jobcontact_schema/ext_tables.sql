# The relation field and the contact table academic_jobs removed in 2.1, without
# TCA as an installation keeps them. Of the fields TYPO3 added from the former
# TCA, the table declares the ones the upgrade wizard and its tests read, plus
# uid, pid, the record timestamps and the language fields.
CREATE TABLE tx_academicjobs_domain_model_job (
	contact int(11) unsigned NOT NULL DEFAULT '0',
);

CREATE TABLE tx_academicjobs_domain_model_contact (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted smallint(5) unsigned DEFAULT '0' NOT NULL,
	hidden smallint(5) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) unsigned DEFAULT '0' NOT NULL,
	job int(11) unsigned NOT NULL DEFAULT '0',
	name varchar(255) NOT NULL DEFAULT '',
	email varchar(255) NOT NULL DEFAULT '',
	phone varchar(255) NOT NULL DEFAULT '',
	additional_information text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
