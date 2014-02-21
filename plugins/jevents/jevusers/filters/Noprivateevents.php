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
class jevNoprivateeventsFilter extends jevFilter
{
	private $ignorefilter = false;
	
	function __construct($tablename, $filterfield, $isstring=true){
		$this->filterType="jevnopriv";
		$this->filterNullValue="";
		parent::__construct($tablename,$filterfield, true);
		$user = JFactory::getUser();
		if (JEVHelper::isAdminUser($user)){
			$this->ignorefilter = true;
		}
	}

	function _createFilter($prefix=""){
		if ($this->ignorefilter) return "";
		if (!$this->filterField ) return "";
                $user = JFactory::getUser();
                if (JEVHelper::isAdminUser($user)){
                    return "";
                }
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
		if ($user->id==0) return "";
		
		$filter = "(jum.privateevent IS NULL OR jum.privateevent=0)";
		return $filter;
	}

	// always used in conjunction with private events filter so no need for join
	function _createJoinFilter($prefix=""){
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