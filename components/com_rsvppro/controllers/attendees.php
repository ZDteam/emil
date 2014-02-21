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

include_once(RSVP_ADMINPATH . "/controllers/attendees.php");

/** 
 * Attendstate explanations
 * 0 = not attending
 * 1 = attending
 * 2 = maybe attending
 * 3 = attending but subject to approval i.e.pending
 * 4 = attending but has outstanding balance to pay
 */
class FrontAttendeesController extends AdminAttendeesController
{

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('record', 'recordAttendance');

		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);

		JLoader::register('JEventDate', JEV_PATH . "/libraries/jeventdate.php");

	/* 
	 * All done in parent constructor
		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE . '/components/com_community/community.php'))
		{
			if (JComponentHelper::isEnabled("com_community"))
			{
				$this->jomsocial = true;
			}
		}

		$this->params = JComponentHelper::getParams("com_rsvppro");

		JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
		$this->jevrreminders = new JevRsvpReminders($this->params, $this->jomsocial);

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($this->params);
	 */

	}

	// This redirects calls to the helper class is possible
	public function __call($name,  $arguments)
	{
		if (isset($this->event) && !isset($this->helper->event)){
			$this->helper->event = $this->event;
		}
		if (is_callable(array($this->helper, $name)))
		{
			return call_user_func_array(array($this->helper, $name),  $arguments);
		}

	}

	function overview()
	{
		$user = JFactory::getUser();
		$params = JComponentHelper::getParams("com_rsvppro");
		if ($user->id == 0 && !$params->get("showtoanon",0))
			return;


		if (! JEVHelper::isAdminUser($user) )
		{
			$atd_id = JRequest::getVar("atd_id", "post", "array");
			if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
			{
				JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
			}

			list($atd_id, $rp_id) = explode("|", $atd_id[0]);
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atd_id);
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			if (!$rsvpdata)
			{
				JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
			}

			if (!$rsvpdata->showattendees) 
			{
				$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
				if (!$vevent || $vevent->created_by() != intval($user->id))
				{
					return "";
				}
			}
		}


		return parent::overview();

	}

	function export()
	{
		$user = JFactory::getUser();
		if ($user->id == 0)
			return;

		if (true || ! JEVHelper::isAdminUser($user))
		{
			$atd_id = JRequest::getVar("atd_id", "post", "array");
			if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
			{
				JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
			}

			list($atd_id, $rp_id) = explode("|", $atd_id[0]);
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atd_id);
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			if (!$rsvpdata)
			{
				JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
			}

			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
			if (JEVHelper::canEditEvent($vevent)){
				parent::export();
			}
		}

	}

	function confirm()
	{
		return parent::confirm();
	}

	function remindconfirm()
	{
		return parent::remindconfirm();

	}
	
	function approve()
	{
		return parent::approve();

	}

	function delete()
	{
		return parent::delete();
	}

	function edit()
	{
		return parent::edit();
	}

	function save()
	{
		return parent::save();

	}

	function ticket()
	{
		$attendee = JRequest::getInt("attendee");

		$user = JFactory::getUser();

		// Check for request forgeries
		//JRequest::checkToken('request') or jexit( 'Invalid Token' );

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}

		$db = & JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee;
		$db->setQuery($sql);
		$attendee = $db->loadObject();
		if (!$attendee)
			return false;
		if ($attendee->attendstate != 1 && $attendee->attendstate != 4)
			return;		

		if ($attendee->user_id == 0)
		{
			$emailaddress = $this->getEmailAddress();
			if ($emailaddress == "" || strtolower($attendee->email_address) != strtolower($emailaddress)){
				echo "Invalid security code - please click the link in your confirmation email";
				return false;
			}
		}

		$at_id = JRequest::getInt("at_id", -1);
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
			return false;

		if ($attendee->user_id != 0 && $attendee->user_id != $user->id )
		{
			$this->authoriseAttendee($rsvpdata);
		}
		
		if ($rsvpdata->allrepeats)
		{
			$ev_id = $rsvpdata->ev_id;
			$datamodel = new JEventsDataModel();
			$row = $datamodel->queryModel->getEventById($ev_id, 1, "icaldb");
		}
		else
		{
			$rp_id = $attendee->rp_id;
			$datamodel = new JEventsDataModel();
			$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
		}
		if (!$row)
			return false;

		// get the view
		$this->view = & $this->getView("attendees", "html");

		if (isset($attendee->params))
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
				$feesAndBalances = $params->outstandingBalance($attendee);
				$this->view->assignRef("attendeeParams", $params);
			}
		}
		else
		{
			return;
		}
		
		if ( $attendee->attendstate == 4) {
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
			if (is_int($xmlfile) &&  $xmlfile>0){
				$db = JFactory::getDbo();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($xmlfile));

				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
					if (isset($templateParams->whentickets) && is_array($templateParams->whentickets) && in_array("outstandingbalance", $templateParams->whentickets))
					{
						// do nothing here - just an access test
					}
					else {
						return;
					}
				}
				else return;
			}
			else return;
		}
		

		// Set the layout
		$this->view->setLayout('ticket');
		$this->view->assign('event', $row);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('attendee', $attendee);

		$this->view->display();

	}

	public function recordAttendance()
	{
		$user = JFactory::getUser();

		// Check for request forgeries
		//JRequest::checkToken('request') or jexit( 'Invalid Token' );

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}

		$db = & JFactory::getDBO();
		$at_id = JRequest::getInt("at_id", -1);
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
			return false;

		$rp_id = JRequest::getInt("rp_id", -1);
		$datamodel = new JEventsDataModel();
		$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
		if (!$row)
			return false;

		$this->event = $row;

		$cache = & JFactory::getCache('com_jevents');
		$cache->clean();

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		$db = JFactory::getDBO();

		$this->countAttendees($rsvpdata->id);
		$guestcount = JRequest::getInt("guestcount", 1);

		$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $rsvpdata->id;
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		$attendeeCount = $db->loadResult();

		// Hygene trap
		$jevattend_hidden = JRequest::getInt("jevattend_hidden", 0);
		if ($jevattend_hidden)
		{

			// Is this an admin user cancelling an attendance record
			//$this->adminCancelAttendance($rsvpdata, $row);

			// Is the admin approving an attendance record
			//$this->adminApproveAttendance($rsvpdata, $row);

			// Is this an existing attendee
			// if anon user and email attendance is allowed then find accordingly
			if ($user->id == 0 && $this->params->get("attendemails", 0))
			{
				$emailaddress = $this->getEmailAddress();
				// Make sure can only cancel or modify their own email address!!!
				if ($emailaddress == "" || $emailaddress != trim(strtolower(JRequest::getString("jevattend_email"))))
				{
					$attendee = false;

					// block attempts to double book the same email
					if (trim(strtolower(JRequest::getString("jevattend_email"))) != "")
					{
						$emailaddress = trim(strtolower(JRequest::getString("jevattend_email")));
						// check  if we already have this attendee
						$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and LOWER(email_address)=" . $db->Quote($emailaddress);
						if (!$rsvpdata->allrepeats)
						{
							$sql .= " AND rp_id=" . $row->rp_id();
						}
						$db->setQuery($sql);
						$testattendee = $db->loadObject();
						
						// if we allowunconfirmedpendingunpaidchanges then we remove this attendee to create another but only if unconfirmed and confirmation IS required
						if ($testattendee)
						{
							if (!isset($testattendee->outstandingBalances))
							{
								// This MUST be called before renderToBasicArray to populate the balance fields - so we do it here to be safe
								if (isset($testattendee->params))
								{
									$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

									if (is_int($xmlfile) || file_exists($xmlfile))
									{
										$params = new JevRsvpParameter($testattendee->params, $xmlfile, $rsvpdata, null);
										$feesAndBalances = $params->outstandingBalance($testattendee);
									}
								}
							}

							if ($this->params->get("allowunconfirmedpendingunpaidchanges",0) && !$testattendee->confirmed && (
									($this->params->get("requireconfirmationpaid",0) && isset(  $testattendee->outstandingBalances) &&  $testattendee->outstandingBalances['hasfees'] && $testattendee->outstandingBalances['feepaid'] ==0 ) 
									|| ($this->params->get("requireconfirmation",0) && (!isset(  $testattendee->outstandingBalances) || !$testattendee->outstandingBalances['hasfees'] )))
								){
		
								$testattendee = null;
								$sql = "DELETE FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and LOWER(email_address)=" . $db->Quote($emailaddress);
								if (!$rsvpdata->allrepeats)
								{
									$sql .= " AND rp_id=" . $row->rp_id();
								}
								$db->setQuery($sql);
								$db->query();								
							}
						}
						
						if ($testattendee && $this->params->get("requireconfirmation",0))
						{
							$mainframe = JFactory::getApplication();
							$Itemid = JRequest::getInt("Itemid");
							list($year, $month, $day) = JEVHelper::getYMD();
							
							$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
							
							// re-send email with confirmation details
							$creator = JFactory::getUser($row->created_by());
							if ($creator)
							{
								$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em=" . $code, false);							
								// some sef addons have a but that adds the root already
								if (strpos($link, "/") !== 0 && strpos($link, 'http://') !== 0)
								{
									$link = "/" . $link;
								}

								$uri = & JURI::getInstance(JURI::base());
								$root = $uri->toString(array('scheme', 'host', 'port'));
								if (strpos($link, 'http://') !== 0){
									$link = $root . $link;
								}
								
								// confirm email message 
								$this->notifyUser($rsvpdata, $this->event, $user, $emailaddress, $emailaddress, $testattendee,'cem', $testattendee->waiting, "","",$link);
								/*
								if ($testattendee->confirmed){
									$subject = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND_SUBJECT", $row->title());
									$message = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND", $link, $row->title(), $row->title());
								}
								else {
									// TODO offer a different message here!
									$subject = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND_SUBJECT", $row->title());
									$message = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND", $link, $row->title(), $row->title());									
								}

								$this->helper->sendMail($creator->email, $creator->name, $emailaddress, $subject, $message, 1);
								 */
							}

							// Must remove the authentication code otherwise there is no point!
							$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid));
							
							$mainframe->enqueueMessage(JText::_("JEV_ATTENDANCE_MISSING_AUTHENTICATION_CODE"));
							$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_AUTHENTICATION_CODE_RESENT"));
						}
					}
					else {
							$mainframe = JFactory::getApplication();
							$Itemid = JRequest::getInt("Itemid");
							$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid));
							$mainframe->redirect($link, JText::_("JEV_MISSING_EMAIL"));						
					}
				}
				else
				{
					// fetch attendee for auto remind
					$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and email_address=" . $db->Quote($emailaddress);
					if (!$rsvpdata->allrepeats)
					{
						$sql .= " AND rp_id=" . $row->rp_id();
					}
					$db->setQuery($sql);
					$attendee = $db->loadObject();
				}
			}
			else
			{
				// fetch attendee for auto remind
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and user_id=" . $user->id;
				if (!$rsvpdata->allrepeats)
				{
					$sql .= " AND rp_id=" . $row->rp_id();
				}
				$db->setQuery($sql);
				$attendee = $db->loadObject();
			}

			$jevattend = JRequest::getInt("jevattend", 0);

			// if template has fees then no need to confirm the email attendee if requireconfirmationpaid is false
			$templateHasFees = $this->templateHasFees($rsvpdata, $row);
			if ($templateHasFees && $this->params->get("requireconfirmation", 0) && !$this->params->get("requireconfirmationpaid", 0)  && $user->id == 0 && $this->params->get("attendemails", 0)){
				$this->params->set("requireconfirmation", 0 );
				// must also set em in the URL!
				$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
				JRequest::setVar("em",$code);
			}
			else if (isset($emailaddress)) {
				$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress. "invited"));
				if (JRequest::getVar("em2","")==$code){					
					$this->params->set("requireconfirmation", 0 );
					JRequest::setVar("em",$code);
				}
			}

			// Scenarios that matter
			// x 1. No attendee record - saying no
			// x 2. No attendee record - saying maybe
			// x 3. No attendee record - saying subject to approval
			// x 4. No attendee record - saying yes
			// x 5. Existing attendee record was yes - now no
			// x 6. Existing attendee record was yes - now maybe
			// x 7. Existing attendee record was yes - now subject to approval
			// x 8. Existing attendee record was yes - now yes (params changed)
			// x 9. Existing attendee record was no - now maybe
			// x 10. Existing attendee record was no - now yes
			// x 11. Existing attendee record was no - now subject to approval
			// x 12. Existing attendee record was maybe - now yes
			// x 13. Existing attendee record was maybe - now no
			// x 14. Existing attendee record was maybe - now subject to approval
			// x 15. Existing attendee record was maybe - now maybe  (params changed)
			// x 16. Existing attendee record was subject to approval - now no
			// x 17. Existing attendee record was subject to approval - now maybe
			// x 18. Existing attendee record was subject to approval - now yes
			// x 19. Existing attendee record was subject to approval - now subject to approval  (params changed)
						
			// Trap recaptcha if any and check coupons
			$rsvpparams = $this->getRSVPParmeters($rsvpdata, $row);
			if ($rsvpparams ) {
				$rsvpparamsarray = $rsvpparams->renderToBasicArray();
				// find the paymentoptionlist
				foreach ($rsvpparamsarray as $pield => $pm)
				{
					// only use the recaptcha when signing up for a registration and not for cancelling which has other traps built in
					if ($pm["type"] == "jevrecaptcha" && $jevattend)
					{
						if (isset($attendee) && isset($attendee->user_id) && $attendee->user_id==0 &&  $attendee->confirmed){
							continue;
						}
						
						// check access
						$accessible = true;
						if (isset($pm["accessflag"])){
							$levels = $user->getAuthorisedViewLevels();
							$nodeaccess = explode(",", $pm["access"]);
							if (count(array_intersect($levels, $nodeaccess)) == 0)
							{
								$accessible = false;
							}

							// if access flag is 0 then members of this level are BLOCKED
							if ($pm["accessflag"]==0){
								$accessible = !$accessible;
							}
						}
						if (!$accessible){
							break;
						}

						// Did we set the secret captcha field correctly i.e. passed the javascript test
						if (JRequest::getString("secretcaptcha") == md5(JRequest::getString("recaptcha_response_field").  $this->params->get("recaptchaprivate",false) )){
							$x = 1;
						}
						// otherwise fall back in case browser didn't support javascript
						else {
							if (!defined("RECAPTCHA_API_SERVER"))	require_once(JPATH_SITE.'/plugins/jevents/jevrsvppro/rsvppro/recaptcha/recaptcha.php');
							$response = recaptcha_check_answer($this->params->get("recaptchaprivate",false),JRequest::getString("REMOTE_ADDR","","server"), JRequest::getString("recaptcha_challenge_field"),JRequest::getString("recaptcha_response_field"));
							if (!$response || !$response->is_valid){

								echo "<script> alert('".JText::_("JEV_RECAPTCHA_ERROR",true)."'); window.history.go(-1); </script>\n";
								exit();
							}
						}
					}
					
					// Can this attendee use any coupons attached to the form record
					if ($pm["type"] == "jevrcoupon"){
						$fieldname = $pm["name"];
						// must trim the input
						$_REQUEST["xmlfile"][$fieldname]=trim($_REQUEST["xmlfile"][$fieldname]);
						$_POST["xmlfile"][$fieldname]=trim($_POST["xmlfile"][$fieldname]);

						if (isset($pm["maxuses"]) && $pm["maxuses"] > 0)
						{
							$sql = "SELECT * FROM #__jev_rsvp_couponusage  where atd_id=" . intval($at_id);
							if (!$rsvpdata->allrepeats){
								$sql .= " AND rp_id=".intval($rp_id);
							}
							$db->setQuery($sql);
							$couponusage = $db->loadObject();
							if ($couponusage){
								$couponparams = json_decode($couponusage->params);
								// submitted coupon code
								$inparams = JRequest::getVar("xmlfile", array());
								if (!isset($inparams[$fieldname])){
									break;
								}
								$couponcode = trim($inparams[$fieldname]);
								// is this a valid coupon code and could we be at the max use limit!
								if (isset($couponparams->$fieldname) && isset($couponparams->$fieldname->$couponcode) && intval($couponparams->$fieldname->$couponcode)+1 > $pm["maxuses"] ){
									$canusecoupon = false;
									// make sure we are not already using this same coupon
									if ($attendee){
										$attendeeparams = json_decode($attendee->params);
										if (isset($attendeeparams->$fieldname) && trim($attendeeparams->$fieldname)==$couponcode){
											$canusecoupon	= true;
											//throwerror("Already using coupon");
										}
									}
									if (!$canusecoupon) {
										if (isset($_REQUEST["xmlfile"][$fieldname])){
											// Can't uyse JRequest setVar here because its an array!
											$_REQUEST["xmlfile"][$fieldname]="";
											$_POST["xmlfile"][$fieldname]="";
											// Important must also reset $params
											/*
											if (isset($params)){
												$jparams = json_decode($params);
												$decodeparams->$fieldname="";
												$params =  json_encode($decodeparams);
											}
											 */
										}
										// Load language
										$lang = & JFactory::getLanguage();
										$lang->load("plg_jevents_jevrsvppro", JPATH_ADMINISTRATOR);
					
										JFactory::getApplication()->enqueueMessage(JText::_("RSVP_COUPON_ALREADY_USED"), "warning");
									}
								}
								//JText::

							}
						}
					}
			

				}
			}
			
			// Need the params for most of these scenarios
			$params = $this->getRsvpDataParams($rsvpdata);
			
			// I need the redirect link often
			$mainframe = JFactory::getApplication();
			$Itemid = JRequest::getInt("Itemid");
			list ( $year, $month, $day ) = JEVHelper::getYMD();

			// Need the creation date for these records
			if (class_exists("JevDate")) {
				$date = JevDate::getDate("+0 seconds");
			}
			else {
				$date = JFactory::getDate("+0 seconds");
			}				
			$created = $date->toMySQL();
			
			// Scenarios 1-4. No attendee Record
			if (!$attendee)
			{
				// 1. No attendee record - saying no
				// 2. No attendee record - saying maybe
				// 3. No attendee record - saying subject to approval


				if ($user->id == 0 && $this->params->get("attendemails", 0))
				{
					// in this case we have no email address to must set the variable
					$emailaddress = trim(strtolower(JRequest::getString("jevattend_email")));
				}

				if ($jevattend == 0 || $jevattend == 2 || $jevattend == 3)
				{
					// Here we record the decision with no checks on waiting lists etc. but do require email confirmation


					$onWaitingList = false;

					if ($user->id == 0 && $this->params->get("attendemails", 0))
					{
						$confirmed = $this->params->get("requireconfirmation", 0)?0:1;
						$sql = "INSERT INTO #__jev_attendees SET at_id=" . $rsvpdata->id . ", confirmed=$confirmed, email_address=" . $db->Quote($emailaddress) . ", created=" . $db->Quote($created). ", modified=" . $db->Quote($created);
						$name = $emailaddress;
						$username = $emailaddress;
					}
					else
					{
						$sql = "INSERT INTO #__jev_attendees SET at_id=" . $rsvpdata->id . ", user_id=" . $user->id . ", created=" . $db->Quote($created). ", modified=" . $db->Quote($created);
						$name = $user->name;
						$username = $user->username;
					}
					if ($onWaitingList)
					{
						$sql .= " , waiting = 1";
					}
					if (!$rsvpdata->allrepeats)
					{
						$sql .= ", rp_id=" . $row->rp_id();
					}
					$sql .= ", params=" . $db->Quote($params);
					$sql .= ", guestcount=" . $guestcount;
					$sql .= ", attendstate=" . intval($jevattend);
					// And the locked template
					$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
					$db->setQuery($sql);
					$db->query();

					$insertid = $db->insertid();
					$db->setQuery("SELECT * from #__jev_attendees where id=" . intval($insertid));
					$attendee = $db->loadObject();
				} // 4. No attendee record - saying yes
				else if ($jevattend == 1)
				{

					if ($this->params->get("capacity", 0) && $rsvpdata->capacity > 0)
					{

						// If over capacity and waiting list then just ignore
						if ($attendeeCount >= $rsvpdata->capacity + $rsvpdata->waitingcapacity)
						{
							$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
							$mainframe->redirect($link, JText::_("JEV_EVENT_FULL"));
						}

						// Should this be on the waiting list
						$onWaitingList = false;
						if ($attendeeCount >= intval($rsvpdata->capacity))
						{
							$onWaitingList = true;
						}
					}
					else
					{
						$onWaitingList = false;
					}

					if ($user->id == 0 && $this->params->get("attendemails", 0))
					{
						$confirmed = $this->params->get("requireconfirmation", 0) ? 0 : 1;
						$sql = "INSERT INTO #__jev_attendees SET at_id=" . $rsvpdata->id . ", confirmed=$confirmed, email_address=" . $db->Quote($emailaddress) . ", created=" . $db->Quote($created). ", modified=" . $db->Quote($created);
						$name = $emailaddress;
						$username = $emailaddress;
					}
					else
					{
						$sql = "INSERT INTO #__jev_attendees SET at_id=" . $rsvpdata->id . ", user_id=" . $user->id . ", created=" . $db->Quote($created). ", modified=" . $db->Quote($created);
						$name = $user->name;
						$username = $user->username;
					}
					if ($onWaitingList)
					{
						$sql .= " , waiting = 1";
					}
					if (!$rsvpdata->allrepeats)
					{
						$sql .= ", rp_id=" . $row->rp_id();
					}
					$sql .= ", params=" . $db->Quote($params);
					$sql .= ", attendstate=" . intval($jevattend);
					$sql .= ", guestcount=" . $guestcount;
					// And the locked template
					$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
					$db->setQuery($sql);
					$db->query();

					$insertid = $db->insertid();
					$db->setQuery("SELECT * from #__jev_attendees where id=" . intval($insertid));
					$attendee = $db->loadObject();

					// auto remind attendees
					if ($this->params->get("autoremind", 0) == 1 && !$onWaitingList && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
					{
						// create reminder
						// NB email address must be in the request object
						JRequest::setVar("jevremindemail", isset($emailaddress) ? $emailaddress : $attendee->email_address);
						$user = JFactory::getUser($attendee->user_id);
						$this->jevrreminders->remindUser($rsvpdata, $row, $user, isset($emailaddress) ? $emailaddress : $attendee->email_address);
						$mainframe = JFactory::getApplication();
						$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
					}

					// Record activity points
					$this->recordRsvpActivity("attendance.confirmed", $row);
				}

				// Make sure the counts are in sync!
				$this->countAttendees($rsvpdata->id);

				// Send notification/confirmation messages
				if ($user->id == 0 && $this->params->get("attendemails", 0))
				{
					$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
					$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em=" . $code, false);

					if ($this->params->get("requireconfirmation", 0))
					{
						// send email with confirmation details
						$creator = JFactory::getUser($this->event->created_by());
						if ($creator)
						{
							// some sef addons have a but that adds the root already
							if (strpos($link, "/") !== 0 && strpos($link, 'http://') !== 0)
							{
								$link = "/" . $link;
							}

							$uri = & JURI::getInstance(JURI::base());
							$root = $uri->toString(array('scheme', 'host', 'port'));
							if (strpos($link, 'http://') !== 0){
								$link = $root . $link;
							}

							// confirm email message - passing special link through!
							$this->notifyUser($rsvpdata, $this->event, $user, $emailaddress, $username, $attendee,'cem', $onWaitingList,  false, "","",$link);
							//$this->notifyCreator($rsvpdata, $this->event, $emailaddress, $emailaddress, $attendee, false, $onWaitingList);

							/*
							$subject = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND_SUBJECT", $this->event->title());
							$message = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND", $link, $this->event->title(), $this->event->title());
							if ($rsvpdata->allowcancellation)
							{
								$message .= JText::_("JEV_ATTENDANCE_PENDING");
							}
							
							if (!$onWaitingList)
							{
								str_replace("{WAITINGMESSAGE}", "", $message);
							}
							else
							{
								str_replace("{WAITINGMESSAGE}", JText::_("JEV_WAITING_MESSAGE"), $message);
							}
							$this->helper->sendMail($creator->email, $creator->name, $emailaddress, $subject, $message, 1);
							 */
						}
						//reset the link to not have the 'code' in it!!
						$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
						$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_PENDING"));
					}

					// reset attendance state
					$attendee->attendstate = $jevattend;
					// only send yes or subject to approval
					if ($jevattend == 1 || $jevattend == 3)
					{
						// Do not send notification if only sending cancelations!
						if ($this->params->get("notifycreator", 0) && $this->params->get("notifycreator", 0)!=3) {
							$this->notifyCreator($rsvpdata, $row, $name, $username, $attendee, false, $onWaitingList);
						}
						if ($this->params->get("notifyuser", 0)){
							$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee,'ack', $onWaitingList);
						}
					}

					$this->postUpdateActions($rsvpdata, $row, $attendee,$onWaitingList, $rsvpparams);
					
					if ($onWaitingList)
					{
						$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_ON_WAITING_LIST"));
					}
					else
					{
						$message =  JText::_("JEV_ATTENDANCE_CONFIRMED");
						if ($jevattend == 3) {
							$message .="<br/>". JText::_("JEV_ATTENDING_PENDING_APPROVAL");
						}
						if ($jevattend == 1 || $jevattend == 3) {
							$this->redirectToPaymentPage($attendee, $rsvpdata, $message);
						}
						$mainframe->redirect($link, $message);
					}
				}
				else
				{
					// reset attendance state
					$attendee->attendstate = $jevattend;
					// only send yes or subject to approval
					if ($jevattend == 1 || $jevattend == 3)
					{
						// Do not send notification if only sending cancelations!
						if ($this->params->get("notifycreator", 0) && $this->params->get("notifycreator", 0)!=3)
							$this->notifyCreator($rsvpdata, $row, $name, $username, $attendee, false, $onWaitingList);
						if ($this->params->get("notifyuser", 0))
							$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'ack', $onWaitingList);
					}

					$this->postUpdateActions($rsvpdata, $row, $attendee,$onWaitingList, $rsvpparams);
					
					$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
					$this->recordRsvpActivity("attendance.confirmed", $row);
					if ($onWaitingList)
					{
						$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_ON_WAITING_LIST"));
					}
					else
					{
						$message =  JText::_("JEV_ATTENDANCE_CONFIRMED");
						if ($jevattend == 3) {
							$message .="<br/>". JText::_("JEV_ATTENDING_PENDING_APPROVAL");
						}
						if ($jevattend == 1 || $jevattend == 3) {
							$this->redirectToPaymentPage($attendee, $rsvpdata, $message);
						}
						$mainframe->redirect($link, $message);
					}
				}
			} // Scenarios 5-19. Existing attendee record
			else
			{

				$this->countAttendeeContributionToAttendance($row, $rsvpdata, $attendee);

				if ($user->id == 0 && $this->params->get("attendemails", 0))
				{
					$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
					$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em=" . $code);
				}
				else
				{
					$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
				}

				// 5. Existing attendee record was yes - now no
				// 6. Existing attendee record was yes - now maybe
				if (($attendee->attendstate == 1 || $attendee->attendstate == 4 ) && ($jevattend == 0 || $jevattend == 2))
				{

					// Only allow this if cancellation is allowed
					if ($rsvpdata->allowcancellation || $user->id == $row->created_by() || JEVHelper::isAdminUser($user))
					{
						// if anon user and email attendance is allowed then find accordingly
						if ($user->id == 0 && $this->params->get("attendemails", 0))
						{
							$name = $emailaddress;
							$username = $emailaddress;
						}
						else
						{
							$name = $user->name;
							$username = $user->username;
						}
						$sql = "UPDATE #__jev_attendees SET attendstate=" . intval($jevattend);
						$sql .= ", guestcount=" . $guestcount. ", modified=" . $db->Quote($created);
						// And the locked template
						$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
						$sql .= ", params=" . $db->Quote($params) . ", waiting=0 WHERE id=" . $attendee->id;
						$db->setQuery($sql);
						$db->query();

						// auto remind attendees
						if ($this->params->get("autoremind", 0) == 1 && $attendee && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
						{
							// cancel reminder
							$user = JFactory::getUser($attendee->user_id);
							$this->jevrreminders->unremindUser($rsvpdata, $row, $user, $attendee->email_address);
							$mainframe = JFactory::getApplication();
							$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CANCELLED"));
						}

						// cancellation so $attendee is null
						// reset attendance state
						$attendee->attendstate = $jevattend;
						// Do not send notification if only sending notices of new and cancelled registrations
						if ($this->params->get("notifycreator", 0) && $this->params->get("notifycreator", 0)!=2)
							$this->notifyCreator($rsvpdata, $row, $name, $username, $attendee, true);
						if ($this->params->get("notifyuser", 0)){
							$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'usercancel');
						}

						// Make sure the counts are in sync!
						$this->countAttendees($rsvpdata->id);

						$this->recordRsvpActivity("attendance.cancelled", $row);

						$this->postUpdateActions($rsvpdata, $row, $attendee,$onWaitingList, $rsvpparams);
						
						//$this->redirectToPaymentPage($attendee, $rsvpdata, JText::_("JEV_ATTENDANCE_CANCELLED"));
						$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CANCELLED"));
					}
				} // 7. Existing attendee record was yes - now subject to approval
				else if (($attendee->attendstate == 1 || $attendee->attendstate == 4 ) && $jevattend == 3)
				{
					// This should not arise so do nothing
					JError::raiseError(403, "This should not be allowed 1");
				} // 18. Existing attendee record was subject to approval - now yes
				else if ($attendee->attendstate == 3 && $jevattend == 1)
				{

					// setting must have changed to now allow this
					if ($rsvpdata->initialstate == 1)
					{
						// need to check the capacity on these and waiting list
						$onWaitingList = 0;
						if ($this->params->get("capacity", 0) && $rsvpdata->capacity > 0)
						{
							// If over capacity and waiting list then just ignore
							if ($attendeeCount >= $rsvpdata->capacity + $rsvpdata->waitingcapacity)
							{
								$mainframe->redirect($link, JText::_("JEV_EVENT_FULL"));
							}

							// Should this be on the waiting list
							$onWaitingList = 0;
							if ($attendeeCount >= intval($rsvpdata->capacity) && !$attendee->waiting)
							{
								$onWaitingList = 1;
								$sql = "UPDATE #__jev_attendees SET attendstate=" . intval($jevattend);
								$sql .= ", guestcount=" . $guestcount. ", modified=" . $db->Quote($created);
								// And the locked template
								$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
								$sql .= ", params=" . $db->Quote($params) . ", waiting=" . $onWaitingList . " WHERE id=" . $attendee->id;

								$db->setQuery($sql);
								$db->query();
								$mainframe->redirect($link, JText::_("JEV_WILL_PUT_YOU_ON_WAITING_LIST"));
							}
							// if already on waiting list and saying yes to attend then KEEP on the waiting list
							if ($jevattend == 1 && $attendee->waiting){
								$onWaitingList = 1;
							}
							
						}
						else
						{
							$onWaitingList = 0;
						}

						$sql = "UPDATE #__jev_attendees SET attendstate=" . intval($jevattend);
						$sql .= ", guestcount=" . $guestcount. ", modified=" . $db->Quote($created);
						// And the locked template
						$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
						$sql .= ", params=" . $db->Quote($params) . ", waiting=" . $onWaitingList . " WHERE id=" . $attendee->id;

						$db->setQuery($sql);
						$db->query();

						if ($jevattend == 1 || $jevattend == 3) {
							$this->redirectToPaymentPage($attendee, $rsvpdata, JText::_("JEV_ATTENDANCE_UPDATED"));
						}
						$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_UPDATED"));
					}
					else
					{
						// This should not arise so do nothing
						JError::raiseError(403, "This should not be allowed 2");
					}
				}

				// 8. Existing attendee record was yes - now yes (params changed)
				// 10. Existing attendee record was no - now yes
				// 12. Existing attendee record was maybe - now yes
				else if (
						(($attendee->attendstate == 1 || $attendee->attendstate == 4 ) && $jevattend == 1) 
						|| ($attendee->attendstate == 0 && $jevattend == 1)
						|| ($attendee->attendstate == 2 && $jevattend == 1))
				{
					// need to check the capacity on these and waiting list
					$onWaitingList = 0;
					if ($this->params->get("capacity", 0) && $rsvpdata->capacity > 0)
					{
						// If over capacity and waiting list then just ignore
						// remember to add the change in this registrations entry
						if ($attendeeCount + ($row->newcnt - $row->currentcnt) > $rsvpdata->capacity + $rsvpdata->waitingcapacity)
						{
							$mainframe->redirect($link, JText::_("JEV_EVENT_FULL"));
						}

						// Should this be on the waiting list
						// remember to add the change in this registrations entry
						$onWaitingList = 0;
						if ($attendeeCount + ($row->newcnt - $row->currentcnt) > intval($rsvpdata->capacity) && !$attendee->waiting)
						{
							/*
							  // DO WE WANT TO PUT SOMEONE ONTO THE WAITING LIST??
							  $onWaitingList = 1;
							  $sql = "UPDATE #__jev_attendees SET attendstate=".intval($jevattend). ", modified=" . $db->Quote($created);
							  $sql .= ", params=".$db->Quote($params) . ", waiting=". $onWaitingList. " WHERE id=".$attendee->id;

							  $db->setQuery($sql);
							  $db->query();
							 */
							$mainframe->redirect($link, JText::_("JEV_WILL_PUT_YOU_ON_WAITING_LIST"));
						}
						// if already on waiting list and saying yes to attend then KEEP on the waiting list
						if ($jevattend == 1 && $attendee->waiting){
							$onWaitingList = 1;
						}
					}
					else
					{
						$onWaitingList = 0;
					}
					
					$sql = "UPDATE #__jev_attendees SET attendstate=" . intval($jevattend);
					$sql .= ", guestcount=" . $guestcount. ", modified=" . $db->Quote($created);
					// And the locked template
					$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
					$sql .= ", params=" . $db->Quote($params) . ", waiting=" . $onWaitingList . " WHERE id=" . $attendee->id;

					$db->setQuery($sql);
					$db->query();

					// no->yes maybe->yes yes->yes (changed params) can trigger notification  message (but not if cancellation messages only
					if ($this->params->get("notifycreator", 0) && $this->params->get("notifycreator", 0)!=3 && 
							(($attendee->attendstate == 0 && $jevattend == 1) || 
							($attendee->attendstate == 2 && $jevattend == 1) || 
							 (($attendee->attendstate == 1 || $attendee->attendstate == 4 ) && $jevattend == 1)))
					{
						// reset attendance state
						$attendee->attendstate = $jevattend;
						if ($user->id == 0 && $this->params->get("attendemails", 0))
						{
							$emailaddress = $this->getEmailAddress();
							$username = $name = $emailaddress;
						}
						else
						{
							$name = $user->name;
							$username = $user->username;
						}
						$this->notifyCreator($rsvpdata, $row, $name, $username, $attendee, false);
					}

					$this->postUpdateActions($rsvpdata, $row, $attendee,$onWaitingList, $rsvpparams);
					
					if ($jevattend == 1 || $jevattend == 3) {
						$this->redirectToPaymentPage($attendee, $rsvpdata, JText::_("JEV_ATTENDANCE_UPDATED"));
					}
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_UPDATED"));
				}

				// 9. Existing attendee record was no - now maybe
				// 11. Existing attendee record was no - now subject to approval
				// 13. Existing attendee record was maybe - now no
				// 14. Existing attendee record was maybe - now subject to approval
				// 15. Existing attendee record was maybe - now maybe  (params changed)
				// 16. Existing attendee record was subject to approval - now no
				// 17. Existing attendee record was subject to approval - now maybe
				// 19. Existing attendee record was subject to approval - now subject to approval  (params changed)
				else if (($attendee->attendstate == 0 && ($jevattend == 2 || $jevattend == 3)) || ($attendee->attendstate == 2 && ($jevattend == 0 || $jevattend == 3 || $jevattend == 2)) || ($attendee->attendstate == 3 && ($jevattend == 0 || $jevattend == 2 || $jevattend == 3)))
				{

					$sql = "UPDATE #__jev_attendees SET attendstate=" . intval($jevattend);
					$sql .= ", guestcount=" . $guestcount. ", modified=" . $db->Quote($created);
					// And the locked template
					$sql .= ", lockedtemplate=" . intval($rsvpdata->template);
					$sql .= ", params=" . $db->Quote($params) . ", waiting=0 WHERE id=" . $attendee->id;

					$db->setQuery($sql);
					$db->query();

					// Make sure the counts are in sync!
					$this->countAttendees($rsvpdata->id);

					// redirect to ensure not in tmpl=component mode
					if ($jevattend == 1 || $jevattend == 3) {
						$this->redirectToPaymentPage($attendee, $rsvpdata, JText::_("JEV_ATTENDANCE_UPDATED"));
					}
					$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_UPDATED"));
				}
			}
		}
		return true;

	}

	public function listaction(){
		$user = JFactory::getUser();

		// Check for request forgeries
		//JRequest::checkToken('request') or jexit( 'Invalid Token' );

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}

		$db = & JFactory::getDBO();
		$at_id = JRequest::getInt("at_id", -1);
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
			return false;

		$rp_id = JRequest::getInt("rp_id", -1);
		$datamodel = new JEventsDataModel();
		$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
		if (!$row)
			return false;

		$this->event = $row;

		$cache = & JFactory::getCache('com_jevents');
		$cache->clean();

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		$db = JFactory::getDBO();

		$this->countAttendees($rsvpdata->id);
		$guestcount = JRequest::getInt("guestcount", 1);

		$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $rsvpdata->id;
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		$attendeeCount = $db->loadResult();

		// Hygene trap
		$jevattendlist_id = JRequest::getInt("jevattendlist_id", 0);
		$jevattendlist_id_approve = JRequest::getInt("jevattendlist_id_approve", 0);
		if ($jevattendlist_id || $jevattendlist_id_approve)
		{

			// Is this an admin user cancelling an attendance record
			$this->adminCancelAttendance($rsvpdata, $row);

			// Is the admin approving an attendance record
			$this->adminApproveAttendance($rsvpdata, $row);
		}		
	}

	private function templateHasFees($rsvpdata, $row){
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				if (isset($rsvpparams->_rawtemplate) && isset($rsvpparams->_rawtemplate->withfees)){
					return $rsvpparams->_rawtemplate->withfees;
				}
			}
		}
		return false;
		
	}

	private function countAttendeeContributionToAttendance(&$row, $rsvpdata, $attendee)
	{

		// TODO bring this in line with full count attendees code!
		
		// get the existing contribution to the attendance count
		if ($attendee->attendstate == 1)
		{
			$attendeeparams = new JRegistry($attendee->params);
			$row->currentcnt = $attendee->guestcount;
			unset($row->_reductionapplied);

			// New parameterised fields
			if ($rsvpdata->template != "")
			{
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$rsvpparams = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$params = $rsvpparams->renderInputDataToBasicArray($attendee);
					foreach ($params as $param)
					{
						if (isset($param ["includeintotalcapacity"]) && isset($param ["capacitycount"]))
						{
							$row->currentcnt += intval($param ["capacitycount"]);
							if (isset($param ["reducevaluefortotalcapacity"]) && intval($param ["capacitycount"]) > 0 && !isset($row->_reductionapplied))
							{
								$row->currentcnt -= $param ["reducevaluefortotalcapacity"];
								$row->_reductionapplied = 1;
							}
						}
					}
				}
			}
			else
			{
				$row->currentcnt = 1;
			}
		}
		else
		{
			$row->currentcnt = 0;
		}

		$jevattend = JRequest::getInt("jevattend", 0);
		// Now the new ones
		if ($jevattend == 1)
		{
			$attendeeparams = new JRegistry(JRequest::getVar("xmlfile", array()));

			unset($row->_reductionapplied);
			$row->newcnt = JRequest::getInt("guestcount",0);
			// New parameterised fields
			if ($rsvpdata->template != "")
			{
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$rsvpparams = new JevRsvpParameter($attendeeparams, $xmlfile, $rsvpdata, $row);
					$params = $rsvpparams->renderInputDataToBasicArray($attendee);
					foreach ($params as $param)
					{
						if (isset($param ["includeintotalcapacity"]) && isset($param ["capacitycount"]))
						{
							$row->newcnt += intval($param ["capacitycount"]);
							if (isset($param ["reducevaluefortotalcapacity"]) && intval($param ["capacitycount"]) > 0 && !isset($row->_reductionapplied))
							{
								$row->newcnt -= 1;
								$row->_reductionapplied = 1;
							}
						}
					}
				}
			}
			else
			{
				$row->newcnt = 1;
			}
		}
		else
		{
			$row->newcnt = 0;
		}
		unset($row->_reductionapplied);

	}

	private function recordRsvpActivity($cmd, $row)
	{

		if (!$this->jomsocial)
			return;
		$user = JFactory::getUser();
		if ($user->id == 0)
			return;

		require_once (JPATH_ROOT . '/' . 'components' . '/' . 'com_community' . '/' . 'defines.community.php');

		// Require the base controller
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'error.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'controllers' . '/' . 'controller.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'apps.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'core.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'template.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'userpoints.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'views' . '/' . 'views.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'url.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'ajax.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'time.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'owner.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'azrul.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'helpers' . '/' . 'string.php');

		if ($cmd == "attendance.cancelled")
		{
			CuserPoints::assignPoint('rsvppro.cancel');		
		}
		else
		{
			CuserPoints::assignPoint('rsvppro.signup');		
		}
				
		if (!$this->params->get("activitystream", 0))
			return;
		
		CFactory::load('libraries', 'activities');

		$mainframe = JFactory::getApplication();
		$Itemid = JRequest::getInt("Itemid");
		list ( $year, $month, $day ) = JEVHelper::getYMD();

		$link = JRoute::_($row->viewDetailLink($year, $month, $day, false, $Itemid));

		if (strpos($link, "/") !== 0)
		{
			$link = "/" . $link;
		}

		$uri = & JURI::getInstance(JURI::base());
		$root = $uri->toString(array('scheme', 'host', 'port'));

		$link = $root . $link;

		if ($cmd == "attendance.cancelled")
		{
			$title = JText::sprintf("JOMSOCIAL_ACTIVITY_CANCELLED", $link, $row->title());
		}
		else
		{
			$title = JText::sprintf("JOMSOCIAL_ACTIVITY_REGISTERED", $link, $row->title());
		}
		$act = new stdClass ();
		$act->cmd = $cmd;
		$act->actor = $user->id;
		$act->target = 0;
		$act->title = $title;
		$act->content = "";
		$act->app = "jevents";
		$act->cid = 0;

		$privateevent = isset($row->_privateevent) ? $row->_privateevent : 0;
		switch ($privateevent) {
			case 0 :
				// public event
				break;
			case 1 :
				// private event
				$act->access = 40;
				break;
			case 2 :
				// Jom social friends
				$act->access = 30;
				break;
			case 3 :
				// details private
				break;
			case 4 :
				// JomSocial Groups
				// No specific rule available for this
				$act->access = 30;
				break;
			case 5 :
			case 6 :
				// Group Jive or CB Connections
				break;
		}

		CActivityStream::add($act);

	}

	private function redirectToPaymentPage(&$attendee, $rsvpdata, $msg = "")
	{

		// Since we have just saved the attendee we must reload to get the new balance values correctly
		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->id;
		$db->setQuery($sql);
		$attendee = $db->loadObject();

		// ALSO redirect to payment page if necessary
		if (!isset($attendee->outstandingBalances))
		{
			// This MUST be called before renderToBasicArray to populate the balance fields - so we do it here to be safe
			if (isset($attendee->params))
			{
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, null);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
			}
		}

		$feesAndBalances = isset($attendee->outstandingBalances) ? $attendee->outstandingBalances : false;
		if ($feesAndBalances && $params && $feesAndBalances["hasfees"])
		{
			$paymentoptionlistfield = "paymentoptionlist";
			$paramarray = $params->renderToBasicArray();
			// find the paymentoptionlist
			foreach ($paramarray as $paramfield => $param)
			{
				if ($param["type"] == "jevrpaymentoptionlist")
				{
					$paymentoptionlistfield = $param["name"];
				}
			}
			$gateway = $params->getValue($paymentoptionlistfield, 'xmlfile', "manual");
			$outstandingBalance = $feesAndBalances["feebalance"];
			$token = JSession::getFormToken();;
			$code = "";
			$em = JRequest::getString("em", "");
			if ($em != "")
			{
				$code = "&em=$em";
			}

			if ($outstandingBalance > 0)
			{
				// If we have a deposit that is required now then change the amount to be paid to match the deposit 
				if ($feesAndBalances["deposit"]>0 && $feesAndBalances["feepaid"]==0){
					$amount = $feesAndBalances["deposit"];
				}
				else {
					$amount = $outstandingBalance;
				}
				
				$mainframe = JFactory::getApplication();
				$Itemid = JRequest::getInt("Itemid");
				$user = JFactory::getUser();
				if ($user->id == 0 && $this->params->get("attendemails", 0) && $code == "")
				{
					$emailaddress = trim(strtolower(JRequest::getString("jevattend_email")));
					$code = "&em=" .base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
                                    }
				$link = JRoute::_("index.php?option=com_rsvppro&task=accounts.paymentpage&gateway=$gateway&Itemid=$Itemid&amount=$amount&rsvpid=$rsvpdata->id&invoiceid=$attendee->id&$token=1$code", false);
				$mainframe->redirect($link, $msg);
			}
			else if ($outstandingBalance < 0)
			{

				// TODO request repayment
				return;
				$mainframe = JFactory::getApplication();
				$link = JRoute::_("index.php?option=com_rsvppro&task=accounts.paymentpage&gateway=$gateway&Itemid=$Itemid&amount=$outstandingBalance&rsvpid=$rsvpdata->id&invoiceid=$attendee->id&$token=1$code", false);
				$mainframe->redirect($link, $msg);
			}
		}

	}

	/*
	 * We cannot rely on __call magic function to pass variables by reference!
	 */
	private function notifyUser($rsvpdata, & $row, $user, $name, $username, $attendee = null, $messagetype = 'ack', $onWaitingList = false, $transaction = false, $subject = "", $message = "", $speciallink=false) {
		if (isset($this->event) && !isset($this->helper->event)){
			$this->helper->event = $this->event;
		}
		return $this->helper->notifyUser($rsvpdata,  $row, $user, $name, $username, $attendee, $messagetype, $onWaitingList, $transaction, $subject, $message, $speciallink);
	}
	
	private function notifyCreator($rsvpdata,& $row, $name, $username, $attendee = null, $cancellation = false, $onWaitingList = false) {
		if (isset($this->event) && !isset($this->helper->event)){
			$this->helper->event = $this->event;
		}
		return $this->helper->notifyUser($rsvpdata, $row, $name, $username, $attendee, $cancellation, $onWaitingList) ;
		
	}
}