<?php

/**
 * @copyright	Copyright (C) 2009 GWE Systems Ltd. All rights reserved.
 */
//ini_set("display_errors",1);
// Set flag that this is a parent file
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);
$x = realpath(dirname(__FILE__) . DS . ".." . DS . ".." . DS);
if (!file_exists($x . DS . "plugins"))
{
	$x = realpath(dirname(__FILE__) . DS . ".." . DS . ".." . DS . ".." . DS);
}
define('JPATH_BASE', $x);

// create the mainframe object
$_REQUEST['tmpl'] = 'component';

require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

// User must be able to access all the events we need to
if (JVersion::isCompatible("1.6.0"))
{
	jimport('joomla.log.log');
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	$query->select('folder AS type, element AS name, params')
			->from('#__extensions')
			->where('enabled >= 1')
			->where('type =' . $db->Quote('plugin'))
			->where("folder ='jevents'")
			->where("element ='jevnotify'")
			->where('state >= 0')
			->order('ordering');

	$plugin = $db->setQuery($query)->loadObject();
	if (!$plugin)
	{
		die("no plugin");
	}
}
else
{
	require_once( JPATH_CONFIGURATION . "/configuration.php" );
	require_once( JPATH_LIBRARIES . "/joomla/config.php" );
	// Create the JConfig object
	$config = new JConfig();

	// Get the global configuration object
	$registry = & JFactory::getConfig(JPATH_CONFIGURATION . "/configuration.php");

	// Load the configuration values into the registry
	$registry->loadObject($config);

	JFactory::getConfig();
	jimport('joomla.plugin.helper');
	$plugin = JPluginHelper::getPlugin("jevents", "jevnotify");
}
jimport("joomla.html.parameter");
$params = new JRegistry($plugin->params);

// are there ip restrictions
$iplist = $params->get("iplist", "");
if ($iplist != "")
{
	$iplist = explode(',', $iplist);
	if (!in_array($_SERVER['REMOTE_ADDR'], $iplist))
	{
		die("Invalid IP address");
	}
}

// User must be able to access all the events we need to
if (JVersion::isCompatible("1.6.0"))
{
	// force lifetime for session to be very short as a precaution - this means that this session CANNOT continue
	///shortlifetime validation
	if ($params->get("shortlifetime", 1))
	{
		JFactory::getConfig()->set("lifetime", 0.0001);
	}
	jimport('joomla.event.dispatcher');
	jimport('joomla.environment.response');
	jimport('joomla.log.log');
	$session = JFactory::getSession();

	// remove the cookie
	if (isset($_COOKIE[session_name()]))
	{
		$config = JFactory::getConfig();
		$cookie_domain = $config->get('cookie_domain', '');
		$cookie_path = $config->get('cookie_path', '/');
		setcookie(session_name(), '', time() - 42000, $cookie_path, $cookie_domain);
	}
}

global $mainframe;
$mainframe = & JFactory::getApplication('site');
$mainframe->initialise();

// User must be able to access all the events we need to
$params = JComponentHelper::getParams("com_jevents");
$adminuser = $params->get("jevadmin");

// joomla 1.5 access level
if (!JVersion::isCompatible("1.6.0"))
{
	// it doesn't work with Joomla 1.6
	$user = & JFactory::getUser();
	$user = JUser::getInstance($adminuser);
	$user->aid = 2;

	$resetsession = true;

	// remove the cookie
	if (isset($_COOKIE[session_name()]))
	{
		$config = JFactory::getConfig();
		$cookie_domain = $config->get('cookie_domain', '');
		$cookie_path = $config->get('cookie_path', '/');
		setcookie(session_name(), '', time() - 42000, $cookie_path, $cookie_domain);
	}
}
else
{
	$resetsession = false;
	$user = JFactory::getUser();
	if ($user->id == 0)
	{
		// delete the session from the database straight away so that it can't be resused at all!  
		// BELTS AND BRACES
		$db = JFactory::getDbo();
		$db->setQuery(
				'DELETE  FROM `#__session`' .
				' WHERE `session_id` = ' . $db->quote($session->getId()));
		$exists = $db->query();

		// DANGEROUS - it logs in this user but we set the session time to be short in the up top!
		$adminuser = JUser::getInstance($adminuser);
		// put spoof data into the user profile so that its meaningless
		//$adminuser->name = "silly";
		$adminuser->username = "silly";
		//$adminuser->email = "silly@silly.com";
		//$adminuser->id  = 1;
		$adminuser->password = 'something secret';
		$adminuser->password_clear = '';
		$session->set('user', $adminuser);
		$user = JFactory::getUser();
		$resetsession = true;
	}
}

