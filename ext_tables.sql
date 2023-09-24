#
# Table structure for table 'tx_timer_domain_model_event'
#
create TABLE tx_timer_domain_model_event (

	tx_timer_scheduler int(4) unsigned DEFAULT '1' NOT NULL comment 'allow the scheduler, to define starttime and endtime for this element',
	tx_timer_timer text DEFAULT 'default' comment 'contains a flex-element for timer-definitions',
	tx_timer_selector varchar(255) DEFAULT 'default' comment 'contains the selector for timer-definitions',

	teaser_slogan varchar(255) DEFAULT '' NOT NULL,
	teaser_infotext text DEFAULT '',

	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '',

	flag_test int(4) unsigned DEFAULT '0' NOT NULL,

);

#
# Table structure for table 'tx_timer_domain_model_listing'
#
create TABLE tx_timer_domain_model_listing (

	tx_timer_scheduler int(4) unsigned DEFAULT '0' NOT NULL,
	tx_timer_timer text DEFAULT 'default' comment 'contains a flex-element for timer-definitions',
	tx_timer_selector varchar(255) DEFAULT 'default' comment 'contains the selector for timer-definitions',

	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '',

);

#
# Table structure for table 'pages'
#
create TABLE pages (

	tx_timer_scheduler int(4) unsigned DEFAULT '0' NOT NULL,
	tx_timer_timer text DEFAULT 'default' comment 'contains a flex-element for timer-definitions',
	tx_timer_selector varchar(255) DEFAULT 'default' comment 'contains the selector for timer-definitions',

);

#
# Table structure for table 'sys_file_reference'
#
create TABLE sys_file_reference (

	tx_timer_scheduler int(4) unsigned DEFAULT '0' NOT NULL,
	tx_timer_timer text DEFAULT 'default' comment 'contains a flex-element for timer-definitions',
	tx_timer_selector varchar(255) DEFAULT 'default' comment 'contains the selector for timer-definitions',
    starttime int(11) unsigned NOT NULL default '0',
    endtime int(11) unsigned NOT NULL default '0',

);

#
# Table structure for table 'tt_content'
#
create TABLE tt_content (

	tx_timer_scheduler int(4) unsigned DEFAULT '0' NOT NULL,
	tx_timer_timer text DEFAULT 'default' comment 'contains a flex-element for timer-definitions',
	tx_timer_selector varchar(255) DEFAULT 'default' comment 'contains the selector for timer-definitions',

);

