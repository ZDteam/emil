<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgJEventsJevcalendar extends JPlugin
{
	function plgJEventsJevcalendar(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$lang 		=& JFactory::getLanguage();
		$lang->load("plg_jevents_jevcalendar", JPATH_ADMINISTRATOR);
	}

	/**
	 * When editing a JEvents menu item can add additional menu constraints dynamically
	 *
	 */
	function onEditMenuItem(&$menudata, $value,$control_name,$name, $id, $param)
	{
		// already done this param
		if (isset($menudata[$id])) return;

		static $matchingextra = null;
		// find the parameter that matches jevc: (if any)
		if (!isset($matchingextra)){
			if (version_compare(JVERSION, '1.6.0', '>=') ){
				$params = $param->getGroup('params');
				foreach ($params as $key => $element){
					$val = $element->value;
					if (strpos($key,"jform_params_extras")===0 ){
						if (strpos($val,"jevc:")===0){
							$matchingextra = $key;
							break;
						}
					}
				}
			}
			else {
				$keyvals = $param->toArray();
				foreach ($keyvals as $key=>$val) {
					if (strpos($key,"extras")===0 && strpos($val,"jevc:")===0){
						$matchingextra = str_replace("extras","",$key);
						break;
					}
				}
			}
			if (!isset($matchingextra)){
				$matchingextra = false;
			}
		}

		// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
		if ((($matchingextra==$id || $matchingextra=="jform_params_extras".$id) && strpos($value,"jevc:")===0) || (($value==""||$value=="0") && $matchingextra===false)){
			$matchingextra = $id;
			$invalue = str_replace("jevc:","",$value);
			$invalue = str_replace(" ","",$invalue);
			if (strlen($invalue)>0){
				$invalue = explode(",",$invalue);
				JArrayHelper::toInteger($invalue);
			}
			else {
				$invalue = array();
			}

			$db = & JFactory::getDBO();
			$sql = "SELECT * FROM #__jevents_icsfile as isc";
			$db->setQuery($sql);
			$calendars = @$db->loadObjectList('ics_id');

			$input = "<div style='float: left;align-text:left;'>";
			$input .= "<select multiple='multiple' id='jevcals' onchange='updateJevCalendar();'>";
			$selected = (count($invalue)==0)?"selected='selected'":"";
			if (!JVersion::isCompatible("3.0")){
				$input .= "<option value='' $selected>---</option>";
			}
			foreach ($calendars as $calendar) {
				$title=$calendar->label;
				$icsid=$calendar->ics_id;
				$selected = in_array($calendar->ics_id,$invalue)?"selected='selected'":"";
				$input .= "<option value='$icsid' $selected>$title</option>";
			}
			$input .= "</select>";
			if (version_compare(JVERSION, '1.6.0', '>=') ){
				$input .= '<input type="hidden"  name="'.$name.'"  id="jevcalendar" value="'.$value.'" />';
				// for CB plugins !!!
				$input .= '<input type="hidden"  name="'.$control_name.'['.$name.']"   id="compat_jevcalendar"  value="'.$value.'" />';				
			}
			else {
				$input .= '<input type="hidden"  name="'.$control_name.'['.$name.']"  id="jevcalendar" value="'.$value.'" />';
			}
			$input .= "</div>";

			$script = '
			function updateJevCalendar(){
				var select = document.getElement("select#jevcals");
				var input = document.getElementById("jevcalendar");
				var cbinput = document.getElementById("compat_jevcalendar");
				input.value="";
				if (cbinput) cbinput.value="";
				$$(select.options).each(
					function(item,index){
						if (item.selected) {
							// if select none - reset everything else
							if (item.value=="") {
								select.selectedIndex=0;
								return;
							}
							if (input.value!="") input.value+=",";
							input.value+="jevc:"+item.value;
							if (cbinput) {
								if (cbinput.value!="") cbinput.value+=",";
								cbinput.value+="jevc:"+item.value;
							}
						}
					}
				);
			}
			';
			$document = JFactory::getDocument();
			$document->addScriptDeclaration($script);

			$data = new stdClass();
			$data->name = "calendars";
			$data->html = $input;
			$data->label = JText::_("JEV_SPECIFIED_CALENDARS");
			$data->description = JText::_("JEV_SPECIFIED_CALENDARS_DESC");
			$data->options = array();
			$menudata[$id] = $data;
		}
	}

	function onSelectIcals( &$query){
		if(JFactory::getApplication()->isAdmin()) {
			return;
		}

		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams("com_jevents");

		// If loading from a module then get the modules params from the registry
		$reg =& JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams){
			$compparams = $modparams;
		}

		for ($extra = 0;$extra<20;$extra++){
			$extraval = $compparams->get("extras".$extra, false);
			if (strpos($extraval,"jevc:")===0){
				break;
			}
		}
		if (!$extraval) return true;

		$invalue = str_replace("jevc:","",$extraval);
		$invalue = str_replace(" ","",$invalue);
		if (strlen($invalue)>0){
			$invalue = explode(",",$invalue);
			JArrayHelper::toInteger($invalue);
		}
		else {
			return true;
		}

		$query .= " AND ical.ics_id IN (".implode(",",$invalue).")";
		
	}


	function onListIcalEvents( & $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby=false)
	{
		if(JFactory::getApplication()->isAdmin()) {
			return;
		}
		
		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams("com_jevents");

		// If loading from a module then get the modules params from the registry
		$reg =& JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams){
			$compparams = $modparams;
		}

		// if its called from a module then we don't need the taglookup filter if we are ignoring the filter module
		if (!$modparams || ! $modparams->get("ignorefiltermodule", false)){
			if (version_compare(JVERSION, '1.6.0', '>=') ){
				$pluginsDir = JPATH_ROOT."/".'plugins'."/".'jevents'."/".'jevcalendar';
			}
			else {
				$pluginsDir = JPATH_ROOT."/".'plugins'."/".'jevents';
			}
			$filters = jevFilterProcessing::getInstance(array("calendar","multicalendar"),$pluginsDir."/"."filters"."/");
			$filters->setWhereJoin($extrawhere,$extrajoin);
		}

		for ($extra = 0;$extra<20;$extra++){
			$extraval = $compparams->get("extras".$extra, false);
			if (strpos($extraval,"jevc:")===0){
				break;
			}
		}
		if (!$extraval) return true;

		$invalue = str_replace("jevc:","",$extraval);
		$invalue = str_replace(" ","",$invalue);
		if (strlen($invalue)>0){
			$invalue = explode(",",$invalue);
			JArrayHelper::toInteger($invalue);
		}
		else {
			return true;
		}

		$extrawhere[]  = "icsf.ics_id IN (".implode(",",$invalue).")";
		return true;
	}

	function onListEventsById( & $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{
		if(JFactory::getApplication()->isAdmin()) {
			return;
		}

		if (version_compare(JVERSION, '1.6.0', '>=') ){
			$pluginsDir = JPATH_ROOT."/".'plugins'."/".'jevents'."/".'jevcalendar';
		}
		else {
			$pluginsDir = JPATH_ROOT."/".'plugins'."/".'jevents';
		}
		$filters = jevFilterProcessing::getInstance(array("calendar","multicalendar"),$pluginsDir."/"."filters"."/");
		$filters->setWhereJoin($extrawhere,$extrajoin);
		
		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams("com_jevents");

		// If loading from a module then get the modules params from the registry
		$reg =& JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams){
			$compparams = $modparams;
		}

		for ($extra = 0;$extra<20;$extra++){
			$extraval = $compparams->get("extras".$extra, false);
			if (strpos($extraval,"jevc:")===0){
				break;
			}
		}
		if (!$extraval) return true;

		$invalue = str_replace("jevc:","",$extraval);
		$invalue = str_replace(" ","",$invalue);
		if (strlen($invalue)>0){
			$invalue = explode(",",$invalue);
			JArrayHelper::toInteger($invalue);
		}
		else {
			return true;
		}

		$extrawhere[]  = "icsf.ics_id IN (".implode(",",$invalue).")";
		return true;
	}


}