if ($params->get("icaltimezonelive", "") != "" && is_callable("date_default_timezone_set"))
{
	$timezone = date_default_timezone_get();
	$tz = $params->get("icaltimezonelive", "");
	date_default_timezone_set($tz);
	$registry = & JRegistry::getInstance("jevents");
	$registry->set("jevents.timezone", $timezone);
}

$plugin = JPluginHelper::getPlugin("jevents", "jevnotify");
if (!$plugin)
{
	echo "No Plugin";
	return;
}

jimport("joomla.html.parameter");
$params = new JRegistry($plugin->params);

include_once(JPATH_SITE . "/components/com_jevents/jevents.defines.php");

// Select the next 20 emails to send
$db = JFactory::getDBO();
jimport("joomla.utilities.date");
if (class_exists("JevDate"))
{
	$dateClass = "JevDate";
}
else
{
	$dateClass = "JDate";
}

$now = new $dateClass("+0 seconds");
$now = $now->toSql();
$lag = intval($params->get("lag", 0));
$lagtime = new $dateClass("-$lag seconds");
$lagtime = $lagtime->toSql();

$limit = intval($params->get("batchsize", 10));
$db->setQuery("Select n.*, u.name, u.email , u.username from #__jev_notifications as n LEFT JOIN #__users as u on u.id = n.user_id where n.sentmessage=0 and created<" . $db->Quote($lagtime) . " order by n.created limit $limit");

$notifications = $db->loadObjectList();

if (count($notifications) == 0)
	die("No matching notifcations to process");

$datamodel = & new JEventsDataModel();

// prepare the messages
$sent = 0;
foreach ($notifications as $notification)
{

	list ($y, $m, $d) = JEVHelper::getYMD();

	// just incase we don't have jevents plugins registered yet
	JPluginHelper::importPlugin("jevents");
	$event = $datamodel->getEventData($notification->rp_id, 'icaldb', $y, $m, $d);

	if (is_null($event))
	{
		// use the local version 
		$event = getEventData($notification->rp_id, 'icaldb', $y, $m, $d, $datamodel);
		if (is_null($event))
		{
			// this event no longer appears to exist to remove the notifications for it
			echo "Deleted notifications for missing event" . $notification->rp_id . "<br/>";
			$db->setQuery("DELETE FROM  #__jev_notifications WHERE rp_id=" . $notification->rp_id);
			$db->query();
			continue;
		}
	}

	$event = $event['row'];

	$creator = JFactory::getUser($event->created_by());
	if ($event->created_by() == 0)
	{
		$dispatcher = & JDispatcher::getInstance();
		$dispatcher->trigger('onDisplayCustomFields', array(&$event));
		$creator = new stdClass();
		if (isset($event->authorname) && isset($event->authoremail))
		{
			$creator->email = $event->authoremail;
			$creator->name = $event->authorname;
		}
	}

	if ($notification->messagetype == 0)
	{
		$subject = parseMessage($params->get("newsubject"), $event, $notification, $creator);
		$message = parseMessage($params->get("newmessage"), $event, $notification, $creator);
	}
	else if ($notification->messagetype == 2)
	{
		$subject = parseMessage($params->get("deletesubject"), $event, $notification, $creator);
		$message = parseMessage($params->get("deletemessage"), $event, $notification, $creator);
	}
	else
	{
		$subject = parseMessage($params->get("changesubject"), $event, $notification, $creator);
		$message = parseMessage($params->get("changemessage"), $event, $notification, $creator);
	}


	$email = $notification->email ? $notification->email : $notification->email_address;
	$name = $notification->name ? $notification->name : $notification->email_address;

	if ($name == "")
	{
		echo "Could not send message to user ", $notification->user_id . " this user no longer exists<br/>";
		if ($notification->user_id > 0)
		{
			// this user no longer appears to exist to remove the notifications for it
			echo "Deleted notifications for missing user " . $notification->user_id . "<br/>";
			$db->setQuery("DELETE FROM  #__jev_notifications WHERE user_id=" . $notification->user_id);
			$db->query();
		}
		continue;
	}

	if (isset($creator->name) && isset($creator->email))
	{
		$success = JFactory::getMailer()->sendMail($creator->email, $creator->name, $email, $subject, $message, 1);
	}
	else
	{
		// simulate sending
		echo "No not have creator name and email address for event " . $event->title() . " - message NOT sent to  " . $name . "<br/>";
		$success = true;
	}

	global $mainframe;
	if ($success === true)
	{
		echo "Sent message to ", $name . "<br/>";

		$sql = "UPDATE #__jev_notifications set sentmessage=1 , whensent='" . $now . "' WHERE id=" . $notification->id;
		$db->setQuery($sql);
		$db->query();

		$sent++;
	}
	else
	{
		echo "FAILED TO SEND message to ", $name . "<br/>";
	}
}
echo "Send $sent notifications";

