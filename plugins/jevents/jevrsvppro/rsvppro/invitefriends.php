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

$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/') ;
if (!file_exists($x.'/'."plugins")){
	$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/'. "..".'/' ) ;
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

	require_once JPATH_SITE.'/components/com_jevents/jevents.defines.php';
	
	$requestObject = unserialize(REQUESTOBJECT);
	$returnData = unserialize(RETURNDATA);

	$returnData->titles	= array();
	$returnData->exactmatch=false;

	ini_set("display_errors",0);

	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client,array("site","administrator"))){
		$client = $requestObject->client;
	}
	$mainframe =& JFactory::getApplication($client);
	$mainframe->initialise();

	if (!isset($requestObject->ev_id) || $requestObject->ev_id==0){
		throwerror("There was an error");
	}

	$token = JSession::getFormToken();;
	if (!isset($requestObject->token)  || $requestObject->token!=$token){
		throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$user = JFactory::getUser();
	if ($user->id==0){
		throwerror("There was an error");
	}

	$db=JFactory::getDBO();
	$db->setQuery("SELECT * FROM #__jevents_vevent where ev_id=".intval($requestObject->ev_id));
	$event = $db->loadObject();
	if (!$event || ($event->created_by!=$user->id && ! JEVHelper::isAdminUser($user))){
		throwerror("There was an error");
	}

	if ($requestObject->error){
		return "Error";
	}

	if (!isset($requestObject->task) || ($requestObject->task!=="inviteFriends" && $requestObject->task!=="inviteJSGroup" && $requestObject->task!=="inviteJUGroup"  && $requestObject->task!=="inviteCBGroup" && $requestObject->task!=="inviteList")){
		throwerror("There was an error");
	}

	$params = JComponentHelper::getParams("com_rsvppro");
	$inviteusertype = $params->get("inviteusertype",0);
	if (JVersion::isCompatible("1.6.0"))
	{
		$join = 'LEFT JOIN #__user_usergroup_map AS map2 ON map2.user_id = ju.id';
		if ($inviteusertype)
		{
			if (is_array($inviteusertype)){
				JArrayHelper::toInteger($inviteusertype);
				$inviteusertype[] = -1;
				$inviteusertype = implode(",",$inviteusertype);
			}
			$where = ' AND map2.group_id IN ( ' . $inviteusertype .')';
		}
	}
	else
	{
		$where = " AND ju.gid>=" . intval($inviteusertype);
		$join = "";
	}

	if ($requestObject->task=="inviteFriends" ) {
		$sql = "SELECT ju.username, ju.name, ju.id  FROM #__users as ju "
		.$join
		." LEFT JOIN #__community_connection as jcc on jcc.connect_to=ju.id AND jcc.status=1 AND jcc.group=0"
		." WHERE jcc.connect_from=".intval($user->id)
		. $where
		." order by name asc" ;
		$db->setQuery($sql);
		$matches = $db->loadObjectList();
	}
	else if ($requestObject->task=="inviteJSGroup" ) {
		if (!isset($requestObject->groupid)) {
			throwerror("There was an error");
		}
		$groupid = intval(str_replace("invitationgroup_","",$requestObject->groupid));

		$sql = "SELECT ju.username, ju.name, ju.id  FROM #__users as ju " 
		. $join
		." LEFT JOIN #__community_groups_members as jcgm on jcgm.memberid=ju.id AND jcgm.approved=1 "
		." LEFT JOIN #__community_groups as jcg on jcgm.groupid=jcg.id"
		." WHERE jcgm.groupid=".$groupid." AND (jcg.published=1 OR (jcgm.approved=1 AND jcgm.memberid=".$user->id."))"
		. $where
		." order by name asc" ;
		$db->setQuery($sql);
		$matches = $db->loadObjectList();
	}
	else if ($requestObject->task=="inviteJUGroup" ) {
		if (!isset($requestObject->groupid)) {
			throwerror("There was an error");
		}
		$groupid = intval(str_replace("invitationgroup_","",$requestObject->groupid));

		$sql = "SELECT ju.username, ju.name, ju.id  FROM #__users as ju " 
		. $join
		." LEFT JOIN #__user_usergroup_map as ugm on ugm.user_id=ju.id  "
		." WHERE ugm.group_id=".$groupid
		. $where
		." order by name asc" ;
		$db->setQuery($sql);
		$matches = $db->loadObjectList();
	}
	else if ($requestObject->task=="inviteList" ) {
		if (!isset($requestObject->listid)) {
			throwerror("There was an error");
		}
		$listid = intval(str_replace("invitationlist_","",$requestObject->listid));

		$sql = "SELECT ju.username, ju.name, ju.id  FROM #__users as ju "
		.$join
		." LEFT JOIN #__jev_invitelist_member as ilm on ilm.user_id=ju.id"
		." LEFT JOIN #__jev_invitelist as il on ilm.list_id=il.id"
		." WHERE ilm.list_id=".$listid ." AND il.user_id=".$user->id
		. $where
		." order by name asc" ;
		$db->setQuery($sql);
		$matches = $db->loadObjectList();
		
		// and get the email based entries too 
		$sql = "SELECT ilm.email_name as name, email_address as username, 0 as id FROM #__jev_invitelist_member as ilm "
		." LEFT JOIN #__jev_invitelist as il on ilm.list_id=il.id"
		." WHERE ilm.list_id=".$listid ." AND il.user_id=".$user->id." AND email_address <> ''"
		." order by ilm.email_name asc" ;
		$db->setQuery($sql);
		$emailmatches = $db->loadObjectList();
		/*
		if (count($emailmatches)>0)
		{
			foreach ($emailmatches as &$match){
				$match->name =  $match->email_name;
				$match->username =  $match->email_address;
				$match->id  =  $match->email_name."(". $match->email_address.")";
			}
			unset ($match);
		}
		*/
		$matches = array_merge($matches,$emailmatches);
	}
	else if ($requestObject->task=="inviteCBGroup" ) {
		if (!isset($requestObject->groupid)) {
			throwerror("There was an error");
		}
		$groupid = intval(str_replace("invitationgroup_","",$requestObject->groupid));

		$sql = "SELECT ju.username, ju.name, ju.id  FROM #__users as ju " 
		. $join
		." LEFT JOIN #__groupjive_users as jgu on jgu.user_id=ju.id AND jgu.status>0 "
		." WHERE jgu.group=".$groupid." AND jgu.status>0"
		. $where
		." order by name asc" ;
		$db->setQuery($sql);
		$matches = $db->loadObjectList();
	}
	if (count($matches)==0){
		$returnData->result = 0;
	}
	else {
		$returnData->result = count($matches);
		foreach ($matches as $match) {
			$row = array("name"=>$match->name,"username"=>$match->username,"id"=>$match->id);			
			$returnData->titles[] = $row; 
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