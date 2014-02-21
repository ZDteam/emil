<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgJEventsJevnotify extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		JPlugin::loadLanguage('plg_jevents_jevnotify', JPATH_ADMINISTRATOR);

	}

	function onAfterSaveEvent($event, $dryrun=false)
	{
		if (!$event->state)
			return;
		if ($dryrun)
			return;

		if ($event->ev_id > 0 && JRequest::getInt("evid", 0) == $event->ev_id)
		{
			$cmd = "modified";
			$ev_id = $event->ev_id;

			// get the data and query models
			$dataModel = new JEventsDataModel();
			$jevent = $dataModel->queryModel->getEventById(intval($ev_id), 1, "icaldb");

			if ($jevent)
			{
				$this->changedEvent($jevent);
			}
		}
		else
		{
			$cmd = "created";
			$ev_id = $event->ev_id;

			// get the data and query models
			$dataModel = new JEventsDataModel();
			$jevent = $this->loadEvent($ev_id);

			if ($jevent)
			{
				$this->newEvent($jevent);
			}
		}

		return true;

	}

	function onPublishEvent($cid, $newstate)
	{
		// We assume any published events are NEW - we have no way of knowing otherwise !
		if (is_array($cid))
		{
			foreach ($cid as $ev_id)
			{
				// get the data and query models
				$dataModel = new JEventsDataModel();
				$jevent = $dataModel->queryModel->getEventById(intval($ev_id), 1, "icaldb");
				if ($jevent)
				{
					// Unpublished events trigger same as deletion of event
					if ($newstate == 0)
					{
						$this->deleteEvent($jevent);
					}
					else
					{
						$this->newEvent($jevent);
					}
				}
			}
		}

	}

	/*
	 * this will not work because the database entries are already gone
	  function onDeleteEventDetails($idlist) {
	  // delete the metatags too
	  $db = JFactory::getDBO();

	  $query = "SELECT DISTINCT (rp_id) FROM #__jevents_repetition WHERE eventdetail_id IN ($idlist)";
	  $db->setQuery( $query);
	  $repeatids = $db->loadColumn();

	  foreach ($repeatids as $repeatid){

	  }
	  return true;

	  }
	 */

	function onStoreCustomDetails(&$evdetail)
	{
		$detailid = $evdetail->evdet_id;

		// Are we saving a repeat - then notify of a change to this repeat
		$task = JRequest::getString("task", "");
		if ($task == "icalrepeat.save" || $task == "icalrepeat.apply"  || $task == "icals.reload")
		{
			$repeat_id = intval(JRequest::getVar("rp_id", "0"));
			// get the data and query models
			$dataModel = new JEventsDataModel();
			$jevent = $dataModel->queryModel->listEventsById(intval($repeat_id), 1, "icaldb");

			if (!$jevent || !$jevent->published())
				return;
			if ($jevent)
			{
				$this->changedEvent($jevent);
			}
		}

	}

	private function changedEvent($event)
	{
		$this->cleanUnsent(1, $event->rp_id());

		$this->newUnsent(1, $event);

	}

	private function newEvent($event)
	{
		$this->cleanUnsent(0, $event->rp_id());

		$this->newUnsent(0, $event);

	}

	private function deleteEvent($event)
	{
		$this->cleanUnsent(2, $event->rp_id());

		$this->newUnsent(2, $event);

	}

	private function newUnsent($type, $event, $manual = false)
	{
		// imported events - we don't know if they have changed so we never set change notifications.
		$task = JRequest::getString("task", "");
		if ( $task == "icals.reload"){
			$manual = true;
		}
		// if not manually triggered and it is a changed event requireing manual intervention then do nothing here
		if (!$manual && $type==1 && $this->params->get("changenotificationtype") == 1)
		{
			return;
		}

		$rpid = $event->rp_id();
		if ($type == 0)
		{
			// new event
			$notifications = $this->params->get("neweventnotifications");
		}
		else if ($type == 2)
		{
			// delete event
			$notifications = $this->params->get("deletenotifications");
		}
		else
		{
			// TODO CHECK MANUAL/AUTO TYPE
			// changed event
			$notifications = $this->params->get("changenotifications");
		}

		if (is_string($notifications))
		{
			if ($notifications == 0)
				return;
			$notifications = array($notifications);
		}
		JArrayHelper::toInteger($notifications);
		if (class_exists("JevDate")){
			$date = JevDate::getDate("+0 seconds");
		}
		else {
			$date = & JFactory::getDate('+0 seconds');
		}
		$created = $date->toSql();
		$db = JFactory::getDBO();

		$task = JRequest::getString("task", "");

		foreach ($notifications as $notification)
		{
			if ($notification < 0)
			{
				if (!JVersion::isCompatible("1.6.0")) continue;
					$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created)
				SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, user_id, " . $db->Quote($created) . " as created  FROM #__user_usergroup_map  where group_id=".(-$notification)." group by user_id");
				// Joomfish bug workaround
				$db->_skipSetRefTables = true;
				$db->query();
				$db->_skipSetRefTables = false;
				
			}
			else
			{
				switch ($notification) {
					case 0:
						// Bootstrap doesn't clear the zero select option !!! ARGH!!
						if (count($notifications) == 1){
							return;
						}
							
						break;
					case 1:
						// registered users
						if (JVersion::isCompatible("1.6.0")) continue;
							$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created)
						SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created  FROM #__users where gid>0 AND gid<19");

						// Joomfish bug workaround
						$db->_skipSetRefTables = true;
						$db->query();
						$db->_skipSetRefTables = false;
						break;
					case 2:
						// special users
						if (JVersion::isCompatible("1.6.0")) continue;
							$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created)
						SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created FROM #__users where gid>=19");

						// Joomfish bug workaround
						$db->_skipSetRefTables = true;
						$db->query();
						$db->_skipSetRefTables = false;
						break;
					case 3:
						// attendees
						// RSVP Pro first

						$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id();
						$db->setQuery($sql);
						$rsvpdata = $db->loadObject();
						if ($rsvpdata)
						{
							$sql = "SELECT a.id, a.user_id FROM #__jev_attendees as a WHERE a.at_id=" . $rsvpdata->id;
							if ($task == "icalrepeat.save" || $task == "icalrepeat.apply"  || $task == "icals.reload")
							{
								// if attending specific repeat only notify when this particular repeat is changed
								if (!$rsvpdata->allrepeats)
								{
									$sql .= " and a.rp_id=" . $event->rp_id();
								}
								// attending all repeats to notift if any repeat is changed
								else
								{
									$sql .= " and a.rp_id= 0";
								}
							}
							else
							{
								// if changing the event then always notify
							}
							$sql .= " and a.attendstate!=0";
							$db->setQuery($sql);
							$attendees = $db->loadObjectList();
							if ($attendees && count($attendees) > 0)
							{
								$userids = array(-1);
								$atids = array();
								foreach ($attendees as $attendee)
								{
									if (!in_array($attendee->user_id, $userids) && $attendee->user_id > 0)
									{
										$userids[] = $attendee->user_id;
										$atids[] = $attendee->id;
									}
								}
								// now remove any notifications for attendees who we are about to insert
								$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id IN(" . implode(",", $userids) . ")");
								$db->query();

								// Now REPLACE the new entries
								$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, attendee_id, created, user_id, emailaddress)
								SELECT " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created, user_id, email_address FROM #__jev_attendees  where id IN(" . implode(",", $atids) . ")");
								// Joomfish bug workaround
								$db->_skipSetRefTables = true;
								$db->query();
								$db->_skipSetRefTables = false;
							}
						}

						// Now Attend JEvents
						$sql = "SELECT * FROM #__aje_sessions WHERE event_id=" . $event->ev_id();
						$db->setQuery($sql);
						$ajedata = $db->loadObject();
						if ($ajedata)
						{
							$sql = "SELECT r.registration_id, r.userid FROM #__aje_registrations  as r WHERE r.session_id=" . $ajedata->session_id;
							// ignore cancelled entries
							$sql .= " and r.status!=3";
							$db->setQuery($sql);
							$attendees = $db->loadObjectList();
							if (!$attendees || count($attendees) == 0)
							{
								break;
							}

							$userids = array(-1);
							$atids = array();
							foreach ($attendees as $attendee)
							{
								if (!in_array($attendee->userid, $userids) && $attendee->userid > 0)
								{
									$userids[] = $attendee->userid;
									$atids[] = $attendee->registration_id;
								}
							}
							// now remove any notifications for attendees who we are about to insert
							$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id IN(" . implode(",", $userids) . ")");
							$db->query();

							// Now REPLACE the new entries
							$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, ajeattendee_id, created, user_id, emailaddress)
							SELECT " . $db->Quote($rpid) . " as rpid,$type as type, registration_id, " . $db->Quote($created) . " as created, userid, email FROM #__aje_registrations  where registration_id IN(" . implode(",", $atids) . ")");
							// Joomfish bug workaround
							$db->_skipSetRefTables = true;
							$db->query();
							$db->_skipSetRefTables = false;
						}

						break;
					case 4:
						// invitees
						$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id();
						$db->setQuery($sql);
						$rsvpdata = $db->loadObject();
						if ($rsvpdata)
						{
							$sql = "SELECT a.id, a.user_id FROM #__jev_invitees as a WHERE a.at_id=" . $rsvpdata->id;

							if ($task == "icalrepeat.save" || $task == "icalrepeat.apply"  || $task == "icals.reload")
							{
								// if invited specific repeat only notify when this particular repeat is changed
								if (!$rsvpdata->allrepeats)
								{
									$sql .= " and a.rp_id=" . $event->rp_id();
								}
								// invited all repeats to notift if any repeat is changed
								else
								{
									$sql .= " and a.rp_id= 0";
								}
							}
							else
							{
								// if changing the event then always notify
							}

							// only notify invitees who have viewed the event
							if ($this->params->get("whichinvitees") == 1)
							{
								$sql .= " and a.viewedevent=1";
							}

							$db->setQuery($sql);
							$invitees = $db->loadObjectList();
							if ($invitees && count($invitees) > 0)
							{
								$userids = array(-1);
								$atids = array();
								foreach ($invitees as $invitee)
								{
									if (!in_array($invitee->user_id, $userids) && $invitee->user_id > 0)
									{
										$userids[] = $invitee->user_id;
										$atids[] = $invitee->id;
									}
								}
								// now remove any notifications for attendees who we are about to insert
								$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id IN(" . implode(",", $userids) . ")");
								$db->query();

								// Now REPLACE the new entries
								$db->setQuery("REPLACE INTO #__jev_notifications (rp_id, messagetype, invitee_id, created, user_id, emailaddress)
									SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created, user_id, email_address  FROM #__jev_invitees where id IN(" . implode(",", $atids) . ")");
								// Joomfish bug workaround
								$db->_skipSetRefTables = true;
								$db->query();
								$db->_skipSetRefTables = false;
							}
						}
						break;
					case 5:
						// remindees
						$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id();
						$db->setQuery($sql);
						$rsvpdata = $db->loadObject();
						if ($rsvpdata)
						{
							$sql = "SELECT a.id, a.user_id FROM #__jev_reminders as a WHERE a.at_id=" . $rsvpdata->id;
							// if reminded for  specific repeat only notify when this particular repeat is changed
							if ($task == "icalrepeat.save" || $task == "icalrepeat.apply"  || $task == "icals.reload")
							{
								$sql .= " and ( a.rp_id=" . $event->rp_id() . " OR a.rp_id=0) ";
							}
							else
							{
								// All repeats have changes to notify them all
							}

							$db->setQuery($sql);
							$invitees = $db->loadObjectList();
							if ($invitees && count($invitees) > 0)
							{
								$userids = array(-1);
								$atids = array();
								foreach ($invitees as $invitee)
								{
									if (!in_array($invitee->user_id, $userids) && $invitee->user_id > 0)
									{
										$userids[] = $invitee->user_id;
										$atids[] = $invitee->id;
									}
								}
								// now remove any notifications for attendees who we are about to insert
								$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id IN(" . implode(",", $userids) . ")");
								$db->query();

								// Now REPLACE the new entries
								$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, remindee_id, created, user_id, emailaddress)
									SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created,  user_id, email_address FROM #__jev_reminders where id IN(" . implode(",", $atids) . ")");

								// Joomfish bug workaround
								$db->_skipSetRefTables = true;
								$db->query();
								$db->_skipSetRefTables = false;
							}
						}
						break;
					case 6:
						// Event creators - when the event is edited by someone else
						$user = JFactory::getUser();
						if ($user->id != $event->created_by())
						{
							if ($event->created_by() > 0)
							{
								// now remove any notifications for event editors we are about to insert
								$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id=" . $event->created_by());
								$db->query();

								// Now REPLACE the new entries
								$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created)
								SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, id, " . $db->Quote($created) . " as created FROM #__users where id=" . $event->created_by());
								// Joomfish bug workaround
								$db->_skipSetRefTables = true;
								$db->query();
								$db->_skipSetRefTables = false;
							}
							else
							{
								$db->setQuery("SELECT * FROM #__jev_anoncreator WHERE ev_id=" . $event->ev_id());
								$creator = @$db->loadObject();

								if ($creator)
								{
									// now remove any notifications for attendees who we are about to insert
									$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND emailaddress = ", $db->Quote($creator->email));
									$db->query();

									// Now REPLACE the new entries
									$db->setQuery("REPLACE INTO #__jev_notifications (rp_id, messagetype, created, emailaddress)
																									 VALUES (" . $db->Quote($rpid) . ",$type ," . $db->Quote($created) . " ," . $db->Quote($creator->email) . ")");
									// Joomfish bug workaround
									$db->_skipSetRefTables = true;
									$db->query();
									$db->_skipSetRefTables = false;
								}
							}
						}
						break;
					case 7:
						// Event notifications requested by users
						// now remove any notifications for USER we are about to insert
						$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND user_id in(SELECT user_id FROM #__jev_notification_map WHERE cat_id=" . $event->catid() . ")");
						$db->query();

						// Now REPLACE the new entries
						$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created)
								SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, user_id, " . $db->Quote($created) . " as created FROM #__jev_notification_map where cat_id=" . $event->catid());
						// Joomfish bug workaround
						$db->_skipSetRefTables = true;
						$db->query();
						$db->_skipSetRefTables = false;

						break;
					case 8:
						// Event notifications sent to associated Managed People
						$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0 AND notificationtype=".$notification);
						$db->query();
						
						// Find the associated managed people
						/*
						$db->setQuery("SELECT p.linktouser FROM #__jev_peopleeventsmap as pm 
							LEFT JOIN #__jev_people as p ON p.pers_id=pm.pers_id 
							WHERE p.linktouser<> 0 AND pm.evdet_id=".$event->evdet_id()
							);
						*/
						// Now REPLACE the new entries
						$db->setQuery("REPLACE INTO #__jev_notifications (rp_id,  messagetype, user_id, created, notificationtype)
								SELECT  " . $db->Quote($rpid) . " as rpid,$type as type, p.linktouser, " . $db->Quote($created) . " as created, $notification  FROM #__jev_peopleeventsmap as pm 
							LEFT JOIN #__jev_people as p ON p.pers_id=pm.pers_id 
							WHERE p.linktouser<>0 AND pm.evdet_id=".$event->evdet_id());
						// Joomfish bug workaround
						$db->_skipSetRefTables = true;
						$db->query();
						$db->_skipSetRefTables = false;
						break;
					default:
						break;
				}
			}
		}
		//die("done");

	}

	private function cleanUnsent($type, $rpid, $manual = false)
	{
		if (!$manual && $this->params->get("changenotificationtype") == 1)
		{
			return;
		}
		$db = JFactory::getDBO();
		$db->setQuery("DELETE FROM #__jev_notifications WHERE messagetype=$type AND rp_id=$rpid AND sentmessage=0");
		$db->query();

	}
	function onDisplayCustomFields(&$row)
	{
		// only valid if manual notifications
		if ($this->params->get("changenotificationtype") == 0)
		{
			return;
		}
		if (JEVHelper::canPublishEvent($row))
		{

			// Only do this if there are no queued notifications
			$db = JFactory::getDBO();
			$rpid = $row->rp_id();
			$db->setQuery("SELECT max(created) FROM #__jev_notifications WHERE rp_id=$rpid");
			$created = $db->loadResult();

			if (!isset($row->_modified) || is_null($row->_modified) || $row->_modified==""  || $row->_modified == "0000-00-00 00:00:00" || (isset($row->_modified) && $row->_modified <= $created))
			{
				return;
			}

			if (JRequest::getInt("jevnotify", 0))
			{
				// do it manutally
				$this->cleanUnsent(1, $row->rp_id(), 1);
				$this->newUnsent(1, $row, 1);
				JFactory::getApplication()->enqueueMessage(JText::_('JEV_NOTIFICATIONS_QUEUED'));
			}
			else
			{
				global $Itemid;
				list($year, $month, $day) = JEVHelper::getYMD();
				$link = $row->viewDetailLink($year, $month, $day, true, $Itemid);
				$html = '
<form action="' . $link . '"  method="post" >
	<input type="hidden" name="jevnotify" value="1"/>
	<input type="submit" name="submit" value="' . JText::_('JEV_NOTIFY_ANY_CHANGES') . '" />
</form>';

				// Add reference in the event
				$row->_jevnotify = $html;
				return $html;
			}
		}

	}

	static function fieldNameArray($layout='detail')
	{
		// only offer in detail view
		if ($layout != "detail")
			return array();

		$lang = & JFactory::getLanguage();
		$lang->load("plg_jevents_jevnotify", JPATH_ADMINISTRATOR);

		$labels = array();
		$labels[] = JText::_("JEV_MANUAL_NOTIFICATION", true);
		$values = array();
		$values[] = "JEVNOTIFY";

		$return = array();
		$return['group'] = JText::_("JEV_CHANGE_NOTIFICATION", true);
		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

	}

	static function substitutefield($row, $code)
	{
		if ($code == "JEVNOTIFY")
		{
			if (isset($row->_jevnotify))
				return $row->_jevnotify;
		}
		return "";

	}

	private function loadEvent($ev_id)
	{
		// can't use DB model since plugins may mess up the search!
		$db = JFactory::getDBO();
		// make sure we pick up the event state
		$query = "SELECT ev.*, rpt.*, rr.*, det.* , ev.state as state "
				. "\n , YEAR(rpt.startrepeat) as yup, MONTH(rpt.startrepeat ) as mup, DAYOFMONTH(rpt.startrepeat ) as dup"
				. "\n , YEAR(rpt.endrepeat  ) as ydn, MONTH(rpt.endrepeat   ) as mdn, DAYOFMONTH(rpt.endrepeat   ) as ddn"
				. "\n , HOUR(rpt.startrepeat) as hup, MINUTE(rpt.startrepeat ) as minup, SECOND(rpt.startrepeat ) as sup"
				. "\n , HOUR(rpt.endrepeat  ) as hdn, MINUTE(rpt.endrepeat   ) as mindn, SECOND(rpt.endrepeat   ) as sdn"
				. "\n FROM #__jevents_vevent as ev "
				. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id"
				. "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
				. "\n LEFT JOIN #__jevents_rrule as rr ON rr.eventid = ev.ev_id"
				. "\n WHERE ev.ev_id = '$ev_id'"
				. "\n GROUP BY rpt.rp_id"
				. "\n LIMIT 1";

		$db->setQuery($query);
		$row = $db->loadObject();
		if (!$row)
			return false;
		$row = new jIcalEventRepeat($row);
		return $row;

	}

}

