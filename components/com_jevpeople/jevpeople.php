<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

//error_reporting(E_ALL);

jimport('joomla.filesystem.path');

if (!defined("JEVEX_COM_COMPONENT")){
	define("JEVEX_COM_COMPONENT","com_jevpeople");
	define("JEVEX_COMPONENT",str_replace("com_","",JEVEX_COM_COMPONENT));
}
if (!defined("JEV_COM_COMPONENT")){
	define("JEV_COM_COMPONENT","com_jevents");
	define("JEV_COMPONENT",str_replace("com_","",JEV_COM_COMPONENT));
}
JLoader::register('JEVHelper',JPATH_SITE."/components/com_jevents/libraries/helper.php");
JLoader::register('JevPeopleHelper',JPATH_ADMINISTRATOR."/components/com_jevpeople/libraries/helper.php");
JLoader::register('JevCfForm',JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/jevcfform.php");

// load admin language too
$lang 		=& JFactory::getLanguage();		
$lang->load("com_jevpeople", JPATH_ADMINISTRATOR);

// In Joomla 1.6 JComponentHelper::getParams(JEV_COM_COMPONENT) is a clone so the menu params do not propagate so we force this here!
if (JVersion::isCompatible("1.6.0")){
	$newparams	= JFactory::getApplication('site')->getParams();
	// Because the application sets a default page title,
	// we need to get it from the menu item itself
	$menu = JFactory::getApplication()->getMenu()->getActive();
	if ($menu) {
		$newparams->def('page_heading', $newparams->get('page_title', $menu->title));
	}
	else {
		$params = JComponentHelper::getParams(JEVEX_COM_COMPONENT);
		$newparams->def('page_heading', $params->get('page_title')) ;
	}
	$component =& JComponentHelper::getComponent(JEVEX_COM_COMPONENT);
	$component->params =& $newparams;
}
$params = JComponentHelper::getParams(JEVEX_COM_COMPONENT);

$cmd = JRequest::getCmd('task', 'people.people');
$view = JRequest::getCmd('view', 'people');
$layout = JRequest::getCmd('layout', '');

if (strpos($cmd, '.') != false) {
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $cmd);
	
	// Define the controller name and path
	$controllerName	= strtolower($controllerName);
	$controllerPath	= JPATH_COMPONENT."/".'controllers'."/".$controllerName.'.php';
	$controllerName = "Front".$controllerName;
	
	// If the controller file path exists, include it ... else lets die with a 500 error
	if (file_exists($controllerPath)) {
		require_once($controllerPath);
	} else {
		JError::raiseError(500, 'Invalid Controller '.$controllerName);
	}
} else {
	// Base controller, just set the task 
	$controllerName = $view;
	$task = $layout;
}

// Set the name for the controller and instantiate it
$controllerClass = ucfirst($controllerName).'Controller';
if (class_exists($controllerClass)) {
	$controller = new $controllerClass();
} else {
	JError::raiseError(500, 'Invalid Controller Class - '.$controllerClass.(file_exists($controllerPath)?" Exists":" doesnt Exist" ));
}

$config	=& JFactory::getConfig();

// Perform the Request task
$controller->execute($task);

// Set the browser title to include site name if required
$title =  JFactory::getDocument()->GetTitle();
$app = JFactory::getApplication();
if (empty($title)) {
	$title = $app->getCfg('sitename');
}
elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
	$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
}
elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
	$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
}
JFactory::getDocument()->SetTitle($title);

// Redirect if set by the controller
$controller->redirect();
