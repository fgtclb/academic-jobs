CREATE TABLE tx_academicjobs_domain_model_job (
	title varchar(255) NOT NULL DEFAULT '',
	employment_start_date date DEFAULT NULL,
	description text,
	image int(11) unsigned NOT NULL DEFAULT '0',
	company_name text NOT NULL DEFAULT '',
	sector text NOT NULL DEFAULT '',
	employment_type int(11) DEFAULT '0' NOT NULL,
	work_location varchar(255) NOT NULL DEFAULT '',
	link varchar(255) NOT NULL DEFAULT '',
	slug varchar(255) NOT NULL DEFAULT '',
	type int(11) DEFAULT '0' NOT NULL,
	contact int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_academicjobs_domain_model_contact (
	job int(11) unsigned NOT NULL DEFAULT '0',
	name varchar(255) NOT NULL DEFAULT '',
	email varchar(255) NOT NULL DEFAULT '',
	phone varchar(255) NOT NULL DEFAULT '',
	additional_information text NOT NULL DEFAULT ''
);