if ($resetsession && JVersion::isCompatible("1.6.0"))
{
	// reset the session user
	$session->set('user', 0);
	// delete the session  BELTS AND BRACES
	$session->destroy();
}

function parseMessage($message, $row, $notification, $creator)
{

	$message = str_replace("{USERNAME}", $notification->username, $message);
	$message = str_replace("{NAME}", $notification->name, $message);
	$message = str_replace("{EVENT}", $row->title(), $message);
	$message = str_replace("{CREATOR}", $creator->name, $message);

	$event_up = new JEventDate($row->publish_up());
	$row->start_date = JEventsHTML::getDateFormat($event_up->year, $event_up->month, $event_up->day, 0);
	$row->start_time = JEVHelper::getTime($row->getUnixStartTime());

	$event_down = new JEventDate($row->publish_down());
	$row->stop_date = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day, 0);
	$row->stop_time = JEVHelper::getTime($row->getUnixEndTime());
	$row->stop_time_midnightFix = $row->stop_time;
	$row->stop_date_midnightFix = $row->stop_date;
	if ($event_down->second == 59)
	{
		$row->stop_time_midnightFix = JEVHelper::getTime($row->getUnixEndTime() + 1);
		$row->stop_date_midnightFix = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day + 1, 0);
	}

	$message = str_replace("{REPEATSUMMARY}", $row->repeatSummary(), $message);

	$regex = "#{DATE}(.*?){/DATE}#s";
	preg_match($regex, $message, $matches);
	if (class_exists("JevDate"))
	{
		$dateClass = "JevDate";
	}
	else
	{
		$dateClass = "JDate";
	}

	if (count($matches) == 2)
	{
		$date = new $dateClass($row->getUnixStartDate());
		$message = preg_replace($regex, $date->toFormat($matches[1]), $message);
	}

	$regex = "#{LINK}(.*?){/LINK}#s";
	preg_match($regex, $message, $matches);
	if (count($matches) == 2)
	{
		global $Itemid;
		list($year, $month, $day) = JEVHelper::getYMD();
		$link = $row->viewDetailLink($year, $month, $day, true, $Itemid);

		if (strpos($link, "/") !== 0)
		{
			$link = "/" . $link;
		}
		$link = str_replace("plugins/jevents/jevnotify/", "", $link);
		$link = str_replace("plugins/jevents/", "", $link);

		$uri = & JURI::getInstance(JURI::base());
		$root = $uri->toString(array('scheme', 'host', 'port'));

		$link = $root . $link;
		$link = str_replace("plugins/jevents/jevnotify/", "", $link);
		$link = str_replace("plugins/jevents/", "", $link);

		if ($row->access() > 0)
		{
			if (strpos($link, "?") > 0)
			{
				$link .= "&login=1";
			}
			else
			{
				$link .= "?login=1";
			}
		}

		$message = preg_replace($regex, "<a href='$link'>" . $matches[1] . "</a>", $message);
	}

	// convert relative to absolute URLs
	$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#', '$1="' . JURI::root(), $message);
	$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:))/#', '$1="' . JURI::root(), $message);

	$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#", "$1='" . JURI::root(), $message);
	$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:))/#", "$1='" . JURI::root(), $message);
	return $message;

}

