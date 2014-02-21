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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrFieldList.php");

class JFormFieldJevrpaymentoptionlist extends JevrFieldList
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrpaymentoptionlist';
	const name = 'jevrpaymentoptionlist';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrpaymentoptionlist.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
			if ($field->defaultvalue=="") {
				$field->defaultvalue="none";
			}
		}
		else
		{
			$id = '###';
		}
		ob_start();
?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrpaymentoptionlist"); ?><?php RsvpHelper::fieldId($id);?></div>
			<div class="rsvpclear"></div>

<?php
		RsvpHelper::hidden($id, $field, self::name);
		RsvpHelper::label($id, $field);
		RsvpHelper::tooltip($id, $field);
?>

		<div class="rsvplabel"><?php echo JText::_("RSVP_OPTIONS"); ?></div>
		<div class="rsvpinputs">
<?php echo JText::_("RSVP_NUMERIC_OPTION_NOTES"); ?><br/>
			<!-- Put the selected option here //-->
			<input type="hidden" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : "none"; ?>" />
<?php
		$engines = JFormFieldJevrpaymentoptionlist::fetchPluginPaymentOptions();
		$options = array();
		if ($field && $field->options != "")
		{
			$optionvalues = json_decode($field->options);
		}
		$maxvalue = -1;

		$countoptions = 0;
		if (isset($optionvalues))
		{
			foreach ($optionvalues->label as $lab)
			{
				if ($lab == "")
				{
					break;
				}
				$option = new stdClass();
				$option->value = $optionvalues->value[$countoptions];
				$option->surcharge = isset($optionvalues->surcharge) ? $optionvalues->surcharge[$countoptions] : 0;
				$option->label = $lab;
				$options[] = $option;
				$countoptions++;
			}
		}

		// add 5 blank options at the end
		for ($op = 0; $op < 5; $op++)
		{
			$option = new stdClass();
			$option->value = 'none';
			$option->surcharge = 0;
			$maxvalue++;
			$option->label = "";
			$options[] = $option;
		}
?>
		<input type="button" value="<?php echo JText::_("RSVP_NEW_OPTION") ?>" onclick="jevrpaymentoptionlist.newOption('<?php echo $id; ?>');"/>
		<table id="options<?php echo $id; ?>">
			<tr >
				<th><?php echo JText::_("RSVP_OPTION_TEXT") ?></th>
				<th><?php echo JText::_("RSVP_OPTION_VALUE") ?></th>
				<th><?php echo JText::_("RSVP_PAYMENT_METHOD_SURCHARGE") ?></th>
				<th><?php echo JText::_("RSVP_DEFAULT_VALUE") ?></th>
				<th/>
			</tr>
<?php
		for ($op = 0; $op < count($options); $op++)
		{
			$option = $options[$op];
			$style = "";
			if ($op > 0 && $op >= $countoptions)
			{
				$style = "style='display:none;'";
			}

			$checked = "";
			if (($field && $option->value == $field->defaultvalue) || (!$field && $option->value == "none" && $op==0))
			{
				$checked = "checked='checked'";
			}
?>
			<tr <?php echo $style; ?> >
				<td>
					<input type="text" class="inputlabel" name="options[<?php echo $id; ?>][label][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" value="<?php echo $option->label; ?>" <?php JFormFieldJevrpaymentoptionlist::buttonAction($id, $op); ?>/>
				</td>
				<td>
					<?php
					echo JHtml::_('jevrList.genericlist', $engines, "options[".$id."][value][]", JFormFieldJevrpaymentoptionlist::buttonAction($id, $op), 'value', 'text', $option->value,  "options".$id."_v_".$op);
					?>
				</td>
				<td>
					<input type="text" name="options[<?php echo $id; ?>][surcharge][]" id="options<?php echo $id; ?>_s_<?php echo $op; ?>" value="<?php echo $option->surcharge; ?>" class="jevfee_value"/>
				</td>
				<td>
					<input type="radio" value="1" onclick="jevrpaymentoptionlist.defaultOption(this, '<?php echo $id; ?>', '<?php echo $op; ?>');"  name="default<?php echo $id; ?>" <?php echo $checked; ?>/>
				</td>
				<td>
					<input type="button" value="<?php echo JText::_("RSVP_DELETE_OPTION") ?>" onclick="jevrpaymentoptionlist.deleteOption(this);"/>
				</td>
			</tr>
<?php
		}
?>
		</table>

	</div>
	<div class="rsvpclear"></div>

