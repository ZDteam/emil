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

// strips out private events
class jevPrivateeventsFilter extends jevFilter
{
	private $jomsocial = false;
	private $cb = false;
	private $groupjive = false;
	private $ignorefilter = false;

	function __construct($tablename, $filterfield, $isstring=true){
		$this->filterType="jevpriv";
		$this->filterNullValue="";
		parent::__construct($tablename,$filterfield, true);

		if (JFolder::exists(JPATH_SITE.'/components/com_community')){
			if (JComponentHelper::isEnabled("com_community")) {
				$this->jomsocial = true;
			}
		}

		if (JFolder::exists(JPATH_SITE.'/components/com_comprofiler')){
			if (JComponentHelper::isEnabled("com_comprofiler")) {
				$this->cb = true;
				if (JFile::exists(JPATH_SITE."/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.php")){
					$this->groupjive = true;
				}
			}
		}

		JPluginHelper::importPlugin('jevents');
		$plugin = JPluginHelper::getPlugin("jevents","jevusers");
		$this->params = new JRegistry($plugin->params);
		if ($this->params->get("allowadmin",0)){
			// This code is used by the iCals code with a spoofed user so check if this is what is happening
			if (JRequest::getString("jevtask", "") == "icals.export")
			{
				$registry = & JRegistry::getInstance("jevents");
				$user = $registry->get("jevents.icaluser", false);
				if (!$user)
					$user = JFactory::getUser();
			}
			else
			{
				$user = JFactory::getUser();
			}
			if (JEVHelper::isAdminUser($user)){
				$this->ignorefilter = true;
			}
		}

	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";

		if ($this->ignorefilter) return "";

		// This code is used by the iCals code with a spoofed user so check if this is what is happening
		if (JRequest::getString("jevtask", "") == "icals.export")
		{
			$registry = & JRegistry::getInstance("jevents");
			$user = $registry->get("jevents.icaluser", false);
			if (!$user)
				$user = JFactory::getUser();
		}
		else
		{
			$user = JFactory::getUser();
		}
		if ($this->jomsocial || $this->cb ){
			/*
			$filter = "(jum.privateevent IS NULL OR jum.privateevent=0 OR jum.privateevent=3 OR jum.user_id=".intval($user->id)." OR (jum.privateevent=1 AND jum.user_id=".intval($user->id).")"
			." OR (jum.privateevent=2 AND jcc.connect_from=ev.created_by AND jcc.connect_to=".intval($user->id).")"
			." OR (jum.privateevent=4 AND jcgm.groupid=jum.groupid AND jcgm.memberid=".intval($user->id).")"
			.")";
			*/
			$filter = "(jum.privateevent IS NULL OR jum.privateevent=0 OR jum.privateevent=3 OR jum.user_id=".intval($user->id)." OR (jum.privateevent=1 AND jum.user_id=".intval($user->id).")";
			if ($this->jomsocial) {
				//$filter .= " OR (jum.privateevent=2 AND jcc.connect_from=ev.created_by AND jcc.connect_to=".intval($user->id).")";
				//$filter .= " OR (jum.privateevent=4 AND jcgm.groupid=jum.groupid AND jcgm.memberid=".intval($user->id).")";
				$filter .= " OR (jum.privateevent=2 AND jcc.connect_from=ev.created_by )";
				$filter .= " OR (jum.privateevent=4 AND jcgm.groupid=jum.groupid )";
			}

			//if ($this->cb) $filter .= " OR (jum.privateevent=6 AND cbcm.memberid=ev.created_by AND cbcm.referenceid=".intval($user->id).")";
			//if ($this->groupjive) $filter .= " OR (jum.privateevent=5 AND gjgm.group=jum.groupid AND gjgm.user_id=".intval($user->id).")";
			if ($this->cb) $filter .= " OR (jum.privateevent=6 AND cbcm.memberid=ev.created_by)";
			if ($this->groupjive) $filter .= " OR (jum.privateevent=5 AND gjgm.group=jum.groupid)";
			$filter .=")";

		}
		else {
			$filter = "(jum.privateevent IS NULL OR jum.privateevent=0 OR jum.privateevent=3 OR jum.user_id=".intval($user->id).")";
		}
		return $filter;
	}

	function _createJoinFilter($prefix=""){
		if ($this->ignorefilter) return "";
		if ($this->jomsocial || $this->cb ){
			$user = JFactory::getUser();
			$this->needsgroupby = true;
			$filter = "#__jev_usereventsmap as jum ON jum.evdet_id=det.evdet_id";
			if ($this->jomsocial) {
				// only interested in joining connections where connect to is correct
				$filter .= " LEFT JOIN #__community_connection as jcc on jcc.connect_from=ev.created_by AND jcc.status=1 AND jcc.group=0  AND jcc.connect_to=".intval($user->id);
				// JomSocial Groups but onlu where user is a member
				$filter .= " LEFT JOIN #__community_groups_members as jcgm on jcgm.groupid=jum.groupid AND jcgm.approved=1  AND jcgm.memberid=".intval($user->id);
			}
			if ($this->cb) {
				// only join to rows with correct reference_id
				$filter .= " LEFT JOIN #__comprofiler_members as cbcm on cbcm.memberid=ev.created_by AND cbcm.accepted=1 AND cbcm.pending=0 AND cbcm.referenceid=".intval($user->id);
				// GroupJive Groups only those with correct user_id
				if ($this->groupjive)  $filter .= " LEFT JOIN #__groupjive_users as gjgm on gjgm.group=jum.groupid AND gjgm.status=1  AND gjgm.user_id=".intval($user->id);
			}
			return $filter;
		}
		else {
			return  "#__jev_usereventsmap as jum ON jum.evdet_id=det.evdet_id";
		}
	}

	function _createfilterHTML(){

		if (!$this->filterField) return "";

		$db = JFactory::getDBO();

		$filterList=array();
		$filterList["title"]="";
		$filterList["html"] = "<input type='text' name='".$this->filterType."_fv'  id='".$this->filterType."_fv'  class='evuserssearch'  value='".$this->filter_value."' />";

		return $filterList;

	}
}