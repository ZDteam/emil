<?php
/**
* @copyright	Copyright (C) 2008 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
*/
ini_set("display_errors",0);

if (version_compare(phpversion(), '5.2.0', '<')===true) {
	include_once(JPATH_SITE."/components/com_jevents/libraries/jsonwrapper.php");
}

list($usec, $sec) = explode(" ", microtime());
define('_SC_START', ((float)$usec + (float)$sec));

// Set flag that this is a parent file
define( '_JEXEC', 1 );

$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/'. "..".'/') ;
if (!file_exists($x.'/'."plugins".'/'."jevents")){
	$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/'."..".'/'. "..".'/') ;
}
if (!file_exists($x . '/' . "plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}
define( 'JPATH_BASE', $x );

// create the mainframe object
$_REQUEST['tmpl'] = 'component';

// Create JSON data structure
$data = new stdClass();
$data->error = 0;
$data->result = "ERROR";
$data->user = "";

// Enforce referrer
if (!array_key_exists("HTTP_REFERER",$_SERVER) ){
	throwerror("There was an error");
}

$live_site = $_SERVER['HTTP_HOST'];
$ref_parts = parse_url($_SERVER["HTTP_REFERER"]);

if (!isset($ref_parts["host"]) || $ref_parts["host"] != $live_site ){
	throwerror("There was an error - missing host in referrer");
}


// Get JSON data
if (!array_key_exists("json",$_REQUEST)){
	throwerror("There was an error - no request data");
}
else {
	$requestData = $_REQUEST["json"];

	if (isset($requestData)){
		try {
			if (ini_get("magic_quotes_gpc")){
				$requestData= stripslashes($requestData);
			}

			$requestObject = json_decode($requestData, 0);
			if (!$requestObject){
				$requestObject = json_decode(utf8_encode($requestData), 0);
			}
		}
		catch (Exception $e) {
			throwerror("There was an exception");
		}

		if (!$requestObject){
			file_put_contents(dirname(__FILE__)."/cache/error.txt", var_export($requestData,true));
			throwerror("There was an error - no request object ");
		}
		else if ($requestObject->error){
			throwerror("There was an error - Request object error ".$requestObject->error);
		}
		else {

			try {
				$data = ProcessRequest($requestObject, $data);
			}
			catch (Exception $e){
				throwerror("There was an exception ".$e->getMessage());
			}
		}
	}
	else {
		throwerror("Invalid Input");
	}
}

header("Content-Type: application/x-javascript; charset=utf-8");

list ($usec,$sec) = explode(" ", microtime());
$time_end = (float)$usec + (float)$sec;
$data->timing = round($time_end - _SC_START,4);

// Must suppress any error messages
@ob_end_clean();
echo json_encode($data);

function ProcessRequest(&$requestObject, $returnData){

	define("REQUESTOBJECT",serialize($requestObject));
	define("RETURNDATA",serialize($returnData));

	require_once JPATH_BASE.'/'.'includes'.'/'.'defines.php';
	require_once JPATH_BASE.'/'.'includes'.'/'.'framework.php';

	$requestObject = unserialize(REQUESTOBJECT);
	$returnData = unserialize(RETURNDATA);

	$returnData->titles	= array();
	$returnData->exactmatch=false;

	ini_set("display_errors",0);

	//$mainframe = JFactory::getApplication();
	if (!isset($requestObject->client)){
		throwerror("No Client");
	}
	$mainframe =& JFactory::getApplication($requestObject->client?'administrator':'site');
	$mainframe->initialise();

	$token = JSession::getFormToken();;
	if (!isset($requestObject->token)  || $requestObject->token!=$token){
		throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$user = JFactory::getUser();
	if ($user->id==0){
		throwerror("There was an error");
	}

	$access = false;
	if ($user->get('id')>0){
		if (JVersion::isCompatible("1.6.0")) {
			$access = $user->authorise('core.deleteall', 'com_jevents');
		}
		else {
			// does this logged in have backend access
			// Get an ACL object
			$acl =& JFactory::getACL();
			$grp = $acl->getAroGroup($user->get('id'));
			// if no valid group (e.g. anon user) then skip this.
			if (!$grp) return;

			$access = $acl->is_group_child_of($grp->name, 'Public Backend');
		}
	}

	$db = JFactory::getDBO();
	$db->setQuery("SELECT * FROM #__jevents_vevent where ev_id=" . intval($requestObject->ev_id));
	$event = $db->loadObject();
	if ($event && ($event->created_by == $user->get('id') || JEVHelper::isAdminUser($user)))
	{
 		$access=true;
	}
	
	if (!$access){
		throwerror("There was an error - no access");
	}

	if ($requestObject->error){
		return "Error";
	}
	if (isset($requestObject->title) && trim($requestObject->title)!==""){
		$returnData->result = "title is ".$requestObject->title;
	}
	else {
		throwerror ( "There was an error - no valid argument");
	}

	$db = JFactory::getDBO();

	$title = JFilterInput::getInstance()->clean($requestObject->title,"string");
	$text  = $db->Quote( $db->getEscaped( $title, true ).'%', false );

	// Remove any dodgy characters from fields
	// Only allow a to z , 0 to 9, ', " space (\\040), hyphen (\\-), underscore (\\_)
	/*
	$regex     = '/[^a-zA-Z0-9_\'\"\'\\40\\-\\_]/';
	$title    = preg_replace($regex, "", $title);
	$title = substr($title."    ",0,4);
	*/

	if (trim($title)=="" && trim($title)==""){
		throwerror ( "There was an error - no valid argument");
	}

	if (strlen($title)<2 && $title!="*"){
		$returnData->result = 0;
		return $returnData;
	}
	if (isset($requestObject->task) && $requestObject->task=="checkTitle"){
		if ($title!="*"){
			$sql = "SELECT username, name, id  FROM #__users WHERE name LIKE ".$text." OR username LIKE ".$text."  OR email LIKE ".$text." order by name asc" ;
		}
		else {
			$sql = "SELECT username, name, id  FROM #__users " ;
		}
	}
	$db->setQuery($sql);
	$matches = $db->loadObjectList();
	if (count($matches)==0){
		$returnData->result = 0;
	}
	else {
		$returnData->result = count($matches);
		foreach ($matches as $match) {
			if (trim(strtolower($match->name))==trim(strtolower($title)) || trim(strtolower($match->username))==trim(strtolower($title)))	$returnData->exactmatch=true;
			$returnData->titles[] = array("name"=>$match->name,"username"=>$match->username,"id"=>$match->id);
		}
	}

	return $returnData;
}



function throwerror ($msg){
	$data = new stdClass();
	//"document.getElementById('products').innerHTML='There was an error - no valid argument'");
	$data->error = "alert('".$msg."')";
	$data->result = "ERROR";
	$data->user = "";

	header("Content-Type: application/x-javascript");
	// Must suppress any error messages
	@ob_end_clean();
	echo json_encode($data);
	exit();
}