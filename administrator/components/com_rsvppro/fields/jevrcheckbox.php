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

include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/fields/JevrFieldCheckbox.php");
// need this for this list  interface
include_once("jevrlist.php");

class JFormFieldJevrcheckbox extends JevrFieldCheckbox
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrcheckbox';

	const name = 'jevrcheckbox';
	
	function loadScript($field = false)
	{
		if ($field)
		{
			if (strpos($field->defaultvalue, "[") === 0 || strpos($field->defaultvalue, "{") === 0)
			{
				$field->defaultvalue = json_decode($field->defaultvalue);
			}
			else
			{
				$field->defaultvalue = array($field->defaultvalue);
			}
		}

		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcheckbox.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		$hasfeeClass = "rsvp_nofees";
		ob_start();
		?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrcheckbox"); ?><?php RsvpHelper::fieldId($id); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			RsvpHelper::tooltip($id, $field);
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_OPTIONS"); ?></div>
			<div class="rsvpinputs">
				<?php echo JText::_("RSVP_NUMERIC_OPTION_NOTES"); ?><br/>
				<?php
				$options = array();
				if ($field && $field->options != "")
				{
					$optionvalues = json_decode($field->options);
				}
				$maxvalue = -1;

				$countoptions = 0;
				if (isset($optionvalues))
				{
					foreach ($optionvalues->value as $val)
					{
						$maxvalue = $maxvalue > $val ? $maxvalue : $val;
					}

					foreach ($optionvalues->label as $lab)
					{
						if ($lab == "")
						{
							break;
						}
						$option = new stdClass();
						$option->value = $optionvalues->value[$countoptions];
						$option->price = isset($optionvalues->price) ? $optionvalues->price[$countoptions] : 0;
						$option->label = $lab;
						$options[] = $option;
						$countoptions++;
					}
				}

				// add 20 blank options at the end
				for ($op = 0; $op < 20; $op++)
				{
					$option = new stdClass();
					$option->value = $maxvalue + 1;
					$option->price = 0;
					$maxvalue++;
					$option->label = "";
					$options[] = $option;
				}
				?>
				<input type="button" value="<?php echo JText::_("RSVP_NEW_OPTION") ?>" onclick="jevrcheckbox.newOption('<?php echo $id; ?>');"/>
				<table id="options<?php echo $id; ?>">
					<tr >
						<th><?php echo JText::_("RSVP_OPTION_TEXT") ?></th>
						<th><?php echo JText::_("RSVP_OPTION_VALUE") ?></th>
		<?php if (JVersion::isCompatible("1.6"))
		{ ?>
							<th class="<?php echo $hasfeeClass; ?>"><?php echo JText::_("RSVP_FEE_VALUE") ?></th>
					<?php } ?>
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
						// default value is not the correct value and not just an index!
						//if (($field && in_array($op, $field->defaultvalue)) || (!$field && $option->value == ""))
						if (($field && in_array($option->value, $field->defaultvalue)) || (!$field && $option->value == ""))
						{
							$checked = "checked='checked'";
						}
						?>
						<tr <?php echo $style; ?> >
							<td>
								<input type="text" class="inputlabel" name="options[<?php echo $id; ?>][label][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" value="<?php echo $option->label; ?>" <?php JFormFieldJevrcheckbox::buttonAction($id, $op); ?>/>
							</td>
							<td>
								<input type="text" name="options[<?php echo $id; ?>][value][]" id="options<?php echo $id; ?>_v_<?php echo $op; ?>" value="<?php echo $option->value; ?>" <?php JFormFieldJevrcheckbox::buttonAction($id, $op); ?>  class="jevoption_value" />
							</td>
			<?php if (JVersion::isCompatible("1.6"))
			{ ?>
								<td class="<?php echo $hasfeeClass; ?>">
									<input type="text" name="options[<?php echo $id; ?>][price][]" id="options<?php echo $id; ?>_v_<?php echo $op; ?>" value="<?php echo $option->price; ?>" class="jevfee_value" />
								</td>
			<?php } ?>
							<td>
								<input type="checkbox" value="<?php echo $option->value; ?>" onclick="jevrcheckbox.defaultOption(this, '<?php echo $id; ?>', '<?php echo $op; ?>');"  name="dv[<?php echo $id; ?>][]" id="default<?php echo $id; ?>_r_<?php echo $op; ?>" <?php echo $checked; ?>/>
							</td>
							<td>
								<input type="button" value="<?php echo JText::_("RSVP_DELETE_OPTION") ?>" onclick="jevrcheckbox.deleteOption(this);"/>
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
			RsvpHelper::conditional($id, $field);
			RsvpHelper::peruser($id, $field);
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
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<div id="pdv<?php echo $id; ?>" >
				<?php
				for ($op = 0; $op < count($options); $op++)
				{
					$option = $options[$op];
					if ($option->label == "")
						continue;
					$checked = "";
					// default value is not the correct value and not just an index!
					//if (($field && in_array($op, $field->defaultvalue)) || (!$field && $option->value == ""))
					if (($field && in_array($option->value, $field->defaultvalue)) || (!$field && $option->value == ""))
					{
						$checked = "checked='checked'";
					}
					?>
					<label><?php echo $option->label; ?><input type="checkbox" <?php echo $checked; ?>  value="<?php echo $option->value; ?>"/></label><br/>
			<?php
		}
		?>
			</div>

		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function getInput()
	{
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;
		
		$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class=" xxx"' );

		$html = "";
		$hasprice = false;
		$options = array();
		$prices = array();
		foreach ($this->element->children() as $option)
		{
			$val = (string) $option["value"];
			$price = (string) $option['price'];
			$text = (string) $option;
			$htmloption = JHtml::_('select.option', $val, JText::_($text));
			if (!is_null($price))
			{
				$htmloption->price = $price;
				$prices[$val] = $price;
				$hasprice = true;
			}
			else
			{
				$prices[$val] = 0;
			}
			$options[] = $htmloption;
		}

		$action = "";
		if ($hasprice)
		{
			$this->hasPrices = count($prices) > 0;
			$this->pricesArray = $prices;
			$this->prices = json_encode($prices);

			$action = " onclick='JevrFees.calculate(document.updateattendance);'";
		}

		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			// a non-attendee or new field wtll have $value == default value to convert to appropriate array
			if ($value == $this->attribute("default"))
			{
				$newvalue = array();
				for ($i = 0; $i < (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1); $i++)
				{
					$newvalue[$i] = $value;
				}
			}

			$this->fixValue($value);

			$html = "";
			for ($i = 0; $i < (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1); $i++)
			{
				$val = array_key_exists($i, $value) ? $value[$i] : $this->attribute("default");
				$elementname = $name . '[' . $i . '][]';
				if ($i == 0)
				{
					if ($this->attribute("peruser") == 2)
					{
						$thisclass = str_replace(" xxx", " disabledfirstparam rsvpparam rsvpparam0 rsvp_ rsvp_$fieldname rsvp_xmlfile_$fieldname", $attribs);
					}
					else
					{
						$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_ rsvp_$fieldname rsvp_xmlfile_$fieldname", $attribs);
					}
				}
				else
				{
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam$i  rsvp_ rsvp_$fieldname rsvp_xmlfile_$fieldname", $attribs);
				}
				$thisclass .= " id = 'rsvp_" . $fieldname . "_span_$i' ";
				$html .= JHtml::_('jevrList.checkboxlist', $options, $elementname, $thisclass, 'value', 'text', $val, $id . "_" . $i);
			}
			$val = $this->attribute("default");
			$elementname = $name . '[xxxyyyzzz][]';
			$thisclass = str_replace(" xxx", " paramtmpl  rsvp_$fieldname rsvp_xmlfile_$fieldname", $attribs);
			$thisclass .= " id = 'rsvp_" . $fieldname . "_span_xxxyyyzzz' ";
			$html .= JHtml::_('jevrList.checkboxlist', $options, "paramtmpl_" . $elementname, $thisclass, 'value', 'text', $val,  $id . "_xxxyyyzzz");
		}
		else
		{
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_$fieldname rsvp_xmlfile_$fieldname", $attribs);
			// per user is 0 so don't put a guest number on the element!
			$thisclass .= " id = 'rsvp_" . $fieldname . "_span_' ";
			$html = JHtml::_('jevrList.checkboxlist', $options, '' . $name . '[]', $thisclass, 'value', 'text', $value,  $id . "_");
		}

		// Attach the action to the input cells
		$html = str_replace('<input', '<input  ' . $action . " ", $html);
		return $html;

	}

	// use this JS function to fetch the fee calculation script!

	function fetchBalanceScript($value)
	{
		$this->setPrices();
		if ($this->hasPrices)
		{
			$pricefunction = " function(name){return priceJevrRadio(name, " . $this->prices . ");}";
			$peruser = $this->attribute("peruser");
			if (is_null($peruser))
			{
				$peruser = 0;
			}
			return "JevrFees.fields.push({'name':'" . $this->id. "',  'amount' :0, 'peruser' :" . $peruser . ", 'price' : " . $pricefunction . "});\n ";
		}
		return "";

	}

	private function setPrices() {
		$name = $this->attribute("name");
		
		static $hasPricesData = array();
		static $pricesArrayData = array();
		static $pricesData = array();
		
		if (!isset($this->hasPricesData[$name]))
		{
			$prices = array();
			foreach ($this->element->children() as $option)
			{
				$val = (string) $option["value"];
				$price = (string) $option['price'];
				$text = (string) $option;
				if (!is_null($price))
				{
					$prices[$val] = $price;
					$hasprice = true;
				}
				else
				{
					$prices[$val] = 0;
				}
			}
			$hasPricesData[$name] = count($prices) > 0;
			$pricesArrayData[$name] = $prices;
			$pricesData[$name] = json_encode($prices);
		}		
		$this->hasPrices = $hasPricesData[$name];
		$this->pricesArray = $pricesArrayData[$name];
		$this->prices = $pricesData[$name];
	}
	
	function fetchBalance()
	{
		$this->setPrices();

		if (!$this->hasPrices)
		{
			return 0;
		}

		$prices = $this->pricesArray;
		$params = new JRegistry($this->attendee->params);
		$value = $params->get($this->attribute("name"), "INVALID RSVP SELECTION");
		if ($value == "INVALID RSVP SELECTION")
		{
			// TODO - do we need a warning here?
			return 0;
		}

		// TODO this needs to check is visible too!
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value,  false);

			$sum = 0;
			foreach ($value as $i => $val)
			{
				if (!$this->isVisible( $this->attendee, $i))
				{
					continue;
				}
				if (is_array($val))
				{
					foreach ($val as $subval)
					{
						if (array_key_exists($subval, $prices))
						{
							$sum += $prices[$subval];
						}
					}
				}
				else
				{
					if ($val == "#%^£xx£^%#")
						continue;
					if (array_key_exists($val, $prices))
					{
						$sum += $prices[$val];
					}
					else
					{
						// TODO - we need a warning here
						$sum += 999999;
					}
				}
			}
			return $sum;
		}
		else
		{
			if (!$this->isVisible( $this->attendee, 0))
				return 0;

			if (is_array($value))
			{
				$price = 0;
				foreach ($value as $val)
				{
					if (array_key_exists($val, $prices))
					{
						$price += $prices[$val];
					}
				}
				return $price;
			}
			else if (array_key_exists($value, $prices))
			{
				return $prices[$value];
			}
			else
			{
				// TODO - we need a warning here
				return 999999;
			}
		}

	}

	public function convertValue($value)
	{
		static $values;
		$name = $this->attribute("name");
		if (!isset($values))
		{
			$values = array();
		}
		if (!isset($values[$name]))
		{
			$values[$name] = array();
			foreach ($this->element->children() as $option)
			{
				$val = (string) $option["value"];
				$text = (string) $option;
				$values[$name][$val] = $text;
			}
		}
		if (!isset($values[$name]))
		{
			return JText::_("unknown value $value / $name");
		}
		if (is_array($value))
		{
			$cv = array();
			foreach ($value as $val)
			{
				if (isset($values[$name][$val]))
				{
					$cv[] = $values[$name][$val];
				}
			}
			return implode(", ", $cv);
		}
		else
		{
			if (!isset($values[$name][$value]))
				return $value;
			return $values[$name][$value];
		}

	}

	public function prehandleValues($invalue, $node, $attendee, $output = true)
	{
		if (is_array($invalue) && $attendee)
		{
			$values = array();
			$values = array_pad($values, $attendee->guestcount, "");

			foreach ($invalue as $g => $val)
			{
				if ($output)
				{
					$values[$g] = ($val == "" || !$this->isVisible( $attendee, $g)) ? "" : $this->convertValue($val);
				}
				else
				{
					$values[$g] = $val;
				}
			}
			return $values;
		}
		return $invalue;

	}

	function fetchRequiredScript()
	{

		$script = "";
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value);

			$i = 0;
			foreach ($value as $val)
			{
				if ($val == "#%^£xx£^%#" || $i > 2)
					continue;
				$elementid =  $this->id . '_' . $i;
				$elementname = $this->name.  "[xxxyyyzzz]";
				$script .= "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "', 'type':'checkbox', 'id':'" . $elementid . "',  'default' :'" . $this->attribute("default") . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
				break;
				$i++;
			}
		}
		else
		{
			$elementid = $this->id;
			$elementname = $this->name;
			$script = "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "','type':'checkbox', 'id':'" . $elementid . "',  'default' :'" . $this->attribute("default") . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
		}
		return $script;

	}

	function currentAttendeeCount($node, $value)
	{
		if (is_array($value) && count($value) > 1)
		{
			return count($value) - 1;
		}
		return 1;

	}

	function buttonAction($id, $op)
	{
		echo 'onkeyup="jevrcheckbox.updatePreview( \'' . $id . '\', \'' . $op . '\');" '; //onblur="jevrcheckbox.updatePreview( \''.$id.'\');"';
		return "";
		echo 'onkeyup="jevrcheckbox.showNext(this, \'' . $id . '\', ' . $op . ');" onblur="jevrcheckbox.showNext(this, \'' . $id . '\', ' . $op . ');"';

	}

}