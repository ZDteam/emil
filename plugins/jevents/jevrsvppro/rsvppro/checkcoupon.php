<?php

/**
 * @copyright	Copyright (C) 2008-2011 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
 */
ini_set("display_errors", 0);

if (version_compare(phpversion(), '5.2.0', '<')===true) {
	include_once(JPATH_SITE."/components/com_jevents/libraries/jsonwrapper.php");
}

list($usec, $sec) = explode(" ", microtime());
define('_SC_START', ((float) $usec + (float) $sec));

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

// Create JSON data structure
$data = new stdClass();
$data->error = 0;
$data->result = "ERROR";
$data->user = "";

// Get JSON data
if (!array_key_exists("json", $_REQUEST))
{
	throwerror("There was an error - no request data");
}
else
{
	$requestData = $_REQUEST["json"];

	if (isset($requestData))
	{
		try {
			if (ini_get("magic_quotes_gpc"))
			{
				$requestData = stripslashes($requestData);
			}

			$requestObject = json_decode($requestData, 0);
			if (!$requestObject)
			{
				$requestObject = json_decode(utf8_encode($requestData), 0);
			}
		}
		catch (Exception $e) {
			throwerror("There was an exception");
		}

		if (!$requestObject)
		{
			file_put_contents(dirname(__FILE__) . "/cache/error.txt", var_export($requestData, true));
			throwerror("There was an error - no request object ");
		}
		else if ($requestObject->error)
		{
			throwerror("There was an error - Request object error " . $requestObject->error);
		}
		else
		{

			try {
				$data = ProcessRequest($requestObject, $data);
			}
			catch (Exception $e) {
				throwerror("There was an exception " . $e->getMessage());
			}
		}
	}
	else
	{
		throwerror("Invalid Input");
	}
}

header("Content-Type: application/x-javascript; charset=utf-8");

list ($usec, $sec) = explode(" ", microtime());
$time_end = (float) $usec + (float) $sec;
$data->timing = round($time_end - _SC_START, 4);

// Must suppress any error messages
@ob_end_clean();
echo json_encode($data);

