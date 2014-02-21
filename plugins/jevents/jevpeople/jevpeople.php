<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgJEventsJevpeople extends JPlugin
{

	var $_dbvalid = 0;

	function plgJEventsJevpeople(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$lang = & JFactory::getLanguage();
		$lang->load("com_jevpeople", JPATH_SITE);
		$lang->load("com_jevpeople", JPATH_ADMINISTRATOR);
		$lang->load("plg_jevents_jevpeople", JPATH_ADMINISTRATOR);

	}

	/**
	 * When editing a JEvents menu item can add additional menu constraints dynamically
	 *
	 */
	function onEditMenuItem(&$menudata, $value, $control_name, $name, $id, $param)
	{
		static $matchingextra = null;
		// find the parameter that matches jevp: (if any)
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
						if (strpos($val, "jevp:") === 0)
						{
							$matchingextra = str_replace("jform_extras", "", $key);
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
					if (strpos($key, "extras") === 0 && strpos($val, "jevp:") === 0)
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

		// already done this param
		if (isset($menudata[$id]))
			return;

		// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
		if (($matchingextra == $id && strpos($value, "jevp:") === 0) || (($value == "" || $value == "0") && $matchingextra === false))
		{
			$matchingextra = $id;
			$invalue = str_replace("jevp:", "", $value);
			$invalue = str_replace(" ", "", $invalue);
			$invalue = explode(",", $invalue);
			JArrayHelper::toInteger($invalue);
			JHTML::script('administrator/components/com_jevpeople/assets/js/people.js');
			JHTML::script('administrator/components/com_jevpeople/assets/js/sortablepeople.js');
			$person = " -- ";
			if (count($invalue) > 0)
			{
				$db = & JFactory::getDBO();
				$sql = "SELECT jp.pers_id, jp.title, jpt.title as typename FROM #__jev_people as jp
						LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id 				
						where jp.pers_id IN(" . implode(",", $invalue) . ")";
				$db->setQuery($sql);
				$people = @$db->loadObjectList('pers_id');
			}

			$link = JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component");
			$input = "<div style='float: left;align-text:left;'><ul id='sortablePeople' style='margin:0px;padding:0px;cursor:move;'>";
			foreach ($invalue as $persid)
			{
				if (!array_key_exists($persid, $people))
					continue;
				$jpm = $people[$persid];
				$persname = $jpm->title;
				$pid = $jpm->pers_id;
				$type = $jpm->typename;
				$persname = $persname . " ($type)";
				$input .= "<li id='sortablepers$pid' >$persname</li>";
			}
			$input .= "</ul>";
			if (version_compare(JVERSION, '1.6.0', '>='))
			{
				$input .= '<input type="hidden"  name="' . $name . '"  id="menuperson" value="' . $value . '" />';
				// for CB plugins !!!
				$input .= '<input type="hidden"  name="' . $control_name . '[' . $name . ']"   id="compat_menuperson"  value="' . $value . '" />';
				$input .= '<div style="clear:left"></div>';
			}
			else
			{
				$input .= '<input type="hidden"  name="' . $control_name . '[' . $name . ']"  id="menuperson" value="' . $value . '" />';
			}

			$input .= "</div>";
			$input .= "<img src='" . JURI::Root() . "components/com_jevpeople/assets/images/Trash.png' class='sortabletrash' id='trashimage' style='display:none;padding-right:2px;cursor:pointer;' title='" . JText::_("JEV_REMOVE_PERSON", true) . "'/>";
			$input .= "<script type='text/javascript'>
			peopleDeleteWarning='" . JText::_("JEV_REMOVE_PERSON_WARNING", true) . "';
			window.addEvent('domready', function(){
				if (sortablePeople) sortablePeople.setup();
			});
			var jevpeople = {\n";
			$input .= "duplicateWarning : '" . JText::_("Already_Selected", true) . "'\n";
			$input .= "		}
			</script>";
			$input.='<div class="button2-left"><div class="blank"><a href="javascript:sortablePeople.selectPerson(\'' . JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component") . '\')" title="' . JText::_('SELECT_PERSON') . '"  >' . JText::_('SELECT_PERSON') . '</a></div></div>';

			$data = new stdClass();
			$data->name = "jevpeople";
			$data->html = $input;
			$data->label = "Specified Person?";
			$data->description = "Specify a person for this menu item";
			$data->options = array();
			$menudata[$id] = $data;
		}

	}

	function onEditCustom(&$row, &$customfields)
	{

		JHTML::script('administrator/components/com_jevpeople/assets/js/people.js');
		JHTML::script('administrator/components/com_jevpeople/assets/js/sortablepeople.js');
		$jevuser = JEVHelper::getAuthorisedUser();

		$compparams = JComponentHelper::getParams("com_jevpeople");

		$db = & JFactory::getDBO();

		// get the data from database and attach to row
		$detailid = intval($row->evdet_id());

		$db = & JFactory::getDBO();
		$sql = "SELECT * from #__jev_peopletypes as jpt";
		$db->setQuery($sql);
		$types = @$db->loadObjectList('type_id');

		$sql = "SELECT jp.pers_id, jp.title, jpt.title as typename, jpt.type_id as type_id , jpt.categories ,jpt.calendars  FROM #__jev_peopleeventsmap as jpm
		LEFT JOIN #__jev_people as jp ON jp.pers_id = jpm.pers_id 
		LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id 
		WHERE evdet_id=" . $detailid . "
		ORDER BY jpm.ordering,jp.type_id, jp.ordering,jp.title";

		$db->setQuery($sql);
		$jpmlist = $db->loadObjectList();

		JHTML::_('behavior.modal');
		JHTML::_('behavior.tooltip');
		$link = JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component");
		if ($compparams->get("personselect", 0) == 0)
		{
			$input = "<div style='float: left;'><ul id='sortablePeople' style='margin:0px;cursor:move;'>";
		}
		else
		{
			$input = "<div style='margin-bottom:5px;'><ul id='sortablePeople' style='margin:0px;cursor:move;'>";
		}
		foreach ($jpmlist as $jpm)
		{

			$jp = $jpm;
			if ($jp->categories != "all" && $jp->categories != "")
			{
				$cats = explode("|", $jp->categories);
				JArrayHelper::toInteger($cats);
				if (!in_array($row->catid(), $cats))
					continue;
			}
			if ($jp->calendars != "all" && $jp->calendars != "")
			{
				$cals = explode("|", $jp->calendars);
				JArrayHelper::toInteger($cals);
				if (!in_array($row->_icsid, $cals))
					continue;
			}

			$name = $jpm->title;
			$pid = $jpm->pers_id;
			$type = $jpm->typename;
			$name = $name . " ($type)";
			$input .= "<li id='sortablepers$pid' >$name</li>";
		}
		$input .= "</ul>";
		$input .= '<select multiple="multiple" name="custom_person[]" id="custom_person" size="4" style="display:none" >';
		foreach ($jpmlist as $jpm)
		{
			$name = $jpm->title;
			$pid = $jpm->pers_id;
			$type = $jpm->typename;
			$name = $name . " ($type)";
			$input .= "<option value='$pid' selected='selected' id='sortablepers" . $pid . "option'>$name</option>";
		}
		$input .= "</select>";
		$input .= "</div>";

		// If configured for a single person selection
		if ($compparams->get("personselect", 0) == 0)
		{
			$selectPerson = JText::_('SELECT_PERSON');
			$selectPersonTip = JText::_('SELECT_PERSON_TIP');
			$input .= '<div class="button2-left" ><div class="blank"><a href="javascript:sortablePeople.selectPerson(\'' . $link . '\');" title="' . $selectPerson . '::' . $selectPersonTip . '"  class="hasTip">' . JText::_('JEV_SELECT') . '</a></div></div>';
			$input .= "<img src='" . JURI::Root() . "components/com_jevpeople/assets/images/Trash.png' class='sortabletrash' id='trashimage' style='display:none;padding-right:2px;cursor:pointer;'/>";
			$input .= "<script type='text/javascript'>
			peopleDeleteWarning='" . JText::_("JEV_REMOVE_PERSON_WARNING", true) . "';
			window.addEvent('domready', function(){
				if (sortablePeople) sortablePeople.setup();
			});
			var jevpeople = {\n";
			$input .= "duplicateWarning : '" . JText::_("Already_Selected", true) . "'\n";
			$input .= "		}
			</script>";

			$label = JText::_('SELECT_PERSON');

			$customfield = array("label" => $label, "input" => $input);
			$customfields["people_one"] = $customfield;
		}
		else
		{
			$customfield = array("label" => "", "input" => $input);
			$customfields["people"] = $customfield;

			$input = "";

			$firstpass = true;
			$style = "";
			$script = "";
			foreach ($types as $type)
			{

				$showtype = true;
				if ($type->categories != "all" && $type->categories != "")
				{
					$cats = explode("|", $type->categories);
					JArrayHelper::toInteger($cats);
					if (!in_array($row->catid(), $cats))
					{
						$style .= ".jevplugin_people" . $type->type_id . " {display:none;}";
						$showtype = false;
					}
					else
					{
						$jevparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
						if ($jevparams->get("multicategory", 0))
						{
							$multicats = $row->catids();
							$showtype = false;
							foreach ($multicats as $cat)
							{
								if (in_array($cat, $cats))
								{
									$showtype = true;
									break;
								}
							}
							if (!$showtype)
							{
								$style .= ".jevplugin_people" . $type->type_id . " {display:none;}";
							}
						}
					}
				}
				else
				{
					$cats = array();
				}
				if ($showtype && $type->calendars != "all" && $type->calendars != "")
				{
					$cals = explode("|", $type->calendars);
					JArrayHelper::toInteger($cals);
					if (!in_array($row->_icsid, $cals))
					{
						$style .= ".jevplugin_people" . $type->type_id . " {display:none;}";
					}
				}
				else
				{
					$cals = array();
				}

				$typelink = JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component&type_id=" . intval($type->type_id));
				$input = "";
				$selectPerson = JText::sprintf("Select_by_type", $type->title);
				$selectPersonTip = JText::sprintf("Select_by_type_TIP", $type->title);
				$input .= '<div class="button2-left" style="cursor:move' . $style . '"><div class="blank"><a href="javascript:sortablePeople.selectPerson(\'' . $typelink . '\');" title="' . $selectPerson . '::' . $selectPersonTip . '"  class="hasTip">' . JText::_('JEV_SELECT') . '</a></div></div>';
				$input .= "<img src='" . JURI::Root() . "components/com_jevpeople/assets/images/Trash.png' class='sortabletrash' id='trashimage' style='display:none;padding-right:2px;cursor:pointer;'/>";
				if ($firstpass)
				{
					$input .= "<script type='text/javascript'>
			peopleDeleteWarning='" . JText::_("JEV_REMOVE_PERSON_WARNING", true) . "';
			window.addEvent('domready', function(){
				if (sortablePeople) sortablePeople.setup();
			});
			var jevpeople = {\n";
					$input .= "duplicateWarning : '" . JText::_("Already_Selected", true) . "'\n";
					$input .= "		}
			</script>";
					$firstpass = false;
				}

				$label = JText::sprintf("Select_by_type", $type->title);

				$script .= "JevrCategoryPeople.fields.push({'id':'" . $type->type_id . "' ,'catids':" . json_encode($cats) . ",'calids':" . json_encode($cals) . "});\n ";

				$customfield = array("label" => $label, "input" => $input);
				$customfields["people" . $type->type_id] = $customfield;
			}
			if ($style != "")
			{
				$document = JFactory::getDocument();
				$document->addStyleDeclaration($style);

				$this->setupCategorySpecificTypes($script);
			}
		}
		// do we allow self allocation?
		$db->setQuery("select pt.*, pma.maxallocation  from #__jev_peopletypes as pt 
			left join #__jev_peopleeventsmaxallocation as pma on pma.type_id = pt.type_id and pma.evdet_id=$detailid
			where pt.selfallocate=1 and pt.allowedgroups<>'' order by pt.title asc");
		$selfallocations = $db->loadObjectList();
		if ($selfallocations && count($selfallocations) > 0)
		{
			$label = JText::_("JEV_PEOPLE_MAX_ALLOCATIONS");
			$input = JText::_("JEV_PEOPLE_MAX_ALLOCATIONS_DESC");
			$input .= "<br/>";
			foreach ($selfallocations as $selfallocation)
			{
				$value = isset($selfallocation->maxallocation) ? intval($selfallocation->maxallocation) : 0;
				$input .= "<label for='jevpsa" . $selfallocation->type_id . "'>" . $selfallocation->title . " <input type='text' name='custom_jevpsa[" . $selfallocation->type_id . "]' id='jevpsa" . $selfallocation->type_id . "' size='3'  value='$value' /></label><br/>";
			}
			$customfield = array("label" => $label, "input" => $input);
			$customfields["people_selfallocation"] = $customfield;
		}

		return true;

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
	function onStoreCustomDetails($evdetail)
	{
		$detailid = intval($evdetail->evdet_id);
		$person = array_key_exists("person", $evdetail->_customFields) ? $evdetail->_customFields["person"] : "0";
		$db = & JFactory::getDBO();

		// first of all remove all the old mappings
		$sql = "DELETE FROM #__jev_peopleeventsmap WHERE evdet_id=" . $detailid;
		$db->setQuery($sql);
		$success = $db->query();

		if ($person != 0 && count($person) > 0)
		{
			$order = 0;
			foreach ($person as $val)
			{
				$sql = "INSERT INTO #__jev_peopleeventsmap SET pers_id=" . intval($val) . ",  evdet_id=" . $detailid . ", ordering=" . $order;
				$db->setQuery($sql);
				$success .= $db->query();
				$order++;
			}
		}

		if (array_key_exists("jevpsa", $evdetail->_customFields))
		{
			$jevpsa = $evdetail->_customFields["jevpsa"];
			foreach ($jevpsa as $type_id => $maxallocation)
			{
				$sql = "REPLACE INTO #__jev_peopleeventsmaxallocation (type_id, evdet_id, maxallocation) VALUES(" . intval($type_id) . ",  " . $detailid . "," . $maxallocation . ")";
				$db->setQuery($sql);
				$success .= $db->query();
			}
		}
		return $success;

	}

	/**
	 * Clean out custom details for deleted event details
	 *
	 * @param comma separated list of event detail ids $idlist
	 */
	function onDeleteEventDetails($idlist)
	{
		return true;

	}

	function onListIcalEvents(& $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby = false)
	{
		if (JFactory::getApplication()->isAdmin())
		{
			return;
		}

		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams("com_jevents");

		// If loading from a module then get the modules params from the registry
		$reg = & JFactory::getConfig();
		$modparams = $reg->get("jev.modparams", false);
		if ($modparams)
		{
			$compparams = $modparams;
		}

		// if its called from a module then we don't need the taglookup filter if we are ignoring the filter module
		if (!$modparams || !$modparams->get("ignorefiltermodule", false))
		{

			$pluginsDir = JPATH_ROOT . '/' . 'plugins' . '/' . 'jevents';
			$filters = jevFilterProcessing::getInstance(array("peoplesearch", "peoplelookup", "peoplemultilookup"), $pluginsDir . '/' . "filters" . '/');
			$filters->setWhereJoin($extrawhere, $extrajoin);
			if (!$needsgroupdby)
				$needsgroupdby = $filters->needsGroupBy();
		}

		for ($extra = 0; $extra < 20; $extra++)
		{
			$extraval = $compparams->get("extras" . $extra, false);
			if (strpos($extraval, "jevp:") === 0)
			{
				break;
			}
		}
		if (!$extraval)
			return true;

		$invalue = str_replace("jevp:", "", $extraval);
		$invalue = str_replace(" ", "", $invalue);
		if (substr($invalue, strlen($invalue) - 1) == ",")
		{
			$invalue = substr($invalue, 0, strlen($invalue) - 1);
		}
		$invalue = explode(",", $invalue);
		JArrayHelper::toInteger($invalue);

		$extrawhere[] = "pers.pers_id IN (" . implode(",", $invalue) . ")";
		$needsgroupdby = true;

		// if its called from a module then we don't need the taglookup filter if we are ignoring the filter module so we need to include the hoin
		if ($modparams && $modparams->get("ignorefiltermodule", false))
		{
			$extrajoin[] = " #__jev_peopleeventsmap as persmap ON det.evdet_id=persmap.evdet_id LEFT JOIN #__jev_people as pers ON pers.pers_id=persmap.pers_id ";
		}

		return true;

	}

	function onSearchEvents(& $extrasearchfields, & $extrajoin, & $needsgroupdby = false)
	{
		static $usefilter;

		if (!isset($usefilter))
		{
			if (JFactory::getApplication()->isAdmin())
			{
				$usefilter = false;
				return;
			}

			$pluginsDir = JPATH_ROOT . '/' . 'plugins' . '/' . 'jevents';
			$filters = jevFilterProcessing::getInstance(array("peoplesearch"), $pluginsDir . '/' . "filters" . '/', false, 'peoplesearch');
			$filters->setSearchKeywords($extrasearchfields, $extrajoin);
		}

		return true;

	}

	function onListEventsById(& $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{
		return true;

	}

	function onDisplayCustomFields(&$row)
	{

		$db = & JFactory::getDBO();

		// get the data from database and attach to row
		$detailid = intval($row->evdet_id());

		$sql = "SELECT jp.*, jpt.title as typename,jpt.type_id as type_id, jpt.categories ,jpt.calendars  FROM #__jev_peopleeventsmap as jpm
		LEFT JOIN #__jev_people as jp ON jp.pers_id = jpm.pers_id 
		LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id 
		WHERE evdet_id=" . $detailid . "
		ORDER BY jpm.ordering,jp.type_id, jp.ordering,jp.title";

		JHTML::_('behavior.modal');

		$compparams = JComponentHelper::getParams("com_jevpeople");
		/*
		  <option value="0">Person Type in DIVs, Person in UL/LIs</option>
		  <option value="1">Person Type in UL/LIs, Person in UL/LIs</option>
		  <option value="2">Person Type in DIVs, Persons in single div with separator</option>
		 */

		$presentation = $compparams->get("presentation", 0);
		$presentationfields = $compparams->get("presentationfields", "{TITLE}");

		$separator = $compparams->get("separator", ",");
		$db->setQuery($sql);
		$jpmlist = $db->loadObjectList();
		$text = "";
		if (count($jpmlist) > 0)
		{
			$ptype = false;
			$persopen = false;
			$ulopen = false;
			$divopen = false;
			$needsseparator = false;
			$text .= "<div class='jevpeople'>\n";
			foreach ($jpmlist as $jp)
			{

				if ($jp->categories != "all" && $jp->categories != "")
				{
					$cats = explode("|", $jp->categories);
					JArrayHelper::toInteger($cats);
					if (!in_array($row->catid(), $cats))
						continue;
				}
				if ($jp->calendars != "all" && $jp->calendars != "")
				{
					$cals = explode("|", $jp->calendars);
					JArrayHelper::toInteger($cals);
					if (!in_array($row->_icsid, $cals))
						continue;
				}

				if ($jp->typename != $ptype)
				{
					if ($presentation == 0 || $presentation == 1)
					{
						if ($ulopen)
						{
							$text .= "</ul>\n";
							$ulopen = false;
						}
					}
					else if ($presentation == 2)
					{
						if ($divopen)
						{
							$text .= "</div>\n";
							$divopen = false;
						}
					}

					if ($presentation == 1)
					{
						if ($ptype)
						{
							$text .= "</li></ul>\n";
						}
						$text .= "<ul class='jevpeople_title'>\n";
						$text .= "<li>" . $jp->typename;
					}
					else
					{
						$text .= "<div class='jevpeople_title'>\n";
						$text .= $jp->typename;
						$text .= "</div>\n";
					}

					if ($presentation == 0 || $presentation == 1)
					{
						$text .= "<ul>\n";
						$ulopen = true;
					}
					if ($presentation == 2)
					{
						$text .= "<div class='jevpeople_entries'>\n";
						$divopen = true;
					}
					$ptype = $jp->typename;
					$needsseparator = false;
				}
				if ($presentation == 0 || $presentation == 1)
				{
					$text .= "<li class='jevpeople_entries'>\n";
				}

				if ($compparams->get("jomsociallist", 0) && $jp->linktouser > 0)
				{
					/*
					  require_once ( JPATH_ROOT.'/'.'components'.'/'.'com_community'.'/'.'defines.community.php');

					  // Require the base controller
					  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'error.php');
					  require_once (COMMUNITY_COM_PATH.'/'.'controllers'.'/'.'controller.php');
					  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'apps.php' );
					  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'core.php');

					  $juser = CFactory::getUser($jp->linktouser);
					  $info = $juser->getInfo("FIELD_PAYPAL");
					 */
					$link = JRoute::_("index.php?option=com_community&view=profile&userid=" . $jp->linktouser);
				}
				else if ($compparams->get("cblist", 0) && $jp->linktouser > 0)
				{
					$link = JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $jp->linktouser);
				}
				else
				{
					$link = JRoute::_("index.php?option=com_jevpeople&task=people.detail&tmpl=component&pers_id=" . $jp->pers_id);
				}
				if ($presentation == 2)
				{
					if ($needsseparator)
					{
						$text .= $separator;
					}
					$text .= "<span>";
					$needsseparator = true;
				}

				if ($compparams->get("jomsociallist", 0) && $jp->linktouser > 0)
				{
					$modal = false;
				}
				else if ($compparams->get("cblist", 0) && $jp->linktouser > 0)
				{
					$modal = false;
				}
				else
				{
					$modal = true;
				}

				// Get the media component configuration settings
				$mparams = & JComponentHelper::getParams('com_media');
				// Set the path definitions
				$mediapath = JURI::root(true) . '/' . $mparams->get('image_path', 'images/stories');

				if ($modal)
				{
					$linkstart = "<a href='$link' class='modal' rel='{\"handler\": \"iframe\",\"size\": {\"x\": 750, \"y\": 500},\"closeWithOverlay\": 0}'>";
				}
				else
				{
					$linkstart = "<a href='$link' >";
				}
				if ($jp->image != "")
				{
					$img = $linkstart . '<img src="' . $mediapath . '/jevents/jevpeople/' . $jp->image . '" alt="' . htmlspecialchars($jp->imagetitle) . '"/></a>';
					$thumb = $linkstart . '<img src="' . $mediapath . '/jevents/jevpeople/thumbnails/thumb_' . $jp->image . '" alt="' . htmlspecialchars($jp->imagetitle) . '"/></a>';
				}
				else
				{
					$img = $thumb = "";
				}

				$presentationoutput = $presentationfields;
				$presentationoutput = str_replace("{TITLE}", $linkstart . $jp->title . "</a>\n", $presentationoutput);
				$presentationoutput = str_replace("{DESCRIPTION}", $jp->description , $presentationoutput);
				$presentationoutput = str_replace("{IMAGE}", $img, $presentationoutput);
				$presentationoutput = str_replace("{THUMB}", $thumb, $presentationoutput);

				$text .= $presentationoutput;

				if ($presentation == 0 || $presentation == 1)
				{
					$text .= "</li>\n";
				}
				if ($presentation == 2)
				{
					$text .= "</span>\n";
				}

				// New custom fields
				JLoader::register('JevCfForm', JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/jevcfform.php");
				$compparams = JComponentHelper::getParams("com_jevpeople");
				$template = $compparams->get("template", "");
				if ($template != "")
				{
					$html = "";
					$customfields = array();
					$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
					if (file_exists($xmlfile))
					{
						$db = JFactory::getDBO();
						$db->setQuery("SELECT * FROM #__jev_customfields2 WHERE target_id=" . intval($jp->pers_id) . " AND targettype='com_jevpeople'");
						$cfdata = $db->loadObjectList('name');
						$customdata = array();
						foreach ($cfdata as $dataelem)
						{
							if (strpos($dataelem->name, ".") !== false)
							{
								$dataelem->name = str_replace(".", "_", $dataelem->name);
							}
							$customdata[$dataelem->name] = $dataelem->value;
						}

						$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
						$jcfparams->bind($customdata);
						$jcfparams->setEvent($jp);

						$customfields = array();
						$groups = $jcfparams->getFieldsets();
						foreach ($groups as $group => $element)
						{
							if ($jcfparams->getFieldCountByFieldSet($group))
							{
								$customfields = array_merge($customfields, $jcfparams->renderToBasicArray('params', $group));
							}
						}
					}
					$templatetop = $compparams->get("templatetop", "<table border='0'>");
					$templaterow = $compparams->get("templatebody", "<tr><td class='label'>{LABEL}</td><td>{VALUE}</td>");
					$templatebottom = $compparams->get("templatebottom", "</table>");

					$row->jevp_custompeople_raw = $customfields;
					$row->jevp_custompeople = array();
					$user = JFactory::getUser();
					foreach ($customfields as $customfield)
					{
						if (!$this->checkAccess($user, $customfield["access"]))
							continue;
						if (!is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == $customfield["hiddenvalue"])
							continue;
						$field = array();
						$field["label"] = $customfield["label"];
						$field["value"] = $customfield["value"];
						$row->jevp_custompeople[$customfield["name"]] = $field;
					}
				}

				// Now add the image
				if (!isset($row->_personimage) && $jp->image != "")
				{
					// Get the media component configuration settings
					$mparams = & JComponentHelper::getParams('com_media');
					// Set the path definitions
					$mediapath = JURI::root(true) . '/' . $mparams->get('image_path', 'images/stories');

					$row->_personimage = '<img src="' . $mediapath . '/jevents/jevpeople/' . $jp->image . '" alt="' . htmlspecialchars($jp->imagetitle) . '"/>';
					$row->_personthumb = '<img src="' . $mediapath . '/jevents/jevpeople/thumbnails/thumb_' . $jp->image . '" alt="' . htmlspecialchars($jp->imagetitle) . '"/>';
				}
			}
			if ($ulopen)
			{
				$text .= "</ul>\n";
			}
			if ($presentation == 1)
			{
				if ($ptype)
				{
					$text .= "</li></ul>\n";
				}
			}
			if ($divopen)
			{
				$text .= "</div>\n";
			}
			$text .= "</div>\n";
		}

		// Add reference to people info in the $event
		$row->_jevpeople = $jpmlist;

		// Are there any unallocated roles?
		if (JVersion::isCompatible("1.6.0"))
		{
			$user = JFactory::getUser();
			if ($user->id > 0)
			{

				$usergroups = $user->getAuthorisedGroups();

				$db->setQuery("select pma.maxallocation,count(jpm.pers_id) as pcount , max(linktouser) as linktouser,  pt.* from #__jev_peopleeventsmaxallocation as pma
LEFT JOIN #__jev_peopletypes as pt on pma.type_id=pt.type_id AND pt.selfallocate=1  
LEFT JOIN #__jev_people as pp on pp.type_id = pt.type_id
LEFT JOIN #__jev_peopleeventsmap as jpm on jpm.pers_id=pp.pers_id and jpm.evdet_id=$detailid
where pma.evdet_id=$detailid AND (pp.linktouser=$user->id OR pt.allowedgroups<>'' )
group by pt.type_id
order by pt.title asc");
				//echo (string) $db->getQuery()."<br/><br/>";
				$selfallocations = $db->loadObjectList();
				//var_dump($selfallocations);
				echo $db->getErrorMsg();
				$rolesavailable = array();
				foreach ($selfallocations as $selfallocation)
				{
					// if not already assigned to this role then are we allowed to be?
					if ($selfallocation->linktouser != $user->id)
					{
						// is this user qualified for this role
						$allowedgroups = json_decode($selfallocation->allowedgroups);
						if (count(array_intersect($allowedgroups, $usergroups)) == 0)
						{
							continue;
						}
					}

					// make sure this user is not already allocated for this role
					$db->setQuery("SELECT pp.* from #__jev_people as pp 
						LEFT JOIN #__jev_peopleeventsmap as jpm on jpm.pers_id=pp.pers_id
						WHERE pp.linktouser=$user->id AND pp.type_id=" . $selfallocation->type_id . " and jpm.evdet_id=$detailid");
					$role = $db->loadObject();
					if ($role)
					{
						continue;
					}
					$rolesavailable[] = $selfallocation;
				}

				$countrolesavailable = 0;
				foreach ($rolesavailable as $role)
				{
					if ($role->maxallocation - $role->pcount > 0)
						$countrolesavailable++;
				}

				if ($countrolesavailable)
				{
					$row->_jevpeople_rolesavailable = JText::_("JEV_PEOPLE_ROLES_AVAILABLE_FOR_EVENT_CLICK_TO_ACCEPT") . "<br/>";
					foreach ($rolesavailable as $role)
					{
						if ($role->maxallocation - $role->pcount > 0)
						{
							$buttonlabel = htmlspecialchars($role->title . " (" . ($role->maxallocation - $role->pcount) . ")");
							$button = '<input type="button" class="jevpeoplerolebutton" onclick="acceptRole(\'' . $role->type_id . '\', ' . $user->id . ', ' . $detailid . ');return false;" value="' . $buttonlabel . '"/>';
							$row->_jevpeople_rolesavailable .= $button . " ";
						}
					}
					$text .= "<br/>" . $row->_jevpeople_rolesavailable;

					$doc = JFactory::getDocument();
					$url = JURI::root() . "plugins/jevents/jevpeople/jevpeople_acceptrole.php";
					$updatedmessage = JText::_("JEV_PEOPLE_ROLE_CONFIRMED");
					$script = <<<SCRIPT
function acceptRole(typeid, userid, detailid){

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.typeid= typeid;
	requestObject.task = "acceptRole";
	requestObject.userid = userid;
	requestObject.detailid = detailid;

	var jSonRequest = new Request.JSON({
		'url':'$url',
		onSuccess: function(json, responsetext){
			if (!json){
				alert('Update Failed');
			}
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				alert('$updatedmessage');
			}
		},
		onFailure: function(x){
			alert('Something went wrong... '+x )
		}
	}).get({'json':JSON.encode(requestObject)});
	
}
SCRIPT;
					$doc->addScriptDeclaration($script);
				}
			}
		}
		$row->_jevpeopletext = $text;

		return $text;

	}

	function onDisplayCustomFieldsMultiRow(&$rows)
	{
		if ($this->params->get("inlists") == 0 || count($rows) == 0)
		{
			return true;
		}
		$db = & JFactory::getDBO();

		// get the data from database and attach to rows
		$detailids = array();
		foreach ($rows as $row)
		{
			$detailids[] = intval($row->evdet_id());
		}

		// New custom fields
		$extrafields = "";
		$extrajoin = "";
		$xmlfile = "";

		JLoader::register('JevCfForm', JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/jevcfform.php");
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$template = $compparams->get("template", "");
		$nullfields = false;
		if ($template != "")
		{
			$html = "";
			$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
			if (file_exists($xmlfile))
			{
				$extrafields = ", cf.* ";
				$extrajoin = " LEFT JOIN #__jev_customfields2 as cf ON  cf.target_id=jp.pers_id AND cf.targettype='com_jevpeople'";

				$nullparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$groups = $nullparams->getFieldsets();
				$nullfields = array();
				foreach ($groups as $group => $element)
				{
					if ($nullparams->getFieldCountByFieldSet($group))
					{
						$nullfields = array_merge($nullfields, $nullparams->renderToBasicArray('params', $group));
					}
				}
			}
		}

		$sql = "SELECT evdet_id, jp.*, jpt.title as typename,jpt.type_id as type_id, jpt.categories ,jpt.calendars   $extrafields  FROM #__jev_peopleeventsmap as jpm
		LEFT JOIN #__jev_people as jp ON jp.pers_id = jpm.pers_id
		$extrajoin
		LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id
		WHERE evdet_id IN (" . implode(",", $detailids) . ")
		GROUP BY jp.pers_id, jpm.evdet_id
		ORDER BY jpm.ordering, jp.type_id, jp.ordering,jp.title, jp.pers_id		";

		$db->setQuery($sql);
		$jpmlist = $db->loadObjectList();

		if ($nullfields)
		{
			foreach ($rows as & $row)
			{
				$row->jevp_custompeople = array();
				$row->jevp_custompeople_jpm = array();

				$customdata = array();

				$foundMatch = false;
				foreach ($jpmlist as $jpm)
				{

					$jp = $jpm;
					if ($jp->categories != "all" && $jp->categories != "")
					{
						$cats = explode("|", $jp->categories);
						JArrayHelper::toInteger($cats);
						if (!in_array($row->catid(), $cats))
							continue;
					}
					if ($jp->calendars != "all" && $jp->calendars != "")
					{
						$cals = explode("|", $jp->calendars);
						JArrayHelper::toInteger($cals);
						if (!in_array($row->_icsid, $cals))
							continue;
					}

					if ($jpm->evdet_id == $row->_eventdetail_id)
					{
						$foundMatch = true;
						$row->jevp_custompeople_jpm[] = $jpm;
					}
				}
				if ($foundMatch)
				{
					foreach ($nullfields as $nullfield)
					{
						if (isset($customdata[$nullfield["name"]]))
							continue;

						foreach ($row->jevp_custompeople_jpm as $jpm)
						{
							if ($jpm->name != $nullfield["name"])
								continue;
							$customrecord = array();
							$customrecord["id"] = 0;
							$customrecord["target_id"] = $jpm->pers_id;
							$customrecord["targettype"] = "com_jevpeople";
							$customrecord["name"] = $jpm->name;
							$customrecord["value"] = $jpm->value;
							$customdata[$nullfield["name"]] = $customrecord;
						}
					}

					// Now add the image
					if (!isset($row->_personimage) && $jpm->image != "")
					{
						// Get the media component configuration settings
						$mparams = & JComponentHelper::getParams('com_media');
						// Set the path definitions
						$mediapath = JURI::root(true) . '/' . $mparams->get('image_path', 'images/stories');

						$row->_personimage = '<img src="' . $mediapath . '/jevents/jevpeople/' . $jpm->image . '" alt="' . htmlspecialchars($jpm->imagetitle) . '"/>';
						$row->_personthumb = '<img src="' . $mediapath . '/jevents/jevpeople/thumbnails/thumb_' . $jpm->image . '" alt="' . htmlspecialchars($jpm->imagetitle) . '"/>';
					}


					// redindex numerically;
					$customdata = array_values($customdata);

					// convert and format
					$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind($customdata);
					$jcfparams->setEvent($row->jevp_custompeople_jpm[0]);

					$customfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$customfields = array_merge($customfields, $jcfparams->renderToBasicArray('params', $group));
						}
					}

					$row->jevp_custompeople_raw = $customfields;

					$user = JFactory::getUser();
					foreach ($customfields as $customfield)
					{
						if (isset($row->jevp_custompeople[$customfield["name"]]))
							continue;

						if (!$this->checkAccess($user, $customfield["access"]))
							continue;
						if (!is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == $customfield["hiddenvalue"])
							continue;

						$field = array();
						$field["label"] = $customfield["label"];
						$field["value"] = nl2br($customfield["value"]);
						$row->jevp_custompeople[$customfield["name"]] = $field;
					}
				}
			}
			unset($row);
		}


		foreach ($rows as & $row)
		{

			if (!$nullfields)
			{
				$row->jevp_custompeople_jpm = array();
				$foundMatch = false;
				foreach ($jpmlist as $jpm)
				{

					$jp = $jpm;
					if ($jp->categories != "all" && $jp->categories != "")
					{
						$cats = explode("|", $jp->categories);
						JArrayHelper::toInteger($cats);
						if (!in_array($row->catid(), $cats))
							continue;
					}
					if ($jp->calendars != "all" && $jp->calendars != "")
					{
						$cals = explode("|", $jp->calendars);
						JArrayHelper::toInteger($cals);
						if (!in_array($row->_icsid, $cals))
							continue;
					}

					if ($jpm->evdet_id == $row->_eventdetail_id)
					{
						$foundMatch = true;
						$row->jevp_custompeople_jpm[] = $jpm;
					}
				}
			}

			if (isset($row->jevp_custompeople_jpm) && count($row->jevp_custompeople_jpm) > 0)
			{
				// Now the people summary
				$compparams = JComponentHelper::getParams("com_jevpeople");
				$presentation = $compparams->get("presentation", 0);
				$separator = $compparams->get("separator", ",");

				$ptype = false;
				$persopen = false;
				$ulopen = false;
				$divopen = false;
				$needsseparator = false;
				$text = "<div class='jevpeople'>\n";
				foreach ($row->jevp_custompeople_jpm as $jp)
				{
					if ($jp->typename != $ptype)
					{
						if ($presentation == 0 || $presentation == 1)
						{
							if ($ulopen)
							{
								$text .= "</ul>\n";
								$ulopen = false;
							}
						}
						else if ($presentation == 2)
						{
							if ($divopen)
							{
								$text .= "</div>\n";
								$divopen = false;
							}
						}

						if ($presentation == 1)
						{
							if ($ptype)
							{
								$text .= "</li></ul>\n";
							}
							$text .= "<ul class='jevpeople_title'>\n";
							$text .= "<li>" . $jp->typename;
						}
						else
						{
							$text .= "<div class='jevpeople_title'>\n";
							$text .= $jp->typename;
							$text .= "</div>\n";
						}

						if ($presentation == 0 || $presentation == 1)
						{
							$text .= "<ul>\n";
							$ulopen = true;
						}
						if ($presentation == 2)
						{
							$text .= "<div class='jevpeople_entries'>\n";
							$divopen = true;
						}
						$ptype = $jp->typename;
						$needsseparator = false;
					}
					if ($presentation == 0 || $presentation == 1)
					{
						$text .= "<li class='jevpeople_entries'>\n";
					}

					if ($compparams->get("jomsociallist", 0) && $jp->linktouser > 0)
					{
						/*
						  require_once ( JPATH_ROOT.'/'.'components'.'/'.'com_community'.'/'.'defines.community.php');

						  // Require the base controller
						  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'error.php');
						  require_once (COMMUNITY_COM_PATH.'/'.'controllers'.'/'.'controller.php');
						  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'apps.php' );
						  require_once (COMMUNITY_COM_PATH.'/'.'libraries'.'/'.'core.php');

						  $juser = CFactory::getUser($jp->linktouser);
						  $info = $juser->getInfo("FIELD_PAYPAL");
						 */
						$link = JRoute::_("index.php?option=com_community&view=profile&userid=" . $jp->linktouser);
					}
					else if ($compparams->get("cblist", 0) && $jp->linktouser > 0)
					{
						$link = JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $jp->linktouser);
					}
					else
					{
						$link = JRoute::_("index.php?option=com_jevpeople&task=people.detail&tmpl=component&pers_id=" . $jp->pers_id);
					}
					if ($presentation == 2)
					{
						if ($needsseparator)
						{
							$text .= $separator;
						}
						$text .= "<span>";
						$needsseparator = true;
					}

					if ($compparams->get("jomsociallist", 0) && $jp->linktouser > 0)
					{
						$text .= "<a href='$link' >" . $jp->title . "</a>\n";
					}
					else if ($compparams->get("cblist", 0) && $jp->linktouser > 0)
					{
						$text .= "<a href='$link' >" . $jp->title . "</a>\n";
					}
					else
					{
						$text .= "<a href='$link' class='modal' rel='{\"handler\": \"iframe\",\"size\": {\"x\": 750, \"y\": 500},\"closeWithOverlay\": 0}'>" . $jp->title . "</a>\n";
					}
					if ($presentation == 0 || $presentation == 1)
					{
						$text .= "</li>\n";
					}
					if ($presentation == 2)
					{
						$text .= "</span>\n";
					}
				}
				if ($ulopen)
				{
					$text .= "</ul>\n";
					$ulopen = false;
				}

				$text .= "</div>\n";
				$row->_jevpeopletext = $text;
			}
		}

		if (count($row->jevp_custompeople_jpm) > 0)
		{
			$row->_jevperson = $row->jevp_custompeople_jpm[0];
		}

	}

	function onCheckEventOverlaps(& $testevent, & $overlaps, $eventid, $requestObject)
	{
		if (!isset($requestObject->formdata->custom_person))
		{
			return;
		}
		$people = $requestObject->formdata->custom_person;
		if (!is_array($people) || count($people) == 0)
		{
			return;
		}
		JArrayHelper::toInteger($people);
		$peopleids = implode(",", $people);

		$db = JFactory::getDBO();

		foreach ($testevent->repetitions as $repeat)
		{

			$sql = "SELECT rpt.*, det.*, evt.*,  pers.title as person FROM #__jevents_repetition as rpt ";
			$sql .= " LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id=rpt.eventdetail_id ";
			$sql .= " LEFT JOIN #__jevents_vevent as evt ON evt.ev_id=rpt.eventid ";
			$sql .= " LEFT JOIN #__jev_peopleeventsmap as persmap ON det.evdet_id=persmap.evdet_id ";
			$sql .= " LEFT JOIN #__jev_people as pers ON pers.pers_id=persmap.pers_id ";
			$sql .= " WHERE rpt.eventid<>" . intval($eventid) . " AND rpt.startrepeat<" . $db->Quote($repeat->endrepeat) . " AND rpt.endrepeat>" . $db->Quote($repeat->startrepeat);
			$sql .= " AND pers.pers_id IN ( " . $peopleids . " ) and pers.overlaps=1";
			$sql .= " GROUP BY rpt.rp_id";
			$db->setQuery($sql);
			$conflicts = $db->loadObjectList();
			if ($conflicts && count($conflicts) > 0)
			{
				foreach ($conflicts as &$conflict)
				{
					$conflict->conflictCause = JText::sprintf("JEV_PEOPLE_CLASH", $conflict->person);
				}
				unset($conflict);
				$overlaps = array_merge($overlaps, $conflicts);
			}
		}

	}

	function onCheckRepeatOverlaps(& $repeat, & $overlaps, $eventid, $requestObject)
	{
		if (!isset($requestObject->formdata->custom_person))
		{
			return;
		}
		$people = $requestObject->formdata->custom_person;
		if (!is_array($people) || count($people) == 0)
		{
			return;
		}
		JArrayHelper::toInteger($people);
		$peopleids = implode(",", $people);

		$sql = "SELECT rpt.*, det.*, evt.*,  pers.title as person FROM #__jevents_repetition as rpt ";
		$sql .= " LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id=rpt.eventdetail_id ";
		$sql .= " LEFT JOIN #__jevents_vevent as evt ON evt.ev_id=rpt.eventid ";
		$sql .= " LEFT JOIN #__jev_peopleeventsmap as persmap ON det.evdet_id=persmap.evdet_id ";
		$sql .= " LEFT JOIN #__jev_people as pers ON pers.pers_id=persmap.pers_id ";
		$sql .= " WHERE rpt.eventid<>" . intval($eventid) . " AND rpt.startrepeat<" . $db->Quote($repeat->endrepeat) . " AND rpt.endrepeat>" . $db->Quote($repeat->startrepeat);
		$sql .= " AND pers.pers_id IN ( " . $peopleids . " ) and pers.overlaps=1";
		$sql .= " GROUP BY rpt.rp_id";
		$db->setQuery($sql);
		$conflicts = $db->loadObjectList();
		if ($conflicts && count($conflicts) > 0)
		{
			foreach ($conflicts as &$conflict)
			{
				$conflict->conflictCause = JText::sprintf("JEV_PEOPLE_CLASH", $conflict->person);
			}
			unset($conflict);
			$overlaps = array_merge($overlaps, $conflicts);
		}

	}

	private function setupCategorySpecificTypes($script)
	{
		// Get all the categories and their parentage
		$db = JFactory::getDBO();
		if (JVersion::isCompatible("1.6.0"))
		{
			$db->setQuery("SELECT id, parent_id from #__categories where extension='com_jevents' and published=1");
		}
		else
		{
			$db->setQuery("SELECT id, parent_id from #__categories where section='com_jevents' and published=1");
		}
		$catlist = $db->loadObjectList("id");

		$cats = array();
		foreach ($catlist as $cat)
		{
			// extract the complete ancestry
			if (!array_key_exists($cat->id, $cats))
			{
				$cats[$cat->id] = array();
				$cats[$cat->id][] = $cat->id;
				$parent = ($cat->parent_id > 0 && array_key_exists($cat->parent_id, $catlist)) ? $catlist[$cat->parent_id] : false;
				while ($parent)
				{
					$cats[$cat->id][] = $parent->id;
					$parent = ($parent->parent_id > 0 && array_key_exists($parent->parent_id, $catlist)) ? $catlist[$parent->parent_id] : false;
				}
			}
		}

		// Must set this up for empty category too
		$cats[0] = array();
		$cats[][] = 0;

		$cats = json_encode($cats);

		// setup required fields script
		$doc = JFactory::getDocument();
		$script2 = <<<SCRIPT
// category conditional people
var JevrCategoryPeople = {
	fields: new Array(),
	cats: $cats,
	setup:function (){
		if (!$('catid')) return;
		var catidel = $('catid');
		var catid = catidel.value;
		var cats = this.cats[catid];
		// These are the ancestors of this cat
		
		this.fields.each(function (item,i) {
			if (item.catids.length==0) return;
			
			var elem = $(document).getElement(".jevplugin_people"+item.id);

			// hide the item by default
			elem.style.display="none";
			
			if (catidel.multiple){
				\$A(catidel.options).each(function(opt) {
					if (opt.selected) {
						catid = opt.value;
						cats = JevrCategoryPeople.cats[catid];
						\$A(cats).each (function(cat,i){
							\$A(item.catids).each (function(cat2,i){
								if (cat==cat2){
									elem.style.display="table-row";
								}
							});
							if (\$A(item.catids).contains(parseInt(cat))){
								//alert("matched "+cat + " cf "+item.catids);
								elem.style.display="table-row";
							}
						});
					}
				}.bind(this));
			}
			else {
				\$A(cats).each (function(cat,i){
					\$A(item.catids).each (function(cat2,i){
						if (cat==cat2){
							elem.style.display="table-row";
						}
					});
					if (\$A(item.catids).contains(parseInt(cat))){
						//alert("matched "+cat + " cf "+item.catids);
						elem.style.display="table-row";
					}
				});
			}

		});
	}
};
window.addEvent("domready",function(){
	if (JevrCategoryPeople){
		JevrCategoryPeople.setup();
		if ($('catid')) {
			$('catid').addEvent('change',function(){
				JevrCategoryPeople.setup();
			});
		}
		if (!$('ics_id')) return;
		$('ics_id').addEvent('change',function(){
			setTimeout("JevrCategoryPeople.setup()",500);
		});
	}
});
SCRIPT;
		$doc->addScriptDeclaration($script2 . $script);

	}

	static function fieldNameArray($layout = 'detail', $showAddress = "1")
	{
		// only offer in detail view
		$plugin = JPluginHelper::getPlugin("jevents", "jevpeople");
		if (!$plugin)
			return "";
		$params = new JRegistry($plugin->params);

		if ($params->get("inlists") == 0 && ($layout != "detail" && $layout != "people" && $layout != "bloglist" ))
			return array();

		$labels = array();
		$values = array();

		$labels[] = JText::_("JEV_PEOPLE_SUMMARY", true);
		$values[] = "JEV_PEOPLE_SUMMARY";

		if (($layout == "detail" && JRequest::getCmd("task") == "defaults.edit") || $layout == "bloglist" || $layout == "person" || $layout == "people" || ($layout == "detail" && JRequest::getCmd("task") == "people.detail") || ($layout == "detail" && JRequest::getCmd("task") == "icalrepeat.detail"))
		{
			$labels[] = JText::_("JEV_PEOPLE_TITLE", true);
			$values[] = "JEVPPL_TITLE";

			$labels[] = JText::_("JEV_PEOPLE_DESCRIPTION", true);
			$values[] = "JEVPPL_DESC";

			$labels[] = JText::_("JEV_PEOPLE_WEBSITE", true);
			$values[] = "JEVPPL_URL";

			$labels[] = JText::_("JEV_PEOPLE_CATEGORIES", true);
			$values[] = "JEVPPL_CATS";

			if ($showAddress == "1")
			{
				$labels[] = JText::_("JEV_PEOPLE_PHONE", true);
				$values[] = "JEVPPL_PHONE";

				$labels[] = JText::_("JEV_PEOPLE_STREET", true);
				$values[] = "JEVPPL_STREET";

				$labels[] = JText::_("JEV_PEOPLE_CITY", true);
				$values[] = "JEVPPL_CITY";

				$labels[] = JText::_("JEV_PEOPLE_STATE", true);
				$values[] = "JEVPPL_STATE";

				$labels[] = JText::_("JEV_PEOPLE_COUNTRY", true);
				$values[] = "JEVPPL_COUNTRY";

				$labels[] = JText::_("JEV_PEOPLE_POSTCODE", true);
				$values[] = "JEVPPL_PCODE";

				if ($layout != "bloglist")
				{
					$labels[] = JText::_("JEV_PEOPLE_MAP", true);
					$values[] = "JEVPPL_MAP";
				}
			}
		}
		if ($layout != "bloglist")
		{
			$labels[] = JText::_("COM_JEVPEOPLE_UPCOMING_EVENTS", true);
			$values[] = "JEVPPL_UPCOMING";
		}
		$labels[] = JText::_("COM_JEVPEOPLE_EVENTS_LINK", true);
		$values[] = "JEVPPL_EVENTSLINK";

		$labels[] = JText::_("JEV_PERSON_LINK_A", true);
		$values[] = "JEVPPL_A";
		$labels[] = JText::_("JEV_PERSON_LINK_CLOSE_A", true);
		$values[] = "JEVPPL_CLOSE_A";

		$labels[] = JText::_("JEV_PERSON_IMAGE", true);
		$values[] = "JEV_PIMAGE";

		$labels[] = JText::_("JEV_PERSON_THUMBNAIL", true);
		$values[] = "JEV_PTHUMB";

		JLoader::register('JevCfForm', JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/jevcfform.php");
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$template = $compparams->get("template", "");
		if ($template != "")
		{
			$html = "";
			$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
			if (file_exists($xmlfile))
			{
				$extrafields = ", cf.* ";
				$extrajoin = " LEFT JOIN #__jev_customfields2 as cf ON  cf.target_id=jp.pers_id AND targettype='com_jevpeople'";

				$nullparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$nullfields = array();
				$groups = $nullparams->getFieldsets();
				foreach ($groups as $group => $element)
				{
					if ($nullparams->getFieldCountByFieldSet($group))
					{
						$nullfields = array_merge($nullfields, $nullparams->renderToBasicArray('params', $group));
					}
				}

				foreach ($nullfields as $field)
				{
					$values[] = "JEVPCF_" . $field["name"];
					$labels[] = $field["label"];

					$label = JText::_("JEV_PEOPLECUSTOM_FIELD_LABEL");
					if (strpos($label, '%') === false)
					{
						$label = "%s Label";
					}
					$labels[] = JText::sprintf($label, $field["label"]);
					$values[] = $field["name"] . "_lbl";
				}
			}
		}

		$return = array();
		$return['group'] = JText::_("JEV_PEOPLE_ADDON", true);
		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

	}

	static function substitutefield($row, $code)
	{
		$compparams = JComponentHelper::getParams("com_jevpeople");

		if (!isset($row->_jevperson) && count($row->_jevpeople) > 0)
		{
			$row->_jevperson = $row->_jevpeople[0];
		}
		if ($code == "JEV_PEOPLE_SUMMARY")
		{
			if (isset($row->_jevpeopletext))
				return $row->_jevpeopletext;
		}
		if ($code == "JEVPPL_CATS")
		{
			if (isset($row->_jevperson))
			{
				$cats = array();
				for ($c = 0; $c < 10; $c++)
				{
					$field = "catname" . $c;
					if (isset($row->_jevperson->$field))
					{
						$cats[] = $row->_jevperson->$field;
					}
				}
				return implode(", ", $cats);
			}
		}
		if ($code == "JEV_PIMAGE")
		{
			if (isset($row->_personimage))
				return $row->_personimage;
			else if (isset($row->_jevperson->image))
			{
				$params = & JComponentHelper::getParams('com_media');
				$mediabase = JURI::root() . $params->get('image_path', 'images/stories');
				$folder = "jevents/jevpeople";
				return '<img src="' . $mediabase . '/' . $folder . '/' . $row->_jevperson->image . '" />';
			}
		}
		if ($code == "JEV_PTHUMB")
		{
			if (isset($row->_personthumb))
				return $row->_personthumb;
			else if (isset($row->_jevperson->image))
			{
				$params = & JComponentHelper::getParams('com_media');
				$mediabase = JURI::root() . $params->get('image_path', 'images/stories');
				$folder = "jevents/jevpeople";
				return '<img src="' . $mediabase . '/' . $folder . '/thumbnails/thumb_' . $row->_jevperson->image . '" />';
			}
		}
		if (strpos($code, "JEVPCF_") === 0)
		{
			$code = str_replace("JEVPCF_", "", $code);
			if (isset($row->jevp_custompeople[$code]))
			{
				return $row->jevp_custompeople[$code]["value"];
			}
			if (isset($row->_jevperson->customfields[$code]))
			{
				return $row->_jevperson->customfields[$code]["value"];
			}
		}

		if ($code == "JEVPPL_A")
		{
			if (isset($row->_jevperson->pers_id))
			{
				$link = JRoute::_('index.php?option=com_jevpeople&task=people.detail&pers_id=' . $row->_jevperson->pers_id . "&se=1" . "&title=" . JFilterOutput::stringURLSafe($row->_jevperson->title));
				return "<a href='$link'>";
			}
		}

		if ($code == "JEVPPL_CLOSE_A")
		{
			if (isset($row->_jevperson->pers_id))
			{
				return "</a>";
			}
		}

		if ($code == "JEVPPL_TITLE")
		{
			if (isset($row->_jevperson->title))
				return $row->_jevperson->title;
		}
		if ($code == "JEVPPL_DESC")
		{
			if (isset($row->_jevperson->description))
				return $row->_jevperson->description;
		}
		if ($code == "JEVPPL_URL")
		{
			if (isset($row->_jevperson->www))
				return $row->_jevperson->www;
		}
		if ($code == "JEVPPL_STREET")
		{
			if (isset($row->_jevperson->street))
				return $row->_jevperson->street;
		}
		if ($code == "JEVPPL_CITY")
		{
			if (isset($row->_jevperson->city))
				return $row->_jevperson->city;
		}
		if ($code == "JEVPPL_STATE")
		{
			if (isset($row->_jevperson->state))
				return $row->_jevperson->state;
		}
		if ($code == "JEVPPL_COUNTRY")
		{
			if (isset($row->_jevperson->country))
				return $row->_jevperson->country;
		}
		if ($code == "JEVPPL_PCODE")
		{
			if (isset($row->_jevperson->postcode))
				return $row->_jevperson->postcode;
		}
		if ($code == "JEVPPL_MAP")
		{
			if (isset($row->_jevperson->map))
			{
				return $row->_jevperson->map;
			}
			if (count($row->_jevpeople) > 1)
			{
				$showmap = false;
				foreach ($row->_jevpeople as $person)
				{
					if ($person->geozoom != 0)
					{
						$showmap = true;
						break;
					}
				}
				if ($showmap)
				{
					
					static $scriptloaded = false;
					if (!$scriptloaded)
					{
						JLoader::register('JEVHelper', JPATH_SITE . "/components/com_jevents/libraries/helper.php");
						JLoader::register('JevPeopleHelper', JPATH_ADMINISTRATOR . "/components/com_jevpeople/libraries/helper.php");

						$compparams = JComponentHelper::getParams("com_jevpeople");
						$googlekey = JevPeopleHelper::getApiKey();
						$googleurl = JevPeopleHelper::getApiUrl();
						$googlemapsurl = JevPeopleHelper::getMapsUrl();
						if ($googlekey != "")
						{
							JHTML::script($googleurl . '/maps/api/js?key=' . $googlekey . "&amp;sensor=false", true);
						}
						else
						{
							JHTML::script($googleurl . '/maps/api/js?sensor=false', true);
						}
						
						$scriptloaded = true;

						$root = JURI::root();
						$Itemid = JRequest::getInt("Itemid");
						$script = "var urlroot = '" . JURI::root() . "media/com_jevlocations/images/';\n";
						$script .= <<<SCRIPT
	var myMapMultiPeople = false;
	function addPointPeople(lat, lon,  persid, title, description, icon){
			// Create our "tiny" marker icon
			var blueIcon = new google.maps.MarkerImage(urlroot + icon,
			// This marker is 32 pixels wide by 32 pixels tall.
			new google.maps.Size(32, 32),
			// The origin for this image is 0,0 within a sprite
			new google.maps.Point(0,0),
			// The anchor for this image is the base of the flagpole at 0,32.
			new google.maps.Point(16, 32));
			// Set up our GMarkerOptions object
			var point = new google.maps.LatLng(lat,lon);
			markerOptions = { icon:blueIcon, draggable:false , map:myMapMultiPeople, icon:blueIcon, position:point};	

			var myMarkerMulti = new google.maps.Marker(markerOptions);

			var infowindow = new google.maps.InfoWindow({disableAutoPan:false, content: "<div style='color:rgb(134,152,150);font-weight: bold;max-width:250px!important;'>"+title+"<br/><br/><span style='color:#454545;font-weight:normal'>"+description+"<span></div>"});
			google.maps.event.addListener(myMarkerMulti, "mouseover", function(e) {
				infowindow.open(myMapMultiPeople,myMarkerMulti);
			});
			google.maps.event.addListener(myMarkerMulti, "mouseout", function(e) {
				infowindow.close(myMapMultiPeople,myMarkerMulti);
			});
			google.maps.event.addListener(myMarkerMulti, "click", function(e) {
				// use for event detail page
				document.location.replace("{$root}index.php?option=com_jevents&task=icalrepeat.detail&Itemid=$Itemid&evid="+evid);
			});
	}

	function myMaploadMultiPeople(){
SCRIPT;
						$minlon = 0;
						$minlat = 0;
						$maxlon = 0;
						$maxlat = 0;
						$first = true;
						foreach ($row->_jevpeople as $person)
						{
							if ($person->geozoom == 0)
								continue;
							if ($first)
							{
								$minlon = floatval($person->geolon);
								$minlat = floatval($person->geolat);
								$maxlon = floatval($person->geolon);
								$maxlat = floatval($person->geolat);
								$first = false;
							}
							$minlon = floatval($person->geolon) > $minlon ? $minlon : floatval($person->geolon);
							$minlat = floatval($person->geolat) > $minlat ? $minlat : floatval($person->geolat);
							$maxlon = floatval($person->geolon) < $maxlon ? $maxlon : floatval($person->geolon);
							$maxlat = floatval($person->geolat) < $maxlat ? $maxlat : floatval($person->geolat);
						}
						if ($minlon == $maxlon)
						{
							$minlon-=0.002;
							$maxlon+=0.002;
						}
						if ($minlat == $maxlat)
						{
							$minlat-=0.002;
							$maxlat+=0.002;
						}
						$midlon = ($minlon + $maxlon) / 2.0;
						$midlat = ($minlat + $maxlat) / 2.0;

						$script.=<<<SCRIPT

		if (!document.getElementById("gmapMultiPeople")) return;
		var myOptionsPeople = {
			scrollwheel: false,
			center: new google.maps.LatLng($midlat,$midlon),
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}

		myMapMultiPeople = new google.maps.Map(document.getElementById("gmapMultiPeople"),myOptionsPeople );

		var bounds = new google.maps.LatLngBounds(new google.maps.LatLng($minlat,$minlon), new google.maps.LatLng($maxlat,$maxlon));
	
SCRIPT;
						foreach ($row->_jevpeople as $person)
						{
							if ($person->pers_id == 0)
								continue;

							if (isset($person->mapicon) && $person->mapicon != "")
							{
								$icon = $person->mapicon;
							}
							else
							{
								$icon = "blue-dot.png";
							}
							$script.="	addPointPeople($person->geolat,$person->geolon,$person->pers_id, '" . addslashes($person->title) . "', '" . str_replace(array("\n", "\r"), "", addslashes($person->description)) . "', '$icon');\n";
						}
						/*
						  // add polyline
						  $points = array();
						  foreach ($row->_jevpeople  as $person)
						  {
						  if ($person->pers_id == 0)
						  continue;
						  $points[] = "new google.maps.LatLng($person->geolat,$person->geolon)";
						  }
						  $script.="	var flightPlanCoordinates = [\n";
						  $script.= implode(",\n",$points);
						  $script.="];\n";
						  $script.="var flightPath = new google.maps.Polyline({
						  path: flightPlanCoordinates,
						  strokeColor: '#FF0000',
						  strokeOpacity: 1.0,
						  strokeWeight: 2
						  });
						  flightPath.setMap(myMapMultiPeople);
						  ";
						 */
						$script.=<<<SCRIPT
	myMapMultiPeople.fitBounds(bounds);
	};
	window.addEvent("load",function (){window.setTimeout("myMaploadMultiPeople()",1000);});

SCRIPT;
						$document = JFactory::getDocument();
						$document->addScriptDeclaration($script);
					}
					$jevplugin = JPluginHelper::getPlugin("jevents", "jevpeople");
					JPluginHelper::importPlugin("jevents", $jevplugin->name);
					$jevpluginparams = new JRegistry($jevplugin->params);
					$map = '<div id="gmapMultiPeople" style="width:' . $jevpluginparams->get("gwidth", 200) . 'px; height:' . $jevpluginparams->get("gheight", 150) . 'px;overflow:hidden;"></div>';

					return $map;
				}
			}
			else if (isset($row->_jevperson) && $row->_jevperson->geolon != 0 && $row->_jevperson->geolat != 0)
			{
				$person = $row->_jevperson;
				JLoader::register('JEVHelper', JPATH_SITE . "/components/com_jevents/libraries/helper.php");
				JLoader::register('JevPeopleHelper', JPATH_ADMINISTRATOR . "/components/com_jevpeople/libraries/helper.php");

				$compparams = JComponentHelper::getParams("com_jevpeople");
				$googlekey = JevPeopleHelper::getApiKey();
				$googleurl = JevPeopleHelper::getApiUrl();
				$googlemapsurl = JevPeopleHelper::getMapsUrl();
				if ($googlekey != "")
				{
					JHTML::script($googleurl . '/maps/api/js?key=' . $googlekey . "&amp;sensor=false", true);
				}
				else
				{
					JHTML::script($googleurl . '/maps/api/js?sensor=false', true);
				}

				$long = $person->geolon;
				$lat = $person->geolat;
				$zoom = $person->geozoom;
				$subtitle = str_replace(" ", "+", $person->title);

				$script = "var urlroot = '" . JURI::root() . "/media/com_jevlocations/images/';\n";
				$script.=<<<SCRIPT
	var globallong = $long;
	var globallat = $lat;
	var globalzoom = $zoom;
	var globaltitle = "$subtitle";
	var googleurl = "$googleurl";
	var googlemapsurl = "$googlemapsurl";
	var maptype = "ROADMAP";
SCRIPT;
				$document = JFactory::getDocument();
				$document->addScriptDeclaration($script);

				JHTML::script('components/com_jevpeople/assets/js/persondetail.js');

				$jevplugin = JPluginHelper::getPlugin("jevents", "jevpeople");
				JPluginHelper::importPlugin("jevents", $jevplugin->name);
				$jevpluginparams = new JRegistry($jevplugin->params);
				$map = '<div id="gmapppl" style="width:' . $jevpluginparams->get("gwidth", 200) . 'px; height:' . $jevpluginparams->get("gheight", 150) . 'px;overflow:hidden;"></div>';

				return $map;
			}
		}
		if ($code == "JEVPPL_A")
		{
			if (isset($row->_jevperson->pers_id))
			{

				$plugin = & JPluginHelper::getPlugin("jevents", "jevpeople");
				$params = new JRegistry($plugin->params);
				$pers_id = $row->_jevperson->pers_id;
				$detailpopup = $params->get("detailpopup", 0);
				$menuItem = $params->get("target_itemid", 0);
				if ($detailpopup)
				{
					JHTML::_('behavior.modal');
					$baseUrl = "index.php?option=com_jevpeople&task=people.detail&tmpl=component&pers_id=$pers_id&title=" . JFilterOutput::stringURLSafe($row->_jevperson->title);
					if ($menuItem != 0)
					{
						$baseUrl .= "&ItemId=" . $menuItem;
					}
					$persUrl = JRoute::_($baseUrl);
					$pwidth = $params->get("pwidth", "750");
					$pheight = $params->get("pheight", "500");
					return "<a href='$persUrl' class='modal' rel='{handler:\"iframe\",\"size\": {\"x\": $pwidth, \"y\": $pheight}}'>";
				}
				else
				{
					$baseUrl = "index.php?option=com_jevpeople&task=people.detail&se=1&pers_id=$pers_id&title=" . JFilterOutput::stringURLSafe($row->_jevperson->title);
					if ($menuItem != 0)
					{
						$baseUrl .= "&ItemId=" . $menuItem;
					}
					$persUrl = JRoute::_($baseUrl);
					return "<a href='$persUrl' >";
				}
			}
		}
		if ($code == "JEVPPL_CLOSE_A")
		{
			if (isset($row->_jevperson->pers_id))
			{
				return "</a>";
			}
		}
		if ($code == "JEVPPL_UPCOMING")
		{
			if (!isset($row->_jevperson->pers_id) || $row->_jevperson->pers_id == 0)
			{
				return "";
			}
			ob_start();
			$compparams = JComponentHelper::getParams("com_jevpeople");
			require_once (JPATH_SITE . "/modules/mod_jevents_latest/helper.php");

			$jevhelper = new modJeventsLatestHelper();
			$theme = JEV_CommonFunctions::getJEventsViewName();

			JPluginHelper::importPlugin("jevents");
			$viewclass = $jevhelper->getViewClass($theme, 'mod_jevents_latest', $theme . '/' . "latest", $compparams);

			// record what is running - used by the filters
			$registry = & JRegistry::getInstance("jevents");
			$registry->set("jevents.activeprocess", "mod_jevents_latest");
			$registry->set("jevents.moduleid", "cb");

			$menuitem = intval($compparams->get("targetmenu", 0));
			if (isset($row->_jevperson->targetmenu) && $row->_jevlocation->targetmenu > 0)
			{
				$menuitem = $row->_jevperson->targetmenu;
			}
			if ($menuitem > 0)
			{
				$compparams->set("target_itemid", $menuitem);
			}
			// ensure we use these settings
			$compparams->set("modlatest_useLocalParam", 1);
			// disable link to main component
			$compparams->set("modlatest_LinkToCal", 0);

			$registry->set("jevents.moduleparams", $compparams);

			$loclkup_fv = JRequest::setVar("peoplelkup_fv", $row->_jevperson->pers_id);
			$modview = new $viewclass($compparams, 0);
			echo $modview->displayLatestEvents();
			JRequest::setVar("peoplelkup_fv", $loclkup_fv);

			echo "<br style='clear:both'/>";

			$task = $compparams->get("view", ",month.calendar");
			$link = JRoute::_("index.php?option=com_jevents&task=$task&peoplelkup_fv=" . $row->_jevperson->pers_id . "&Itemid=" . $menuitem);

			echo "<strong>" . JText::sprintf("COM_JEVPEOPLE_ALL_EVENTS", $link) . "</strong>";
			return ob_get_clean();
		}


		if ($code == "JEVPPL_EVENTSLINK")
		{
			$compparams = JComponentHelper::getParams("com_jevpeople");
			if (!isset($row->_jevperson->pers_id) || $row->_jevperson->pers_id == 0)
			{
				return "";
			}

			$menuitem = intval($compparams->get("targetmenu", 0));
			if (isset($row->_jevperson->targetmenu) && $row->_jevlocation->targetmenu > 0)
			{
				$menuitem = $row->_jevperson->targetmenu;
			}

			$task = $compparams->get("view", ",month.calendar");
			$link = JRoute::_("index.php?option=com_jevents&task=$task&peoplelkup_fv=" . $row->_jevperson->pers_id . "&Itemid=" . $menuitem);

			return JText::sprintf("COM_JEVPEOPLE_ALL_EVENTS", $link);
		}
		return "";

	}

	private function checkAccess($user, $access)
	{
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
