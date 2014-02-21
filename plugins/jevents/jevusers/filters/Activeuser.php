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
class jevActiveuserFilter extends jevFilter
{

	function __construct($tablename, $filterfield, $isstring=true){
		$this->filterType="jevu";
		$this->filterNullValue="";
		parent::__construct($tablename,$filterfield, true);
		//This filter has memory!

		// Set the current user value for the main user filter to pick up
		if ($this->filter_value>0) JRequest::setVar($this->filterType.'_fv', $this->filter_value );
	}
	
	function _createFilter($prefix=""){return "";}


	function _createfilterHTML(){

		$filterList=array();
		
		if (!$this->filterField) return $filterList;

		$db = JFactory::getDBO();

		if ($this->filter_value==$this->filterNullValue || intval($this->filter_value)<0) return $filterList;
		
		$user = JFactory::getUser(intval($this->filter_value));
		if (!$user) return $filterList;
		
		$filterList["title"]=JText::_("Active_User");
		$filterList["html"] = $user->name." <input type='checkbox' name='".$this->filterType."_fv'  id='".$this->filterType."_fv'  value='".$this->filter_value."' checked='checked'/>";

		return $filterList;

	}
}