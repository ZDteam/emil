<?php

/**
 * @copyright	Copyright (C) 2009 GWE Systems Ltd. All rights reserved.
 */
//ini_set("display_errors",0);
// Set flag that this is a parent file
define('_JEXEC', 1);

$x = realpath(dirname(__FILE__) . '/' . ".." . '/' . ".." . '/' . ".." . '/');
if (!file_exists($x . '/' . "plugins"))
{
	$x = realpath(dirname(__FILE__) . '/' . ".." . '/' . ".." . '/' . ".." . '/' . ".." . '/');
}
if (!file_exists($x . '/' . "plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}
define('JPATH_BASE', $x);

// create the mainframe object
$_REQUEST['tmpl'] = 'component';

require_once JPATH_BASE . '/' . 'includes' . '/' . 'defines.php';
require_once JPATH_BASE . '/' . 'includes' . '/' . 'framework.php';

$mainframe = & JFactory::getApplication('site');
$mainframe->initialise();

// User must be able to access all the events we need to
$params = JComponentHelper::getParams("com_jevents");
$adminuser = $params->get("jevadmin");
$user = & JFactory::getUser();
$user = JUser::getInstance($adminuser);
$user->aid = 2;

// remove the cookie
if (isset($_COOKIE[session_name()]))
{
	$config = JFactory::getConfig();
	$cookie_domain = $config->get('cookie_domain', '');
	$cookie_path = $config->get('cookie_path', '/');
	setcookie(session_name(), '', time() - 42000, $cookie_path, $cookie_domain);
}

if ($params->get("icaltimezonelive", "") != "" && is_callable("date_default_timezone_set"))
{
	$timezone = date_default_timezone_get();
	$tz = $params->get("icaltimezonelive", "");
	date_default_timezone_set($tz);
	$registry = & JRegistry::getInstance("jevents");
	$registry->set("jevents.timezone", $timezone);
}

$params = JComponentHelper::getParams("com_rsvppro");

if (!$params->get("reminders", 0))
	die("No reminders allowed");

include_once(JPATH_SITE . "/components/com_jevents/jevents.defines.php");

$db = & JFactory::getDBO();
jimport("joomla.utilities.date");
JLoader::register('JEventsVersion', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/version.php");
$jevversion = JEventsVersion::getInstance();
if ($jevversion->get("RELEASE") < 2)
{
	JLoader::register('JevDate', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevdate.php");
}
else
{
	JLoader::register('JevDate', JPATH_SITE . "/components/com_jevents/libraries/jevdate.php");
}

$now = new JevDate("+0 seconds");
$now = $now->toMySQL();

$factor = 1;
$debug = 0;

$datamodel = & new JEventsDataModel();

$reminders = array();

// Do the whole event reminders first and the every separate repeat reminders too
// Get max reminder notice period
$db->setQuery("Select MAX(remindernotice) from #__jev_attendance where allowreminders>0 and remindallrepeats>0");
$maxnotice = $db->loadResult();

if ($maxnotice)
{
	$cutoff = new JevDate("+$maxnotice seconds");
	$cutofftime = $cutoff->toMySQL();

	echo "whole event cutoff = " . $cutofftime . "<br/>";

	// find list of events from attendances possibly requiring reminders
	// Get the matching event repeats within the reminder window
	$sql = <<<QUERY
SELECT atc.*,atc.id as at_id, rpt.rp_id as repeatid  , rpt.startrepeat, det.summary
FROM #__jevents_vevent as ev  
LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id 
LEFT JOIN #__jevents_vevdetail as det ON rpt.eventdetail_id = det.evdet_id 
LEFT JOIN  #__jev_attendance as atc ON atc.ev_id = ev.ev_id 
WHERE rpt.startrepeat < '$cutofftime' AND rpt.startrepeat> '$now' 
AND atc.allowreminders>0 and atc.remindallrepeats=1
AND TIMESTAMPDIFF(SECOND, '$now', rpt.startrepeat) < atc.remindernotice*$factor
GROUP BY ev.ev_id
ORDER BY rpt.startrepeat ASC
QUERY;
	$db->setQuery($sql);
	$rows = $db->loadObjectList();

	echo "found " . count($rows) . " rows<br/>";

	// Make sure these are the first repeats (if remindallrepeats == 1 i.e. send one reminder at the start of the event)
	foreach ($rows as $row)
	{
		if ($debug)
			echo "Processing Event <strong>" . $row->summary . "</strong><br/>";

		$query = "SELECT rp_id FROM #__jevents_repetition as rpt "
				. "\n WHERE eventid = '" . $row->ev_id . "' ORDER BY rpt.startrepeat asc LIMIT 1";

		$db->setQuery($query);
		$rpid = $db->loadResult();

		// Make sure these are the first repeats (if remindallrepeats == 1 i.e. send one reminder at the start of the event)
		if (intval($rpid) > 0 && intval($rpid) == $row->repeatid && $row->remindallrepeats == 1)
		{

			if ($row->remindallrepeats)
			{
				// first the whole event reminders
				$sql = "SELECT rem.*, rem.id as remid, ju.* , '$row->startrepeat' as startrepeat FROM #__jev_reminders as rem
			LEFT JOIN #__users as ju ON ju.id=rem.user_id
		WHERE rp_id=0 AND rem.at_id=$row->at_id AND sentmessage=0";

				$db->setQuery($sql);
				$reminders1 = $db->loadObjectList();

				echo $db->getErrorMsg();

				echo "found reminders for " . count($reminders1) . " event/users. <br/>";

				if ($debug && count($reminders1) == 0)
				{
					$sql = "SELECT rem.*, rem.id as remid, ju.* , '$row->startrepeat' as startrepeat FROM #__jev_reminders as rem
			LEFT JOIN #__users as ju ON ju.id=rem.user_id
		WHERE rp_id=0 AND rem.at_id=$row->at_id AND sentmessage=1";

					$db->setQuery($sql);
					$temp = $db->loadObjectList();
					echo "Already sent reminders to " . count($temp) . " event/users<br/>";
				}

				foreach ($reminders1 as &$reminder)
				{
					$reminder->event = $row;
				}
				unset($reminder);
				$reminders = array_merge($reminders, $reminders1);
			}
		}
	}
}

// Do the individual repeat reminders next
// Get max reminder notice period
$db->setQuery("Select MAX(remindernotice) from #__jev_attendance where allowreminders>0 and (remindallrepeats=0 OR remindallrepeats=2)");
$maxnotice = $db->loadResult();
$maxnotice *= $factor;
if ($maxnotice)
{
	$cutoff = new JevDate("+$maxnotice seconds");
	$cutofftime = $cutoff->toMySQL();

	echo "repeating events cutoff = " . $cutofftime . "<br/>";

	// find list of event from attendances possibly requiring reminders
	// Get the matching event repeats within the reminder window
	$sql = <<<QUERY
SELECT atc.*,atc.id as at_id, rpt.rp_id as repeatid , rpt.startrepeat
FROM #__jevents_repetition as rpt
LEFT JOIN #__jevents_vevent as ev ON rpt.eventid = ev.ev_id 
LEFT JOIN  #__jev_attendance as atc ON atc.ev_id = ev.ev_id 
WHERE rpt.startrepeat < '$cutofftime' AND rpt.startrepeat> '$now' 
AND atc.allowreminders>0 and  (atc.remindallrepeats=0 OR atc.remindallrepeats=2) 
AND TIMESTAMPDIFF(SECOND, '$now', rpt.startrepeat) < atc.remindernotice*$factor
ORDER BY rpt.startrepeat ASC
QUERY;

	$db->setQuery($sql);
	$rows = $db->loadObjectList();

	echo "found " . count($rows) . " rows<br/>";

	foreach ($rows as $row)
	{
		if ($row->remindallrepeats == 0 || $row->remindallrepeats == 2)
		{
			// second the specific repeat reminders
			$sql = "SELECT rem.*, rem.id as remid, ju.* , '$row->startrepeat' as startrepeat, '$row->allrepeats' as allrepeats FROM #__jev_reminders as rem
			LEFT JOIN #__users as ju ON ju.id=rem.user_id
		WHERE rp_id=$row->repeatid AND rem.at_id=$row->at_id AND sentmessage=0 AND (ju.email IS NOT NULL OR rem.email_address <> '')";
			$db->setQuery($sql);
			$reminders2 = $db->loadObjectList();

			echo $db->getErrorMsg();

			echo "found reminders for " . count($reminders2) . " events<br/>";

			if ($debug && count($reminders2) == 0)
			{
				$sql = "SELECT rem.*, rem.id as remid, ju.* , '$row->startrepeat' as startrepeat , '$row->allrepeats' as allrepeats FROM #__jev_reminders as rem
			LEFT JOIN #__users as ju ON ju.id=rem.user_id
		WHERE rp_id=$row->repeatid AND rem.at_id=$row->at_id AND sentmessage=1 AND (ju.email IS NOT NULL OR rem.email_address <> '')";

				$db->setQuery($sql);
				$temp = $db->loadObjectList();
				echo "Already sent reminders to " . count($temp) . " event/users<br/>";
			}

			foreach ($reminders2 as &$reminder)
			{
				$reminder->event = $row;
			}
			unset($reminder);

			$reminders = array_merge($reminders, $reminders2);
		}
	}
}

if (count($reminders) == 0)
	die("No matching reminders to process");

// Have the combined set of reminders  - now sort by startdate to make sure they are done in priority order
usort($reminders, "sortByStartDate");

// prepare the messages
$sent = 0;
foreach ($reminders as $reminder)
{

	$row = $reminder->event;
	list ($y, $m, $d) = JEVHelper::getYMD();

	$query = "SELECT ev.*, ev.state as published, rpt.*, rr.*, det.*"
			. "\n , YEAR(rpt.startrepeat) as yup, MONTH(rpt.startrepeat ) as mup, DAYOFMONTH(rpt.startrepeat ) as dup"
			. "\n , YEAR(rpt.endrepeat  ) as ydn, MONTH(rpt.endrepeat   ) as mdn, DAYOFMONTH(rpt.endrepeat   ) as ddn"
			. "\n , HOUR(rpt.startrepeat) as hup, MINUTE(rpt.startrepeat ) as minup, SECOND(rpt.startrepeat ) as sup"
			. "\n , HOUR(rpt.endrepeat  ) as hdn, MINUTE(rpt.endrepeat   ) as mindn, SECOND(rpt.endrepeat   ) as sdn"
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
			. "\n LEFT JOIN #__jevents_rrule as rr ON rr.eventid = ev.ev_id"
			. "\n WHERE rpt.rp_id = '$row->repeatid' LIMIT 1";
	$db->setQuery($query);
	$data = $db->loadObject();
	//$data = $datamodel->getEventData($row->repeatid,"icaldb",$y,$m,$d);
	if (is_null($data))
		continue;

	$event = new jIcalEventRepeat($data);

	$creator = JFactory::getUser($event->created_by());

	$email = $reminder->email ? $reminder->email : $reminder->email_address;
	$name = $reminder->name ? $reminder->name : $reminder->email_address;

	// should we skip unpaid attendees
	$skipunpaid = $params->get("skipunpaid", 0);
	if ($skipunpaid)
	{
		static $rsvpdataArray = array();
		if (!isset($rsvpdataArray[$reminder->at_id]))
		{
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($reminder->at_id);
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();

			$rsvpdataArray[$reminder->at_id] = $rsvpdata ? $rsvpdata : false;
		}
		$rsvpdata = $rsvpdataArray[$reminder->at_id];
		if ($rsvpdata && $rsvpdata->allowregistration)
		{

			$db = JFactory::getDBO();
			if ($params->get("attendemails", 0) && $reminder->user_id == 0)
			{
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and email_address=" . $db->Quote($email);
			}
			else
			{
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and user_id=" . $reminder->user_id;
			}
			if (!$rsvpdata->allrepeats)
			{
				$sql .= " AND rp_id=" . $event->rp_id();
			}
			$db->setQuery($sql);
			$attendee = $db->loadObject();

			// check if the attendee has made any payments
			if ($attendee)
			{
				JLoader::register('JevTemplateHelper', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/templatehelper.php");
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					JLoader::register('JevRsvpParameter', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrsvpparameter.php");
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$feesAndBalances = $params->outstandingBalance($attendee);

					if ($feesAndBalances["hasfees"] && $feesAndBalances["totalfee"] > 0 && $feesAndBalances["feepaid"] == 0)
					{
						// has not made any payments to cancel this reminder!
						$sql = "UPDATE #__jev_reminders set sentmessage=1 , sentdate='" . $now . "' WHERE id=" . $reminder->remid;
						$db->setQuery($sql);
						$db->query();

						echo "Skip reminder to  ", $name . " -  they have not paid<br/>";
						continue;
					}
				}
			}
		}
	}

	$subject = parseMessage($row->remindersubject, $reminder, $event, $reminder, $creator);
	$message = parseMessage($row->remindermessage, $reminder, $event, $reminder, $creator);

	JPluginHelper::importPlugin("rsvppro");
	$dispatcher = & JDispatcher::getInstance();
	$sendemail = true;
	$results = $dispatcher->trigger('onSendReminder', array(&$reminder, &$sendemail, $row, $event, $creator, $email, $subject, $message));
	if (!$sendemail)
	{
		$sql = "UPDATE #__jev_reminders set sentmessage=1 , sentdate='" . $now . "' WHERE id=" . $reminder->remid;
		$db->setQuery($sql);
		$db->query();

		$sent++;
		continue;
	}

	// BCC ?
	$bcc = getBCC($reminder->user_id);
	$success = sendMail($creator->email, $creator->name, $email, $subject, $message, 1, null, $bcc);

	$mainframe = JFactory::getApplication('site');
	if ($success === true)
	{
		echo "Sent message to ", $name . "<br/>";

		$sql = "UPDATE #__jev_reminders set sentmessage=1 , sentdate='" . $now . "' WHERE id=" . $reminder->remid;
		$db->setQuery($sql);
		$db->query();

		$sent++;
	}
	else
	{
		echo "FAILED TO SEND message to ", $name . "<br/>";
	}
	if (intval($params->get("maxreminders", 0)) > 0 && $sent >= intval($params->get("maxreminders", 0)))
	{
		echo "Send $sent reminders - " . (count($reminders) - $sent) . " remain";
		die();
	}
}

echo "Send $sent reminders - " . (count($reminders) - $sent) . " remain";

function parseMessage($message, $rsvpdata, $row, $iuser, $creator)
{
	$params = JComponentHelper::getParams("com_rsvppro");

	// do we run through the jevents plugins
	if ($params->get("remindplugins", 0))
	{
		JPluginHelper::importPlugin('jevents');
		$dispatcher = & JDispatcher::getInstance();
		JRequest::setVar("repeating", $rsvpdata->allrepeats);
		JRequest::setVar("atd_id", array($rsvpdata->id . "|" . $row->rp_id()));

		$dispatcher->trigger('onDisplayCustomFields', array(&$row));
	}

	$message = str_replace("{USERNAME}", $iuser->username, $message);
	$message = str_replace("{NAME}", $iuser->name, $message);
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
	if (count($matches) == 2)
	{
		$date = new JevDate($row->getUnixStartTime());
		$message = preg_replace($regex, $date->toFormat($matches[1]), $message);
	}

	$regex = "#{TIME}(.*?){/TIME}#s";
	preg_match($regex, $message, $matches);
	if (count($matches) == 2)
	{
		$date = new JevDate($row->getUnixStartTime());
		$message = preg_replace($regex, $date->toFormat($matches[1]), $message);
	}

	$regex = "#{LINK}(.*?){/LINK}#s";
	preg_match($regex, $message, $matches);
	if (count($matches) == 2)
	{
		$Itemid = JRequest::getInt("Itemid");
		list($year, $month, $day) = JEVHelper::getYMD();
		$link = $row->viewDetailLink($year, $month, $day, true, $Itemid);

		if (strpos($link, "/") !== 0)
		{
			$link = "/" . $link;
		}
		if (strpos($link, "plugins/jevents/rsvppro") !== false)
		{
			$link = str_replace("plugins/jevents/rsvppro/", "", $link);
		}
		if (strpos($link, "plugins/jevents/jevrsvppro/rsvppro") !== false)
		{
			$link = str_replace("plugins/jevents/jevrsvppro/rsvppro/", "", $link);
		}
		if (strpos($link, "plugins/jevents") !== false)
		{
			$link = str_replace("plugins/jevents/", "", $link);
		}

		$uri = & JURI::getInstance(JURI::base());
		$root = $uri->toString(array('scheme', 'host', 'port'));

		$link = $root . $link;
		if (strpos($link, "plugins/jevents/rsvppro") >= 0)
		{
			$link = str_replace("plugins/jevents/rsvppro/", "", $link);
		}
		if (strpos($link, "plugins/jevents/jevrsvppro/rsvppro") >= 0)
		{
			$link = str_replace("plugins/jevents/jevrsvppro/rsvppro/", "", $link);
		}
		if (strpos($link, "plugins/jevents") >= 0)
		{
			$link = str_replace("plugins/jevents/", "", $link);
		}
		if ($row->access() > JVersion::isCompatible("1.6.0") ? 1 : 0)
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

	// do we run through the jevents plugins
	if ($params->get("remindplugins", 0))
	{
		include_once(JEV_VIEWS . "/default/helpers/defaultloadedfromtemplate.php");
		ob_start();
		DefaultLoadedFromTemplate(false, false, $row, 0, $message);
		$message = ob_get_clean();
	}

	return $message;

}

function sortByStartDate($a, $b)
{
	// sort in increasing start repeat date order
	if ($a->startrepeat == $b->startrepeat)
		return 0;
	return $a->startrepeat > $b->startrepeat ? 1 : -1;

}

function getBCC($userid)
{
	$bcc = null;
	$params = JComponentHelper::getParams("com_rsvppro");
	if ($userid > 0 && $params->get("cbbcc") != "")
	{
		$bccfield = $params->get("cbbcc");
		$db = JFactory::getDBO();
		$sql = "select $bccfield from #__comprofiler where user_id = $userid";
		$db->setQuery($sql);
		$bcc = $db->loadResult();
	}
	return $bcc;

}

function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc = null, $attachment = null, $replyto = null, $replytoname = null)
{
	$params = JComponentHelper::getParams("com_rsvppro");
	$from = $params->get("overridesenderemail", $from);
	$fromname = $params->get("overridesendername", $fromname);
	$mail = JFactory::getMailer();
	return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);

}

