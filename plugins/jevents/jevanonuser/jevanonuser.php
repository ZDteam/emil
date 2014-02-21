<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: mod.defines.php 1400 2009-03-30 08:45:17Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgJEventsJevanonuser extends JPlugin
{
	private $recaptchalang = "en";

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		JPlugin::loadLanguage( 'plg_jevents_jevanonuser',JPATH_ADMINISTRATOR );

		$lang = JFactory::getLanguage();

		list ($tag1,$tag2) = explode("-",$lang->getTag());
		// See http://recaptcha.net/apidocs/captcha/client.html for list of supported languages
		$langs = array("en","nl","fr","de","pt","ru","es","tr");

		if (in_array($tag1,$langs)){
			$this->recaptchalang = $tag1;
		}
	}

	// This enable anon users to create (but not edit events)
	function isEventCreator(&$isEventCreator){

		$user = JFactory::getUser();
		// if logged in then do not change isEventCreator
		if ($user->id>0 &&  !$this->params->get("allusers",0) )  return true;

		if (!JVersion::isCompatible("1.6")){
			$document	=& JFactory::getDocument();
			$document->addScript( JURI::root(true).'/includes/js/joomla.javascript.js');
		}

		if (!$this->params->get("allusers",0)) $isEventCreator = true;
		return true;
	}

	// This enable anon users to create (but not edit events)
	function isEventPublisher($type, &$isEventPublisher){

		$user = JFactory::getUser();
		// if logged in then do not change isEventPublisher
		if ($user->id>0 || $type=="strict") return true;

		$isEventPublisher = $this->params->get("canpublishown",0);

	}


	/**
	 * Custom part of form for re-captcha and name/email
	 *
	 * @param unknown_type $row
	 * @param unknown_type $customfields
	 * @return unknown
	 */
	function onEditCustom( &$row, &$customfields )
	{

		$user = JFactory::getUser();

		if ($user->id>0 && $row->ev_id()==0 && !$this->params->get("allusers",0)) return true;

		if ($user->id==0 && $row->ev_id()>0 && $row->created_by()>0) return true;

		// Only setup when editing an event (they should not be able to edit a repeat !!!)
		if (JRequest::getString("jevtask","")!="icalevent.edit") return;

		$anonname=false;
		$anonemail=false;
		if ($row->ev_id()>0){
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=".intval($row->ev_id()));
			$anonrow = $db->loadObject();
			if ($anonrow){
				$anonname=$anonrow->name;
				$anonemail=$anonrow->email;
			}
		}
		
		if ($user->id==0){
			$label = JText::_("JEV_ANON_NAME");
			$input	= '<input size="50" type="text" name="custom_anonusername" id="custom_anonusername" value="'.$anonname.'" />';
			$customfield = array("label"=>$label,"input"=>$input);
			$customfields["anonusername"]=$customfield;

			$label = JText::_("JEV_ANON_EMAIL");
			$input	= '<input size="50" type="text" name="custom_anonemail" id="custom_anonemail" value="'.$anonemail.'" />';
			$customfield = array("label"=>$label,"input"=>$input);
			$customfields["anonemail"]=$customfield;
		}
		else {
			// otherwise show the creator details
			$label = JText::_("JEV_CREATOR_NAME");
			$input	= '<span style="font-decoration:italic">'.$anonname.'</span>';
			$input	= '<input size="50" type="text" name="custom_anonusername" id="custom_anonusername" value="'.$anonname.'" />';
			$customfield = array("label"=>$label,"input"=>$input);
			$customfields["anonusername"]=$customfield;

			$label = JText::_("JEV_CREATOR_EMAIL");
			$input	= '<span style="font-decoration:italic">'.$anonemail.'</span>';
			$input	= '<input size="50" type="text" name="custom_anonemail" id="custom_anonemail" value="'.$anonemail.'" />';
			$customfield = array("label"=>$label,"input"=>$input);
			$customfields["anonemail"]=$customfield;			
		}
		if ($user->id==0 && $this->params->get("recaptchapublic",false)){

			if (JVersion::isCompatible("1.6.0")) {
				JevHelper::script("recaptcha16.js","plugins/jevents/jevanonuser/anonuserlib/",true);
			}
			else {
				JevHelper::script("recaptcha.js","plugins/jevents/anonuserlib/",true);
			}

			$label = JText::_("JEV_ANON_RECAPTCHA");
			if (!defined("RECAPTCHA_API_SERVER"))	require_once(dirname(__FILE__). '/anonuserlib/recaptcha.php');
			$input	= recaptcha_get_html($this->params->get("recaptchapublic",false));
			$customfield = array("label"=>$label,"input"=>$input);
			$customfields["recaptcha"]=$customfield;

			if (JVersion::isCompatible("1.6.0")) {
				$root = JURI::root()."plugins/jevents/jevanonuser/anonuserlib/";
			}
			else {
				$root = JURI::root()."plugins/jevents/anonuserlib/";
			}
			$token = JSession::getFormToken();
			$missingnameemail = JText::_("JEV_MISSING_NAME_OR_EMAIL",true);

			$checkscript = <<<SCRIPT
	anonurlroot = '$root';
var RecaptchaOptions = {
   theme : 'clean',
   lang : '$this->recaptchalang'
};
var missingnameoremail = '$missingnameemail';
SCRIPT;
			$document=& JFactory::getDocument();
			$document->addScriptDeclaration($checkscript);

			$mainframe= JFactory::getApplication();
			$mainframe->setUserState("jevrecaptcha","error");

		}
		else if ($user->id==0) {
			if (JVersion::isCompatible("1.6.0")) {
				JevHelper::script("anonevent.js","plugins/jevents/jevanonuser/anonuserlib/",true);
			}
			else {
				JevHelper::script("anonevent.js","plugins/jevents/anonuserlib/",true);
			}
			$missingnameemail = JText::_("JEV_MISSING_NAME_OR_EMAIL",true);
			$checkscript = <<<SCRIPT
var missingnameoremail = '$missingnameemail';
SCRIPT;
			$document=& JFactory::getDocument();
			$document->addScriptDeclaration($checkscript);
			
		}


return true;
}

