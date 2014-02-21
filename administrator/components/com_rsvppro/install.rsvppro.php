<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
		
class com_rsvpproInstallerScript
{

	//
	// Joomla installer functions
	//
	
	function install($parent)
	{
		$this->createTables();

		$this->updateTables();

		return true;

	}

	function uninstall($parent)
	{
		// No nothing for now

	}

	function update($parent)
	{
		$this->createTables();

		$this->updateTables();


		return true;

	}

	private function createTables()
	{

		$db = JFactory::getDBO();
		if (JVersion::isCompatible("1.6"))
		{
			$db->setDebug(0);
		}
		else
		{
			$db->_debug = 0;
		}

		$charset = ($db->hasUTF()) ? 'DEFAULT CHARACTER SET `utf8`' : '';

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_transactions(
	transaction_id int(11) NOT NULL auto_increment,
	attendee_id int(11) NOT NULL default 0,
	gateway varchar(255) NOT NULL default '',
	currency varchar(255) NOT NULL default '',
	amount float NOT NULL default 0,
	transaction_date datetime  NOT NULL default '0000-00-00 00:00:00',
	logdata text not null default '',
	paymentstate int(2) not null default 0,
	params text not null default '',
	notes text NOT NULL,
	PRIMARY KEY  (transaction_id),
	INDEX (transaction_id,attendee_id),
	INDEX (transaction_id,attendee_id, paymentstate)
) ;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_templates (
	id int(11) NOT NULL auto_increment,
	title varchar(100) NOT NULL default "",
	description text NOT NULL default "",
	withfees tinyint (2) NOT NULL default 0 ,
	withticket tinyint (2) NOT NULL default 0 ,
	global tinyint(1) unsigned NOT NULL default 0,
	locked tinyint(1) unsigned NOT NULL default 0,

	created datetime  NOT NULL default '0000-00-00 00:00:00',
	created_by int(11) unsigned NOT NULL default '0',
	created_by_alias varchar(100) NOT NULL default '',
	modified_by int(11) unsigned NOT NULL default '0',

	ticket text NOT NULL default '',

	published tinyint(3) NOT NULL default 1,
	istemplate tinyint(1) NOT NULL default 1,
	params mediumtext NOT NULL default "",

	PRIMARY KEY  (id)
)  $charset;
SQL;
		$db->setQuery($sql);
		$db->query();
		echo $db->getErrorMsg();
		
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_fields (
	field_id int(11) NOT NULL auto_increment,
	template_id int(11) NOT NULL default 0,
	ordering int(11) NOT NULL default 0,
	name varchar(100) NOT NULL default "",
	type varchar(100) NOT NULL default "",
	label varchar(255) NOT NULL default "",
	tooltip varchar(255) NOT NULL default "",
	defaultvalue text NOT NULL default "",
	required tinyint(1) NOT NULL default 0,
	requiredmessage varchar(255) NOT NULL default "",
	applicablecategories  text NOT NULL default "",
	access int(11) NOT NULL default 0,
	accessflag int(2) NOT NULL default 1,
	size int(5) NOT NULL default 0,
	cols int(5) NOT NULL default 0,
	rows int(5) NOT NULL default 0,
	maxlength int(5) NOT NULL default 0,
	allowoverride  int(2) NOT NULL default 0,
	formonly  int(2) NOT NULL default 0,
	peruser  int(2) NOT NULL default 0,
	showinlist  int(2) NOT NULL default 1,
	showinform  int(2) NOT NULL default 1,
	showindetail  int(2) NOT NULL default 1,
	options text NOT NULL default "",
	params text NOT NULL default "",

	
	PRIMARY KEY  (field_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		$db->query();
		echo $db->getErrorMsg();

		// for radio and select fields
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_options (
	option_id int(11) NOT NULL auto_increment,
	field_id int(11) NOT NULL default 0,
	ordering int(11) NOT NULL default 0,
	label varchar(255) NOT NULL default "",
	value text NOT NULL default "",		
	PRIMARY KEY  (option_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		$db->query();
		echo $db->getErrorMsg();

		// Add one native calendar by default if none exist already
		$sql = "SELECT id from #__jev_rsvp_templates";
		$db->setQuery($sql);
		$tem = $db->loadResult();

		if (!$tem || is_null($tem) || $tem == 0)
		{
			//$sql = "INSERT INTO #__jev_templates (title,value) VALUES ('".JText::_("JEV_EVENT_DETAIL")."','".."')";
			$db->setQuery($sql);
			$db->query();
			echo $db->getErrorMsg();
		}

		$db = & JFactory::getDBO();
		$charset = ($db->hasUTF()) ? 'DEFAULT CHARACTER SET `utf8`' : '';
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_attendance(
	id int(11) NOT NULL auto_increment,
	ev_id int(11) NOT NULL default 0,
	allowregistration tinyint(1) unsigned NOT NULL default 0,
	allowcancellation tinyint(1) unsigned NOT NULL default 0,
	allrepeats tinyint(1) unsigned NOT NULL default 1,
	invites tinyint(1) unsigned NOT NULL default 0,
	showattendees tinyint(1) unsigned NOT NULL default 0,
	message text NOT NULL default '',
	subject varchar(255) NOT NULL default '',
	allowreminders tinyint(1) unsigned NOT NULL default 1,
	remindersubject varchar(255) NOT NULL default '',
	remindermessage text NOT NULL default '',
	remindernotice int(11) NOT NULL default 86400,
	remindallrepeats tinyint(1) unsigned NOT NULL default 0,
	hidenoninvitees tinyint(1) unsigned NOT NULL default 1,
	allinvites tinyint(1) unsigned NOT NULL default 1,
	template varchar(255) NOT NULL default '',
	capacity int(11) NOT NULL default 0,
	regclose datetime  NOT NULL default '0000-00-00 00:00:00',
	attendintro text NOT NULL default '',
	regopen datetime  NOT NULL default '0000-00-00 00:00:00',
	cancelclose datetime  NOT NULL default '0000-00-00 00:00:00',
	waitingcapacity int(11) NOT NULL default 0,
	initialstate tinyint(1) unsigned NOT NULL default 1,
	params text NOT NULL default '',
	allowchanges tinyint(2)  NOT NULL default -1,
	sessionaccess int(11) NOT NULL default -1,
	sessionaccessmessage varchar(255) NOT NULL default '',
	PRIMARY KEY  (id),
	INDEX (ev_id),
	INDEX (ev_id,invites),
	INDEX (ev_id,invites,hidenoninvitees)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}


		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_attendees(
	id int(11) NOT NULL auto_increment,
	at_id int(11) NOT NULL default 0,
	user_id int(11) NOT NULL default 0,
	rp_id int(11) NOT NULL default 0,
	email_address varchar(255) NOT NULL default '',
	confirmed tinyint(2) NOT NULL default 1,
	waiting tinyint(2) NOT NULL default 0,
	params text not null default '',
	created datetime NOT NULL default '0000-00-00 00:00:00',
	modified datetime NOT NULL default '0000-00-00 00:00:00',
	attendstate int(3) NOT NULL default 1,
	atdcount int(11) NOT NULL default 0,
	guestcount int(11) NOT NULL default 1,
	didattend tinyint(2) NOT NULL default 0,
	lockedtemplate int(11) NOT NULL default 0,
	attendnotes text not null,
	PRIMARY KEY  (id),
	INDEX (at_id,user_id),
	INDEX (at_id,user_id,rp_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}


		// atdcount tracks the count of attendees - the record is stored in the negative equivalent of at_id !
		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_attendeecount(
	at_id int(11) NOT NULL default 0,
	rp_id int(11) NOT NULL default 0,
	atdcount int(11) NOT NULL default 0,
	PRIMARY KEY  (at_id,rp_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_invitees (
	id int(11) NOT NULL auto_increment,
	at_id int(11) NOT NULL default 0,
	user_id int(11) NOT NULL default 0,
	email_address varchar(255) NOT NULL default '',
	email_name varchar(255) NOT NULL default '',
	rp_id int(11) NOT NULL default 0,
	sentmessage tinyint(1) unsigned NOT NULL default 0,
	viewedevent tinyint(1) unsigned NOT NULL default 0,

	invitedate datetime  NOT NULL default '0000-00-00 00:00:00',

	PRIMARY KEY  (id),
	INDEX (at_id,user_id),
	INDEX (at_id,user_id,rp_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_reminders (
	id int(11) NOT NULL auto_increment,
	at_id int(11) NOT NULL default 0,
	user_id int(11) NOT NULL default 0,
	rp_id int(11) NOT NULL default 0,
	sentmessage tinyint(1) unsigned NOT NULL default 0,
	sentdate datetime  NOT NULL default '0000-00-00 00:00:00',
	email_name varchar(255) NOT NULL default '',
	email_address varchar(255) NOT NULL default '',
		
	PRIMARY KEY  (id),
	INDEX (at_id,user_id),
	INDEX (at_id,user_id,rp_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}


		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_invitelist (
	user_id int(11) NOT NULL default 0,
	listname varchar(255) NOT NULL default '',
	id int(11) NOT NULL auto_increment,
	PRIMARY KEY  (user_id, listname),
	INDEX (id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$db = & JFactory::getDBO();
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_invitelist_member (
	id int(11) NOT NULL auto_increment,
	list_id int(11) NOT NULL default 0,
	user_id int(11) NOT NULL default 0,
	email_address varchar(255) NOT NULL default '',
	email_name varchar(255) NOT NULL default '',
	PRIMARY KEY  (id),
	INDEX (list_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}


		// archive tables
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_templates_archive (
	id int(11) NOT NULL auto_increment,
	template_id int(11) NOT NULL default 0,
	title varchar(100) NOT NULL default "",
	description text NOT NULL default "",
	withfees tinyint (2) NOT NULL default 0 ,
	withticket tinyint (2) NOT NULL default 0 ,
	global tinyint(1) unsigned NOT NULL default 0,

	archived datetime  NOT NULL default '0000-00-00 00:00:00',
	archived_by int(11) unsigned NOT NULL default '0',

	created datetime  NOT NULL default '0000-00-00 00:00:00',
	created_by int(11) unsigned NOT NULL default '0',
	created_by_alias varchar(100) NOT NULL default '',
	modified_by int(11) unsigned NOT NULL default '0',

	ticket text NOT NULL default '',

	published tinyint(3) NOT NULL default 1,
	istemplate tinyint(1) NOT NULL default 1,
	params mediumtext NOT NULL default "",

	PRIMARY KEY  (id),
	INDEX (template_id)		
)  $charset;
SQL;
		$db->setQuery($sql);
		$db->query();
		echo $db->getErrorMsg();

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_fields_archive (
	id int(11) NOT NULL auto_increment,
	field_id int(11) NOT NULL default 0,
	template_id int(11) NOT NULL default 0,
	archive_template_id int(11) NOT NULL default 0,
	ordering int(11) NOT NULL default 0,
	name varchar(100) NOT NULL default "",
	type varchar(100) NOT NULL default "",
	label varchar(255) NOT NULL default "",
	tooltip varchar(255) NOT NULL default "",
	defaultvalue text NOT NULL default "",
	required tinyint(1) NOT NULL default 0,
	requiredmessage varchar(255) NOT NULL default "",
	applicablecategories  text NOT NULL default "",
	access int(11) NOT NULL default 0,
	accessflag int(2) NOT NULL default 1,
	size int(5) NOT NULL default 0,
	cols int(5) NOT NULL default 0,
	rows int(5) NOT NULL default 0,
	maxlength int(5) NOT NULL default 0,
	allowoverride  int(2) NOT NULL default 0,
	formonly  int(2) NOT NULL default 0,
	peruser  int(2) NOT NULL default 0,
	showinlist  int(2) NOT NULL default 1,
	showinform  int(2) NOT NULL default 1,
	showindetail  int(2) NOT NULL default 1,
	options text NOT NULL default "",
	params text NOT NULL default "",

	
	PRIMARY KEY  (id),
	INDEX (field_id),
	INDEX (archive_template_id)
		
)  $charset;
SQL;
		$db->setQuery($sql);
		$db->query();
		echo $db->getErrorMsg();


		$config = new JConfig();
		if (JVersion::isCompatible("1.6"))
		{
			$db->setDebug($config->debug);
		}
		else
		{
			$db->_debug = $config->debug;
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_rsvp_couponusage(
	atd_id int(11) NOT NULL default 0,
	rp_id int(11) NOT NULL default 0,
	params text not null default '',
	PRIMARY KEY  (atd_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

	}

	private function updateTables()
	{

		$db = & JFactory::getDBO();
		$db->setDebug(0);

		$sql = "SHOW COLUMNS FROM `#__jev_rsvp_templates`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("locked", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN locked  tinyint(1) unsigned NOT NULL default 0 ");
			@$db->query();
		}

		if (!array_key_exists("global", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN global tinyint(1) unsigned NOT NULL default 0 ");
			@$db->query();
		}

		if (!array_key_exists("withfees", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN withfees tinyint(2) unsigned NOT NULL default 0 ");
			@$db->query();
		}

		if (!array_key_exists("withticket", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN withticket tinyint(2) unsigned NOT NULL default 0 ");
			@$db->query();
		}

		if (!array_key_exists("ticket", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN  ticket text NOT NULL default '' ");
			@$db->query();
		}

		if (!array_key_exists("istemplate", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN istemplate tinyint(1) unsigned NOT NULL default 1 ");
			@$db->query();
		}

		if (!array_key_exists("params", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN params mediumtext NOT NULL default '' ");
			@$db->query();
		}

		if (!array_key_exists("notes", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_templates ADD COLUMN notes text NOT NULL  ");
			@$db->query();
		}

		$sql = "SHOW COLUMNS FROM `#__jev_rsvp_fields`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("params", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN params text NOT NULL default '' ");
			@$db->query();
		}
		if (!array_key_exists("applicablecategories", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN applicablecategories  text NOT NULL default '' ");
			@$db->query();
		}
		if (!array_key_exists("size", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN size int(5) NOT NULL default 0");
			@$db->query();
		}
		if (!array_key_exists("accessflag", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN accessflag int(2) NOT NULL default 1");
			@$db->query();
		}
		if (!array_key_exists("cols", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN cols int(5) NOT NULL default 0");
			@$db->query();
		}
		if (!array_key_exists("rows", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN rows int(5) NOT NULL default 0 ");
			@$db->query();
		}
		if (!array_key_exists("maxlength", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN maxlength int(5) NOT NULL default 0 ");
			@$db->query();
		}
		if (!array_key_exists("allowoverride", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN allowoverride  int(2) NOT NULL default 0 ");
			@$db->query();
		}
		if (!array_key_exists("peruser", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN peruser  int(2) NOT NULL default 0 ");
			@$db->query();
		}
		if (!array_key_exists("showinlist", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN showinlist  int(2) NOT NULL default 1 ");
			@$db->query();
		}
		if (!array_key_exists("showinform", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN showinform  int(2) NOT NULL default 1 ");
			@$db->query();
		}
		if (!array_key_exists("showindetail", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN showindetail  int(2) NOT NULL default 1");
			@$db->query();
		}
		if (!array_key_exists("options", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_rsvp_fields ADD COLUMN options text NOT NULL default '' ");
			@$db->query();
		}

		$sql = "SHOW COLUMNS FROM `#__jev_attendance`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("sessionaccess", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendance ADD sessionaccess int(11) NOT NULL default -1 ");
			@$db->query();
		}
		if (!array_key_exists("sessionaccessmessage", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendance ADD sessionaccessmessage varchar(255) NOT NULL default '' ");
			@$db->query();
		}

		$sql = "SHOW COLUMNS FROM `#__jev_attendees`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("lockedtemplate", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN lockedtemplate int(11) NOT NULL default 0 ");
			@$db->query();
		}
		if (!array_key_exists("guestcount", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN guestcount int(11) NOT NULL default 1");
			@$db->query();
		}
		if (!array_key_exists("atdcount", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN atdcount int(11) NOT NULL default 0");
			@$db->query();
		}
		if (!array_key_exists("created", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN created datetime NOT NULL default '0000-00-00 00:00:00'");
			@$db->query();
		}
		if (!array_key_exists("modified", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN modified datetime NOT NULL default '0000-00-00 00:00:00'");
			@$db->query();
		}
		if (!array_key_exists("email_address", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN email_address varchar(255) NOT NULL default ''");
			@$db->query();
		}
		if (!array_key_exists("params", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN params  text  NOT NULL default ''");
			@$db->query();
		}
		if (!array_key_exists("waiting", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN waiting tinyint(2) NOT NULL default 0");
			@$db->query();
		}
		if (!array_key_exists("didattend", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN didattend tinyint(2) NOT NULL default 0");
			@$db->query();
		}
		if (!array_key_exists("confirmed", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN confirmed tinyint(2) NOT NULL default 1");
			@$db->query();
		}
		if (!array_key_exists("attendstate", $cols))
		{
			// Attend State
			// 1 = attending, 0 = not attending, 2 = maybe attending, 3 = subject to approval
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN attendstate int(3) NOT NULL default 1");
			@$db->query();
		}

		if (!array_key_exists("notes", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendees ADD COLUMN notes text NOT NULL  ");
			@$db->query();
		}
		
		$sql = "SHOW COLUMNS FROM `#__jev_invitees`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("email_address", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_invitees ADD COLUMN email_address varchar(255) NOT NULL default ''");
			@$db->query();
		}
		if (!array_key_exists("email_name", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_invitees ADD COLUMN email_name varchar(255) NOT NULL default ''");
			@$db->query();
		}
		if (!array_key_exists("email_address", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_reminders ADD COLUMN email_address varchar(255) NOT NULL default ''");
			@$db->query();
		}




		// If upgrading then add new columns - do all the tables at once
		$sql = "SHOW COLUMNS FROM `#__jev_attendance`";
		$db->setQuery($sql);
		$cols = $db->loadObjectList("Field");
		if (!array_key_exists("allowchanges", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendance ADD COLUMN allowchanges tinyint(2)  NOT NULL default -1");
			@$db->query();
		}
		if (!array_key_exists("initialstate", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD initialstate tinyint(1) NOT NULL default 1";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("attendintro", $cols))
		{
			$db->setQuery("ALTER TABLE #__jev_attendance ADD COLUMN attendintro text NOT NULL default ''");
			@$db->query();
		}
		if (!array_key_exists("regopen", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD regopen datetime  NOT NULL default '0000-00-00 00:00:00'";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("cancelclose", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD cancelclose datetime  NOT NULL default '0000-00-00 00:00:00'";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("regclose", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD regclose datetime  NOT NULL default '0000-00-00 00:00:00'";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("capacity", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD capacity int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("waitingcapacity", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD waitingcapacity int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("template", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD COLUMN template varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("hidenoninvitees", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD COLUMN hidenoninvitees tinyint(1) unsigned NOT NULL default 1";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("allinvites", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD allinvites tinyint(1) unsigned NOT NULL default 1";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("showattendees", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD showattendees tinyint(1) unsigned NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("invites", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD invites tinyint(1) unsigned NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("subject", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD subject varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("message", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD message text NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("allowreminders", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD allowreminders tinyint(1) unsigned NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("remindersubject", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD remindersubject varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("remindermessage", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD remindermessage text NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("remindernotice", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD remindernotice int(11) NOT NULL default 86400";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("remindallrepeats", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD remindallrepeats tinyint(1) unsigned NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("remindallrepeats", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD remindallrepeats tinyint(1) unsigned NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("params", $cols))
		{
			$sql = "ALTER TABLE #__jev_attendance ADD params text NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}

		/*
		  if (!array_key_exists("email_address", $cols))
		  {
		  $sql = "ALTER TABLE #__jev_attendance ADD INDEX `ev_id` ( `ev_id`)";
		  $db->setQuery($sql);
		  @$db->query();
		  }
		  if (!array_key_exists("email_address", $cols))
		  {
		  $sql = "ALTER TABLE #__jev_attendance ADD INDEX `invites` ( `ev_id`, `invites`)";
		  $db->setQuery($sql);
		  @$db->query();
		  }
		  if (!array_key_exists("email_address", $cols))
		  {
		  $sql = "ALTER TABLE #__jev_attendance ADD INDEX `hidenoninvitees` ( `ev_id`, `invites`,`hidenoninvitees`)";
		  $db->setQuery($sql);
		  @$db->query();
		  }
		 */

	}

}