/**
 *
 * version that doesn't check plugin filters!
 * 
 * @param type $rpid
 * @param type $jevtype
 * @param type $year
 * @param type $month
 * @param type $day
 * @param type $uid
 * @return type 
 */
function getEventData($rpid, $jevtype, $year, $month, $day, $datamodel)
{
	$data = array();


	$pop = intval(JRequest::getVar('pop', 0));
	$Itemid = JEVHelper::getItemid();
	$db = & JFactory::getDBO();

	$cfg = & JEVConfig::getInstance();

	$row = listEventsById($rpid, $datamodel->queryModel);  // include unpublished events for publishers and above

	$num_row = count($row);

	// No matching rows 
	if ($num_row == 0 || !$row)
	{
		return null;
	}

	if ($num_row)
	{
		// process the new plugins
		$dispatcher = & JDispatcher::getInstance();
		$dispatcher->trigger('onGetEventData', array(& $row));

		$params = new JRegistry(null);

		$event_up = new JEventDate($row->publish_up());
		$row->start_date = JEventsHTML::getDateFormat($event_up->year, $event_up->month, $event_up->day, 0);
		$row->start_time = JEVHelper::getTime($row->getUnixStartTime());

		$event_down = new JEventDate($row->publish_down());
		$row->stop_date = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day, 0);
		$row->stop_time = JEVHelper::getTime($row->getUnixEndTime());
		$row->stop_time_midnightFix = $row->stop_time;
		$row->stop_date_midnightFix = $row->stop_date;
		if ($event_down->second == 59)
		{
			$row->stop_time_midnightFix = JEVHelper::getTime($row->getUnixEndTime() + 1);
			$row->stop_date_midnightFix = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day + 1, 0);
		}

		// *******************
		// ** This cloaking should be done by mambot/Joomla function
		// *******************
		// Parse http and  wrap in <a> tag
		// trigger content plugin

		$pattern = '[a-zA-Z0-9&?_.,=%\-\/]';

		// Adresse
		// don't convert address that already has a link tag
		if (strpos($row->location(), '<a href=') === false)
		{
			$row->location(preg_replace('#(http://)(' . $pattern . '*)#i', '<a href="\\1\\2">\\1\\2</a>', $row->location()));
		}
		$tmprow = new stdClass();
		$tmprow->text = $row->location();

		$dispatcher = & JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		if (JVersion::isCompatible("1.6.0"))
		{
			$dispatcher->trigger('onContentPrepare', array('com_jevents', &$tmprow, &$params, 0));
		}
		else
		{
			$dispatcher->trigger('onPrepareContent', array(&$tmprow, &$params, 0));
		}
		$row->location($tmprow->text);

		//Contact
		if (strpos($row->contact_info(), '<a href=') === false)
		{
			$row->contact_info(preg_replace('#(http://)(' . $pattern . '*)#i', '<a href="\\1\\2">\\1\\2</a>', $row->contact_info()));
		}
		$tmprow = new stdClass();
		$tmprow->text = $row->contact_info();

		if (JVersion::isCompatible("1.6.0"))
		{
			$dispatcher->trigger('onContentPrepare', array('com_jevents', &$tmprow, &$params, 0));
		}
		else
		{
			$dispatcher->trigger('onPrepareContent', array(&$tmprow, &$params, 0));
		}
		$row->contact_info($tmprow->text);

		//Extra
		if (strpos($row->extra_info(), '<a href=') === false)
		{
			$row->extra_info(preg_replace('#(http://)(' . $pattern . '*)#i', '<a href="\\1\\2">\\1\\2</a>', $row->extra_info()));
		}
		//$row->extra_info(eregi_replace('[^(href=|href="|href=\')](((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)','\\1', $row->extra_info()));
		$tmprow = new stdClass();
		$tmprow->text = $row->extra_info();

		if (JVersion::isCompatible("1.6.0"))
		{
			$dispatcher->trigger('onContentPrepare', array('com_jevents', &$tmprow, &$params, 0));
		}
		else
		{
			$dispatcher->trigger('onPrepareContent', array(&$tmprow, &$params, 0));
		}
		$row->extra_info($tmprow->text);

		// Do main mambot processing here
		// process bots
		//$row->text      = $row->content;
		$params->set("image", 1);
		$row->text = $row->content();

		if (JVersion::isCompatible("1.6.0"))
		{
			$dispatcher->trigger('onContentPrepare', array('com_jevents', &$row, &$params, 0));
		}
		else
		{
			$dispatcher->trigger('onPrepareContent', array(&$row, &$params, 0));
		}
		$row->content($row->text);

		$data['row'] = $row;

		return $data;
	}

}

