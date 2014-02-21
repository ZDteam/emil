<?php

/**
 * Copyright (C) 2010 GWE Systems Ltd
 *
 * All rights reserved.
 *
 */
defined('_JEXEC') or die('No Direct Access');

/**
 * Attendstate explanations
 * 0 = not attending
 * 1 = attending
 * 2 = maybe attending
 * 3 = attending but subject to approval i.e.pending
 * 4 = attending but has outstanding balance to pay
 */
class RsvpAttendeeHelper
{

	private $params;
	private $jomsocial = false;

	public function __construct($params)
	{
		$this->params = $params;
		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE . '/components/com_community/community.php'))
		{
			if (JComponentHelper::isEnabled("com_community"))
			{
				$this->jomsocial = true;
			}
		}

		JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
		$this->jevrreminders = new JevRsvpReminders($this->params, $this->jomsocial);

	}

	public function isAttending($rsvpdata, $row, $emailaddress = "", $anystatus = false)
	{

		// Cannot say if email based attendance is true so ignore this
		$user = JFactory::getUser();

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}
		// if no email address then can't be an attendee
		if ($this->params->get("attendemails", 0) && $user->id == 0 && $emailaddress == "")
		{
			return false;
		}

		$db = JFactory::getDBO();
		if ($this->params->get("attendemails", 0) && $user->id == 0)
		{
			$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and email_address=" . $db->Quote($emailaddress);
		}
		else
		{
			$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and user_id=" . $user->id;
		}
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " AND rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		$this->attendee = $db->loadObject();

		$this->analyseGuests($this->attendee, $rsvpdata, $row);
		$this->countAttendees($rsvpdata->id);

		// attending or wit outstanding payment amounts
		if ($this->attendee && ($this->attendee->attendstate == 1 || $this->attendee->attendstate == 4 ) && $this->attendee->confirmed == 1)
		{
			return $this->attendee;
		}
		else if ($anystatus)
		{
			return $this->attendee;
		}
		else
		{
			return false;
		}

	}

	public function calculateBalances(&$view, $rsvpdata, $row)
	{
		// This MUST be called before renderToBasicArray to populate the balance fields - so we do it here to be safe
		if (isset($this->attendee) && isset($this->attendee->params))
		{

			// ensure plugins like virtuemart that don't have a universal notify mechanism are up to date!
			JPluginHelper::importPlugin("rsvppro");
			$dispatcher = & JDispatcher::getInstance();
			$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $this->attendee, $row));

			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $rsvpdata, $row);
				$feesAndBalances = $params->outstandingBalance($this->attendee);
				$view->assignRef("attendeeParams", $params);
			}
		}

	}

	public function getEmailAddress($em = "em")
	{
		$emailaddress = "";
		if ($this->params->get("attendemails", 0))
		{
			$em = JRequest::getString($em, "");

			if ($em != "")
			{
				$emd = base64_decode($em);
				if (strpos($emd, ":") > 0)
				{
					list ( $emailaddress, $code ) = explode(":", $emd);
					if ($em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress)) &&
							$em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress . "invited")))
					{
						$emailaddress = "";
					}
				}
			}
		}
		return $emailaddress;

	}

	public function adminCancelAttendance($rsvpdata, $row)
	{
		$user = JFactory::getUser();

		$db = JFactory::getDBO();
		// admin/creator cancelling user?
		if ($user->id == $row->created_by() || JEVHelper::canPublishEvent($row))
		{
			$jevattend_id = JRequest::getInt("jevattendlist_id", 0);
			if ($jevattend_id > 0)
			{

				// auto remind attendees
				if ($this->params->get("autoremind", 0) == 1 && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
				{
					// cancel reminder
					$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id . " AND attendstate=1";
					$db->setQuery($sql);
					$attendee = $db->loadObject();
					$attendeeuser = JFactory::getUser($attendee->user_id);

					$this->jevrreminders->unremindUser($rsvpdata, $row, $user, $attendee->email_address);
				}

				if ($this->params->get("notifyadmincancelled", 0))
				{

					$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
					$db->setQuery($sql);
					$attendee = $db->loadObject();

					$attendeeuser = JFactory::getUser($attendee->user_id);
					if ($attendeeuser->id == 0 && $this->params->get("attendemails", 0))
					{
						$name = $attendee->email_address;
						$username = $attendee->email_address;
					}
					else
					{
						$name = $attendeeuser->name;
						$username = $attendeeuser->username;
					}
					$attendee->attendstate = 0;
					$this->notifyUser($rsvpdata, $row, $attendeeuser, $name, $username, $attendee, 'admincancelled', false);
				}

				$sql = "DELETE FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db->setQuery($sql);
				$db->query();

				$mainframe = JFactory::getApplication();
				$Itemid = JRequest::getInt("Itemid");
				//list ( $year, $month, $day ) = JEVHelper::getYMD();
				$year = $row->yup();
				$month = $row->mup();
				$day = $row->dup();
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

				// Make sure the counts are in sync!
				$this->countAttendees($rsvpdata->id);

				$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CANCELLED_BY_ADMIN"));
			}
		}

	}

	public function adminApproveAttendance($rsvpdata, $row)
	{
		$user = JFactory::getUser();

		// admin/creator cancelling user?
		if ($user->id == $row->created_by() || JEVHelper::canPublishEvent($row))
		{
			$jevattend_id = JRequest::getInt("jevattendlist_id_approve", 0);
			if ($jevattend_id > 0)
			{

				$db = JFactory::getDBO();
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db->setQuery($sql);
				$attendee = $db->loadObject();

				if (!$attendee)
					return;
				// auto remind attendees
				if ($this->params->get("autoremind", 0) == 1 && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
				{
					// cancel reminder
					$user = JFactory::getUser($attendee->user_id);

					$this->jevrreminders->remindUser($rsvpdata, $row, $user, $attendee->email_address);
				}

				$db = JFactory::getDBO();
				$sql = "UPDATE #__jev_attendees SET attendstate=1, waiting=0 WHERE id=" . $attendee->id;

				$db->setQuery($sql);
				$db->query();

				$mainframe = JFactory::getApplication();
				$Itemid = JRequest::getInt("Itemid");
				//list ( $year, $month, $day ) = JEVHelper::getYMD();
				$year = $row->yup();
				$month = $row->mup();
				$day = $row->dup();
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

				// Make sure the counts are in sync!
				$this->countAttendees($rsvpdata->id);

				// notify user?				
				if ($this->params->get("notifyapproved", 0))
				{
					$user = JFactory::getUser($attendee->user_id);
					if ($user->id == 0 && $this->params->get("attendemails", 0))
					{
						$name = $attendee->email_address;
						$username = $attendee->email_address;
					}
					else
					{
						$name = $user->name;
						$username = $user->username;
					}
					$onWaitingList = false;
					$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'approve', $onWaitingList);
				}


				$cache = & JFactory::getCache('com_jevents');
				$cache->clean();

				$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_APPROVED"));
			}
		}

	}

	public function notifyCreator($rsvpdata,& $row, $name, $username, $attendee = null, $cancellation = false, $onWaitingList = false)
	{
		$this->event = $row;
		$creator = JFactory::getUser(isset($this->event->created_by) ? $this->event->created_by : $this->event->created_by());		
		if ($creator)
		{
			// if attendee then reload from database to make sure latest status is included in the message
			if ($attendee){
				$db = JFactory::getDbo();				
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->id;
				$db->setQuery($sql);
				$newattendee = $db->loadObject();				
				if ($newattendee){
					$attendee = $newattendee;
				}
				else {
					// attendee has been deleted so set the attendance state to not attending for the message
					$attendee->attendstate = 0;
				}
			}
			
			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();

			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
			$root = JURI::root();
			// NO sef since sub solder installs get the path twice

			$link = $root . $link;

			$comparams = JComponentHelper::getParams("com_rsvppro");
			$notifybcc = $comparams->get("notifybcc", "");
			$notifybcc = str_replace(" ", "", $notifybcc);

			$transaction = false;
			$speciallink=false;
			
			if ($cancellation)
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "cancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "cancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
			}
			else
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "notifysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "notifymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
			}

			$this->sendMail($creator->email, $creator->name, $creator->email, $subject, $message, 1, null, $notifybcc);
		}

	}

	public function notifyUser($rsvpdata, & $row, $user, $name, $username, $attendee = null, $messagetype = 'ack', $onWaitingList = false, $transaction = false, $subject = "", $message = "", $speciallink=false)
	{
		if (!isset($this->event)) {
			$this->event = $row;
		}
		$creator = JFactory::getUser(isset($this->event->created_by) ? $this->event->created_by : $this->event->created_by());
		if ($creator)
		{
			// if attendee then reload from database to make sure latest status is included in the message
			if ($attendee){
				$db = JFactory::getDbo();
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->id;
				$db->setQuery($sql);
				$newattendee = $db->loadObject();				
				if ($newattendee){
					if ($messagetype != 'admincancelled' && $messagetype != 'usercancel'){
						$attendee = $newattendee;
					}
					else {
						// attendee has been deleted so set the attendance state to not attending for the message
						$attendee->attendstate = 0;
					}
				}
				else {
					// attendee has been deleted so set the attendance state to not attending for the message
					$attendee->attendstate = 0;
				}
			}
			
			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();

			if ($messagetype == 'ack')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "acksubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "ackmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'approve')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "approvedsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "approvedmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'admincancelled')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "admincancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "admincancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'manpay')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "manpaysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "manpaymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'pplpay')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "pplpaysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "pplpaymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'vmpay')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "vmpaysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "vmpaymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'azpay')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "azpaysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "azpaymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'usercancel')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "usercancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "usercancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'cem')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "emailaddressconfirmationsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "emailaddressconfirmationmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, false);
			}
			else if ($messagetype == 'waitupdate')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "waitingliststatussubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "waitingliststatusmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink,false);
			}
			else if ($subject != "" && $message != "")
			{
				$subject = $this->parseMessage($subject, $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($message, $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else
			{
				return;
			}

			// do not send empty messages
			if ($subject == "" && $message == "")
			{
				return;
			}

			if ($user->id > 0)
			{
				/*
				  if (JFactory::getUser()->id == 79)
				  {
				  JFactory::getApplication()->enqueueMessage("get BCC and send message to  " . $user->id);
				  }
				 */
				$bcc = $this->getBCC($user->id);
				/*
				  if (JFactory::getUser()->id == 79)
				  {
				  JFactory::getApplication()->enqueueMessage("got BCC of " . $bcc);
				  }
				 * 
				 */
				$this->sendMail($creator->email, $creator->name, $user->email, $subject, $message, 1, null, $bcc);
			}
			else
			{
				$this->sendMail($creator->email, $creator->name, $name, $subject, $message, 1);
			}
		}

	}

	/*
	  public function updateWaitingList(&$rsvpdata, $atdid = 0)
	  {
	  $atdid = intval($atdid);
	  $db = JFactory::getDBO ();

	  if ($atdid == 0) {
	  // Now make sure the atdcount is in sync
	  $db->setQuery("SELECT atd.id FROM #__jev_attendance as atd");
	  $rows = $db->loadObjectList();

	  foreach ($rows as $row) {
	  if (intval($row->id) == 0)
	  continue;
	  $this->updateWaitingList($rsvpdata, $row->id);
	  }
	  } else if ($atdid > 0) {
	  // Now make sure the atdcount is in sync
	  $db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
	  LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
	  LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
	  WHERE atd.id =" . intval($atdid) . " AND atdees.waiting=1 AND atdees.attendstate=1 ORDER BY atdees.id asc ");
	  $rows = $db->loadObjectList();
	  // may not have any attendees so get a null here
	  if (!$rows)
	  return;

	  $updatedCount = 0;
	  foreach ($rows as $row) {
	  if ($row->atdcount - count($rows) + $updatedCount >= $row->capacity)
	  break;
	  // TODO FIX THIS
	  $db->setQuery("UPDATE #__jev_attendees SET waiting=0 where id=" . $row->atdee_id);
	  if ($db->query()) {
	  $updatedCount++;

	  // auto remind attendees
	  if ($this->params->get("autoremind", 0) == 1 && $rsvpdata->allowreminders) {
	  // create reminder
	  // NB email address must be in the request object
	  $user = JFactory::getUser($row->user_id);
	  $this->jevrreminders->remindUser($rsvpdata, $row, $user);
	  $mainframe = JFactory::getApplication();
	  $mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
	  }

	  if ($this->params->get("notifyuser", 0))
	  $this->notifyWaitingUser($row);
	  } else
	  echo $db->getErrorMsg();
	  }
	  }
	  }
	 */

	public function notifyWaitingUser($attendee)
	{
		
		// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$rsvpdata = $registry->get("rsvpdata");
		$row = $registry->get("event");
		$user = JFactory::getUser($attendee->user_id );
		
		$Itemid = JRequest::getInt("Itemid");		

		if ($attendee->user_id > 0)
		{
			$attendeeuser = JFactory::getUser($attendee->user_id );
			$name = $attendeeuser->name;
			$username = $attendeeuser->username;
		}
		else
		{
			$name = $attendee->email_address;
			$username = $attendee->email_address;
		}		
		
		// get the true attendeem record
		$db = JFactory::getDbo();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->atdee_id;
		$db->setQuery($sql);
		$newattendee = $db->loadObject();				
		
		// not an iCalRepeat object to get the correct one
		if (isset($attendee->rp_id) && !isset($row->_rp_id)){
			$datamodel = new JEventsDataModel();
			if ($attendee->rp_id > 0)
			{
				$evt = $datamodel->queryModel->listEventsById($attendee->rp_id, 1, "icaldb");
			}
			else
			{
				$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
			}
			$row=$evt;
		}
		
		$this->notifyUser($rsvpdata, $row, $user, $name, $username, $newattendee, 'waitupdate', $newattendee->waiting);

	}

	public function parseMessage($message, $rsvpdata,& $row, $name, $username, $attendee = null, $requirelogin = false, $onWaitingList = false, $transaction = false,$speciallink=false, $issubject=false)
	{
		$params = JComponentHelper::getParams("com_rsvppro");

		// do we run through the jevents plugins
		if ($params->get("remindplugins", 0)) {
			JPluginHelper::importPlugin('jevents');
			$dispatcher	=& JDispatcher::getInstance();
			JRequest::setVar("repeating",$rsvpdata->allrepeats);
			JRequest::setVar("atd_id",array($rsvpdata->id."|".$row->rp_id()));

			$dispatcher->trigger( 'onDisplayCustomFields', array( &$row) );
		}					

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		// any balances outstanding?
		if ($attendee && isset($attendee->params))
		{
			if (!isset($attendee->outstandingBalances))
			{
				// ensure plugins like virtuemart that don't have a universal notify mechanism are up to date!
				JPluginHelper::importPlugin("rsvppro");
				$dispatcher = & JDispatcher::getInstance();
				$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $attendee, $row));

				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
			}
			if (isset($attendee->outstandingBalances))
			{
				if ($attendee->outstandingBalances["feebalance"] > 0.000001)
				{
					$attendee->attendstate = 4;
				}
				else if ($attendee->outstandingBalances["feebalance"] <= 0 && $attendee->attendstate = 4)
				{
					$attendee->attendstate = 1;
				}
			}
		}

		if (isset($attendee->outstandingBalances) && isset($attendee->outstandingBalances['hasfees']) && $attendee->outstandingBalances['hasfees'])
		{
			$message = str_ireplace("{BALANCE}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feebalance']), $message);
			$message = str_ireplace("{FEESPAID}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feepaid']), $message);
			$message = str_ireplace("{TOTALFEES}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['totalfee']), $message);
		}

		$attendstate = array(JText::_('RSVP_NOT_ATTENDING'), JText::_('RSVP_ATTENDING'), JText::_('RSVP_MAYBE_ATTENDING'), JText::_('RSVP_PENDING_APPROVAL'), JText::_('RSVP_OUTSTANDING_BALANCE'),JText::_("JEV_WAITING_MESSAGE"));
		$confirmedstate = array(JText::_('RSVP_USER_NOT_CONFIRMED'), JText::_('RSVP_USER_CONFIRMED'));

		$user = JFactory::getUser();
		if (!is_null($attendee))
		{
			if (isset($attendee->username))
			{
				$message = str_replace("{USERNAME}", $attendee->username, $message);
			}
			else if (isset($attendee->user_id))
			{
				$user = JFactory::getUser($attendee->user_id);
				$message = str_replace("{USERNAME}", $user->username, $message);
			}
			else
			{
				$message = str_replace("{USERNAME}", $name, $message);
			}
			if (isset($attendee->email_address) && $attendee->email_address != "")
			{
				$message = str_replace("{EMAIL}", $attendee->email_address, $message);
			}
			else if (isset($attendee->user_id))
			{
				$user = JFactory::getUser($attendee->user_id);
				$message = str_replace("{EMAIL}", $user->email, $message);
			}
			else
			{
				$message = str_replace("{EMAIL}", $name, $message);
			}

			$attendeestate = $attendee->attendstate;
			if ($attendee->waiting) {
				$attendee->attendstate=5;
			}
			$message = str_replace("{ATTENDSTATE}", $attendstate[$attendee->attendstate], $message);
			$message = str_replace("{CONFIRMED}", $confirmedstate[$attendee->confirmed], $message);
		}
		else
		{
			$message = str_replace("{USERNAME}", $name, $message);
			$message = str_replace("{ATTENDSTATE}", "?", $message);
			$message = str_replace("{EMAIL}", "", $message);
			$message = str_replace("{CONFIRMED}", "?", $message);
		}
		$message = str_replace("{EVENT}", $row->title(), $message);

		$message = str_replace("{LOCATION}", $row->location(), $message);

		if ($row->created_by() > 0)
		{
			$creator = JFactory::getUser($row->created_by());
			$creator = $creator->name;
		}
		else
		{
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=" . intval($row->ev_id()));
			$anonrow = @$db->loadObject();
			if ($anonrow)
			{
				$creator = $anonrow->name;
			}
			else
			{
				$creator = "unknown";
			}
		}

		$message = str_replace("{CREATOR}", $creator, $message);

		// this is needed for the repeat summary to work
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

		if (!$onWaitingList)
		{
			$message = str_replace("{WAITINGMESSAGE}",  JText::_("JEV_NOT_WAITING_MESSAGE"), $message);
		}
		else
		{
			// I can't do this since it would add this to the subject line too!
			if (!$issubject){
				if (strpos($message, "{WAITINGMESSAGE}") === false)
				{
					//$message .= "<br>" . JText::_("JEV_WAITING_MESSAGE");
				}
				else
				{
				$message = str_replace("{WAITINGMESSAGE}", JText::_("JEV_WAITING_MESSAGE"), $message);
				}
			}
		}

		$regex = "#{DATE}(.*?){/DATE}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}

		$regex = "#{TIME}(.*?){/TIME}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}


		$regex = "#{TRANSACTIONID}(.*?){/TRANSACTIONID}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2 && $transaction)
		{
			$message = preg_replace($regex, sprintf($matches [1], $transaction->transaction_id), $message);
		}

		$regex = "#{LINK}(.*?){/LINK}#s";
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();
			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

			if (isset($attendee) && isset($attendee->email_address) && $attendee->email_address != "" && $attendee->user_id == 0)
			{
				$emailaddress = $attendee->email_address;
			}
			else
			{
				$emailaddress = $this->getEmailAddress();
			}
			if ($this->params->get("attendemails", 0) && $emailaddress != "")
			{
				$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em=" . $code;
				$requirelogin = false;
			}

			if ($requirelogin)
			{
				$link .= "&login=1";
			}

			// do not SEF since root() and sef add the path!			
			$root = JURI::root();
			$link = $root . $link;

			if ($speciallink){
				$link = $speciallink;
			}
			
			$message = preg_replace($regex, "<a href='$link'>" . $matches [1] . "</a>", $message);
		}

		// New parameterised fields
		if ($rsvpdata->template != "" && !is_null($attendee))
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{

				$html = "<table><tr>";
				$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				$params = $params->renderToBasicArray();
				foreach ($params as $param)
				{
					$html .= '<th>' . JText::_($param ['label']) . '</th>';
				}

				$html .= '</tr>';

				$html .= '<tr>';

				$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";
				// in this scenario we don't need the rowspan!
				if ($user->id != $row->created_by() && !JEVHelper::isAdminUser($user) && !$this->params->get("showcf", 0))
				{
					$rowspan = "";
				}

				if (isset($attendee->params))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
				else
				{
					$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				}

				$params = $params->renderToBasicArray(null, $attendee);

				$message = preg_replace("/({[^}]*:label:)/U", '{fieldlabel:', $message);
				$message = preg_replace("/({[^}]*:field:)/U", '{field:', $message);

				foreach ($params as $param)
				{

					$value = $param["value"];
					$label = $param["label"];
					$fieldname = "name";

					// is this a name field?  If so then use this in preference to the user's profile name
					if (isset($param["isname"]) && $param["isname"])
					{
						if (is_array($value) && $value[0] != "")
						{
							$name = $value[0];
						}
						else if (!is_array($value) && $value != "")
						{
							$name = $value;
						}
					}

					$html .= '<td>' . $param ['value'] . '</td>';


					if ($label != "")
					{
						$message = preg_replace("/{fieldlabel:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}' . $label . '$2', $message);
					}
					else
					{
						$message = preg_replace("/{fieldlabel:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
					}
					if ($value != "")
					{
						if (is_array($value))
						{
							// guest only fields should not have a comma at the start
							if ($param["peruser"] == 2 && count($value) >= 2)
							{
								array_shift($value);
							}
							$value = implode(", ", $value);
						}
						$message = preg_replace("/{field:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}' . $value . '$2', $message);
					}
					else
					{
						$message = preg_replace("/{field:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
					}

					// total fees etc. are parameterised differently and so we need to check the fieldname field too!
					if (isset($param["fieldname"]) && $param["fieldname"] != $param["name"])
					{
						$fieldname = "fieldname";
						if ($label != "")
						{
							$message = preg_replace("/{fieldlabel:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}' . $label . '$2', $message);
						}
						else
						{
							$message = preg_replace("/{fieldlabel:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
						}
						if ($value != "")
						{
							if (is_array($value))
							{
								// guest only fields should not have a comma at the start
								if ($param["peruser"] == 2 && count($value) >= 2)
								{
									array_shift($value);
								}
								$value = implode(", ", $value);
							}
							// There was a server that didn't match with preg_replace !!!
							$message = str_replace("{field:#" . $param[$fieldname] . "#}", $value, $message);
							$message = preg_replace("/{field:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}' . $value . '$2', $message);
						}
						else
						{
							$message = preg_replace("/{field:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
						}
					}
				}

				$html .= '</tr><table>';

				$message = str_replace("{CUSTOM}", $html, $message);
			}
		}

		// do the name LATE so we can change the name field value from the parameters.
		$message = str_replace("{NAME}", $name, $message);

		if ($transaction)
		{
			$regex = "#{TIMEPAID}(.*?){/TIMEPAID}#s";
			$matches = array();
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				jimport('joomla.utilities.date');
				$date = new JevDate("+0 second");
				$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
			}

			//{AMOUNTPAID}
			// The plugin should not need to be loaded since its only ever used if there is a valid transaction in which case we have a real template to fall back on!
			$plugin = JPluginHelper::getPlugin("rsvppro", "manual");
			if (!$plugin)
			{
				$plugin = JPluginHelper::getPlugin("rsvppro", "paypalipn");
				if (!$plugin)
				{
					$plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
				}
			}
			$params = new JRegistry($plugin ? $plugin->params : null);
			$comparams = JComponentHelper::getParams("com_rsvppro");

			$currency = $comparams->get("Defaultcurrency", "USD");

			if (isset($rsvpdata->template) && is_numeric($rsvpdata->template))
			{
				$db = JFactory::getDBO();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));
				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
				}
				else
				{
					$templateParams = $params;
				}
			}
			else
			{
				$templateParams = $params;
			}

			$message = str_replace("{AMOUNTPAID}", RsvpHelper::phpNewMoneyFormat($transaction->amount, $templateParams), $message);
		}

		if (false)
		{
			echo "Itemid = " . JRequest::getVar("Itemid", "not set");
			echo "<br/>";
			var_dump($row);
			exit();
		}

		include_once(JPATH_SITE . "/components/com_jevents/views/default/helpers/defaultloadedfromtemplate.php");
		ob_start();
		DefaultLoadedFromTemplate(false, false, $row, 0, $message);
		$newmessage = ob_get_clean();
		if ($newmessage != "" && strpos($newmessage, "<script ")===false)
		{
			$message = $newmessage;
		}

		
		return $message;

	}

	public function analyseGuests(&$attendee, $rsvpdata, $event, $attendeeparams = false)
	{
		if (is_null($attendee) || empty($attendee))
		{
			return;
		}
		if (!isset($attendee->guestcount))
		{
			$attendee->guestcount = 1;
		}

		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
			{
				if (!$attendeeparams)
				{					
					if (isset($attendee->params))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $event);
						// get fees and balances to ensure we can reuse the cache from  renderToBasicArray
						$feesAndBalances = $params->outstandingBalance($attendee);						
					}
					else
					{
						// if we don't have an attendee we should not be here!
						return;
					}

					$countAttendeeAndGuests = 1;
					$paramsarray = $params->renderToBasicArray(null, $attendee);
				}
				else
				{
					$paramsarray = $attendeeparams;
					$countAttendeeAndGuests = 1;
				}
				if (count($paramsarray) > 0)
				{
					foreach ($paramsarray as $param)
					{
						if ($param['formonly'] || !isset($param['value']) || !isset($param['peruser']) || $param['peruser'] <= 0)
						{
							continue;
						}
						if (is_array($param['value']))
						{
							$countAttendeeAndGuests = count($param['value']) > $countAttendeeAndGuests ? count($param['value']) : $countAttendeeAndGuests;
						}
					}
				}

				if ($countAttendeeAndGuests != $attendee->guestcount)
				{
					$attendee->guestcount = $countAttendeeAndGuests;
					// Update the attendee record if we have the wrong guest count
					$db = JFactory::getDBO();
					$sql = "UPDATE #__jev_attendees set guestcount= " . $countAttendeeAndGuests . " WHERE id=" . $attendee->id;
					$db->setQuery($sql);
					$db->query();
				}
			}
		}

	}

	public function countAttendees($atdid = 0, $forcecount = false)
	{
		// only do this on jevents detail pages and any rsvp pro pages or if we want a forced count e.g. from payment status update
		if (!(JRequest::getCmd("option") == "com_rsvppro" || $forcecount || 
				(JRequest::getCmd("option") == "com_jevents" && (JRequest::getCmd("jevtask") == "icalrepeat.detail" || JRequest::getCmd("jevtask") == "icalevent.detail"))
				))
		{
			return;
		}
		JLoader::register('JevRsvpParameter', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrsvpparameter.php");

		$db = JFactory::getDBO();
		$dataModel = new JEventsDataModel();
		$queryModel = new JEventsDBModel($dataModel);

		$where = "";
		$where2 = "";
		if ($atdid != 0)
		{
			$where = " atd.id =" . intval($atdid) . " AND ";
			$where2 = "  WHERE at_id=" . intval($atdid);
		}

		// Now make sure the atdcount is in sync
		//$db->setQuery("SELECT count(DISTINCT atdees.id) as atcnt, atdees.rp_id, atdees.at_id  FROM #__jev_attendance as atd  LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id WHERE $where atdees.attendstate=1 and  atdees.confirmed=1 GROUP BY atdees.rp_id");
		// config option to allow unpaid/part paid attendees to count towards capacity is checked further down the page
		$db->setQuery("SELECT sum(guestcount) as atcnt, atdees.id, atdees.rp_id, atdees.at_id  FROM #__jev_attendance as atd  LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id WHERE $where (atdees.attendstate=1 OR  atdees.attendstate=4) and  atdees.confirmed=1 GROUP BY atdees.rp_id");
		$rows = $db->loadObjectList();

		$db->setQuery("DELETE FROM #__jev_attendeecount $where2");
		$db->query();

		$processedRepeats = array();

		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				if (intval($row->at_id) == 0)
					continue;

				// Now find any "includeintotalcapacity" fields
				$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
				$db->setQuery($sql);
				$rsvpdata = $db->loadObject();

				// Find the first repeat
				$vevent = $dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
				if (!$vevent)
					continue;
				if ($row->rp_id == 0)
				{
					$repeat = $vevent->getFirstRepeat();
				}
				else
				{
					list($year, $month, $day) = JEVHelper::getYMD();
					$repeatdata = $dataModel->getEventData(intval($row->rp_id), "icaldb", $year, $month, $day);
					if ($repeatdata && isset($repeatdata["row"]))
						$repeat = $repeatdata["row"];
					else
						continue;
				}

				// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
				$registry = & JRegistry::getInstance("jevents");
				$registry->set("rsvpdata", $rsvpdata);
				$registry->set("event", $repeat);

				// do we want to include unpaid users in capacity
				$db = JFactory::getDBO();
				$templateParams = false;
				if ($rsvpdata->template != "")
				{
					$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

					$templateParams = $db->loadObject();
					if ($templateParams)
					{
						$templateParams = json_decode($templateParams->params);
					}
				}
				if (!$templateParams || !isset($templateParams->unpaidcapacity) || $templateParams->unpaidcapacity == 0)
				{
					$where = " atd.id =" . intval($row->at_id) . " AND atdees.rp_id=" . intval($row->rp_id) . " AND ";
					$db->setQuery("SELECT sum(guestcount) as atcnt, atdees.id, atdees.rp_id, atdees.at_id FROM #__jev_attendance as atd LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id WHERE $where atdees.attendstate=1 and atdees.confirmed=1 GROUP BY atdees.rp_id");
					$sql = $db->getQuery()."<Br/>";

					// replace row details
					$row = $db->loadObject();
					if (!$row || is_null($row->id))
					{
						continue;
					}
				}

				if (in_array($row->rp_id, $processedRepeats))
					continue;
				$processedRepeats[] = $row->rp_id;

				// New parameterised fields
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if (is_int($xmlfile) || file_exists($xmlfile))
					{
						$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
						$params = $rsvpparams->renderToBasicArray();
						foreach ($params as $param)
						{
							if ((isset($param ["includeintotalcapacity"]) && $param ["includeintotalcapacity"] > 0) && (isset($param ["capacitycount"]) || $rsvpdata->capacity > 0))
							{
								// now get the capacity summary - this recalculates the whole thing so we know for certain the numbers for all attendees
								$atdee = false;
								$rsvpparams->calculateRowContributionsToCapacity($param, $param['type'], $atdee);

								$row->atcnt += intval($param ["capacitycount"]);

								if (isset($param ["reducevaluefortotalcapacity"]) && (intval($param ["capacitycount"]) > 0 || $rsvpdata->capacity > 0))
								{
									$row->atcnt -= $param ["reducetotalcapacity"];
								}
							}
						}
					}
				}

				$db->setQuery("INSERT INTO #__jev_attendeecount (at_id, rp_id, atdcount) VALUES ($row->at_id,$row->rp_id,$row->atcnt)");
				if (!$db->query())
				{
					//echo (string) $db->getQuery();				
					echo $db->getErrorMsg();
				}
			}
		}

		if (count($rows) > 0 && isset($rsvpdata))
		{
			$this->updateWaitingList($rsvpdata, $atdid);
		}

		// process coupons to make sure each one is not over used
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				if (intval($row->at_id) == 0)
					continue;

				static $atdcoupons = array();
				if (isset($atdcoupons[$row->at_id]))
				{
					continue;
				}
				$atdcoupons[$row->at_id] = 1;
				if ($row->at_id != $rsvpdata->id)
				{
					// Now find any "includeintotalcapacity" fields
					$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
					$db->setQuery($sql);
					$rsvpdata = $db->loadObject();
				}

				static $couponCodeUsages = array();
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if (is_int($xmlfile) || file_exists($xmlfile))
					{
						$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, null);
						$params = $rsvpparams->renderToBasicArray();
						foreach ($params as $param)
						{
							// record even if no max usage is set (in case template implements this later)
							if ($param["type"] == "jevrcoupon")
							{
								$db = JFactory::getDbo();
								$sql = "SELECT id, attendstate, params FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and params like ('%\"" . $param["name"] . "\"%')";
								//$sql .=  " AND attendstate=1";
								$db->setQuery($sql);
								$attendees = $db->loadObjectList();
								foreach ($attendees as $atdee)
								{
									$couponfieldname = $param["name"];
									$atdeeparams = json_decode($atdee->params);

									if ($atdeeparams->$couponfieldname != "")
									{
										if (!isset($couponCodeUsages[$couponfieldname]))
										{
											$couponCodeUsages[$couponfieldname] = array();
										}
										if (!isset($couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)]))
										{
											$couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)] = 0;
										}
										$couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)]++;
									}
								}

								if (count($couponCodeUsages) > 0)
								{
									$db = JFactory::getDbo();
									$sql = "REPLACE INTO #__jev_rsvp_couponusage SET atd_id=$rsvpdata->id, params=" . $db->quote(json_encode($couponCodeUsages));
									$db->setQuery($sql);
									$db->query();
									echo $db->getErrorMsg();
								}
							}
						}
					}
				}
				if (count($couponCodeUsages) > 0)
				{
					$db = JFactory::getDbo();
					$sql = "CREATE TABLE SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
					$db->setQuery($sql);
				}
			}
		}

	}

	public function updateWaitingList($rsvpdata = null, $atdid = 0)
	{
		$atdid = intval($atdid);
		$db = JFactory::getDBO();

		static $params;
		if (!isset($params))
		{
			$params = JComponentHelper::getParams("com_rsvppro");
		}

		if ($atdid == 0)
		{
			// Run over all attendance records
			$db->setQuery("SELECT atd.id FROM #__jev_attendance as atd");
			$rows = $db->loadObjectList();

			foreach ($rows as $row)
			{
				if (intval($row->id) == 0)
					continue;
				$this->updateWaitingList($rsvpdata, $row->id);
			}
		}
		else if ($atdid > 0)
		{
			// Now make sure the atdcount is in sync - NB only update status of CONFIRMED attendees!
			$db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
			LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
			LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
			WHERE atd.id =" . intval($atdid) . " AND atdees.waiting=1 AND atdees.confirmed=1 AND atdees.attendstate=1 ORDER BY atdees.id asc");
			$rows = $db->loadObjectList();
			// may not have any attendees so get a null here
			if (!$rows)
				return;

			$updatedCount = 0;
			foreach ($rows as $row)
			{
				if ($row->atdcount - count($rows) + $updatedCount >= $row->capacity)
					break;
				// TODO FIX THIS
				$db->setQuery("UPDATE #__jev_attendees SET waiting=0 where id=" . $row->atdee_id);
				if ($db->query())
				{
					$updatedCount++;

					// auto remind attendees
					if ($params->get("autoremind", 0) == 1)
					{
						if (is_null($rsvpdata))
						{
							if ($atid > 0)
							{
								$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
								$db->setQuery($sql);
								$rsvpdata = $db->loadObject();
							}
						}

						// always allow auto reminders for attendees if forced reminders enabled
						if ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0) == 1)
						{
							// create reminder
							// NB email address must be in the request object
							$user = JFactory::getUser($row->user_id);
							// create reminder
							$datamodel = new JEventsDataModel();
							if ($row->rp_id > 0)
							{
								$evt = $datamodel->queryModel->listEventsById($row->rp_id, 1, "icaldb");
							}
							else
							{
								$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
							}
							if ($evt)
							{
								$this->jevrreminders->remindUser($rsvpdata, $evt, $user, $row->email_address);
								$mainframe = JFactory::getApplication();
								$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
							}
						}
					}

					if ($params->get("notifyuser", 0)){
						$this->notifyWaitingUser($row);
					}
				}
				else
					echo $db->getErrorMsg();
			}
		}

	}

	public function getMessageTemplate($params, $rsvpdata, $msgkey)
	{

		$templateParams = false;
		if ($rsvpdata->template != "")
		{
			if (isset($rsvpdata->templateParams))
			{
				$templateParams = $rsvpdata->templateParams;
			}
			else
			{
				$db = JFactory::getDbo();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
					$rsvpdata->templateParams = $templateParams;
				}
				else
				{
					$templateParams = false;
				}
			}
			if ($templateParams && isset($templateParams->$msgkey) && $templateParams->$msgkey != "")
			{
				return $templateParams->$msgkey;
			}
		}
		return $params->get($msgkey);

	}

	public function getBCC($userid)
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
			/*
			  if (JFactory::getUser()->id == 79)
			  {
			  JFactory::getApplication()->enqueueMessage("get BCC query = " . $db->getQuery() .  " : " . $bcc);
			  }
			 * 
			 */
		}
		return $bcc;

	}

	public function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc = null, $attachment = null, $replyto = null, $replytoname = null)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$from = $params->get("overridesenderemail", $from);
		$fromname = $params->get("overridesendername", $fromname);
		if ($bcc == "")
		{
			$bcc = null;
		}
		// send message to bcc address only
		if ($this->params->get("notifycreator", 0) == 4 && $bcc != null)
		{
			$recipient = $bcc;
			$bcc = null;
		}

		/*
		  if (JFactory::getUser()->id == 79)
		  {
		  if ($bcc)
		  {
		  JFactory::getApplication()->enqueueMessage("Sending message from $from to $recipient with bcc to $bcc");
		  }
		  else
		  {
		  JFactory::getApplication()->enqueueMessage("Sending message from $from to $recipient with NO bcc ");
		  }
		  }
		 * 
		 */
		$mail = JFactory::getMailer();
		return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);

	}

	static public function isVisibleStatic($node, $attendee, $guest, $nodes)
	{
		// always return false if dependent node is for guests and we have guest 0 = prime attendee
		if (is_object($node->attribute("peruser")) || is_object($guest)){
			return false;
		}
		if ($node->attribute("peruser") == 2 && $guest == 0)
		{
			return false;
		}

		$conditionnode = false;
		$cf = $node->attribute("cf");
		if ($cf == "")
		{
			return true;
		}
		if (!isset($attendee->params) || $attendee->params == "")
		{
			return true;
		}
		$attendeefields = json_decode($attendee->params);
		if (!isset($attendeefields->$cf))
		{
			return true;
		}

		// search for field on which this node is conditioned
		foreach ($nodes as $cnode)
		{
			if ($cnode->fieldname == $cf)
			{
				$conditionnode = $cnode;
				break;
			}
		}
		if (!$conditionnode)
		{
			return true;
		}

		$cfieldvalue = $attendeefields->$cf;
		// global condition
		if ($conditionnode->attribute("peruser") == 0)
		{
			// condition is visible then use the guest count
			if ($cfieldvalue == $node->attribute("cfvfv"))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		// individual or guest condition
		else
		{
			if ($node->attribute("peruser") > 0)
			{
				// check the index
				if ((is_array($cfieldvalue) && array_key_exists($guest, $cfieldvalue) && $cfieldvalue[$guest] == $node->attribute("cfvfv") )
						|| (is_object($cfieldvalue) && isset($cfieldvalue->$guest) && $cfieldvalue->$guest == $node->attribute("cfvfv")))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				// you can't have an individual condition field driving a group value!
				return false;
			}
		}

	}

}
