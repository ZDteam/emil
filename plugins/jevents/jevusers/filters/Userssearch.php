<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: Search.php 1410 2009-04-09 08:13:54Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined('_JEXEC') or die( 'No Direct Access' );

// searches author of event
class jevUserssearchFilter extends jevFilter
{
	private $hasrsvp = false;
	private $hasaje = false;
	private $rsvpparams = null;
	private $rsvpversion = 1.2;
	private $ajeparams = null;
	private $params = null;

	function __construct($tablename, $filterfield, $isstring=true){
		$this->filterType="jevu";
		$this->filterNullValue="";
		parent::__construct($tablename,$filterfield, true);

		// Should these be ignored?
		$reg =& JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			$this->filter_value = $this->filterNullValue;
			return ;
		}
		
		// Only have memory on page with the module visible or
		$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue );

		// get plugin params
		$plugin =& JPluginHelper::getPlugin('jevents', 'jevusers');
		if (!$plugin) return "";
		
		$this->params = new JRegistry($plugin->params);

		// should we show session attendance
		if ($this->params->get("showattending",1) || $this->params->get("showattended",0)){
			$rsvpplugin = JPluginHelper::getPlugin("jevents","jevrsvppro");
			if (is_array($rsvpplugin) && count($rsvpplugin)==0){
				$rsvpplugin = JPluginHelper::getPlugin("jevents","jevrsvp");
			}
			if (is_array($rsvpplugin) && count($rsvpplugin)==0) {
				return;
			}
			else{
				$this->hasrsvp = true;
				$this->rsvpparams = new JRegistry($rsvpplugin->params);
				if ($this->rsvpparams->get("allowmaybe",-1)<0 || $this->rsvpparams->get("allowpending",-1)>0){
					$this->rsvpversion = 1;
				}
			}
		}
		
		// should we show session attendance
		if ($this->params->get("showattending",1) || $this->params->get("showattended",0)){
			$ajeplugin = JPluginHelper::getPlugin("jevents","jevsessions");
			if (is_array($ajeplugin) && count($ajeplugin)==0) {
				return;
			}
			else {
				$this->hasaje = true;
				$this->ajeparams = new JRegistry($ajeplugin->params);
			}
		}

	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";

		if (trim($this->filter_value)==$this->filterNullValue) return "";

		$db = JFactory::getDBO();
		$this->filter_value = explode(",",$this->filter_value);
		JArrayHelper::toInteger($this->filter_value);
		if (count($this->filter_value)==0) return "";
		$this->filter_value = implode(",",$this->filter_value);

		// should we show attendees too?
		if (($this->hasrsvp || $this->hasaje) && ($this->params->get("showattending",1) ||  $this->params->get("showattended",0))) {
			$filter = "( ev.created_by IN (".$this->filter_value.")";
			if ($this->hasrsvp){
				//$filter .= " OR uae.user_id =".$this->filter_value;
				//$extrawhere[]  = "((uatd.allrepeats=1 AND atdees.rp_id=0) OR (atd.allrepeats=0 AND atdees.rp_id=rpt.rp_id)) AND atd.allowregistration=1";
				$attendstate = "";
				if ($this->rsvpversion>=1.2){
					$attendstate = " AND uae.attendstate=1 ";
				}
				$filter .= " OR ( uae.user_id =".$this->filter_value. " AND ((uatd.allrepeats=1 AND uae.rp_id=0) OR (uatd.allrepeats=0 AND uae.rp_id=rpt.rp_id)) AND uatd.allowregistration>0 $attendstate)";
			}
			if ($this->hasaje && $this->params->get("showattending",1)){
				$filter .= " OR ajer.userid =".$this->filter_value;
			}
			if ($this->hasaje && $this->params->get("showattended",0)){
				$filter .= " OR ( ajer.userid =".$this->filter_value. " AND  ajer.didattend=1 ) ";
			}
			$filter .=")";
		}
		else {
			$filter = "ev.created_by IN (".$this->filter_value.")";
		}
		return $filter;
	}

	function _createJoinFilter($prefix=""){
		if (trim($this->filter_value)==$this->filterNullValue) return "";

		// should we show attendees too?
		if (($this->hasrsvp || $this->hasaje) && ($this->params->get("showattending",1) ||  $this->params->get("showattended",0))) {
			$this->needsgroupby = true;
			if ($this->hasrsvp){
				$return = " #__jev_attendance as uatd ON ev.ev_id=uatd.ev_id LEFT JOIN #__jev_attendees as uae ON uae.at_id=uatd.id AND uae.user_id = ".$this->filter_value;
			}
			if ($this->hasaje){
				if ($this->hasrsvp){
					$return .= " LEFT JOIN ";
				}
				$return .= " #__aje_sessions as ajes ON ev.ev_id=ajes.event_id LEFT JOIN #__aje_registrations as ajer ON ajes.session_id=ajer.session_id AND ajer.userid = ".$this->filter_value." AND ajer.status=2 ";
			}

			return $return;
		}
		return "";
	}

	function _createfilterHTML(){

		if (!$this->filterField) return "";

		$db = JFactory::getDBO();

		$filterList=array();
		$filterList["title"]="User Search";
		$filterList["html"] = "<input type='text' name='".$this->filterType."_fv'  id='".$this->filterType."_fv'  class='evuserssearch'  value='".$this->filter_value."' />";

		return $filterList;

	}
}