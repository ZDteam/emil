<?php

/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */
// no direct access
defined('_JEXEC') or die('Restricted Access');

class JevRsvpAttendees
{

	public $params;
	public $rsvpdata;
	public $jomsocial = false;
	public $view;

	public function __construct($params, $jomsocial, &$rsvpdata)
	{
		$this->params = $params;
		$this->jomsocial = $jomsocial;
		$this->rsvpdata = $rsvpdata;
		$this->canCancel = true;

		jimport('joomla.application.component.view');

		$theme = JEV_CommonFunctions::getJEventsViewName();

		if (JVersion::isCompatible("1.6.0"))
		{
			$this->_basepath = JPATH_SITE . '/plugins/jevents/jevrsvppro/rsvppro/';
		}
		else
		{
			$this->_basepath = JPATH_SITE . '/plugins/jevents/rsvppro/';
		}
		$this->view = new JViewLegacy(array('base_path' => $this->_basepath,
					"template_path" => $this->_basepath . "tmpl/default"
					, "name" => $theme
				));

		$this->view->addTemplatePath($this->_basepath . "tmpl/" . $theme);

		$this->view->addTemplatePath($this->_basepath . "tmpl/" . $theme);
		$this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . "default");
		$this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . $theme);

		$this->view->setLayout("invites");

		$this->view->assign("jomsocial", $this->jomsocial);
		$this->view->assignRef("params", $this->params);

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($this->params);

	}

	public function setView(&$view)
	{
		$this->view = $view;

	}

	public function fetchAttendanceForm($row, $rsvpdata, $attending, $emailaddress)
	{
		$user = JFactory::getUser();

		$rsvp_attendanceform = "";

		// Is this a blocked overlaps sesssion - if so need to make sure the attendee is not already attending an overlapping session
		/*
		  $rsvp_attendanceform =  $this->view->loadTemplate("overlappingsession");
		  if ($rsvp_attendanceform != "") {
		  return $rsvp_attendanceform;
		  }
		 */

		// can this user cancel or amend his registration
		$canamend = true;
		if (!$rsvpdata->allowcancellation && !$rsvpdata->allowchanges && $attending)
		{
			$canamend = false;
			// BUT if pending we can made a change
			if ($this->params->get("allowunconfirmedpendingunpaidchanges", 1) && $this->attendee->attendstate == 3)
			{
				$canamend = true;
			}
			// if fees are due but nothing paid yet then allow changes too 
			if ($this->params->get("allowunconfirmedpendingunpaidchanges", 1) && isset($this->attendee->outstandingBalances)
					&& $this->attendee->outstandingBalances['hasfees'] && $this->attendee->outstandingBalances['feepaid'] == 0 && $this->attendee->outstandingBalances['totalfee'] > 0)
			{
				$canamend = true;
			}
		}

		if (!$canamend)
		{
			// NO
			// I need the attendance form if I'm administering and attending the event otherwise I can't cancel attendees!
			if ($user->id == $row->created_by() || JEVHelper::isAdminUser($user))
			{
				$rsvp_attendanceform .= $this->attendanceForm($row, $rsvpdata, $attending, $emailaddress);
			}
			else
			{
				$rsvp_attendanceform .= $this->view->loadTemplate("emptyattendanceform");
				$rsvp_attendanceform .= $this->isAttendingInfo($row, $rsvpdata, $attending, $emailaddress);
			}
		}
		else
		{
			// otherwise check the date against the cancelation close
			if (!$attending)
			{
				$rsvp_attendanceform = $this->attendanceForm($row, $rsvpdata, $attending, $emailaddress);
			}
			else if ($this->canCancel($rsvpdata, $row))
			{
				if ($rsvpdata->allowcancellation && !$rsvpdata->allowchanges)
				{
					$rsvp_attendanceform = $this->cancellationForm($row, $rsvpdata, $attending, $emailaddress);
				}
				else if (!$rsvpdata->allowcancellation && $rsvpdata->allowchanges)
				{
					$rsvp_attendanceform = $this->changesForm($row, $rsvpdata, $attending, $emailaddress);
				}
				else
				{
					$rsvp_attendanceform = $this->attendanceForm($row, $rsvpdata, $attending, $emailaddress);
				}
			}
			// $this->canCancel is set by canCancel method
			else if ((!$this->canCancel || !$rsvpdata->allowcancellation) && $rsvpdata->allowchanges)
			{
				$rsvp_attendanceform = $this->changesForm($row, $rsvpdata, $attending, $emailaddress);
			}
			// if cancellation is allowed but is no longer possible
			else if ($attending && $rsvpdata->allowcancellation && !$this->canCancel)
			{

				$rsvp_attendanceform = $this->cancellationExpiredForm($row, $rsvpdata, $attending, $emailaddress);
				//SEE TOPIC http://www.jevents.net/forum/viewtopic.php?f=24&t=16686&p=85996#p85996
				// include a can no longer cancel output!
			}
		}
		return $rsvp_attendanceform;

	}

	public function attendanceForm($row, $rsvpdata, $attending, $emailaddress)
	{
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$html = "";
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return $html;
		}

		// if only show registration form to invitees then check if this user is invited
		// with strict check on whether super admin is invited
		if ($rsvpdata->allowregistration == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, false, $emailaddress))
		{
			return $html;
		}

		// Make sure waiting list is up to date first of all
		if ($rsvpdata && $rsvpdata->id > 0)
			$this->updateWaitingList($rsvpdata, $rsvpdata->id);

		$this->view->assignRef("attendee", $this->attendee);
		return $this->view->loadTemplate("attendanceform");

	}

	public function isAttendingInfo($row, $rsvpdata, $attending, $emailaddress)
	{
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$html = "";
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return $html;
		}

		// if only show registration form to invitees then check if this user is invited
		// with strict check on whether super admin is invited
		if ($rsvpdata->allowregistration == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, false, $emailaddress))
		{
			return $html;
		}

		// Make sure waiting list is up to date first of all
		if ($rsvpdata && $rsvpdata->id > 0)
			$this->updateWaitingList($rsvpdata, $rsvpdata->id);

		$this->view->assignRef("attendee", $this->attendee);
		return $this->view->loadTemplate("youareattending");

	}

	public function cancellationForm($row, $rsvpdata, $attending, $emailaddress)
	{
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$html = "";
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return $html;
		}

		// if only show registration form to invitees then check if this user is invited
		// with strict check on whether super admin is invited
		if ($rsvpdata->allowregistration == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, false, $emailaddress))
		{
			return $html;
		}

		// Make sure waiting list is up to date first of all
		if ($rsvpdata && $rsvpdata->id > 0)
			$this->updateWaitingList($rsvpdata, $rsvpdata->id);

		$this->view->assignRef("attendee", $this->attendee);
		return $this->view->loadTemplate("cancelform");

	}

	public function cancellationExpiredForm($row, $rsvpdata, $attending, $emailaddress)
	{
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$html = "";
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return $html;
		}

		// if only show registration form to invitees then check if this user is invited
		// with strict check on whether super admin is invited
		if ($rsvpdata->allowregistration == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, false, $emailaddress))
		{
			return $html;
		}

		// Make sure waiting list is up to date first of all
		if ($rsvpdata && $rsvpdata->id > 0)
			$this->updateWaitingList($rsvpdata, $rsvpdata->id);

		$this->view->assignRef("attendee", $this->attendee);
		$this->view->assign("canCancel", $this->canCancel);
		return $this->view->loadTemplate("cancelexpiredform");

	}

	public function changesForm($row, $rsvpdata, $attending, $emailaddress)
	{
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$html = "";
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return $html;
		}

		// if only show registration form to invitees then check if this user is invited
		// with strict check on whether super admin is invited
		if ($rsvpdata->allowregistration == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, false, $emailaddress))
		{
			return $html;
		}

		// Make sure waiting list is up to date first of all
		if ($rsvpdata && $rsvpdata->id > 0)
			$this->updateWaitingList($rsvpdata, $rsvpdata->id);

		$this->view->assignRef("attendee", $this->attendee);
		return $this->view->loadTemplate("changeform");

	}

	public function showAttendees(&$row, $rsvpdata)
	{

		$html = "";
		$row->attendeecount = 0;
		
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');
		// if attendance tracking not enabled then skip this
		if (!$rsvpdata->allowregistration)
			return $html;

		// Usually want the current count
		$db = JFactory::getDBO();
		$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $rsvpdata->id;
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		// convert to integer in case no entry (i.e. zero confirmed attendees)
		$row->attendeecount = $attendeecount = intval($db->loadResult());
		
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("showtoanon", 0))
		{
			return $html;
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		// only list attendees if appropriate i.e. showattendees or event creator/super admin AND when there aren't too many of them
		if (($rsvpdata->showattendees || $user->id == $row->created_by() || JEVHelper::isAdminUser($user)) && $attendeecount < 100)
		{

			$emailaddress = $this->getEmailAddress();

			// if only show attendees to invitees then check if this user is invited
			if ($rsvpdata->showattendees == 2 && !$this->jevrinvitees->isInvitee($row, $rsvpdata, $emailaddress))
			{
				return $html;
			}

			// record attendee count in $row
			$row->attendeecount = $attendeecount;

			$sql = "SELECT u.name, u.username, a.* FROM #__jev_attendees as a LEFT JOIN #__users as u on u.id=a.user_id WHERE a.at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allrepeats)
			{
				$sql .= " and a.rp_id=" . $row->rp_id();
			}
			// if no maybes or pendings then only show actual attendees
			/*
			  if (!$this->params->get("allowmaybe", 0) && !$this->params->get("allowpending", 0)) {
			  $sql .= " and a.attendstate=1";
			  }
			 */
			if (!$this->params->get("allowmaybe", 0))
			{
				$sql .= " and a.attendstate!=2";
			}
			if (!$this->params->get("allowpending", 0))
			{
				$sql .= " and a.attendstate!=3";
			}
			if ($user->id != $row->created_by() && !JEVHelper::isAdminUser($user))
			{
				if (!$this->params->get("shownonattendees", 0))
				{
					$sql .= " and a.attendstate!=0";
				}
				if (!$this->params->get("showunpaidattendees", 0))
				{
					$sql .= " and a.attendstate!=4";
				}
			}
			$sql .= " ORDER BY a.confirmed DESC, a.created asc";

			$db->setQuery($sql);
			$attendees = $db->loadObjectList();

			if (is_array($attendees))
				foreach ($attendees as &$attendee)
				{
					if ($attendee)
					{
						$attendee->guestcount = 1;
					}

					// New parameterised fields may have a name field that we should use
					if ($rsvpdata->template != "")
					{
						$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
						if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
						{
							static $rsvpParameters;
							if (!isset($rsvpParameters))
							{
								$rsvpParameters = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
							}
							if (isset($attendee->params))
							{
								$params = clone $rsvpParameters;
								// inject the attendee parameter data into the copy!
								$params->loadData($attendee->params, $rsvpdata, $row);
								//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
							}
							else
							{
								$params = clone $rsvpParameters;
								//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
							}
//$this->checktime(true);					
							$params = $params->renderToBasicArray( 'xmlfile', $attendee);
//$this->checktime(false , "after renderToBasicArray");			
							// Analyse Guests now to dave repeated calls to renderToBasicArray
							$this->analyseGuests($attendee, $rsvpdata, $row, $params);
							foreach ($params as $param)
							{
								// is this a name field?  If so then use this in preference to the user's profile name
								if (isset($param["isname"]) && $param["isname"])
								{
									$value = $param["value"];
									if (is_array($value) && isset($value[0]))
									{
										if ($value[0] != "")
										{
											$attendee->name = $value[0];
										}
									}
									else if (!is_array($value) && $value != "")
									{
										$attendee->name = $value;
									}
								}
							}
						}
					}
				}
			unset($attendee);

			$this->view->assignRef("attendeecount", $attendeecount);
			$this->view->assignRef("attendees", $attendees);
//$this->checktime(true);					
			$html = $this->view->loadTemplate("attendees");
//$this->checktime(false , "post loadTemplate");
		}
		// always include the link to allow for management
		if ($user->id == $row->created_by() || JEVHelper::isAdminUser($user))
		{
			$html .= $this->view->loadTemplate("attendeeslink");
		}
		return $html;

	}

	// TODO - move this to the controller 
	public function confirmAttendance($rsvpdata, $row)
	{
		if (!$this->params->get("requireconfirmation", 0))
			return;

		// must avoid recursive calling
		static $confirming = false;
		if ($confirming)
		{
			return true;
		}
		$confirming = true;

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		$jevattend_hidden = JRequest::getInt("jevattend_hidden", 0);

		$emailaddress = $this->getEmailAddress();
		if ($this->params->get("attendemails", 0) && $emailaddress != "")
		{
			// Make sure can only confirm their own email address!!!
			if (trim(strtolower(JRequest::getString("jevattend_email"))) && $emailaddress != trim(strtolower(JRequest::getString("jevattend_email"))))
			{
				return;
			}

			$db = JFactory::getDBO();

			$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and confirmed=1 and email_address=" . $db->Quote($emailaddress);
			$db->setQuery($sql);
			if ($attendee = $db->loadObject())
				return;

			// if an invitee then there may be no record yet
			$invitee = $this->jevrinvitees->fetchInviteeByEmail($row, $rsvpdata, $emailaddress, true);
			if ($invitee)
			{
				$sql = "INSERT INTO #__jev_attendees SET at_id=" . $rsvpdata->id . ", confirmed=1, email_address=" . $db->Quote($emailaddress);
				if (!$rsvpdata->allrepeats)
				{
					$sql .= ", rp_id=" . $row->rp_id();
				}
			}
			else
			{
				$sql = "UPDATE #__jev_attendees SET confirmed=1 WHERE at_id=" . $rsvpdata->id . " AND email_address=" . $db->Quote($emailaddress);
			}
			$db->setQuery($sql);
			$db->query();

			// Make sure the counts are in sync!
			$this->countAttendees($rsvpdata->id);

			$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and confirmed=1 and email_address=" . $db->Quote($emailaddress);
			$db->setQuery($sql);
			$attendee = $db->loadObject();

			$mainframe = JFactory::getApplication();
			$Itemid = JRequest::getInt("Itemid");
			list ( $year, $month, $day ) = JEVHelper::getYMD();
			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

			$emailaddress = $this->getEmailAddress();
			if ($this->params->get("attendemails", 0) && $emailaddress != "")
			{
				$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
				// use em2 - which is used for viewing !!!
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em2=" . $code;
				$requirelogin = false;
			}

			// Do not send notification if only sending cancelations!
			if ($this->params->get("notifycreator", 0) && $this->params->get("notifycreator", 0) != 3)
				$this->notifyCreator($rsvpdata, $row, $emailaddress, $emailaddress, $attendee, false, $attendee->waiting);
			$user = JFactory::getUser();
			if ($this->params->get("notifyuser", 0))
				$this->notifyUser($rsvpdata, $row, $user, $emailaddress, $emailaddress, $attendee, 'ack', $attendee->waiting);

			// auto remind attendees
			if ($this->params->get("autoremind", 0) == 1 && !$attendee->waiting && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
			{
				// create reminder
				// NB email address must be in the request object
				$user = JFactory::getUser(0);
				$this->helper->jevrreminders->remindUser($rsvpdata, $row, $user);
				$mainframe = JFactory::getApplication();
				$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
			}

			$this->postUpdateActions($rsvpdata, $row, $attendee, $attendee->waiting, $this->getRSVPParmeters($rsvpdata, $row));

			switch ($attendee->attendstate) {
				case 0:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED_NOT_ATTENDING"));
					break;
				case 1:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED2"));
					break;
				case 2:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED_MAYBE_ATTENDING"));
					break;
				case 3:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED_PENDING_APPROVAL"));
					break;
				case 4:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED_OUTSTANDING_BALANCE"));
					break;
				default:
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CONFIRMED2"));
					break;
			}
		}

	}

	private function canCancel($rsvpdata, & $row)
	{
		$this->canCancel = true;
		$user = JFactory::getUser();
		// I always show the attendance form fopr special users
		if ($user->id == $row->created_by() || JEVHelper::isAdminUser($user))
		{
			$this->canCancel = true;
			return true;
		}
		else
		{
			jimport('joomla.utilities.date');
			// Must use strtotime format for force JevDate to not just parse the date itself!!!
			$jnow = new JevDate("+1 second");
			$now = $jnow->toUnix();

			// We see if cancellations are still possible open
			// if attendance tracked for the event as a whole then must compare the time of the start of the event
			$cancelclose = $rsvpdata->cancelclose == "0000-00-00 00:00:00" ? $row->dtstart() : strtotime($rsvpdata->cancelclose);
			if ($rsvpdata->allrepeats)
			{
				if ($now > $cancelclose)
				{
					$this->canCancel = false;
					return false;
				}
				$this->canCancel = true;
				return true;
			} // otherwise the start of the repeat
			else
			{
				$eventstart = $row->dtstart();
				$repeatstart = $row->getUnixStartTime();
				$adjustedcancelclose = $cancelclose + ($repeatstart - $eventstart);
				if ($now > $adjustedcancelclose)
				{
					$this->canCancel = false;
					return false;
				}
				$this->canCancel = true;
				return true;
			}
		}

	}

	// This redirects calls to the helper class is possible
	public function __call($name, $arguments)
	{
		if (is_callable(array($this->helper, $name)))
		{
			return call_user_func_array(array($this->helper, $name), $arguments);
		}

	}

	public function calculateBalances(&$view, $rsvpdata, $row)
	{
		return $this->helper->calculateBalances($view, $rsvpdata, $row);

	}

	public function updateWaitingList(&$rsvpdata, $atdid = 0)
	{
		return $this->helper->updateWaitingList($rsvpdata, $atdid);

	}

	public function analyseGuests(&$attendee, $rsvpdata, $event, $attendeeparams = false)
	{
		return $this->helper->analyseGuests($attendee, $rsvpdata, $event, $attendeeparams);

	}

	// post confirmation actions in parameter fields e.g. signup for newsletters etc.
	protected function postUpdateActions($rsvpdata, $row, $attendee, $onWaitingList, $rsvpparams)
	{
		if ($rsvpparams)
		{
			return $rsvpparams->postUpdateActions($rsvpdata, $row, $attendee, $onWaitingList);
		}

	}

	protected function getRsvpDataParams($rsvpdata)
	{
		$params = "";
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = JRequest::getVar("params", array(), "post");
				$jregistry = new JRegistry ();
				$jregistry->loadArray($params);

				// checkboxes do not return any data which causes problems when we have guests!
				$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, null);
				$emptyarray = $rsvpparams->renderToBasicArray();
				foreach ($emptyarray as $key => $ea)
				{
					if (!isset($params[$key]))
					{
						$jregistry->set($key, "");
					}
				}

				// TODO VERY IMPORTANT - CALCULATE THE FEES AND BALANCE HERE - DON'T RELY ON JAVASCRIPT!!
				if ($jregistry->get("totalfee", -1) != -1)
				{
					$jregistry->set("totalfee", 0);
					$jregistry->set("feepaid", 7.00);
					$jregistry->set("feebalance", 0);
				}

				$params = $jregistry->toString();
			}
		}
		return $params;

	}

	protected function getRSVPParmeters($rsvpdata, $row)
	{
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				return $rsvpparams;
			}
		}
		return false;

	}

	private function checktime($start = true, $action = "action")
	{
		static $starttime;
		if ($start)
		{
			list($usec, $sec) = explode(" ", microtime());
			$starttime = (float) $usec + (float) $sec;
		}
		else
		{
			list ($usec, $sec) = explode(" ", microtime());
			$time_end = (float) $usec + (float) $sec;
			echo "$action = " . round($time_end - $starttime, 4) . "<br/>";
			$starttime = $time_end;
		}

	}

}