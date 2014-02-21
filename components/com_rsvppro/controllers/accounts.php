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

include_once(RSVP_ADMINPATH . "/controllers/accounts.php");

class FrontAccountsController extends AdminAccountsController
{

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerDefaultTask("notify");

	}

	function notify()
	{

		JPluginHelper::importPlugin("rsvppro");

		$activePlugin = "";

		$dispatcher = & JDispatcher::getInstance();
		$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "notify"));

		if ($activePlugin != "" && class_exists($activePlugin))
		{
			$args = array();
			call_user_func_array(array($activePlugin, "notifyRsvpGateway"), array(&$args));
		}

	}

	function paymentpage()
	{
		JPluginHelper::importPlugin("rsvppro");

		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$activePlugin = "";

		$dispatcher = & JDispatcher::getInstance();
		$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "paymentpage"));

		if ($activePlugin != "" && class_exists($activePlugin))
		{

			// fetch the session and the attendee
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendees WHERE id=" . JRequest::getInt('invoiceid', -1);
			$db->setQuery($sql);
			$attendee = $db->loadObject();
			if (!$attendee) 
                        {
			    return;
                        }
			if ($attendee->at_id != JRequest::getInt('rsvpid', -1))
                        {
			    return;
                        }

			// security
			if ($attendee->user_id == 0)
			{
				$em = JRequest::getString("em", "");
				if ($em != "")
				{
					$emd = base64_decode($em);

					if (strpos($emd, ":") > 0)
					{
						list ( $emailaddress, $code ) = explode(":", $emd);

						$params = JComponentHelper::getParams("com_rsvppro");

						if ($em != base64_encode($emailaddress . ":" . md5($params->get("emailkey", "email key") . $emailaddress)))
						{
							return "";
						}
						else if ($emailaddress != $attendee->email_address)
						{
							return "";
						}
					}
					else
					{
						return "";
					}
				}
				else
				{
					return "";
				}
			}

			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			if (!$rsvpdata) {
                            return;
                        }

			// attach balance info to the attendee
			if (!isset($attendee->outstandingBalances))
			{
				// This MUST be called before renderToBasicArray to populate the balance fields - so we do it here to be safe
				if (isset($attendee->params))
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if (is_int($xmlfile) || file_exists($xmlfile))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, null);
						
						// do we need to update the payment method in the $params AND in the attendee->params data (to be safe)!
						$rsvpparamsarray = $params->renderToBasicArray();
						// find the paymentoptionlist
						foreach ($rsvpparamsarray as $pield => $pm)
						{
							if ($pm["type"] == "jevrpaymentoptionlist"){
								
								$newval = JRequest::getVar("xmlfile", array(), "POST");
								if (isset($newval[$pield])){
									$attendeefields = new JRegistry($attendee->params);
									$oldval = $attendeefields->get($pield);
									$attendeefields->set($pield, $newval[$pield]);
									$attendee->params = $attendeefields->toString();
									//$attendee->params = str_replace('"'.$pield.'":"'.$pm["value"].'"','"'.$pield.'":"'.$newval[$pield].'"',$attendee->params);
									// must also update the database
									$db = JFactory::getDbo();
									$db->setQuery("UPDATE #__jev_attendees set params=".$db->Quote($attendee->params). " WHERE id=" . $attendee->id);
									$db->query();
									
									// reset the active gateway
									JRequest::setVar("gateway",  $newval[$pield]);
									$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "paymentpage"));
									if (!$activePlugin  || !class_exists($activePlugin))
									{
										exit ("Invalid payment gateway");										
									}									
									
									$params->bind($newval);
								}
							}							
						}
						
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
				}
			}

			if ($rsvpdata->allrepeats){
				// get the first repeat
				$event = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
			}
			else {
				$event = $this->dataModel->queryModel->listEventsById($attendee->rp_id, false, "icaldb");
			}

			// Create the base transaction - empty ones are useful since I may build a system for tracking abandoned transactions later
			$transaction = new rsvpTransaction();
			$transaction->attendee_id = $attendee->id;
			$transaction->store();

			$html = "";
			$dispatcher = & JDispatcher::getInstance();
			// load plugin parameters
			$pluginname = strtolower(str_replace("plgRsvppro", "", $activePlugin));

			$plugin = & JPluginHelper::getPlugin("rsvppro", $pluginname);

			// create the plugin
			$gateway = new $activePlugin($dispatcher, (array) ($plugin));

			//call_user_func_array(array($gateway,"generatePaymentPage"),array(&$html, $attendee, $rsvpdata, $event, $transaction));
			$gateway->generatePaymentPage($html, $attendee, $rsvpdata, $event, $transaction);
			echo $html;
		}

	}

}