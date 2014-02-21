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


abstract class JevrFieldCheckbox extends JFormField
{
	function getInput(){
		echo "should not call this directly JevrFieldCheckbox<br/>";
	}

	protected function fixValue(&$value,  $fleshout = true)
	{
		if (is_string($value) && (strpos($value, "[") === 0 || strpos($value, "{") === 0))
		{
			$value = json_decode($value);
		}

		if (is_object($value))
		{
			$value = get_object_vars($value);
		}
		if (!is_array($value))
		{
			$value = array($value);
		}
		$count = count($value);
		$defaultValue = $this->attribute("default");
		if (strpos($defaultValue, "[") === 0 || strpos($defaultValue, "{") === 0)
		{
			$defaultValue = json_decode($defaultValue);
			if ($this->attribute("peruser") > 0)
			{
				$defaultValue = array($defaultValue);
			}
		}
		// if we have an attendee then checkbox may give us a non-full array so fill out but NOT with default values !!!
		if ($fleshout  &&  isset($this->attendee) && $count < $this->currentAttendees)
		{
			$newvalues = array();
			$newvalues = array_pad($newvalues, $this->currentAttendees,"");

			foreach ($value as $g=>$val)
			{
				$newvalues[$g] = $val;
			}
			$value = $newvalues;
			
		}
		else if ($fleshout && !$this->attendee)
		{
			if ($this->attribute("peruser") > 0 && !is_array($defaultValue))
			{
				$defaultValue = array($defaultValue);
			}
			$value = $defaultValue;
		}

	}

	function toXML($field)
	{
		$result = array();
		$result[] = "<field";
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

			if ($k == "default" && is_array($v))
			{
				$v = json_encode($v);
			}
			$result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
		}
		$result[] = ">";
		if (is_string($field->options))
		{
			$field->options = @json_decode($field->options);
		}
		if (is_object($field->options))
		{
			for ($i = 0; $i < count($field->options->label); $i++)
			{
				if ($field->options->label[$i] == "")
					break;
				$result[] = "<option";
				$result[] = 'value="' . addslashes(htmlspecialchars($field->options->value[$i])) . '"';
				if (isset($field->options->price))
				{
					$result[] = 'price="' . addslashes(htmlspecialchars($field->options->price[$i])) . '"';
				}
				$result[] = ">" . addslashes(htmlspecialchars($field->options->label[$i])) . "</option>";
			}
		}
		$result[] = "</field>";
		$xml = implode(" ", $result);
		return $xml;

	}

	public function isVisible( $attendee, $guest)
	{
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		return RsvpAttendeeHelper::isVisibleStatic($this, $attendee, $guest, $this->nodes);	
	}

	public function addAttribute($name, $value)
	{
		// Add the attribute to the element, override if it already exists
		$this->element->attributes()->$name = $value;
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