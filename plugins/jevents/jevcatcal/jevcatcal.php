<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgJEventsJevcatcal extends JPlugin
{
	private $whitelist;

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$lang 		=& JFactory::getLanguage();
		$lang->load("plg_jevents_jevcatcal", JPATH_ADMINISTRATOR);
		if (!isset($this->whitelist)){
			if (JRequest::getString("jevtask2")=="icalevent.edit" || JRequest::getString("jevtask")=="icalrepeat.edit"){
				$this->whitelist=array(0);
			}
			else {
				$this->whitelist = explode(",",$this->params->get("whitelist",""));
				 JArrayHelper::toInteger($this->whitelist );
				 if ($this->whitelist == array(0)){
					 $this->whitelist = array();
				 }
			}
		}
	}

	/*
	function onSelectIcals( &$query){
		global $mainframe;
		if($mainframe->isAdmin()) {
			return;
		}

		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams("com_jevents");

		// If loading from a module then get the modules params from the registry
		$reg =& JFactory::getConfig();
		$modparams = $reg->getValue("jev.modparams",false);
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
	*/

	function onGetAccessibleCategories(&$cats, $reindex=true){
		// are we authorised to do anything with this category or calendar
		$jevuser =& JEVHelper::getAuthorisedUser();
		
		if (!$jevuser || is_null($jevuser))  {
			if (is_array($cats)) {
				if (count($this->whitelist)>0) {
					$allowedcats = $this->whitelist;
					$incats = $cats;
					$count = count($incats);
					$keys = array_keys($incats);
					for ($i=0;$i<$count;$i++) {
						$key = $keys[$i];
						$incat = $incats[$key];
						if (is_object($incat) && isset($incat->id))	$incat = $incat->id;
						if ($incat>0 && !in_array($incat,$allowedcats)) unset($incats[$key]);
					}
					if ($reindex) $incats = array_values($incats);
					$cats = $incats;
				}
				else {
					$cats = array();
				}
			}
			else {
				if (count($this->whitelist)>0) {
					$incats = explode(",",$cats);
					$incats = array_merge($incats, $this->whitelist);
					$cats = implode(",",$incats);
				}
				else {
					$cats = -1;
				}
			}
			return true;
		}		
		
		$incats = $cats;
		$isarray = is_array($incats);
		if (!$isarray) $incats = explode(",",$incats);
				
		if ($jevuser->categories!="" && $jevuser->categories!="all"){
			$allowedcats = explode("|",$jevuser->categories);
			if (count($this->whitelist)>0) {
				$allowedcats = array_merge($allowedcats, $this->whitelist);
			}
			$count = count($incats);
			$keys = array_keys($incats);
			for ($i=0;$i<$count;$i++) {
				$key = $keys[$i];
				$incat = $incats[$key];
				if (is_object($incat) && isset($incat->id))	$incat = $incat->id; 
				if ($incat>0 && !in_array($incat,$allowedcats)) unset($incats[$key]);
			}
			if ($reindex) $incats = array_values($incats);
		}
		
		$cats = (!$isarray)?implode(",",$incats):$incats;
	}
	
	
	function onListIcalEvents( & $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby=false)
	{
		// are we authorised to do anything with this category or calendar
		$jevuser =& JEVHelper::getAuthorisedUser();
		
		
		$params = JComponentHelper::getParams("com_jevents");
		
		if (!$jevuser || is_null($jevuser))  {
			if (count($this->whitelist)>0) {
				if ($params->get("multicategory",0)){						
					$extrawhere[]  = "catmap.catid IN (".implode(",",$this->whitelist).")";
				}
				else {
					$extrawhere[]  = "ev.catid IN (".implode(",",$this->whitelist).")";					
				}
			}
			else {
				$extrawhere[] = "0";
			}
			return true;
		}
		
		if ($jevuser->calendars!="" && $jevuser->calendars!="all"){
			$allowedcals = explode("|",$jevuser->calendars);
			$extrawhere[]  = "icsf.ics_id IN (".implode(",",$allowedcals).")";
		}
		
		if ($jevuser->categories!="" && $jevuser->categories!="all"){
			$allowedcats = explode("|",$jevuser->categories);
			if (count($this->whitelist)>0) {
				$allowedcats = array_merge($allowedcats, $this->whitelist);
			}
			if ($params->get("multicategory",0)){						
				$extrawhere[]  = "catmap.catid IN (".implode(",",$allowedcats).")";
			}
			else {
				$extrawhere[]  = "ev.catid IN (".implode(",",$allowedcats).")";
			}
		}
		return true;
	}

	function onListEventsById( & $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{
		// are we authorised to do anything with this category or calendar
		$jevuser =& JEVHelper::getAuthorisedUser();
		
		if (!$jevuser || is_null($jevuser))  {
			$extrawhere[] = "0";
			return true;
		}

		$params = JComponentHelper::getParams("com_jevents");
		
		if ($jevuser->calendars!="" && $jevuser->calendars!="all"){
			$allowedcals = explode("|",$jevuser->calendars);
			$extrawhere[]  = "icsf.ics_id IN (".implode(",",$allowedcals).")";
		}
		
		if ($jevuser->categories!="" && $jevuser->categories!="all"){
			$allowedcats = explode("|",$jevuser->categories);
			if (count($this->whitelist)>0) {
				$allowedcats = array_merge($allowedcats, $this->whitelist);
			}
			if ($params->get("multicategory",0)){						
				$extrawhere[]  = "catmap.catid IN (".implode(",",$allowedcats).")";
			}
			else {
				$extrawhere[]  = "ev.catid IN (".implode(",",$allowedcats).")";
			}
		}
		return true;
	}


}