function onBeforeSaveEvent(&$array, &$rrule){
	$user = JFactory::getUser();

	if ($user->id>0) return true;

	// make sure self publishing respects plugin settings and also set access level to public
	if (JVersion::isCompatible("1.6.0")) {
		$array["access"]=1;
	}
	else {
		$array["access"]=0;
	}

	if (!$this->params->get("canpublishown",0)){
		$params =& JComponentHelper::getParams(JEV_COM_COMPONENT);
		$params->set("jevpublishown",0);
	}

}

/**
	 * Store custom fields at event level
	 *
	 */
function onStoreCustomEvent($event){

	$user = JFactory::getUser();
	if ($user->id>0 && $event->ev_id==0) return true;

	// do I need to reset the created_by field to 0;
	$name = JRequest::getString("custom_anonusername","");
	$email = JRequest::getString("custom_anonemail","");

	if ($event->ev_id>0 && ($name || $email)){
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=".intval($event->ev_id));
		$anonrow = $db->loadObject();

		if ($anonrow) {
			$db->setQuery("UPDATE #__jevents_vevent SET created_by=0 WHERE ev_id=".intval($event->ev_id));
			$db->query();

			$event->created_by = 0;
			// place private reference to created_by in event detail in case needed by plugins
			$event->_detail->_created_by = $event->created_by ;

			$creator = new JTableAnonCreator($db);
			$creator->id = $anonrow->id;
			$creator->name = $name;
			$creator->email = $email;
			$creator->store();
			return true;
		}
	}

	if ($event->ev_id>0 && $event->created_by>0) return true;

	$eventid = $event->ev_id;

	if ($this->params->get("recaptchaprivate",false)){

		$mainframe= JFactory::getApplication();
		$jevrecaptcha = $mainframe->getUserState("jevrecaptcha");

		$name = JRequest::getString("custom_anonusername","");
		$email = JRequest::getString("custom_anonemail","");

		if ($jevrecaptcha == "ok" && $name!="" && $email!="")  {
			$mainframe->setUserState("jevrecaptcha","error");

			// Store the name and email address with the event
			$db = JFactory::getDBO();
			$creator = new JTableAnonCreator($db);
			$creator->id = 0;
			$creator->email = $email;
			$creator->name = $name;
			$creator->ev_id = $eventid;
			$creator->store();
			return true;
		}
		// Belt and braces
		$label = JText::_("JEV_ANON_RECAPTCHA");
		if (!defined("RECAPTCHA_API_SERVER"))	require_once('anonuserlib/recaptcha.php');
		$response = recaptcha_check_answer($this->params->get("recaptchaprivate",false),JRequest::getString("REMOTE_ADDR","","server"), JRequest::getString("recaptcha_challenge_field"),JRequest::getString("recaptcha_response_field"));
		if (!$response->is_valid){

			// The event has already been saved - I need to delete it!
			$db = JFactory::getDBO();

			$query = "SELECT detail_id FROM #__jevents_vevent WHERE ev_id IN ($eventid)";
			$db->setQuery( $query);
			$detailidstring = $db->loadResult();

			$query = "DELETE FROM #__jevents_rrule WHERE eventid IN ($eventid)";
			$db->setQuery( $query);
			$db->query();

			$query = "DELETE FROM #__jevents_repetition WHERE eventid IN ($eventid)";
			$db->setQuery( $query);
			$db->query();

			$query = "DELETE FROM #__jevents_exception WHERE eventid IN ($eventid)";
			$db->setQuery( $query);
			$db->query();

			$query = "DELETE FROM #__jevents_vevdetail WHERE evdet_id IN ($detailidstring)";
			$db->setQuery( $query);
			$db->query();

			// I also need to clean out associated custom data
			$dispatcher	=& JDispatcher::getInstance();
			// just incase we don't have jevents plugins registered yet
			JPluginHelper::importPlugin("jevents");
			$res = $dispatcher->trigger( 'onDeleteEventDetails' , array($detailidstring));

			$query = "DELETE FROM #__jevents_vevent WHERE ev_id IN ($eventid)";
			$db->setQuery( $query);
			$db->query();

			// I also need to delete custom data
			$dispatcher	=& JDispatcher::getInstance();
			// just incase we don't have jevents plugins registered yet
			JPluginHelper::importPlugin("jevents");
			$res = $dispatcher->trigger( 'onDeleteCustomEvent' , array(&$eventid));

			echo "<script> alert('".JText::_("JEV_RECAPTCHA_ERROR",true)."'); window.history.go(-1); </script>\n";
			exit();

		}
	}
	else {
		$name = JRequest::getString("custom_anonusername","");
		$email = JRequest::getString("custom_anonemail","");

		if ($name!="" && $email!="")  {
			// Store the name and email address with the event
			$db = JFactory::getDBO();
			$creator = new JTableAnonCreator($db);
			$creator->id = 0;
			$creator->email = $email;
			$creator->name = $name;
			$creator->ev_id = $eventid;
			$creator->store();
			return true;
		}		
	}
	return $success;
}

