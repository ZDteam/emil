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

	$Itemid = JRequest::getInt("Itemid");
	$option = "com_jevents";
	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client,array("site","administrator"))){
		$client = $requestObject->client;
	}
	global $mainframe;
	$mainframe = JFactory::getApplication($client);
	$mainframe->initialise();

	if (!isset($requestObject->rpid) || $requestObject->rpid==0){
		throwerror("There was an error");
	}

	$user = JFactory::getUser();
	if ($user->id==0 || $user->id!=$requestObject->userid){
		throwerror("There was an error");
	}

	include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");

	$datamodel =new JEventsDataModel();
	$event = $datamodel->queryModel->listEventsById( $requestObject->rpid, 0,"icaldb");

	if (!$event || $event->rp_id()!= $requestObject->rpid)  throwerror("There was an error");

	if (!JEVHelper::canEditEvent($event)){
		throwerror("There was an error");
	}

	$capacities = $requestObject->capacities;
	$waiting = $requestObject->waiting;

	$pairs = array();
	$pairs[-1]=array("label"=>JFilterInput::getInstance()->clean($capacities[0]->elval,"string"),"value"=>intval($capacities[1]->elval) ,"waiting"=>intval($capacities[2]->elval));
	for ($v=0;$v<count($capacities)-3;$v+=3){
		$pairs[$v/3]=array("label"=>JFilterInput::getInstance()->clean($capacities[$v+3]->elval,"string"),"value"=>intval($capacities[$v+4]->elval),"waiting"=>intval($capacities[$v+5]->elval));
	}
	
	// does the record exist already?
	$db = JFactory::getDBO();
	$sql = "SELECT * FROM #__jev_attendance  where id=".intval($requestObject->atid)." and ev_id=".$event->ev_id();
	$db->setQuery($sql);
	$rsvpdata = $db->loadObject();
		
	if (!$rsvpdata){
		throwerror("There was an error - no such record");
	}
	
	$fieldname = $requestObject->fieldname[0]->fieldname;
	$fieldlabel = $requestObject->fieldname[0]->label;
	$params = new JRegistry(null);
	$params->set("fieldname_".$fieldname,$fieldlabel);
	
	foreach ($pairs as $index=>$pair){
		if ($pair["label"]!=""){
			$params->set("capacity_".$fieldname."_".$index,$pair["value"]);
			$params->set("label_".$fieldname."_".$index,$pair["label"]);
			$params->set("waiting_".$fieldname."_".$index,$pair["waiting"]);
			//$params->set("price".$index,$pair[$value]);
		}
		
	}
	
	$sql = "UPDATE #__jev_attendance SET params=".$db->Quote($params->toString()). "  where id=".intval($requestObject->atid)." and ev_id=".$event->ev_id();
	$db->setQuery($sql);
	$success = $db->query();

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