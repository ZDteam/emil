<?php

/**
 * @copyright	Copyright (C) 2011 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
 */
ini_set("display_errors", 0);

list($usec, $sec) = explode(" ", microtime());
define('_SC_START', ((float) $usec + (float) $sec));

// Set flag that this is a parent file
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);
$x = realpath(dirname(__FILE__) . DS . ".." . DS . ".." . DS);
if (!file_exists($x . '/' . "plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])));
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

	require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
	require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

	$requestObject = unserialize(REQUESTOBJECT);
	$returnData = unserialize(RETURNDATA);

	ini_set("display_errors", 0);

	global $mainframe;
	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client, array("site", "administrator")))
	{
		$client = $requestObject->client;
	}
	$mainframe = & JFactory::getApplication($client);
	$mainframe->initialise();

	

	JPluginHelper::importPlugin('system');
	$mainframe->triggerEvent('onAfterInitialise');

	$token = JSession::getFormToken();
	if (!isset($requestObject->token) || $requestObject->token != $token)
	{
		throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$user = JFactory::getUser();
	if ($user->id == 0)
	{
		throwerror("There was an error");
	}

	// We have the cats in 	$requestObject->cats; - use these to populate the database
	$db = JFactory::getDBO();

	if (!isset($requestObject->cats))
	{
		throwerror("There was an error - no valid argument");
	}

	$db = JFactory::getDBO();
	$cats = $requestObject->cats;
	JArrayHelper::toInteger($cats);

	$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_notification_map(
	id int(11) NOT NULL auto_increment,
	user_id int(12) NOT NULL  default 0,
	cat_id int(12) NOT NULL  default 0,

	PRIMARY KEY (id),
	INDEX  (user_id),
	INDEX  (cat_id),
	INDEX  (user_id, cat_id)
);
SQL;
	$db->setQuery($sql);
	$db->query();

	$db->setQuery("DELETE FROM #__jev_notification_map WHERE user_id=" . $user->id);
	$db->query();

	if (count($cats) > 0)
	{
		$inserts = array();
		foreach ($cats as $cat)
		{
			if ($cat > 0)
			{
				$inserts[] = "($user->id , $cat)";
			}
		}

		if (count($inserts) > 0)
		{
			$db->setQuery("INSERT INTO #__jev_notification_map (user_id, cat_id) VALUES ".implode(",",$inserts));
			$db->query();
		}
	}

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