<?php
		RsvpHelper::required($id, $field);
		RsvpHelper::requiredMessage($id, $field);
		?>
		<input type="hidden" name="peruser[<?php echo $id; ?>]" value="-1" />
		<?php
		//RsvpHelper::peruser($id, $field);
		RsvpHelper::formonly($id, $field);
		RsvpHelper::showinform($id, $field);
		RsvpHelper::showindetail($id, $field);
		RsvpHelper::showinlist($id, $field);
		RsvpHelper::allowoverride($id, $field);
		RsvpHelper::accessOptions($id, $field);
		RsvpHelper::applicableCategories("facc[$id]", "facs[$id]", $id, $field ? $field->applicablecategories : "all");
?>

		<div class="rsvpclear"></div>

	</div>
	<div class='rsvpfieldpreview' id='<?php echo $id; ?>preview'>
		<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
		<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>'><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
		<select name="pdv[<?php echo $id; ?>]" id="pdv<?php echo $id; ?>" >
<?php
		foreach ($options as $option)
		{
			if ($option->label == "")
				continue;
			$selected = "";
			if (($field && $option->value == $field->defaultvalue) || (!$field && $option->value == ""))
			{
				$selected = "selected='selected'";
			}
?>
			<option value="<?php echo $option->value; ?>" <?php echo $selected; ?> ><?php echo $option->label; ?></option>
<?php
		}
?>
	</select>

