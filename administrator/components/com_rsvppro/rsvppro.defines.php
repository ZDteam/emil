<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevents.defines.php 1417 2009-04-19 07:32:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined('_JEXEC') or die( 'No Direct Access' );

if (!defined("RSVP_COM_COMPONENT")){
	define("RSVP_COM_COMPONENT","com_rsvppro");
	define("RSVP_COMPONENT",str_replace("com_","",RSVP_COM_COMPONENT));
}

if (!defined("RSVP_LIBS")){
	define("RSVP_ADMINPATH",JPATH_ADMINISTRATOR."/components/".RSVP_COM_COMPONENT."/");
	define("RSVP_PATH",JPATH_SITE."/components/".RSVP_COM_COMPONENT."/");
	define("RSVP_LIBS",RSVP_PATH."libraries/");
	define("RSVP_ADMINLIBS",RSVP_ADMINPATH."libraries/");
	define("RSVP_TABLES",RSVP_ADMINPATH."tables/");
	define("RSVP_GATEWAYS",RSVP_ADMINPATH."gateways");
	define("RSVP_HELPERS",RSVP_ADMINPATH."helpers/");
	define("RSVP_CONFIG",RSVP_ADMINPATH."config/");
	define("RSVP_FILTERS",RSVP_ADMINPATH."filters/");
	define("RSVP_LAYOUTS",RSVP_ADMINPATH."layouts/");
	define("RSVP_VIEWS",RSVP_ADMINPATH."views");
}

if (!defined("JEV_COM_COMPONENT")){
	define("JEV_COM_COMPONENT","com_jevents");
	define("JEV_COMPONENT",str_replace("com_","",JEV_COM_COMPONENT));
}

include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");

if (!defined("JEV_LIBS")){
	define("JEV_ADMINPATH",JPATH_ADMINISTRATOR."/components/".JEV_COM_COMPONENT."/");
	define("JEV_PATH",JPATH_SITE."/components/".JEV_COM_COMPONENT."/");
	define("JEV_LIBS",JEV_PATH."libraries/");
	define("JEV_ADMINLIBS",JEV_ADMINPATH."libraries/");
	define("JEV_HELPERS",JEV_ADMINPATH."helpers/");
	define("JEV_CONFIG",JEV_ADMINPATH."config/");
	define("JEV_FILTERS",JEV_ADMINPATH."filters/");
	define("JEV_LAYOUTS",JEV_ADMINPATH."layouts/");
	define("JEV_VIEWS",JEV_ADMINPATH."views");
}

JLoader::register('JevRsvpParameter',RSVP_ADMINLIBS."jevrsvpparameter.php");


JLoader::register('JSite' , JPATH_SITE.'/includes/application.php');

JLoader::register('RSVPConfig',RSVP_ADMINPATH."config.php");
JLoader::register('RSVPAbstractView',RSVP_VIEWS."/abstract/abstract.php");
JLoader::register('RSVP_Helper',RSVP_ADMINLIBS."helper.php");

JLoader::register('RsvpproHelper',RSVP_ADMINPATH."helpers/rsvppro.php");

JLoader::register('JEVConfig',JEV_ADMINPATH."libraries/config.php");
JLoader::register('JEventsDBModel',JEV_PATH."libraries/dbmodel.php");
JLoader::register('JEventsDataModel',JEV_PATH."libraries/datamodel.php");
JLoader::register('JEVAccess',JEV_PATH."libraries/access.php");
JLoader::register('JEVHelper',JEV_PATH."libraries/helper.php");
JLoader::register('JEventsHTML',JEV_PATH."/libraries/jeventshtml.php");

JLoader::register('jEventCal',JEV_PATH."libraries/jeventcal.php");
JLoader::register('jIcal',JEV_PATH."libraries/jical.php");
JLoader::register('jIcalEventDB',JEV_PATH."libraries/jicaleventdb.php");
JLoader::register('jIcalEventRepeat',JEV_PATH."libraries/jicaleventrepeat.php");
JLoader::register('JevModuleHelper',JEV_PATH."/libraries/jevmodulehelper.php"); 

JLoader::register('JEV_CommonFunctions',JEV_PATH."libraries/commonfunctions.php");
JLoader::register('JevDate',JEV_PATH."/libraries/jevdate.php");

JLoader::register('RsvpHelper',RSVP_ADMINLIBS."rsvphelper.php");
JLoader::register('JevTemplateHelper',RSVP_ADMINLIBS."templatehelper.php");

JLoader::register('rsvpAccount',RSVP_TABLES."account.php");
JLoader::register('rsvpTransaction',RSVP_TABLES."transaction.php");

JLoader::register('JEventsVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");
$jevversion = JEventsVersion::getInstance();
if ($jevversion->get("RELEASE") < 2)
{
	JLoader::register('JevDate', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevdate.php");	
}
else
{
	JLoader::register('JevDate', JPATH_SITE . "/components/com_jevents/libraries/jevdate.php");
}
