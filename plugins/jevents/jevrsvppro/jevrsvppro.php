<?php
/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");
JLoader::register('jevFilterProcessing', JPATH_SITE . "/components/com_jevents/libraries/filters.php");
JLoader::register('JevRsvpParameter', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrsvpparameter.php");
JLoader::register('JevRsvpAttendance', dirname(__FILE__) . "/rsvppro/jevrattendance.php");
JLoader::register('JevRsvpDisplayAttendance', dirname(__FILE__) . "/rsvppro/jevrdisplayattendance.php");
JLoader::register('RsvpHelper', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/rsvphelper.php");
JLoader::register('JevTemplateHelper', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/templatehelper.php");

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

include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/rsvppro.defines.php");

/**
 * TODO
 *
 * 1. disable "Login message" for invitee only events (but allow for links from email)
 * 2. option to hide events from non-invitees
 * 3. Setup javascript to recognise if the event is repeating or not.
 */
// see http://www.setcronjob.com/

jimport('joomla.plugin.plugin');

class plgJEventsJevrsvppro extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		// merge in the new config options from the component
		$params = JComponentHelper::getParams("com_rsvppro");
		$this->params->merge($params);

		JPlugin::loadLanguage('plg_jevents_jevrsvppro', JPATH_ADMINISTRATOR);
		JPlugin::loadLanguage('com_rsvppro', JPATH_SITE);
		
		jimport("joomla.filesystem.file");
		try {
			$xml = simplexml_load_string(JFile::read(dirname(__FILE__)."/jevrsvppro.xml"));
			$this->params->set("pluginversion",$xml->version);
		}
		catch (Exception $ex){
			$this->params->set("pluginversion",1);
		}
		// always load mootools!
		JHtml::_('behavior.framework', true);

		// up to date?
		/*
		  if ($this->params->get("setupstate",0)!=1){
		  $this->params->set("setupstate",1);
		  $row  =& JTable::getInstance('plugin');
		  }
		 */

	}

	/**
	 * When editing a JEvents menu item can add additional menu constraints dynamically
	 *
	 */
	function onEditMenuItem(&$menudata, $value, $control_name, $name, $id, $param)
	{

		// already done this param
		if (isset($menudata[$id]))
			return;

		static $matchingextra = null;
		// find the parameter that matches jevrsvp: (if any)
		if (!isset($matchingextra))
		{
			if (version_compare(JVERSION, '1.6.0', '>='))
			{
				$params = $param->getGroup('params');
				foreach ($params as $key => $element)
				{
					$val = $element->value;
					if (strpos($key, "jform_params_extras") === 0)
					{
						if (strpos($val, "jevrsvp:") === 0)
						{
							$matchingextra = $key;
							break;
						}
					}
				}
			}
			else
			{
				$keyvals = $param->toArray();
				foreach ($keyvals as $key => $val)
				{
					if (strpos($key, "extras") === 0 && strpos($val, "jevrsvp:") === 0)
					{
						$matchingextra = str_replace("extras", "", $key);
						break;
					}
				}
			}
			if (!isset($matchingextra))
			{
				$matchingextra = false;
			}
		}

		// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
		if ((strpos($value, "jevrsvp:") === 0 && is_string($matchingextra) && strpos($matchingextra, $name)>=0) || (($value == "" || $value == "0") && $matchingextra === false))
		{
			$matchingextra = true;
			$invalue = str_replace(" ", "", $value);
			if ($invalue == "")
				$invalue = 'jevrsvp:0';

			$options = array();
			$options[] = JHtml::_('select.option', 'jevrsvp:0', JText::_('JEV_NO_RESTRICTIONS'), 'id', 'title');
			$options[] = JHtml::_('select.option', 'jevrsvp:1', JText::_('JEV_EVENTS_ATTENDING'), 'id', 'title');
			$options[] = JHtml::_('select.option', 'jevrsvp:2', JText::_('JEV_EVENTS_INVITED_TO'), 'id', 'title');
			$options[] = JHtml::_('select.option', 'jevrsvp:3', JText::_('JEV_EVENTS_ATTENDING_EVEN_IF_NOT_PAID'), 'id', 'title');
			$options[] = JHtml::_('select.option', 'jevrsvp:4', JText::_('JEV_EVENTS_ANYONE_ATTENDING'), 'id', 'title');

			if (version_compare(JVERSION, '1.6.0', '>='))
			{
				if ($control_name=="params"){
					// for CB
					$input = JHtml::_('select.genericlist',  $options, ''.$control_name.'['.$name.']', '', 'id', 'title', $invalue, $control_name.$name );
				}
				else {
					$input = JHtml::_('select.genericlist', $options, $name, '', 'id', 'title', $invalue, $control_name . $name);
				}
			}
			else
			{
				$input = JHtml::_('select.genericlist', $options, '' . $control_name . '[' . $name . ']', '', 'id', 'title', $invalue, $control_name . $name);
			}

			$data = new stdClass();
			$data->name = "jevrsvpser";
			$data->html = $input;
			$data->label = "JEV_RSVPPRO";
			$data->description = "JEV_RSVPPRO_DESC";
			$data->options = array();
			$menudata[$id] = $data;
		}
		
		// restrict visibility based on session access
		static $matchingextra2 = null;
		// find the parameter that matches jevrsvpsa: (if any)
		if (!isset($matchingextra2))
		{
			if (version_compare(JVERSION, '1.6.0', '>='))
			{
				$params = $param->getGroup('params');
				foreach ($params as $key => $element)
				{
					$val = $element->value;
					if (strpos($key, "jform_params_extras") === 0)
					{
						if (strpos($val, "jevrsvpsa:") === 0)
						{
							$matchingextra2 = $key;
							break;
						}
					}
				}
			}
			else
			{
				$keyvals = $param->toArray();
				foreach ($keyvals as $key => $val)
				{
					if (strpos($key, "extras") === 0 && strpos($val, "jevrsvpsa:") === 0)
					{
						$matchingextra2 = str_replace("extras", "", $key);
						break;
					}
				}
			}
			if (!isset($matchingextra2))
			{
				$matchingextra2 = false;
			}
		}

		// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
		if ((strpos($value, "jevrsvpsa:") === 0 && is_string($matchingextra2) && strpos($matchingextra2, $name)>=0)|| (($value == "" || $value == "0") && $matchingextra2 === false))
		{
			$matchingextra2 = true;
			$invalue = str_replace(" ", "", $value);
			if ($invalue == "")
				$invalue = 'jevrsvpsa:0';

			$options = array();
			$options[] = JHtml::_('select.option', 'jevrsvpsa:0', JText::_('JEV_NO_RESTRICTIONS'), 'id', 'title');
			$options[] = JHtml::_('select.option', 'jevrsvpsa:1', JText::_('JEV_WHERE_AUTHORISED_TO_REGISTER'), 'id', 'title');

			if (version_compare(JVERSION, '1.6.0', '>='))
			{
				if ($control_name=="params"){
					// for CB
					$input = JHtml::_('select.genericlist',  $options, ''.$control_name.'['.$name.']', '', 'id', 'title', $invalue, $control_name.$name );
				}
				else {
					$input = JHtml::_('select.genericlist', $options, $name, '', 'id', 'title', $invalue, $control_name . $name);
				}
			}
			else
			{
				$input = JHtml::_('select.genericlist', $options, '' . $control_name . '[' . $name . ']', '', 'id', 'title', $invalue, $control_name . $name);
			}

			$data = new stdClass();
			$data->name = "jevrsvpsaser";
			$data->html = $input;
			$data->label = "JEV_RSVPPRO_SESSION_ACCESS";
			$data->description = "JEV_RSVPPRO_RESTRICT_BASED_ON_SESSION_ACCESS";
			$data->options = array();
			$menudata[$id] = $data;
		}


	}

	function onEventEdit(&$extraTabs, &$row, &$params)
	{
		if (RsvpHelper::canCreateSessions())
		{
			$jevrattendance = new JevRsvpAttendance($this->params);
			return $jevrattendance->editAttendance($extraTabs, $row, $params);
		}

	}

	function onEditCustom(&$row, &$customfields)
	{
		if (!$this->params->get("attendance", 1))
		{
			$eventid = intval($row->ev_id());
			ob_start();
			?>
			<input type="hidden" name="custom_rsvp_evid" value="<?php echo $eventid; ?>" />
			<input type="hidden" name="custom_rsvp_allowregistration" value="0"  />
			<?php
			$input = ob_get_clean();
			$customfield = array("label" => "", "input" => $input);
			$customfields["rsvppro"] = $customfield;
		}

	}

	/**
	 * Clean out custom fields for event details not matching global event detail
	 *
	 * @param unknown_type $idlist
	 */
	function onCleanCustomDetails($idlist)
	{
		// TODO
		return true;

	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	// TODO update reminder timestamps when saving event exception
	function onStoreCustomDetails($evdetail)
	{
		
	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	// TODO update reminder timestamps when event times have changed
	function onStoreCustomEvent($event)
	{
		// force registration on.
		//$event->_detail->_customFields["rsvp_allowregistration"]=1;
		// set capacity to 10
		//$event->_detail->_customFields["rsvp_capacity"]=10;
		// Could use this approach to set the price too!
				
		$jevrattendance = new JevRsvpAttendance($this->params);
		return $jevrattendance->storeAttendance($event);

	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	// TODO update reminder timestamps when event times have changed
	function onAfterSaveEvent($event, $dryrun = false)
	{
		if ($dryrun)
		{
			return true;
		}
		
		$jevrattendance = new JevRsvpAttendance($this->params);
		$jevrattendance->autoInvite($event);
		$jevrattendance->autoRemind($event);

	}

	/**
	 * Clean out custom details for deleted event details
	 *
	 * @param comma separated list of event detail ids $idlist
	 */
	function onDeleteEventDetails($idlist)
	{
		// TODO remove any orphan attendee, invitee or reminder records
		return true;

	}

	function onDeleteCustomEvent($idlist)
	{
		$jevrattendance = new JevRsvpAttendance($this->params);
		 return $jevrattendance->deleteAttendance($idlist);
	}

	function onListIcalEvents(& $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby=false, & $rptwhere = array())
	{

		$mainframe = JFactory::getApplication();
		if ($mainframe->isAdmin())
		{
			return;
		}
		// find what is running - used by the filters
		$registry = & JRegistry::getInstance("jevents");
		$activeprocess = $registry->get("jevents.activeprocess", "");
		$moduleid = $registry->get("jevents.moduleid", 0);
		$moduleparams = $registry->get("jevents.moduleparams", false);

		// Have we specified restrictions on attending or invited on this menu/module
		$user = JFactory::getUser();

		$compparams = JComponentHelper::getParams("com_jevents");

		$reg = & JFactory::getConfig();
		$modparams = $reg->get("jev.modparams", false);
		if ($modparams)
		{
			$compparams = $modparams;
		}
		
		$filters = array("hidefornoninvitees");

		// Do we need to filter on session access
		for ($extra = 0; $extra < 20; $extra++)
		{
			$extraval = $compparams->get("extras" . $extra, false);
			if (strpos($extraval, "jevrsvpsa:1") === 0 )
			{
				$filters[] = "hidebasedonaccess";
				break;
			}
		}
		
		if (JVersion::isCompatible("1.6.0"))
		{
			$filters = jevFilterProcessing::getInstance($filters, JPATH_SITE . "/plugins/jevents/jevrsvppro/filters", false, $moduleid);
		}
		else
		{
			$filters = jevFilterProcessing::getInstance($filters, JPATH_SITE . "/plugins/jevents/filters", false, $moduleid);
		}

		$filters->setWhereJoin($extrawhere, $extrajoin);
		if (!$needsgroupdby)
			$needsgroupdby = $filters->needsGroupBy();
		
		for ($extra = 0; $extra < 20; $extra++)
		{
			$extraval = $compparams->get("extras" . $extra, false);
			if (strpos($extraval, "jevrsvp:") === 0)
			{
				break;
			}
		}

		if ($this->params->get("showattendeecountinlists", 0))
		{
			$extrajoin[] = "#__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND (atd.allrepeats=1 OR atdcnt.rp_id=rpt.rp_id) ";
			$extrafields .= ", \n atdcnt.atdcount as attendeecount, atd.capacity, atd.waitingcapacity ";
		}
		$extrafields .= ", \n atd.regopen, atd.regclose, atd.allrepeats,  atd.allowregistration";

		if (!$extraval && !$this->params->get("hidefull", 0))
			return true;
		if (!$extraval)
			$extraval = "";

		$invalue = str_replace("jevrsvp:", "", $extraval);
		$invalue = str_replace(" ", "", $invalue);
		if (substr($invalue, strlen($invalue) - 1) == ",")
		{
			$invalue = substr($invalue, 0, strlen($invalue) - 1);
		}
		$invalue = intval($invalue);

		// only for logged in users (overrule for for hidefull!)
		if ($user->id == 0 && (!$this->params->get("hidefull", 0) && !$this->params->get("showattendeecountinlists", 0)))
		{
			// 1 = attending
			// 2 = invited to
			if ($invalue >= 1)
			{
				$extrawhere[] = "1=0";
				return;
			}
			else {
				return;
			}
		}
		
		$atdeesjoined = false;
		// 1 = attending
		if ($invalue == 1)
		{
			$needsgroupdby = true;
			if (!$atdeesjoined) {
				$extrajoin[] = "#__jev_attendees as atdees ON atdees.at_id=atd.id AND atdees.user_id=" . $user->id . " AND atdees.attendstate=1";
				$atdeesjoined = true;
			}
			// show attendance or show attendance to invitees
			$extrawhere[] = "((atd.allrepeats=1 AND atdees.rp_id=0) OR (atd.allrepeats=0 AND atdees.rp_id=rpt.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2)";
			$rptwhere[] = " ((atd.allrepeats=1 AND atdees.rp_id=0) OR (atd.allrepeats=0 AND atdees.rp_id=rpt2.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2)";
		}
		// 2 = invited to
		else if ($invalue == 2)
		{
			// already joined to invites
			$needsgroupdby = true;
			$extrawhere[] = "((atd.allinvites=1 AND inv.rp_id=0) OR (atd.allinvites=0 AND inv.rp_id=rpt.rp_id)) AND atd.invites=1 AND inv.user_id=".$user->id ;
			$rptwhere[] = "((atd.allinvites=1 AND inv.rp_id=0) OR (atd.allinvites=0 AND inv.rp_id=rpt2.rp_id)) AND atd.invites=1 AND inv.user_id=".$user->id ;
		}
		// 3 = attending even if not paid
		else if ($invalue == 3)
		{
			$needsgroupdby = true;
			if (!$atdeesjoined) {
				$extrajoin[] = "#__jev_attendees as atdees ON atdees.at_id=atd.id AND atdees.user_id=" . $user->id . " AND (atdees.attendstate=1 OR atdees.attendstate=4)";
				$atdeesjoined = true;
			}
			// show attendance or show attendance to invitees
			$extrawhere[] = "((atd.allrepeats=1 AND atdees.rp_id=0) OR (atd.allrepeats=0 AND atdees.rp_id=rpt.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2)";
			$rptwhere[] = " ((atd.allrepeats=1 AND atdees.rp_id=0) OR (atd.allrepeats=0 AND atdees.rp_id=rpt2.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2)";
		}
		// 4 = Anyone attending 
		else if ($invalue == 4)
		{
			if (!$this->params->get("hidefull", 0) && !$this->params->get("showattendeecountinlists", 0)){
				$extrajoin[] = "#__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id  AND ((atd.allrepeats=1  AND atdcnt.rp_id=0) OR (atd.allrepeats=0  AND atdcnt.rp_id=rpt.rp_id)) ";
			}
			$needsgroupdby = true;
			// show attendance or show attendance to invitees
			$extrawhere[] = "((atd.allrepeats=1 AND atdcnt.rp_id=0) OR (atd.allrepeats=0 AND atdcnt.rp_id=rpt.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2) AND atdcnt.atdcount>0";
			$rptwhere[] = " ((atd.allrepeats=1 AND atdcnt.rp_id=0) OR (atd.allrepeats=0 AND atdcnt.rp_id=rpt2.rp_id)) AND (atd.allowregistration=1 OR atd.allowregistration=2) AND atdcnt.atdcount>0";
		}

		
		// Should we hide full events from non-attendees
		if ($this->params->get("hidefull", 0))
		{
			// Must make sure we have the attendees linked too
			if ($invalue != 1 && $user->id > 0)
			{
				$needsgroupdby = true;
				if (!$atdeesjoined) {
					$extrajoin[] = "#__jev_attendees as atdees ON atdees.at_id=atd.id AND atdees.user_id=" . $user->id . " AND atdees.attendstate=1";					
				$atdeesjoined = true;
				}
			}

			if (!$this->params->get("showattendeecountinlists", 0))
			{
				$extrajoin[] = "#__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id  AND ((atd.allrepeats=1  AND atdcnt.rp_id=0) OR (atd.allrepeats=0  AND atdcnt.rp_id=rpt.rp_id)) ";
			}

			if (JEVHelper::isAdminUser($user))
			{
				// always show to super admins
			}
			else if ($user->id > 0)
			{
				// MUST allow capacity null for imported or legacy events
				$extrawhere[] = "(atd.capacity=0 OR atd.capacity IS NULL
				OR ev.created_by=$user->id
				OR atdcnt.atdcount IS NULL
			OR (atdcnt.atdcount < (atd.capacity+atd.waitingcapacity) AND ((atd.allrepeats=1 AND atdcnt.rp_id=0) OR (atd.allrepeats=0 AND atdcnt.rp_id=rpt.rp_id)))
			OR atdees.user_id IS NOT NULL) ";
			}
			else
			{
				// MUST allow capacity null for imported or legacy events
				$extrawhere[] = "(atd.capacity=0 OR atd.capacity IS NULL
				OR atdcnt.atdcount IS NULL
			OR (atdcnt.atdcount < (atd.capacity+atd.waitingcapacity)  AND ((atd.allrepeats=1 AND atdcnt.rp_id=0) OR (atd.allrepeats=0 AND atdcnt.rp_id=rpt.rp_id)) )
			) ";
			}
		}

		return true;

	}

	function onListEventsById(& $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{

		$mainframe = JFactory::getApplication();
		if ($mainframe->isAdmin())
		{
			return;
		}
		// find what is running - used by the filters
		$registry = & JRegistry::getInstance("jevents");
		$activeprocess = $registry->get("jevents.activeprocess", "");
		$moduleid = $registry->get("jevents.moduleid", 0);
		$moduleparams = $registry->get("jevents.moduleparams", false);

		if (JVersion::isCompatible("1.6.0"))
		{
			$filters = jevFilterProcessing::getInstance(array("hidefornoninvitees"), JPATH_SITE . "/plugins/jevents/jevrsvppro/filters", false, $moduleid);
		}
		else
		{
			$filters = jevFilterProcessing::getInstance(array("hidefornoninvitees"), JPATH_SITE . "/plugins/jevents/filters", false, $moduleid);
		}

		$filters->setWhereJoin($extrawhere, $extrajoin);

		return true;

	}

	/*
	  function onGetEventData(&$row){
	  }
	 */

	function onDisplayCustomFields(&$row)
	{
		if (isset($row->hidedetail) && $row->hidedetail)
		{
			return "";
		}

		if(JRequest::getVar("jevtask","")=="icals.export") {
			return;
		}
		$jevrattendance = new JevRsvpDisplayAttendance($this->params);
		return $jevrattendance->displayCustomFields($row);

	}

	function onDisplayCustomFieldsMultiRow(&$rows)
	{		
		// get all the attendance id info in one go
		$eventids = array(0);
		foreach ($rows as & $row)
		{		
			$eventids[] = intval($row->ev_id());
		}
		$db = & JFactory::getDBO ();
		$sql = "SELECT * FROM #__jev_attendance WHERE ev_id in (" . implode(",",$eventids).")";
		$db->setQuery($sql);
		$rsvpdataArray = $db->loadObjectList('ev_id');
	
		foreach ($rows as & $row)
		{		
			if (isset($row->hidedetail) && $row->hidedetail)
			{
				continue;
			}

			$eventid = intval($row->ev_id());
			if ($eventid>0 && array_key_exists( $eventid,$rsvpdataArray)) {
				$row->rsvpdata = $rsvpdata = $rsvpdataArray[$eventid];
			}
		}		
		unset($row);
		
		if (!($this->params->get("showattendeesinlists", 0) || $this->params->get("showattendeecountinlists", 0)) || count($rows) == 0)
		{
			return true;
		}
		$task = JRequest::getString("jevtask");
		if ($task == 'icalevent.edit' || $task == 'icalevent.edit')
		{
			return true;
		}
			
		foreach ($rows as & $row)
		{		
			if (isset($row->hidedetail) && $row->hidedetail)
			{
				continue;
			}
			
			$jevrattendance = new JevRsvpDisplayAttendance($this->params);
			
			$jevrattendance->displayCustomFieldsMultiRow($row, $rsvpdataArray);
		}
		unset($row);

	}

	static function fieldNameArray($layout='detail')
	{

		// only offer in detail view
		$plugin = JPluginHelper::getPlugin("jevents", "jevrsvppro");
		if (!$plugin)
			return "";
		$params = JComponentHelper::getParams("com_rsvppro");

		$return = array();

		$return['group'] = JText::_("JEV_ATTENDANCE_FIELDS", true);

		$labels = array();
		$values = array();

		if ($layout == "detail")
		{
			$labels[] = JText::_("JEV_ATTENDANCE_SUMMARY", true);
			$values[] = "JEV_ATTENDANCE_SUMMARY";
			
			$labels[] = JText::_("JEV_ATTENDANCE_LOGINFORM", true);
			$values[] = "RSVP_LOGIN";
			
		}

		if ($layout == "detail" || $params->get("showattendeesinlists", 0))
		{
			$labels[] = JText::_("JEV_ATTENDEES", true);
			$values[] = "ATTENDEES";
		}

		if ($layout == "detail")
		{
			$labels[] = JText::_("JEV_SOCIAL_ATTENDEES", true);
			$values[] = "SOCATTEND";

			$labels[] = JText::_("JEV_INVITEES", true);
			$values[] = "JEV_INVITES";

			$labels[] = JText::_("JEV_ATTENDANCE_FORM", true);
			$values[] = "JEV_ATTENDFORM";
			/*
			  $labels[] = JText::_("JEV_RSVP_MESSAGES",true);
			  $values[] = "JEV_RSVPMSG";
			 */
			$labels[] = JText::_("JEV_REMINDER_FORM", true);
			$values[] = "JEV_REMINDFORM";
		}

		if ($layout == "detail" || $params->get("showattendeecountinlists", 0))
		{
			$labels[] = JText::_("JEV_ATTENDEE_COUNT", true);
			$values[] = "ATTENDCOUNT";

			$labels[] = JText::_("JEV_TOTAL_CAPACITY", true);
			$values[] = "ATTENDCAP";

			$labels[] = JText::_("JEV_REMAINING_SPACES", true);
			$values[] = "ATTENDSPACE";
		}

		if ($layout != "detail")
		{
			$labels[] = JText::_("JEV_REGOPEN", true);
			$values[] = "REGOPEN";
			$labels[] = JText::_("JEV_REGCLOSED", true);
			$values[] = "REGCLOSED";
			$labels[] = JText::_("JEV_REGOPENING", true);
			$values[] = "REGOPENING";
			$labels[] = JText::_("JEV_REGCLOSING", true);
			$values[] = "REGCLOSING";
		}
                
                if ($layout == "detail") 
                {
			$labels[] = JText::_("JEV_REGOPENING", true);
			$values[] = "REGOPENING";
			$labels[] = JText::_("JEV_REGCLOSING", true);
			$values[] = "REGCLOSING";
                }

		// show attendee status
		if ($layout != "detail" && $params->get("showattendeesinlists", 0))	{
			$labels[] = JText::_("JEV_ATENDEE_STATUS", true);
			$values[] = "ATDEESTATUS";
		}
		
		/*
		  $labels[] = JText::_("JEV_WAITING_NUMBER",true);
		  $values[] = "ATTENDWAITCOUNT";

		  $labels[] = JText::_("JEV_WAITING_SPACES",true);
		  $values[] = "ATTENDWAIT";
		 */
		// only offer the reminder in detail view
		if ($layout != "detail")
		{
			$return['values'] = $values;
			$return['labels'] = $labels;

			return $return;
		}

		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

        }

	static function substitutefield($row, $code)
	{
		if (isset($row->hidedetail) && $row->hidedetail)
		{
			return "";
		}
		if ($code == "JEV_ATTENDANCE_SUMMARY")
		{
			if (isset($row->attendancedata))
				return $row->attendancedata;
		}
		if ($code == "RSVP_LOGIN")
		{
			if (isset($row->rsvp_loginform))
				return $row->rsvp_loginform;
		}

		static $allowaccessarray = array();
		if (!isset($allowaccessarray[$row->rp_id()])){
			$user = JFactory::getUser();
			if (isset($row->rsvpdata)){
				$rsvpdata = $row->rsvpdata;
				$allowaccessarray[$row->rp_id()] = plgJEventsJevrsvppro::checkAccess($user, $rsvpdata, $row);
			}
			else {
				$allowaccessarray[$row->rp_id()] = false;
			}
		}
		$allowaccess = $allowaccessarray[$row->rp_id()];
		if (!$allowaccess){
			$row->rsvp_attendanceform = isset($rsvpdata->sessionaccessmessage)?$rsvpdata->sessionaccessmessage : "";
		}
		
		if ($code == "JEV_ATTENDFORM")
		{
			if (isset($row->rsvp_attendanceform))
				return $row->rsvp_attendanceform;
		}

		if (!$allowaccess){
			return "";
		}
		
		if ($code == "ATTENDEES")
		{
			if (isset($row->rsvp_fullattendees))
				return $row->rsvp_fullattendees;
		}
		if ($code == "SOCATTEND")
		{
			if (isset($row->rsvp_socialattendees))
				return $row->rsvp_socialattendees;
		}
		if ($code == "JEV_INVITES")
		{
			if (isset($row->rsvp_createinvitations))
				return $row->rsvp_createinvitations;
		}

		if ($code == "JEV_REMINDFORM")
		{
			if (isset($row->rsvp_reminderform))
				return $row->rsvp_reminderform;
		}

		if ($code == "JEV_RSVPMSG")
		{
			if (isset($row->rsvp_messagarea))
				return $row->rsvp_messagarea;
		}

		if ($code == "ATTENDCOUNT")
		{
			$attendeecount = JText::_("RSVP_ATTENDEE_COUNT");
			if ($attendeecount == "RSVP_ATTENDEE_COUNT") {
				$attendeecount = "%s";
			}
			if (isset($row->attendeecount))
				return JText::sprintf ($attendeecount, $row->attendeecount);
			else if (isset($row->_attendeecount))
				return JText::sprintf ($attendeecount, $row->_attendeecount);
			else if (isset($row->rsvpdata) && $row->rsvpdata->allowregistration)
				return JText::sprintf ($attendeecount, 0);
			else {
				if (JText::_("RSVP_NO_ATTENDANCE")=="RSVP_NO_ATTENDANCE"){
					return "";
				}
				return JText::_("RSVP_NO_ATTENDANCE");
			}
		}

		if ($code == "ATTENDCAP")
		{
			if (isset($row->rsvpdata) && isset($row->rsvpdata->capacity) && $row->rsvpdata->capacity >0 )
				return $row->rsvpdata->capacity;
			else
				return JText::_("RSVP_NO_REGISTRATION_LIMIT");
		}
		
		if ($code == "ATTENDSPACE"  )
		{
			if (isset($row->rsvpdata) && isset($row->rsvpdata->capacity) && $row->rsvpdata->capacity >0 && isset($row->attendeecount))
				$space = intval($row->rsvpdata->capacity) - intval($row->attendeecount);
			else if (isset($row->rsvpdata) && isset($row->rsvpdata->capacity) && $row->rsvpdata->capacity >0 && isset($row->_attendeecount))
				$space = intval($row->rsvpdata->capacity) - intval($row->_attendeecount);
			else if (isset($row->rsvpdata) && isset($row->rsvpdata->capacity) && $row->rsvpdata->capacity >0)
				$space = intval($row->rsvpdata->capacity);
			else
				return "";
			if ($space>0){
				if (JText::_("RSVP_REMAINING_SPACES")== "RSVP_REMAINING_SPACES"){
					return $space;
				}
				else {
					$format = JText::_("RSVP_REMAINING_SPACES");
					if (strpos($format, "[max")>0){
						$pattern = "#\[max([0-9]+)\]#";
						preg_match($pattern, $format,$matches);
						if (count($matches)==2 && $space<=intval($matches[1])){
							$format = str_replace($matches[0], "", $format);
							return sprintf($format, $space);
						}
						return "";
					}
					return JText::sprintf("RSVP_REMAINING_SPACES", $space);
				}
			}
			else if ($space==0) {
				if (JText::_("JEV_NO_REMAINING_SPACES")== "JEV_NO_REMAINING_SPACES"){
					return $space;
				}
				else {
					return JText::_("JEV_NO_REMAINING_SPACES");
				}
				
			}
			else return $space;
		}
		
		if ($code == "ATTENDWAIT")
		{
			if (isset($row->_attendeewaitcap))
				return $row->_attendeewaitcap;
			else
				return 0;
		}

		if ($code == "ATTENDWAITCOUNT")
		{
			if (isset($row->_attendeewaiting))
				return $row->_attendeewaiting;
			else
				return 0;
		}

		if ($code == "ATDEESTATUS") {
			if (isset($row->attendee) && isset($row->attendee->attendstate)){
				$attendstates = array("JEV_ARE_NOT_ATTENDING","JEV_ARE_ATTENDING","JEV_MAY_BE_ATTENDING","JEV_ATTENDING_PENDING_APPROVAL","JEV_YOUR_ATTENDANCE_IS_AWAITING_PAYMENT");
				$state = JText::_($attendstates [$row->attendee->attendstate]);
				if ($row->attendee->waiting){
					$state .= " : ".JText::_("JEV_YOU_ARE_ON_WAITINGLIST");
				}
				if (!$row->attendee->confirmed){
					$state .= " : ".JText::_("JEV_EMAIL_AWAITING_CONFIRMATION");
				}
				return $state;
			}
			else {
				return "";
			}
		}
                
		if ($code == "REGOPEN" || $code == "REGCLOSED" || $code == "REGOPENING" || $code == "REGCLOSING")
		{
			// detail page doesnt set these automatically
			if (isset($row->rsvpdata) && !isset($row->_regopen)){
				$row->_regopen = $row->rsvpdata->regopen;
				$row->_regclose = $row->rsvpdata->regclose ;
				$row->_allowregistration = $row->rsvpdata->allowregistration;
				$row->_allrepeats = $row->rsvpdata->allrepeats ;
				$row->_sessionaccess = $row->rsvpdata->sessionaccess;
			}
			
			if (isset($row->_regopen) && $row->_regopen != "0000-00-00 00:00:00" && $row->_allowregistration)
			{
				// attendance by invitees only?
				if ($row->_allowregistration==2 && isset($row->rsvpdata)) {
					$user = JFactory::getUser();
					if ($user->id>0){
						// is this user invited?
						$db = JFactory::getDBO();
						$db->setQuery("select user_id from #__jev_invitees where at_id=".intval($row->rsvpdata->id). " AND user_id=".intval($user->id));
						$res = $db->loadResult();
						// not invited to return blank
						if (!$res && $row->created_by()!=$user->id && !JEVHelper::isAdminUser()){
							return "";
						}
					}
					else {
						// can't do this for non-logged in users
						return "";
					}
				}
				// Must use strtotime format for force JevDate to not just parse the date itself!!!
				$jnow = new JevDate("+1 second");
				$now = $jnow->toMySQL();
				$regclose = $row->_regclose;
				$regopen = $row->_regopen;
				if ($row->_allrepeats)
				{
					if ($now > $regclose || $now < $regopen)
					{
						if ($now > $regclose && $code == "REGCLOSED" ){
							return JText::_("JEV_REGCLOSED_MESSAGE");
						}
                                                
						if ($now < $regopen && $code == "REGOPENING" ){
							$regopening = new JevDate(JevDate::strtotime($regopen));
							return JText::sprintf("JEV_REGOPENING_MESSAGE", $regopening->toFormat(JText::_("JEV_REGOPENING_FORMAT")));
						}						
						return "";
					}
                                          if ($now < $regclose && $code == "REGCLOSING" ){
							$regclosing = new JevDate(JevDate::strtotime($regclose));
							return JText::sprintf("JEV_REGCLOSING_MESSAGE", $regclosing->toFormat(JText::_("JEV_REGCLOSING_FORMAT")));
						}
					else if ($code == "REGOPEN"){
						return JText::_("JEV_REGOPEN_MESSAGE");
					}
					else {
						return "";
					} 
				}
				// otherwise the start of the repeat
				else
				{
					// need to use corrected dtstart here!
					$eventstart = isset($row->_fixed_dtstart)? $row->_fixed_dtstart : $row->dtstart(); 
					$repeatstart = $row->getUnixStartTime();
					$regclose = new JevDate(JevDate::strtotime($row->_regclose));
					$regclose = $regclose->toUnix();
					$regopen = new JevDate(JevDate::strtotime($row->_regopen));
					$regopen= $regopen->toUnix();

					$adjustedregclose = new JevDate($regclose + ($repeatstart - $eventstart));
					$adjustedregopen = new JevDate($regopen + ($repeatstart - $eventstart));
					$adjustedregclose = $adjustedregclose->toMySQL();
					$adjustedregopen = $adjustedregopen->toMySQL();					
					
					if ($code == "REGCLOSED" ){
						if ($now > $adjustedregclose ){
							return JText::_("JEV_REGCLOSED_MESSAGE");
						}
						return "";
					}
					if ( $code == "REGCLOSING") {
                                                      if ($now < $adjustedregclose){
							$regclosing = new JevDate($adjustedregclose);
							return JText::sprintf("JEV_REGCLOSING_MESSAGE", $regclosing->toFormat(JText::_("JEV_REGCLOSING_FORMAT")));
						}
						return "";						
					}
					
					if ($now< $adjustedregclose && $now > $adjustedregopen)
					{
						if ($code == "REGOPEN"){
							return JText::_("JEV_REGOPEN_MESSAGE");
						}
						else {
							return "";
						}
					}
					if ($code == "REGOPENING" ){
						if ($now < $adjustedregopen){
							$regopening = new JevDate($adjustedregopen);
							return JText::sprintf("JEV_REGOPENING_MESSAGE", $regopening->toFormat(JText::_("JEV_REGOPENING_FORMAT")));
						}
					}
				}
			}
			else
				return "";
		}
		return "";

	}

	static function checkAccess($user, $rsvpdata, $event)
	{
		if ($rsvpdata->sessionaccess>=0){
			$access = $rsvpdata->sessionaccess;
		}
		else {
			$access = $event->access();
		}
		if (JVersion::isCompatible("1.6.0"))
		{
			$access = explode(",", $access);
			return count(array_intersect($access, JEVHelper::getAid($user, 'array'))) > 0;
		}
		else
		{
			return $access <= $user->aid;
		}

	}

}
