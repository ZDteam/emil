<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component frontend
 *
 * @static
 */
class IconicViewYear extends JEventsIconicView 
{
	function listevents($tpl = null)
	{
		$document =& JFactory::getDocument();						
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);

	}	


	function getAdjacentYear($year,$month,$day, $direction=1)
	{
		$d1 = JevDate::mktime(0,0,0,$month,$day,$year+$direction);
		$day = JevDate::strftime("%d",$d1);
		$year = JevDate::strftime("%Y",$d1);
		
		$cfg = & JEVConfig::getInstance();
		$earliestyear =  $cfg->get('com_earliestyear');
		$latestyear = $cfg->get('com_latestyear');
		if ($year>$latestyear || $year<$earliestyear){
			return false;
		}
		
		$month = JevDate::strftime("%m",$d1);
		$task = JRequest::getString('jevtask');
		$Itemid = JEVHelper::getItemid();
		if (isset($Itemid)) $item= "&Itemid=$Itemid";
		else $item="";
		return JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=$task$item&year=$year&month=$month&day=$day");
	}
	function getPrecedingYear($year,$month,$day)
	{
		return 	$this->getAdjacentYear($year,$month,$day,-1);
	}
	function getFollowingYear($year,$month,$day)
	{
		return 	$this->getAdjacentYear($year,$month,$day,+1);
	}
	
}