function ProcessRequest(&$requestObject, $returnData)
{

	define("REQUESTOBJECT", serialize($requestObject));
	define("RETURNDATA", serialize($returnData));

	require_once JPATH_BASE . '/' . 'includes' . '/' . 'defines.php';
	require_once JPATH_BASE . '/' . 'includes' . '/' . 'framework.php';

	$requestObject = unserialize(REQUESTOBJECT);
	$returnData = unserialize(RETURNDATA);

	$returnData->discount = 0;

	ini_set("display_errors", 0);

	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client, array("site", "administrator")))
	{
		$client = $requestObject->client;
	}
	$mainframe = & JFactory::getApplication($client);
	$mainframe->initialise();

	$GLOBALS["mainframe"] = $mainframe;

	if (!JVersion::isCompatible("1.6"))
	{
		JPluginHelper::importPlugin('system');
		$mainframe->triggerEvent('onAfterInitialise');
	}

	$params = JComponentHelper::getParams("com_rsvppro");

	// Enforce referrer
	if ($params->get("testreferrer", 0))
	{
		if (!array_key_exists("HTTP_REFERER", $_SERVER))
		{
			throwerror("There was an error");
		}

		$live_site = $_SERVER['HTTP_HOST'];
		$ref_parts = parse_url($_SERVER["HTTP_REFERER"]);

		if (!isset($ref_parts["host"]) || $ref_parts["host"] != $live_site)
		{
			throwerror("There was an error - missing host in referrer");
		}
	}


	if (!isset($requestObject->rp_id) || $requestObject->rp_id == 0)
	{
		throwerror("There was an error - repeat id");
	}

	if (!isset($requestObject->fieldid) || intval($requestObject->fieldid) == 0)
	{
		throwerror("There was an error - no such field");
	}

	$token = JSession::getFormToken();;
	if (!isset($requestObject->token) || $requestObject->token != $token)
	{
		throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$db = JFactory::getDBO();
	$db->setQuery("SELECT * FROM #__jevents_repetition as rpt LEFT JOIN #__jevents_vevent as evt on evt.ev_id=rpt.eventid where rp_id=" . intval($requestObject->rp_id));
	$event = $db->loadObject();
	if (!$event)
	{
		throwerror("There was an error - no such repeat");
	}

	if ($requestObject->error)
	{
		return "Error";
	}
	// title is actually the coupon code!
	if (isset($requestObject->title) && trim($requestObject->title) !== "")
	{
		$returnData->result = "title is " . $requestObject->title;
	}
	else
	{
		throwerror("There was an error - no valid argument");
	}

	$db->setQuery("SELECT * FROM #__jev_rsvp_fields  where field_id=" . intval($requestObject->fieldid));
	$field = $db->loadObject();
	if (!$field || $field->type != "jevrcoupon")
	{
		throwerror("There was an error - not a coupon");
	}

	$db->setQuery("SELECT * FROM #__jev_attendance where id=" . intval($requestObject->atd_id));
	$rsvpdata = $db->loadObject();
	
	if (strlen($requestObject->title) < 1)
	{
		$returnData->discount = 0;
		return $returnData;
	}
		
	$db = JFactory::getDBO();

	$fieldoptions = json_decode($field->options);
	$i = 0;
	foreach ($fieldoptions->label as $code)
	{
		if (trim($code) == trim($requestObject->title))
		{
			$discount = -$fieldoptions->price[$i];
		}
		$i++;
	}
	
	if (!isset($discount)){
		$returnData->discount = 0;
		return $returnData;		
	}
	$fieldparams = json_decode($field->params);
	if (isset($fieldparams->maxuses) && $fieldparams->maxuses > 0)
	{
		$sql = "SELECT * FROM #__jev_rsvp_couponusage  where atd_id=" . intval($requestObject->atd_id);
		if (!$rsvpdata->allrepeats){
			$sql .= " AND rp_id=".intval($requestObject->rp_id);
		}
		$db->setQuery($sql);
		$couponusage = $db->loadObject();
		if ($couponusage){
			$couponparams = json_decode($couponusage->params);
			$fieldname = "field".$field->field_id;
			$couponcode = trim($requestObject->title);
			// is this a valid coupon code and could we be at the max use limit!
			if (isset($couponparams->$fieldname) && isset($couponparams->$fieldname->$couponcode) && intval($couponparams->$fieldname->$couponcode)+1 > $fieldparams->maxuses ){
				$canusecoupon = false;
				// make sure we are not already using this same coupon
				if ($requestObject->atdee_id >0){
					$sql = "SELECT * FROM #__jev_attendees where id=" . intval($requestObject->atdee_id);
					if (!$rsvpdata->allrepeats){
						$sql .= " AND rp_id=".intval($requestObject->rp_id);
					}
					$db->setQuery($sql);
					$attendeedata = $db->loadObject();
					if ($attendeedata){
						$attendeeparams = json_decode($attendeedata->params);
						if (isset($attendeeparams->$fieldname) && trim($attendeeparams->$fieldname)==$couponcode){
							$canusecoupon	= true;
							//throwerror("Already using coupon");
						}
					}
				}
				if (!$canusecoupon) {
					// Load language
					$lang = & JFactory::getLanguage();
					$lang->load("plg_jevents_jevrsvppro", JPATH_ADMINISTRATOR);
					
					throwerror(JText::_("RSVP_COUPON_ALREADY_USED", true));
				}
			}
			//JText::

		}
	}
	
	$returnData->discount = $discount;

	return $returnData;

}

function throwerror($msg)
{
	$data = new stdClass();
	//"document.getElementById('products').innerHTML='There was an error - no valid argument'");
	$data->error = "alert('" . $msg . "')";
	$data->result = "ERROR";
	$data->user = "";

	header("Content-Type: application/x-javascript");
	// Must suppress any error messages
	@ob_end_clean();
	echo json_encode($data);
	exit();

}