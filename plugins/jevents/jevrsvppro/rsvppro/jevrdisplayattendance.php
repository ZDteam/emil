<?php

/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('JevRsvpInvitees', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrinvitees.php");
JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
JLoader::register('JevRsvpAttendees', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrattendees.php");

// WARNING - REDIRECTS MUST NOT BE XHTML COMPLIANT LINKS


class JevRsvpDisplayAttendance
{

	private $params;
	private $jomsocial = false;
	private $cbuilder = false;
	private $groupjive = false;
	private $event = null;
	private $attendee = false;
	private $jevrinvitees;
	private $jevrreminders;
	private $jevrattendees;
	private $view;

	public function __construct($params)
	{
		$this->params = $params;

		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE.'/components/com_community/community.php')){
			if (JComponentHelper::isEnabled("com_community")) {
				$this->jomsocial = true;
			}
		}
		if (JFile::exists(JPATH_SITE.'/components/com_comprofiler/comprofiler.php')){
			if (JComponentHelper::isEnabled("com_comprofiler")) {
				$this->cbuilder = true;
				if (JFile::exists(JPATH_SITE."/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.php")){
					$this->groupjive = true;
				}								
			}
		}

		jimport('joomla.application.component.view');

		$theme = JEV_CommonFunctions::getJEventsViewName ();
		$this->_basepath = JPATH_SITE . "/plugins/jevents/jevrsvppro/rsvppro/";
		$this->view = new JViewLegacy(array('base_path' => $this->_basepath, "template_path" => $this->_basepath . "tmpl/default", "name" => $theme));

		$this->view->addTemplatePath($this->_basepath . "tmpl/" . $theme);
		$this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication ()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . "default");
		$this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication ()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . $theme);

		$this->view->setLayout("attendee");

		$this->view->assign("jomsocial", $this->jomsocial);
		$this->view->assignRef("params", $this->params);
	}

	public function displayCustomFields(&$row)
	{
		
		$this->event = $row;

		$this->view->assignRef("row", $row);

		// I will need the correct start date for the event - dtstart is not reliable enough so add my own value here
		$this->fixDtStart($row);

		$db = & JFactory::getDBO ();
		$html = '';
		$eventid = intval($row->ev_id());
		if ($eventid > 0)
		{
			// block recursive calls
			static $processedAlready = array();
			if (in_array($eventid, $processedAlready)){
				return;				
			}
			$processedAlready[]=$eventid;
			
			$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $eventid;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			$row->rsvpdata = $rsvpdata;
			if ($rsvpdata)
			{

				if (JRequest::getString("jevtask") == "icalrepeat.detail" || JRequest::getString("jevtask") == "icalevent.detail")
				{
				// Make sure jQuery is in no conflict mode
				?>
<script type="text/javascript">	
if ($(".something_really_stupid_for_rsvppro_to_test") && typeof(Query)!='undefined' && jQuery) {
	alert('<?php echo str_replace("'","",JText::_("JEV_JQUERY_WARNING"));?>');
}
</script>
				<?php
				}

				// Store details in registry - will need them for currency formatting etc.
				$registry = & JRegistry::getInstance("jevents");
				$registry->set("rsvpdata", $rsvpdata);
				$registry->set("event", $row);

				$this->view->assignRef("rsvpdata", $rsvpdata);

				$user = JFactory::getUser ();
				if (!$this->checkAccess($user, $rsvpdata, $row)){
					$row->attendancedata = isset($rsvpdata->sessionaccessmessage)?$rsvpdata->sessionaccessmessage : "";
					return $row->attendancedata;
				}

				// if params allow attendance tracking by email then skip this check of being logged in
				if (!$this->params->get("attendemails", 0) && $user->id == 0 && $rsvpdata->allowregistration)
				{
					$uri = JURI::getInstance ();
					$this->view->assign("uri", $uri);
					$html = $this->view->loadTemplate("login");
					$row->rsvp_loginform = $html;
					
					$row->rsvp_attendanceform = $html;
					// $html .= $row->rsvp_attendanceform;
					$row->attendancedata = $html;

					// if we can show attendee list to anyone then don't return yet
					if ($user->id == 0 && !$this->params->get("showtoanon", 0))
					{
						// reset the dtstart value
						$this->fixDtStart($row, true);						
						return $html;
					}
				}

				$this->jevrinvitees = new JevRsvpInvitees($this->params, $this->jomsocial, $this->cbuilder,  $this->groupjive);
				$this->jevrreminders = new JevRsvpReminders($this->params, $this->jomsocial);
				$this->jevrattendees = new JevRsvpAttendees($this->params, $this->jomsocial, $rsvpdata);
				$this->jevrattendees->setView($this->view);
				$this->jevrattendees->jevrinvitees = $this->jevrinvitees;

				$this->jevrinvitees->recordViewed($rsvpdata, $row);

				$this->jevrattendees->confirmAttendance($rsvpdata, $row);

				// Are we saving an attendance update
				//$this->recordAttendance($rsvpdata, $row);

				if (!$rsvpdata->allowregistration && !$rsvpdata->allowreminders && !$rsvpdata->invites)
				{
					$html .= $this->showstatus();
					$row->attendancedata = $html;
					// reset the dtstart value
					$this->fixDtStart($row, true);						
					return $html;
				}

				// NB can only invite if attendance is enabled
				//if (!$rsvpdata->allowregistration && !$rsvpdata->allowreminders) { $this->fixDtStart($row, true); return $this->showstatus();}

				$emailaddress = $this->jevrattendees->getEmailAddress("em2");
				if (!$emailaddress)
					$emailaddress = $this->jevrattendees->getEmailAddress("em");

				$this->view->assignRef("emailaddress", $emailaddress);

				if ($rsvpdata->allowregistration)
				{
					JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
					/*
					  $row->rsvp_setFieldParameters = $this->setFieldParameters($row,$rsvpdata);
					  $html .= $row->rsvp_setFieldParameters;
					 */

					// Any status of attendance
					$this->attendee = $this->jevrattendees->isAttending($rsvpdata, $row, $emailaddress, true);
					$attending = false;
					if ($this->attendee)
					{
						$attending = true;
					}
					$this->view->assignRef("attending", $attending);

					// if attending (i.e. attendstate = 1 or 4 then calculate balances
					if ($attending )
					{
						$this->jevrattendees->attendee = $this->attendee;
						$this->jevrattendees->calculateBalances($this->view, $rsvpdata, $row);
					}

					if (!isset($row->rsvp_attendanceform))
					{
						if (JRequest::getCmd("task")!="accounts.paymentpage"){
							$row->rsvp_attendanceform = $this->jevrattendees->fetchAttendanceForm($row, $rsvpdata, $attending, $emailaddress);
						}
						else {
							$row->rsvp_attendanceform = "";
						}
						
						$html .= $row->rsvp_attendanceform;
					}
				}

				if (JRequest::getCmd("task")!="accounts.paymentpage"){
					$row->rsvp_reminderform = $this->jevrreminders->reminderForm($row, $rsvpdata, $emailaddress);
					$html .= $row->rsvp_reminderform;

					$row->rsvp_messagarea = $this->showMessageArea($row, $rsvpdata);
					$html .= $row->rsvp_messagarea;

					$row->rsvp_fullattendees = $this->jevrattendees->showAttendees($row, $rsvpdata);
					$html .= $row->rsvp_fullattendees;

					$row->rsvp_createinvitations = $this->jevrinvitees->createInvitations($row, $rsvpdata);
					$html .= $row->rsvp_createinvitations;
				}
				else {
					$row->rsvp_reminderform = "";
					$row->rsvp_messagarea = "";
					$row->rsvp_fullattendees = "";
					$row->rsvp_createinvitations = "";
				}
			}
			$html .= $this->showstatus();
		}

		$row->attendancedata = $html;
		// reset the dtstart value
		$this->fixDtStart($row, true);						

		return $html;

	}

	public function displayCustomFieldsMultiRow(&$row, $rsvpdataArray = array())
	{

		$this->event = $row;

		$this->view->assignRef("row", $row);

		// I will need the correct start date for the event - dtstart is not reliable enough so add my own value here
		$this->fixDtStart($row);

		$db = & JFactory::getDBO ();
		$html = '';
		$eventid = intval($row->ev_id());
		if ($eventid > 0 && array_key_exists($eventid, $rsvpdataArray))
		{

			//$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $eventid;
			//$db->setQuery($sql);
			//$rsvpdata = $db->loadObject();
			//$row->rsvpdata = $rsvpdata;
			$row->rsvpdata = $rsvpdata = $rsvpdataArray[$eventid];
			if ($rsvpdata)
			{

				// Store details in registry - will need them for currency formatting etc.
				$registry = & JRegistry::getInstance("jevents");
				$registry->set("rsvpdata", $rsvpdata);
				$registry->set("event", $row);

				$this->view->assignRef("rsvpdata", $rsvpdata);

				$user = JFactory::getUser ();
				if (!$this->checkAccess($user, $rsvpdata, $row)){
					$row->attendancedata = isset($rsvpdata->sessionaccessmessage)?$rsvpdata->sessionaccessmessage : "";
					return $row->attendancedata;
				}

				$this->jevrinvitees = new JevRsvpInvitees($this->params, $this->jomsocial, $this->cbuilder,  $this->groupjive);
				$this->jevrreminders = new JevRsvpReminders($this->params, $this->jomsocial);
				$this->jevrattendees = new JevRsvpAttendees($this->params, $this->jomsocial, $rsvpdata);
				$this->jevrattendees->setView($this->view);
				$this->jevrattendees->jevrinvitees = $this->jevrinvitees;

				if ($rsvpdata->allowregistration)
				{

					$emailaddress = $this->jevrattendees->getEmailAddress("em2");
					if (!$emailaddress)
					{
						$emailaddress = $this->jevrattendees->getEmailAddress("em");
					}
					$this->attendee = $this->jevrattendees->isAttending($rsvpdata, $row, $emailaddress);
					$attending = false;
					if ($this->attendee)
					{
						$attending = true;
					}
					$row->attendee = $this->attendee;
					$this->view->assignRef("attending", $attending);
				}

				if ($this->params->get("showattendeesinlists", 0)){
					$row->rsvp_fullattendees = $this->jevrattendees->showAttendees($row, $rsvpdata);
					$html .= $row->rsvp_fullattendees;
				}
			}
		}

		$row->attendancedata = $html;
		// reset the dtstart value		
		$this->fixDtStart($row, true);

		return $html;

	}

	// These 3 functions are under development

	private function showMessageArea(&$row, $rsvpdata)
	{
		$html = "";
		$user = JFactory::getUser ();
		if ($user->id == 0)
			return $html;

		if (!$this->params->get("enablemessages", 0))
			return $html;

		if ($user->id != $row->created_by() &&  ! JEVHelper::isAdminUser($user))
			return $html;

		return "";

	}

	private function setFieldParameters($row, $rsvpdata)
	{
		$html = "";
		// special case for setting field parameters
		if (JEVHelper::canEditEvent($row) || JEVHelper::canPublishEvent($row))
		{
			$html = '<div class="cModule jevattendform"><h3><span>' . JText::_( 'JEV_SET_FIELD_PARAMETERS' ) . '</span></h3>';
			$html .= "xxx";
			$html .= "</div>";
		}
		return $html;

	}

	private function showstatus()
	{
		return "";
		$user = JFactory::getUser ();
		if ($this->params->get("showstatus", 0) && $user->id > 0)
		{
			JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
			JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');
			$html = '
	<div class="button2-left" id="jevstatusbutton"  style="margin-right:10px;">
		<div class="blank">
			<a href="#' . JText::_( 'JEV_SHOW_MY_STATUS' ) . '" onclick="showJevStatus();return false;"  title="' . JText::_( 'JEV_SHOW_MY_STATUS' ) . '"  style="padding:0px 5px;text-decoration:none">' . JText::_( 'JEV_SHOW_MY_STATUS' ) . '</a>
		</div>
	</div>
	<div id="jevstatus" style="display:none;clear:both;">Show Me</div>	
	';
			return $html;
		}
		return "";

	}

	private function fixDtStart(&$row, $reset = false) {
		if (is_callable(array($row, "fixDtstart"))){
			if ($reset){
				$row->dtstart($row->_olddtstart);
				$row->dtend($row->_olddtend);
				$row->_publish_up = $row->_oldpu;
				$row->_publish_down =$row->_oldpd;
			}
			else {
				$row->_olddtstart = $row->dtstart();
				$row->_olddtend = $row->dtend();
				$row->_oldpu = $row->publish_up();
				$row->_oldpd = $row->publish_down();				
				$this->doFixDtstart($row);
				$row->_fixed_dtstart = $row->dtstart();
				$row->_fixed_dtend = $row->dtend();				
			}
		}		
	}
	
	// Note that if the timezone gets changed after the event is created and before it is re-edited or compared to the registration date for close times etc
	// then it could be out.  This function fixes this
	function doFixDtstart(&$row){

		// must only ever do this once!
		if (isset($row->dtfixed) && $row->dtfixed) return;
		
		$row->dtfixed = 1;
		
		$db =& JFactory::getDBO();

		// Now get the first repeat since dtstart may have been set in a different timezeone and since it is a unixdate it would then be wrong
		if (strtolower($row->freq())=="none"){
			// no need to be this pedantic in RSVP Pro (more important when editing events) so skip this
			/*
			$repeat = $this->getFirstRepeat($row);
			$row->dtstart($repeat->getUnixStartTime());
			$row->dtend( $repeat->getUnixEndTime());
			 */
			return;
		}
		else {
			// get first repeat also checks if its an exception
			$repeat = $this->getFirstRepeat($row);
			// Is this repeat an exception?
			if (!isset($repeat->ex_id) || $repeat->ex_id==0){
				// set dtstart to match the first repeat
				$row->dtstart($repeat->getUnixStartTime());
				$row->dtend( $repeat->getUnixEndTime());
				return;
			}
			else {
				// This is the scenario where the first repeat is an exception so check to see if we need to be worried
				$jregistry	=& JRegistry::getInstance("jevents");
				// This is the server default timezone
				$jtimezone = $jregistry->get("jevents.timezone", false);
				if ($jtimezone){
					// This is the JEvents set timezone
					$timezone = date_default_timezone_get();
					// Only worry if the JEvents  set timezone is different to the server timezone
					if ($timezone != $jtimezone){
						// look for repeats that are not exceptions
						$repeat2 =  $this->getFirstRepeat($row,false);
						// if we have none then use the first repeat and give a warning
						if (!$repeat2){
							// set dtstart to match the first repeat
							$row->dtstart($repeat->getUnixStartTime());
							$row->dtend( $repeat->getUnixEndTime());
							
							//JFactory::getApplication()->enqueueMessage(JText::_('JEV_PLEASE_CHECK_START_AND_END_TIMES_FOR_THIS_EVENT'));
						}
						else {
							// Calculate the time adjustment (if any) then check against the non-exceptional repeat
							// Convert dtstart using system timezone to date
							date_default_timezone_set($jtimezone);
							$truestarttime = JevDate::strftime("%H:%M:%S",$row->dtstart());
							// if the system timezone version of dtstart is the same time as the first non-exceptional repeat
							// then we are safe to use this adjustment mechanism to dtstart.  We use the real "date" and convert
							// back into unix time using the  Jevents timezone
							if ($truestarttime == JevDate::strftime("%H:%M:%S",JevDate::mktime($repeat2->hup(),$repeat2->minup(),$repeat2->sup(), 0, 0, 0))){
								$truedtstart = JevDate::strftime("%Y-%m-%d %H:%M:%S",$row->dtstart());
								$truedtend = JevDate::strftime("%Y-%m-%d %H:%M:%S",$row->dtend());

								// switch timezone back to Jevents timezone
								date_default_timezone_set($timezone);
								$row->dtstart(JevDate::strtotime($truedtstart));
								$row->dtend(JevDate::strtotime($truedtend));
							}
							else {
								// In this scenario we have no idea what the time should be unfortunately
								
								//JFactory::getApplication()->enqueueMessage(JText::_('JEV_PLEASE_CHECK_START_AND_END_TIMES_FOR_THIS_EVENT'));

								// switch timezone back
								date_default_timezone_set($timezone);
							}


						}
					}
					else {
						// look for repeats that are not exceptions
						$repeat2 =  $this->getFirstRepeat($row,false);
						if (!$repeat2){
							// TODO should really try to find the original data!
							// set dtstart to match the first repeat 
							$row->dtstart($repeat->getUnixStartTime());
							$row->dtend( $repeat->getUnixEndTime());
						}
						else {
							// dtstart is from the event detail so we can use this
							$row->dtstart($repeat2->dtstart());
							$row->dtend( $repeat2->dtstart());
						}
					}
				}
				else {
					// We would not need to do this for event editing but we MUST handle first event as an exception
					// look for repeats that are not exceptions
					$repeat2 =  $this->getFirstRepeat($row,false);
					if (!$repeat2){
						// TODO should really try to find the original data!
						// set dtstart to match the first repeat 
						$row->dtstart($repeat->getUnixStartTime());
						$row->dtend( $repeat->getUnixEndTime());
					}
					else {
						// dtstart is from the event detail so we can use this
						$row->dtstart($repeat2->dtstart());
						$row->dtend( $repeat2->dtstart());
					}
				}

			}
		}

	}
	
	// Gets repeats for this event from databases
	private function getFirstRepeat($row, $allowexceptions=true){

		static $firstrepeats = array();
		$key = $row->ev_id().($allowexceptions?"ex":"");
		if (isset($firstrepeats[$key])){
			return $firstrepeats[$key];
		}
		$db =& JFactory::getDBO();
		$query = "SELECT ev.*, rpt.*, rr.*, det.*, exc.ex_id"
		. "\n , YEAR(rpt.startrepeat) as yup, MONTH(rpt.startrepeat ) as mup, DAYOFMONTH(rpt.startrepeat ) as dup"
		. "\n , YEAR(rpt.endrepeat  ) as ydn, MONTH(rpt.endrepeat   ) as mdn, DAYOFMONTH(rpt.endrepeat   ) as ddn"
		. "\n , HOUR(rpt.startrepeat) as hup, MINUTE(rpt.startrepeat ) as minup, SECOND(rpt.startrepeat ) as sup"
		. "\n , HOUR(rpt.endrepeat  ) as hdn, MINUTE(rpt.endrepeat   ) as mindn, SECOND(rpt.endrepeat   ) as sdn"
		. "\n FROM #__jevents_vevent as ev "
		. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id"
		. "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
		. "\n LEFT JOIN #__jevents_rrule as rr ON rr.eventid = ev.ev_id"
		 . "\n LEFT JOIN #__jevents_exception as exc ON exc.rp_id=rpt.rp_id"
		. "\n WHERE ev.ev_id = '".$row->ev_id()."' "
		. ((!$allowexceptions)?"\n AND exc.rp_id IS NULL":"")
		. "\n ORDER BY rpt.startrepeat asc LIMIT 1" ;

		$db->setQuery( $query );
		$rows = $db->loadObjectList();

		$repeat = null;
		// iCal agid uses GUID or UUID as identifier
		if( $rows ){
			$repeat = new jIcalEventRepeat($rows[0]);
			$repeat->ex_id = $rows[0]->ex_id;
		}
		$firstrepeats[$key] = $repeat;
		return $repeat;
	}

	private function checkAccess($user, $rsvpdata, $event)
	{
		if ($rsvpdata->sessionaccess>=0){
			$access = $rsvpdata->sessionaccess;
		}
		else {
			$access = $event->access();
		}
		if (JVersion::isCompatible("1.6.0"))
		{
			$access = explode(",", $access);
			return count(array_intersect($access, JEVHelper::getAid($user, 'array'))) > 0;
		}
		else
		{
			return $access <= $user->aid;
		}

	}
	
}