/**
 *
 * this version doesn't apply the where from the plugins so should always get the event
 * @param type $rpid
 * @return string 
 */
function listEventsById($rpid, $queryModel)
{
	$user = & JFactory::getUser();
	$db = & JFactory::getDBO();
	$frontendPublish = JEVHelper::isEventPublisher();

	// process the new plugins
	// get extra data and conditionality from plugins
	$extrafields = "";  // must have comma prefix
	$extratables = "";  // must have comma prefix
	$extrawhere = array();
	$extrajoin = array();
	$dispatcher = & JDispatcher::getInstance();
	$dispatcher->trigger('onListEventsById', array(& $extrafields, & $extratables, & $extrawhere, & $extrajoin));
	$extrajoin = ( count($extrajoin) ? " \n LEFT JOIN " . implode(" \n LEFT JOIN ", $extrajoin) : '' );
	$extrawhere = ( count($extrawhere) ? ' AND ' . implode(' AND ', $extrawhere) : '' );

	$query = "SELECT ev.*, ev.state as published, rpt.*, rr.*, det.* $extrafields, ev.created as created "
			. "\n , YEAR(rpt.startrepeat) as yup, MONTH(rpt.startrepeat ) as mup, DAYOFMONTH(rpt.startrepeat ) as dup"
			. "\n , YEAR(rpt.endrepeat  ) as ydn, MONTH(rpt.endrepeat   ) as mdn, DAYOFMONTH(rpt.endrepeat   ) as ddn"
			. "\n , HOUR(rpt.startrepeat) as hup, MINUTE(rpt.startrepeat ) as minup, SECOND(rpt.startrepeat ) as sup"
			. "\n , HOUR(rpt.endrepeat  ) as hdn, MINUTE(rpt.endrepeat   ) as mindn, SECOND(rpt.endrepeat   ) as sdn"
			. "\n FROM (#__jevents_vevent as ev $extratables)"
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
			. "\n LEFT JOIN #__jevents_rrule as rr ON rr.eventid = ev.ev_id"
			. "\n LEFT JOIN #__jevents_icsfile as icsf ON icsf.ics_id=ev.icsid "
			. $extrajoin
			. "\n WHERE rpt.rp_id = '$rpid'";
	$query .="\n GROUP BY rpt.rp_id";

	$db->setQuery($query);
	//echo $db->_sql;
	$rows = $db->loadObjectList();

	// iCal agid uses GUID or UUID as identifier
	if ($rows)
	{
		$row = new jIcalEventRepeat($rows[0]);
	}
	else
	{
		$row = null;
	}

	return $row;

}
