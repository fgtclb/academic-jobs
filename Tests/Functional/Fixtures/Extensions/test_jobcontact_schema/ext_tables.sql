CREATE TABLE tx_academicjobs_domain_model_job (
	contact int(11) unsigned NOT NULL DEFAULT '0',
);

CREATE TABLE tx_academicjobs_domain_model_contact (
	job int(11) unsigned NOT NULL DEFAULT '0',
	name varchar(255) NOT NULL DEFAULT '',
	email varchar(255) NOT NULL DEFAULT '',
	phone varchar(255) NOT NULL DEFAULT '',
	additional_information text
);
