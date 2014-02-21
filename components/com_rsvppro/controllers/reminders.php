<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

include_once(RSVP_ADMINPATH . "/controllers/reminders.php");

class FrontRemindersController extends AdminRemindersController
{

	private $params;
	private $jomsocial = false;

	function __construct($config = array())
	{
		parent::__construct($config);

		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);

		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE . '/components/com_community/community'))
		{
			if (JComponentHelper::isEnabled("com_community"))
			{
				$this->jomsocial = true;
			}
		}

		$this->params = JComponentHelper::getParams("com_rsvppro");

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/reminderhelper.php");
		$this->helper = new RsvpReminderHelper($this->params);

	}

	public function recordReminder()
	{
		$user = JFactory::getUser();
		if ($user->id == 0 && !$this->params->get("remindemails", 0))
		{
			return false;
		}

		$db = & JFactory::getDBO();
		$eventid = JRequest::getInt("eventid", -1);
		$rp_id = JRequest::getInt("rp_id", -1);
		$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $eventid;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
			return false;

		$datamodel = new JEventsDataModel();
		if ($rsvpdata->remindallrepeats==0) {
			$row = $datamodel->queryModel->listEventsById($rp_id, 0, "icaldb");			
		}
		else {
			$row = $datamodel->queryModel->getEventById($eventid, 0, "icaldb");
		}

		$row->rsvpdata = $rsvpdata;


		if ($jsondata = JRequest::getVar("json", false))
		{
			$json = true;
			$jsondata = json_decode($jsondata);
			$jevremind_hidden = 0;
			// Create JSON data structure
			$data = new stdClass();
			$data->error = 0;
			$data->message = "";
			$jevremindemail = "";
			
			if (isset($jsondata->formdata))
			{
				$jevremind_hidden = $jsondata->formdata->jevremind_hidden;
				$jevremind = isset($jsondata->formdata->jevremind) ? $jsondata->formdata->jevremind : 0;
				$jevremindemail = isset($jsondata->formdata->jevremindemail) ? $jsondata->formdata->jevremindemail : '';
			}
		}
		else
		{
			$json = false;
			$jevremind_hidden = JRequest::getInt("jevremind_hidden", 0);
			$jevremind = JRequest::getInt("jevremind", 0);
			$jevremindemail = JRequest::getString("jevremindemail");
		}
		if ($jevremind_hidden)
		{
			if (!$jevremind)
			{
				$emailaddress = $this->getEmailAddress($jsondata);
				// if anon user and email attendance is allowed then find accordingly
				if ($user->id == 0 && $this->params->get("remindemails", 0))
				{
					// Make sure can only cancel their own email address!!!
					if ($emailaddress == "" || $emailaddress != trim(strtolower($jevremindemail)))
					{
						return false;
					}
				}

				$this->helper->unremindUser($rsvpdata, $row, $user, $emailaddress);

				if ($json)
				{
					$data->message = JText::_("JEV_REMINDER_CANCELLED", true);
				}
				else
				{
					$mainframe = JFactory::getApplication();
					$Itemid = JRequest::getInt("Itemid");
					list($year, $month, $day) = JEVHelper::getYMD();
					$link = $row->viewDetailLink($year, $month, $day, true, $Itemid);
					$mainframe->redirect($link, JText::_("JEV_REMINDER_CANCELLED"));
				}
			}
			else if ($jevremind)
			{
				$mainframe = JFactory::getApplication();
				$emailaddress = $jevremindemail;
				if ($link = $this->helper->remindUser($rsvpdata, $row, $user,$emailaddress))
				{

					if ($user->id == 0 && $this->params->get("remindemails", 0))
					{
						if ($json)
						{
							$data->message = JText::sprintf("JEV_REMINDER_CONFIRMED2", $emailaddress, true);
						}
						else
						{
							$mainframe->redirect($link, JText::sprintf("JEV_REMINDER_CONFIRMED2", $emailaddress));
						}
					}
					else
					{
						if ($json)
						{
							$data->message = JText::_("JEV_REMINDER_CONFIRMED", true);
						}
						else
						{
							$mainframe->redirect($link, JText::_("JEV_REMINDER_CONFIRMED"));
						}
					}
				}
			}
		}

		if ($json){
			@ob_end_clean();
			echo json_encode($data);			
			exit();
		}
		return true;

	}

	private function getEmailAddress($jsondata = false)
	{
		$emailaddress = "";
		if ($this->params->get("attendemails", 0))
		{
			if ($jsondata && isset($jsondata->formdata->em)){
				$em = $jsondata->formdata->em;
			}
			else {
				$em = JRequest::getString("em", "");
			}
			if ($em != "")
			{
				$emd = base64_decode($em);
				if (strpos($emd, ":") > 0)
				{
					list($emailaddress, $code) = explode(":", $emd);
					if ($em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress)))
					{
						$emailaddress = "";
					}
				}
			}
		}
		return $emailaddress;

	}

}