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

	$returnData->titles = array();
	$returnData->exactmatch = false;

	ini_set("display_errors", 0);

	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client, array("site", "administrator")))
	{
		$client = $requestObject->client;
	}
	$mainframe = & JFactory::getApplication($client);
	$mainframe->initialise();

	$GLOBALS["mainframe"] = $mainframe;

	if (!JVersion::isCompatible("1.6")){
		JPluginHelper::importPlugin('system');
		$mainframe->triggerEvent('onAfterInitialise');
	}

	$params = JComponentHelper::getParams("com_rsvppro");

	include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");
	
	// Enforce referrer
	if ($params->get("testreferrer", 1))
	{
		if (!array_key_exists("HTTP_REFERER", $_SERVER))
		{
			throwerror("There was an error - no referrer information");
		}

		$live_site = $_SERVER['HTTP_HOST'];
		$ref_parts = parse_url($_SERVER["HTTP_REFERER"]);

		if (!isset($ref_parts["host"]) || $ref_parts["host"] != $live_site)
		{
			throwerror("There was an error - missing host in referrer");
		}
	}


	if (!isset($requestObject->ev_id) || $requestObject->ev_id == 0)
	{
		throwerror("There was an error");
	}

	$token = JSession::getFormToken();;
	if (!isset($requestObject->token) || $requestObject->token != $token)
	{
		throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$user = JFactory::getUser();
	if ($user->id == 0)
	{
		throwerror("There was an error");
	}

	$db = JFactory::getDBO();
	$db->setQuery("SELECT * FROM #__jevents_vevent where ev_id=" . intval($requestObject->ev_id));
	$event = $db->loadObject();
	if (!$event || ($event->created_by != $user->id && !JEVHelper::isAdminUser($user)))
	{
		throwerror("There was an error");
	}

	if ($requestObject->error)
	{
		return "Error";
	}
	if ($requestObject->task == "checkTitle" && isset($requestObject->title) && trim($requestObject->title) !== "")
	{
		$returnData->result = "title is " . $requestObject->title;
	}
	else if($requestObject->task != "checkCBFields")
	{
		throwerror("There was an error - no valid argument");
	}

	$db = JFactory::getDBO();

	$title = JFilterInput::getInstance()->clean($requestObject->title, "string");
	$text = $db->Quote('%' . $db->getEscaped($text, true) . '%', false);

	// Remove any dodgy characters from fields
	// Only allow a to z , 0 to 9, ', " space (\\040), hyphen (\\-), underscore (\\_)
	/*
	  $regex     = '/[^a-zA-Z0-9_\'\"\'\\40\\-\\_]/';
	  $title    = preg_replace($regex, "", $title);
	  $title = substr($title."    ",0,4);
	 */

	if ($requestObject->task == "checkTitle" && trim($title) == "" && trim($title) == "")
	{
		throwerror("There was an error - no valid argument");
	}

	if ($requestObject->task == "checkTitle" && strlen($title) < 2 && $title != "*")
	{
		$returnData->result = 0;
		return $returnData;
	}

	if (JVersion::isCompatible("1.6.0"))
	{
		$inviteusertype = $params->get("inviteusertype", array(1,2,3,4,5,6));
		$join = 'LEFT JOIN #__user_usergroup_map AS map2 ON map2.user_id = u.id';
		if ($inviteusertype)
		{
			if (is_array($inviteusertype)){
				JArrayHelper::toInteger($inviteusertype);
				$inviteusertype[] = -1;
				$inviteusertype = implode(",",$inviteusertype);
			}
			$where = ' map2.group_id IN ( ' . $inviteusertype .')';
		}
		else $where = '1';
	}
	else
	{
		$inviteusertype = $params->get("inviteusertype", 0);
		if (is_array($inviteusertype)){
			if (is_array($inviteusertype)){
				JArrayHelper::toInteger($inviteusertype);
				$inviteusertype[] = -1;
				$inviteusertype = implode(",",$inviteusertype);
			}
			$where = " u.gid IN(" . $inviteusertype.")";
		}
		else {
			if ($inviteusertype ==29 ){ // public frontend
				$inviteusertype = 18; // registered
			}
			else 	if ($inviteusertype ==30 ){ // public backend
				$inviteusertype = 23; // manager
			}

			$where = " u.gid>=" . intval($inviteusertype);
		}
		$join = "";
	}

	if (isset($requestObject->task))
	{
		if($requestObject->task == "checkTitle")
		{
			if ($title != "*")
			{
				$sql = "SELECT DISTINCT(username), name, id  FROM #__users as u $join WHERE $where AND (u.name LIKE " . $db->Quote($title . "%") . " OR u.username LIKE " . $db->Quote($title . "%") . "  OR email LIKE " . $db->Quote($title . "%") . ") order by name asc";
			}
			else
			{
				$sql = "SELECT DISTINCT(username), name, id  FROM #__users as u $join WHERE $where ";
			}
		}
		else if($requestObject->task == "checkCBFields")
		{
			// join CB table #__comprofiler 
			$join .= ' LEFT JOIN #__comprofiler AS cb_profiles ON cb_profiles.id = u.id';
			
			// form where query
			// this is array of column names from #__users table
			$usertable = array( 'name', 'username',	'email', 'usertype', 'block', 'sendEmail', 'registerDate', 'lastvisitDate' );
			
			// loop through fields
			foreach($requestObject->fields as $field)
			{
				// take name of the column if it is range request
				$exploded = explode('__', $field->name);
				$isdate = explode('date',strtolower($field->name));
				
				// check if the CB field is DATE range field (usually date range)
				if(count($exploded)>1 && count($isdate)>1)
				{		$returnData->exploded = $exploded;			
					// check if the field is empty
					if( !empty($field->value) )
					{
						// check if the field is from #__users table or #__comprofiler table and make WHERE clause 
						if( in_array($exploded[0], $usertable) )
						{
							$where .= " AND u.".$exploded[0]." BETWEEN '".$requestObject->fields[$exploded[0].'__minval']."' AND '".$requestObject->fields[$exploded[0].'__maxval']."';";
						}
						else 
						{
							$where .= " AND cb_profiles.".$exploded[0]." BETWEEN '".$requestObject->fields[$exploded[0].'__minval']."' AND '".$requestObject->fields[$exploded[0].'__maxval']."';";
						}
					}
				}
				else
				{
					// check if the field is empty
					if( !empty($field->value) )
					{
						// check if the field is from #__users table or #__comprofiler table and make WHERE clause
						if( in_array($field->name, $usertable) )
						{
							$where .= " AND u.".$field->name." = ".$db->Quote($field->value).";";
						}
						else
						{
							$where .= " AND cb_profiles.".$field->name." = ".$db->Quote($field->value).";";
						}
					}
				}
				
			}
			
			$sql = "SELECT DISTINCT(u.username), u.name, u.id  FROM #__users as u $join WHERE $where ";
			$returnData->sql = $sql;

		}
	}
	$db->setQuery($sql);
	$matches = $db->loadObjectList();
	if (count($matches) == 0)
	{
		$returnData->result = 0;
	}
	else
	{
		$returnData->result = count($matches);
		foreach ($matches as $match)
		{
			if (trim(strtolower($match->name)) == trim(strtolower($title)) || trim(strtolower($match->username)) == trim(strtolower($title)))
				$returnData->exactmatch = true;
			$returnData->titles[] = array("name" => $match->name, "username" => $match->username, "id" => $match->id);
		}
	}
	
	return $returnData;
}

function throwerror($msg)
{
	$data = new stdClass();
	$data->error = "alert('" . $msg . "')";
	$data->result = "ERROR";
	$data->user = "";

	header("Content-Type: application/x-javascript");
	// Must suppress any error messages
	@ob_end_clean();
	echo json_encode($data);
	exit();

}