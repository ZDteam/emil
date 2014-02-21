<?php
/**
* @copyright	Copyright (C) 2008 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
*/
ini_set("display_errors",0);

list($usec, $sec) = explode(" ", microtime());
define('_SC_START', ((float)$usec + (float)$sec));

// Set flag that this is a parent file
define( '_JEXEC', 1 );
define( ''/'', DIRECTORY_SEPARATOR );
$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/') ;
if (!file_exists($x.'/'."plugins")){
	$x = realpath(dirname(__FILE__).'/' ."..".'/'. "..".'/'. "..".'/'. "..".'/' ) ;
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

	ini_set("display_errors",0);

	global  $option, $mainframe;
	$option = "com_jevents";
	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client,array("site","administrator"))){
		$client = $requestObject->client;
	}
	$mainframe = JFactory::getApplication($client);
	$mainframe->initialise();

	if (!isset($requestObject->detailid) || $requestObject->detailid==0){
		throwerror("There was an error");
	}

	$user = JFactory::getUser();
	if ($user->id==0 || $user->id!=$requestObject->userid){
		throwerror("There was an error");
	}

	// does the role exist and is this person able to fill it?
	$detailid = intval($requestObject->detailid);
	$typeid= intval($requestObject->typeid);
	$userid= intval($requestObject->userid);
	
	$db = JFactory::getDBO();
	$db->setQuery("select pma.maxallocation,count(jpm.pers_id) as pcount , max(linktouser) as linktouser,  pt.* from #__jev_peopleeventsmaxallocation as pma
LEFT JOIN #__jev_peopletypes as pt on pma.type_id=pt.type_id AND pt.selfallocate=1 
LEFT JOIN #__jev_people as pp on pp.type_id = pt.type_id
LEFT JOIN #__jev_peopleeventsmap as jpm on jpm.pers_id=pp.pers_id and jpm.evdet_id=$detailid
where pma.evdet_id=$detailid and pma.type_id=$typeid 
GROUP BY pma.type_id");
	$currentallocation = $db->loadObject();
	
	if (!$currentallocation || $currentallocation->maxallocation-$currentallocation->pcount <=0){
		throwerror("No availability");
	}
	
	// make sure this user is not already allocated for this role
	$db->setQuery("SELECT pp.* from #__jev_people as pp 
		LEFT JOIN #__jev_peopleeventsmap as jpm on jpm.pers_id=pp.pers_id
		WHERE pp.linktouser=$userid  and jpm.evdet_id=$detailid AND pp.type_id=$typeid");
	$role = $db->loadObject();
	if ($role){
		throwerror("Already assigned");
	}

	$db->setQuery("SELECT * FROM #__jev_people WHERE type_id=$typeid AND linktouser=$user->id");
	$matchedPerson = $db->loadObject();
	
	include_once (JPATH_ADMINISTRATOR."/components/com_jevpeople/tables/person.php");
	if (!$matchedPerson  && $currentallocation->allowedgroups!=""){
		// is this user qualified for this role
		$usergroups = $user->getAuthorisedGroups();
		$allowedgroups = json_decode( $currentallocation->allowedgroups);
		if (count(array_intersect($allowedgroups,$usergroups))==0){
			throwerror("Not qualified");				
		}
		
		// create the managed person on the fly
		$person  = new TablePerson();
		$person->title = $user->name. " (".$user->username.")";
		$person->linktouser = $user->id;
		$person->type_id= $typeid;
		// sets the alias etc.
		$person->check();

		$person->store();
	}
	// Assign the managed person to the role!
	if (($matchedPerson && $matchedPerson->linktouser > 0 ) || ($person && $person->pers_id>0)){
		$db->setQuery("SELECT * FROM #__jev_people WHERE type_id=$typeid AND linktouser=$user->id");
		$person = $db->loadObject();
		if ($person){
			$db->setQuery("REPLACE INTO #__jev_peopleeventsmap (pers_id, evdet_id) VALUES (".$person->pers_id.", $detailid )");
			if (!$db->query()){
				throwerror("Failed to assign role");	
			}
		}
		else {
			throwerror("Failed to assign role");	
		}
	}
	else {
		throwerror("Failed to assign role");	
	}
	$success = true;
	
	if (!$success) $returnData->error = 1;
	
	$returnData->result = $success;

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