function onAfterSaveEvent(&$row, $dryrun){
	if ($dryrun) return;
	// Make sure created_by is NOT reset during saving process
	if ($row->ev_id>0){
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=".intval($row->ev_id));
		$anonrow = $db->loadObject();
	}
}
// attach creator details to event
function onDisplayCustomFields(&$row){
	$authorname="";
	$authoremail="";
	if ($row->ev_id()>0){
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=".intval($row->ev_id()));
		$anonrow = $db->loadObject();
		if ($anonrow){
			$authorname=$anonrow->name;
			$authoremail=$anonrow->email;
		}
	}

	$row->authorname = $authorname;
	$row->authoremail = $authoremail;
}

	static function fieldNameArray($layout='detail'){

		$return  = array();
		$return['group'] = JText::_("JEV_ANON_CREATOR",true);

		$labels = array();
		$values = array();

		$labels[] = JText::_("JEV_CREATOR_NAME",true);
		$values[] = "JEV_CNAME";

		$labels[] = JText::_("JEV_CREATOR_EMAIL",true);
		$values[] = "JEV_CEMAIL";

		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;
	}

	static function substitutefield($row, $code){
		if ($code == "JEV_CNAME"){
			if (isset($row->authorname)) return $row->authorname;
		}
		if ($code == "JEV_CEMAIL"){
			if (isset($row->authoremail) && $row->authoremail!="") return JHTML::_('email.cloak', $row->authoremail, 0);
		}

		return "";
	}

	/* restrict category selection */
	function onGetAccessibleCategories(&$cats, $reindex=true){

		$user = JFactory::getUser();
		// if logged in then do not change isEventCreator
		if ($user->id>0) return true;

		if (is_array($cats)) return true;

		if (JRequest::getString("task")!="icalevent.edit" && JRequest::getString("task")!="icalrepeat.edit") return true;
		
		$anoncats = $this->params->get("cats","");
		if (is_string($anoncats)){			
			$anoncats = str_replace(" ", "",$anoncats);
			$anoncats = explode (",",$anoncats);
		}
		if (count($anoncats)==0) return true;
		
		JArrayHelper::toInteger($anoncats);

		$anoncats[]= -1;
		
		$incats = explode (",",$cats);
		JArrayHelper::toInteger($anoncats);

		$incats = array_intersect($anoncats, $incats);
		$cats = implode(",",$incats);
	}

	/* restrict category selection during editing phase */
	function onGetAccessibleCategoriesForEditing(&$cats, $reindex = true)
	{
		$anoncats = $this->params->get("cats","");
		if (is_string($anoncats)){			
			$anoncats = str_replace(" ", "",$anoncats);
			$anoncats = explode (",",$anoncats);
		}
		if (count($anoncats)==0) return true;

	}

}

class JTableAnonCreator extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__jev_anoncreator', 'id', $db);
	}

}
