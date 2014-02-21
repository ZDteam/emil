<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: Multicalendar.php 3241 2012-02-08 09:01:25Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// ensure this file is being included by a parent file
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class jevMulticalendarFilter extends jevFilter
{
	function __construct($tablename, $filterfield, $isstring=true){

		// setup for all required function and classes
		$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
		if (file_exists($file) ) {
			include_once($file);
		}
		
		$this->filterType="multicalendar";
		$this->filterLabel=JText::_("Calendar");
		$this->filterNullValue=array(0);
		parent::__construct($tablename,$filterfield, true);

		// Should these be ignored?
		$reg =& JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			$this->filter_value = $this->filterNullValue;
			return;
		}
		JArrayHelper::toInteger($this->filter_value );

	}

	function _createFilter(){
		if (!$this->filterField ) return "";
		if (count($this->filter_value)==0 || $this->filter_value==$this->filterNullValue  || $this->filter_value[0]==0) return "";

		$filter = " ev.icsid IN (".implode(",",$this->filter_value).") ";
		return $filter;
	}

	/**
 * Creates javascript session memory reset action
 *
 */
	function _createfilterHTML(){

		if (!$this->filterField) return "";

		$filterList=array();
		$user = JFactory::getUser();
		$db = JFactory::getDBO();
		
		$query = "SELECT ics_id as value, label as text FROM #__jevents_icsfile WHERE state=1  AND access  " . (version_compare(JVERSION, '1.6.0', '>=') ?  ' IN (' . JEVHelper::getAid($user) . ')'  :  ' <=  ' . JEVHelper::getAid($user)) ." ORDER BY label ASC";
		$db->setQuery( $query );
		$cals = $db->loadObjectList();

		$list[] = JHTML::_( 'select.option', 0, JText::_("Search by Calendar"));
		$list = array_merge($list, $cals);

		$filterList=array();
		$filterList["title"]="<label class='evcallkup_label' for='".$this->filterType."_fv'>".$this->filterLabel."</label>";
		
		$filterList["html"] = JHTML::_( 'select.genericlist', $list, $this->filterType."_fv[]", "id='".$this->filterType."_fv' multiple='multiple' size='4' class='evmulticalkup'", 'value', 'text', $this->filter_value);
		$script = "JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:0});";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);
		
		/*
		$filterList["html"] = "<ul class='evmulticalkup'>";
		foreach ($list as $item) {
			$checked = in_array($item->value,$this->filter_value) ? " checked='checked' ": "";
			$filterList["html"]  .= "<li><label for='jevcal".$item->value."' >".$item->text." <input type='checkbox' id='jevcal".$item->value."' name='".$this->filterType."_fv[]' value='".$item->value."' $checked /></label></li>";
		}
		$filterList["html"]  .= "<ul>";
		*/
		
		return $filterList;

	}

}
