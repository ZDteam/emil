<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('textarea');

class JFormFieldJevtextarea extends JFormFieldTextarea
{

	protected	$type = 'Jevtextarea';

	public function getInput()
	{
		// Initialize some field attributes.
		$class		= $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		$disabled	= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$columns	= $this->element['cols'] ? ' cols="'.(int) $this->element['cols'].'"' : '';
		$rows		= $this->element['rows'] ? ' rows="'.(int) $this->element['rows'].'"' : '';

		$this->value = str_replace('<br />', "\n", JText::_($this->value));

		// Initialize JavaScript field attributes.
		$onchange	= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

		return '<textarea name="'.$this->name.'" id="'.$this->id.'"' .
				$columns.$rows.$class.$disabled.$onchange.'>' .
				htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') .
				'</textarea>';
	}	

}