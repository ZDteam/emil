<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevents.php 1402 2009-04-03 13:00:38Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */


defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

define ("RSVPPRO_DEV",1);

if (version_compare(phpversion(), '5.0.0', '<')===true) {
	echo  '<div style="font:12px/1.35em arial, helvetica, sans-serif;"><div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;"><h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">'.JText::_("RSVP_INVALID_PHP1").'</h3></div>'.JText::_("RSVP_INVALID_PHP2").'</div>';
	return;
}

if (version_compare(phpversion(), '5.2.0', '<')===true) {
	include_once(JPATH_SITE."/components/com_jevents/libraries/jsonwrapper.php");
}

jimport("joomla.filesystem.file");
jimport('joomla.filesystem.path');
$option = JRequest::getCmd("option"); 
include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR.'/'.str_replace("com_","",$option).".defines.php");
include_once(JEV_ADMINPATH.'/'.JEV_COMPONENT.".defines.php");

$registry	=& JRegistry::getInstance("jevents");
$jevparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
// See http://www.php.net/manual/en/timezones.php
$tz=$jevparams->get("icaltimezonelive","");
if ($tz!="" && is_callable("date_default_timezone_set")){
	$timezone= date_default_timezone_get();
	date_default_timezone_set($tz);
	$registry->set("jevents.timezone",$timezone);
}

$lang =& JFactory::getLanguage();
$lang->load(RSVP_COM_COMPONENT, JPATH_SITE);

// also load the plugin language file!
$lang->load( 'plg_jevents_jevrsvppro',JPATH_ADMINISTRATOR );

$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

// access control
$juser = JFactory::getUser();
$authorised = false;
if (JVersion::isCompatible("1.6.0"))
{
	if ($juser->authorise('core.manage', 'com_rsvppro'))
	{
		$authorised = true;
	}
}
else
{
	// >= admins 
	if ($juser->gid >= 24)
	{
		$authorised = true;
	}
}
if (!$authorised)
{
	return;
}

// disable Zend php4 compatability mode
@ini_set("zend.ze1_compatibility_mode","Off");

$cmd = JRequest::getCmd('task', 'cpanel.show');

if (strpos($cmd, '.') != false) {
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $cmd);

	// Define the controller name and path
	$controllerName	= strtolower($controllerName);
	$controllerPath	= JPATH_COMPONENT.'/'.'controllers'.'/'.$controllerName.'.php';
	$controllerName = "Admin".$controllerName;

	// If the controller file path exists, include it ... else lets die with a 500 error
	if (file_exists($controllerPath)) {
		require_once($controllerPath);
	} else {
		JError::raiseError(500, 'Invalid Controller');
	}
} else {
	// Base controller, just set the task
	$controllerName = null;
	$task = $cmd;
}

// Set the name for the controller and instantiate it
$controllerClass = ucfirst($controllerName).'Controller';
if (class_exists($controllerClass)) {
	$controller = new $controllerClass();

	// Perform the Request task
	$controller->execute($task);

	// Must reset the timezone back!!
	if ($tz && is_callable("date_default_timezone_set")){
		date_default_timezone_set($timezone);
	}
		
	// Redirect if set by the controller
	$controller->redirect();

} else {
	echo "missing controllerClass $controllerClass";
	exit();
}