</div>
<div class="rsvpclear"></div>
<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function paidOption(){
		return 1;
	}

	function buttonAction($id, $op)
	{
		return "";

	}

	function getInput()
	{
		//$name, $value, &$node
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$rsvpdata = $registry->get("rsvpdata");
		$row = $registry->get("event");
		$isWaiting = $registry->get("attendeeIsWaiting", false);

		if ($isWaiting) return JText::_("JEV_WAITING_ATTENDEES_PAY_LATER");
		
		$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . '  rsvp_'.$fieldname.' "' : 'class="inputbox rsvp_'.$fieldname.' "' );

		$hasprice = false;
		$options = array();
		$prices = array();
		$hassurchages = false;
		$surcharges = array();
		
		foreach ($this->element->children() as $option)
		{
			$val = (string)$option["value"];
			$text = (string)$option;
			if ($text=="Manual (Admin Only)" && !JFactory::getApplication()->isAdmin()){
				continue;
			}
			$surcharge = (string)$option["surcharge"];
			if (!is_null($surcharge))
			{
				$htmloption->surcharge = $surcharge;
				$surcharges[$val] = $surcharge;
				$hassurchages = true;
			}
			else
			{
				$surcharges[$val] = 0;
			}
			
			//$val = $valuepairs[$text];
			$htmloption = JHtml::_('select.option', $val, JText::_($text));
			$options[] = $htmloption;
		}

		if ($hassurchages)
		{
			$this->hasSurcharges = count($surcharges) > 0;
			$this->surchargesArray = $surcharges;
			$this->surcharges = json_encode($surcharges);

			$attribs .= " onchange='JevrFees.calculate(document.updateattendance);'";
		}
		
		if (count ($options)==1){
			foreach ($this->element->children() as $option)
			{
				$val = (string)$option["value"];
				$text = (string)$option;
				if ($text=="Manual (Admin Only)" && !JFactory::getApplication()->isAdmin()){
					continue;
				}
				$value = $val;
				$attribs .= " style='display:none'";
				return $text.JHtml::_('jevrList.genericlist', $options, $name, $attribs, 'value', 'text', $value, $id);
			}
		}
		else {			
			return JHtml::_('jevrList.genericlist', $options,  $name, $attribs, 'value', 'text', $value, $id);
		}

	}

	// use this JS function to fetch the fee calculation script!

	function fetchSurchargeScript($name, &$node, $control_name, $value)
	{
		if (isset($node->surcharges))
		{
			$surchargefunction = " function(name){return surchargeList(name, " . $node->surcharges . ");}";
			$peruser = $this->attribute("peruser");
			if (is_null($peruser))
			{
				$peruser = 0;
			}
			return "JevrFees.fields.push({'name':'" . $name . "',  'surcharge' :0,  'amount' :0, 'peruser' :" . $peruser . ", 'surchargefunction' : " . $surchargefunction . "});\n ";
		}
		return "";

	}
	
	function fetchSurcharge(&$node)
	{
		if (!isset($this->hasSurcharges))
		{
			$prices = array();
			foreach ($this->element->children() as $option)
			{
				$val = (string)$option["value"];
				$text = (string)$option;
				$surcharge = (string)$option["surcharge"];
				if (!is_null($surcharge))
				{
					$surchages[$val] = $surcharge;
					$hassurcharge = true;
				}
				else
				{
					$surchages[$val] = 0;
				}
			}
			$this->hasSurcharges = count($surchages) > 0;
			$node->surchargesArray = $surchages;
			$node->surcharges = json_encode($surchages);
		}

		if (!$this->hasSurcharges)
		{
			return 0;
		}

		$surcharges = $this->surchargesArray;
		$params = new JRegistry($this->attendee->params);
		$value = $params->get($this->attribute("name"), "INVALID RSVP SELECTION");
		if ($value == "INVALID RSVP SELECTION")
		{
			// TODO - do we need a warning here?
			return 0;
		}
		
		// never guest specific
		if (!$this->isVisible( $this->attendee, 0))
			return 0;
		// data intgerity check (in case value was an array before a template change removing guests on this field)
		if (is_array($value)){
			$value = current($value);
		}
		if (array_key_exists($value, $surcharges))
		{
			return $surcharges[$value];
		}
		else
		{
			// TODO - we need a warning here
			if (JFactory::getApplication()->isAdmin()){
				static $warning;
				if (!isset($warning)){
					JFactory::getApplication()->enqueueMessage(JText::_("RSVP_PAYMENT_METHOD_SURCHARGE_INVALID"),"warning");
					$warning = 1;
				}
			}						
			return 0;
		}

	}

	
	public

	function convertValue($value)
	{
		foreach ($this->element->children() as $option)
		{
			$val = (string)$option["value"];
		

			if ($val == $value)
			{
				$text = (string)$option;
				return JText::_($text);
			}
		}
		return "";

	}
	
	function fetchPaymentChoices($name, $value, $showall=true)
	{
		if ($showall)
		{
			// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
			$registry = & JRegistry::getInstance("jevents");
			$rsvpdata = $registry->get("rsvpdata");
			$row = $registry->get("event");

			$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . '"' : 'class="inputbox"' );

			$hasprice = false;
			$options = array();
			$prices = array();
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option["value"];
				$text = (string)$option;
				$htmloption = JHtml::_('select.option', $val, JText::_($text));
				$options[] =$htmloption;
			}

			return JHtml::_('jevrList.genericlist', $options, $this->name, $attribs, 'value', 'text', $value, $this->id."_choices");
			// use gateway to allow change in method!
			//return JHtml::_('jevrList.genericlist', $options, "gateway", $attribs, 'value', 'text', $value, $this->id."_choices");
		}
		else
		{
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option["value"];
				$text = (string)$option;
				if ($val == $value)
					return JText::sprintf("JEV_PAYMENT_METHOD", $text);
			}
		}

	}

	function fetchPluginPaymentOptions (){
			// Now load the payment engines
			$engines = array();
			$engine  = new stdClass();
			$engine->value = "none";
			$engine->text = JText::_("RSVP_TEMPLATE_SELECT_PAYMENT_TYPE");
			$engines[$engine->text] = $engine;

			$plugins = JPluginHelper::getPlugin("rsvppro");
			foreach ($plugins as $plugin){
				$engine  = new stdClass();
				$engine->value = $plugin->name;
				$loadedPlugin = JPluginHelper::importPlugin("rsvppro", $plugin->name);
				$className = 'plg' . $plugin->type . $plugin->name;
				if (!class_exists($className) || !method_exists($className, "generatePaymentPage")){
					continue;
				}
				$lang = JFactory::getLanguage();
				$lang->load("plg_rsvppro_".$plugin->name, JPATH_ADMINISTRATOR);

				$engine->text = JText::_("engine_".$plugin->name);
				$engines[$engine->text] = $engine;
			}
			return $engines;
	}
	
	/**
	 * override method to hide options if only one payment option is available
	 */
	/*
	public function render(&$xmlElement, $value, $control_name = 'params')
	{
		// Deprecation warning.
		JLog::add('JFormField::render is deprecated.', JLog::WARNING, 'deprecated');

		$name = $xmlElement->attributes()->name;
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');

		//make sure we have a valid label
		$label = $label ? $label : $name;

		if (count($xmlElement->children())==2) {
			$label="";
		}
		
		$result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
		$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;
	}
*/	
}

