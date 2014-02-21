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
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JevrField extends JFormField
{

	function getInput(){
		echo "should not call this directly ".  get_class($this)."  is using JevrField<br/>";
	}
	
	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$name = $xmlElement->attributes()->name;
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');
		//make sure we have a valid label
		$label = $label ? $label : $name;
		$result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
		$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);

		if ($xmlElement->attributes('showinform') == "0")
		{
			$label = "";
			$result[0] = "";
			$result[1] = "";
		}

		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;

	}

	function currentAttendeeCount($node, $value)
	{
		if (is_array($value) && count($value) > 1)
		{
			return count($value) - 1;
		}
		return 1;

	}

	function toXML($field)
	{
		$result = array();
		$result[] = "<field ";
		foreach (get_object_vars($field) as $k => $v)
		{
			if ($k == "options" || $k == "html" || $k == "defaultvalue" || $k == "name")
				continue;
			if ($k == "field_id")
			{
				$k = "name";
				$v = "field" . $v;
			}
			if ($k == "params")
			{
				if (is_string($field->params))
				{
					$field->params = @json_decode($field->params);
				}
				if (is_object($field->params))
				{
					foreach (get_object_vars($field->params) as $label => $value)
					{
						$result[] = $label . '="' . addslashes($value) . '" ';
					}
				}
				continue;
			}

			$result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
		}
		$result[] = " />";
		$xml = implode(" ", $result);
		return $xml;

	}

	public function isVisible( $attendee, $guest)
	{
		$conditionnode = false;
		$cf = $this->attribute("cf");
		if ($cf == "")
		{
			return true;
		}
		if (!isset($attendee->params) || $attendee->params==""){
			return true;
		}
		if (JVersion::isCompatible("1.6")){
			$attendeefields = json_decode($attendee->params);
		}
		else {
			$attendeefields = new JRegistry();
			$attendeefields->loadINI($attendee->params);
			$attendeefields = $attendeefields->toObject();
		}
		if (!isset($attendeefields->$cf))
		{
			return true;
		}
		
		// search for field on which this node is conditioned
		foreach ($this->nodes as $cnode)
		{
			if ($cnode->fieldname == $cf)
			{
				$conditionnode = $cnode;
				break;
			}
		}
		if (!$conditionnode){
			return true;
		}
		
		$cfieldvalue = $attendeefields->$cf;
		// global condition
		if ($conditionnode->attribute("peruser") == 0)
		{
			// condition is visible then use the guest count
			if ($cfieldvalue == $this->attribute("cfvfv"))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		// individual or guest condition
		else
		{
			if ($this->attribute("peruser") > 0)
			{
				// check the index
				if (array_key_exists($guest, $cfieldvalue) && $cfieldvalue[$guest]==$this->attribute("cfvfv") ){
					return true;
				}
				else {
					return false;
				}
			}
			else {
				// you can't have an individual condition field driving a group value!
				return false;
			}				
		}

	}

	public function addAttribute($name, $value)
	{
		// Add the attribute to the element, override if it already exists
		@$this->element->addAttribute($name, $value);
	}

	
	
	public function attribute($attr, $default=""){
		if (!$this->element){
			return $default;
		}
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val)?(string)$val:$default;
		return $val;
	}

	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value) {
		$this->value = $value;
	}	
}