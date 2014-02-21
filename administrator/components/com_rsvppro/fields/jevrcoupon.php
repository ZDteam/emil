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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/jevrlist.php");

class JFormFieldJevrcoupon extends JevrFieldList
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrcoupon';
	const name = 'jevrcoupon';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcoupon.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		ob_start();
		?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrcoupon"); ?><?php RsvpHelper::fieldId($id);?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id,  $field, self::name);
			RsvpHelper::tooltip($id, $field);

			$maxuses = "0";
			if ($field)
			{
				try {
					$params = json_decode($field->params);
					$maxuses = isset($params->maxuses)?$params->maxuses:0;
				}
				catch (Exception $e) {
					$params = array();
				}
			}
			
			$includeintotalcapacity = isset($params->includeintotalcapacity) ? intval($params->includeintotalcapacity) : 0;
			$capacity = isset($params->capacity) ? intval($params->capacity) : 0;
			$nocapacitymessage = isset($params->nocapacitymessage) ? $params->nocapacitymessage : "";
			$reducevaluefortotalcapacity = isset($params->reducevaluefortotalcapacity) ? intval($params->reducevaluefortotalcapacity) : 0;
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_OPTIONS"); ?></div>
			<div class="rsvpinputs">
				<!-- Put the selected option here //-->
				<input type="hidden" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>" />
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
						$option->maxuses = isset($optionvalues->maxuses) ? $optionvalues->maxuses[$countoptions] : 0;
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
					$option->maxuses = 0;
					$maxvalue++;
					$option->label = "";
					$options[] = $option;
				}
				?>
				<input type="button" value="<?php echo JText::_("RSVP_NEW_OPTION") ?>" onclick="jevrcoupon.newOption('<?php echo $id; ?>');"/>
				<table id="options<?php echo $id; ?>">
					<tr >
						<th><?php echo JText::_("RSVP_COUPON_CODE") ?></th>
						<th style="display:none;"><?php echo JText::_("RSVP_OPTION_VALUE") ?></th>
						<th ><?php echo JText::_("RSVP_DISCOUNT_VALUE") ?></th>
						<!-- <th ><?php echo JText::_("RSVP_MAX_USES") ?></th>//-->
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
						if (($field && $option->value == $field->defaultvalue) || (!$field && $option->value == ""))
						{
							$checked = "checked='checked'";
						}
						?>
						<tr <?php echo $style; ?> >
							<td>
								<input type="text" class="inputlabel" name="options[<?php echo $id; ?>][label][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" value="<?php echo $option->label; ?>" <?php JFormFieldJevrcoupon::buttonAction($id, $op); ?>/>
							</td>
							<td  style="display:none;">
								<input type="text" name="options[<?php echo $id; ?>][value][]" id="options<?php echo $id; ?>_v_<?php echo $op; ?>" value="<?php echo $option->value; ?>" <?php JFormFieldJevrcoupon::buttonAction($id, $op); ?> class="jevoption_value"/>
							</td>
							<td >
								<input type="text" name="options[<?php echo $id; ?>][price][]" id="options<?php echo $id; ?>_p_<?php echo $op; ?>" value="<?php echo $option->price; ?>" class="jevfee_value"/>
							</td>
							<!-- 
							<td >
								<input type="text" name="options[<?php echo $id; ?>][maxuses][]" id="options<?php echo $id; ?>_m_<?php echo $op; ?>" value="<?php echo $option->maxuses; ?>" class="jevfee_value"/>
							</td>
							//-->
							<td>
								<input type="button" value="<?php echo JText::_("RSVP_DELETE_OPTION") ?>" onclick="jevrcoupon.deleteOption(this);"/>
							</td>
						</tr>
						<?php
					}
					?>
				</table>

			</div>
			<div class="rsvpclear"></div>
			
			<div class="rsvplabel"><?php echo JText::_("RSVP_MAX_USES_OF_EACH_COUPON"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][maxuses]" id="dv<?php echo $id; ?>maxuses" size="15"   value="<?php echo $maxuses; ?>" size="5" maxsize="10" />
				&nbsp; <?php echo JText::_("RSVP_COUPON_MAXUSE_NOTES") ; ?>
			</div>
			<div class="rsvpclear"></div>

			<?php
			//RsvpHelper::required($id, $field);
			//RsvpHelper::requiredMessage($id, $field);
			RsvpHelper::size($id, $field, self::name);
			RsvpHelper::maxlength($id, $field, self::name);
			RsvpHelper::conditional($id,  $field);

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
		<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<input type="text"  id="pdv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>" size="<?php echo $field ? $field->size : 5; ?>"  />
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function paidOption()
	{
		return 1;

	}

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		$size = ( $this->attribute('size') ? 'size="' . $this->attribute('size') . '"' : '' );
		$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class="inputbox xxx"' );

		$html = "";
		$hasprice = false;
		$options = array();
		$prices = array();
		foreach ($this->element->children() as $option)
		{
			$val	= (string) $option["value"];
			$text = (string)$option;
			$htmloption = JHtml::_('select.option', $val, JText::_($text));
			$price =  (string) $option['price'];
			
			if (!is_null($price))
			{
				$htmloption->price = $price;
				$prices[$text] = $price;
				$hasprice = true;
			}
			else
			{
				$prices[$text] = 0;
			}
			$options[] = $htmloption;
		}

		if ($hasprice)
		{
			$this->hasPrices = count($prices) > 0;
			$this->pricesArray = $prices;
			$this->prices = json_encode($prices);

			$attribs .= " onchange='JevrFees.calculate(document.updateattendance);'";
		}

		$client = JFactory::getApplication()->isAdmin()?"administrator":"site";
		if (JVersion::isCompatible("1.6.0")){
			$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
		}
		else {
			$pluginpath = 'plugins/jevents/rsvppro/';
		}
		
		$fieldid = intval(str_replace("field", "",$this->fieldname));
		//$attribs .= 'onchange="'.$action.'" onkeyup="'.$action.'"';
		
		$attdendeeid = isset($this->attendee->id) ? $this->attendee->id : 0;
		
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			if (!is_array($value))
			{
				$value = array($value);
			}
			if (count($value) < $this->currentAttendees)
			{
				// flesh out the value if there are not the right number of items
				for ($i = 0; $i <= $this->currentAttendees - count($value); $i++)
				{
					$value[] = $this->attribute("default");
				}
			}
			$elementname =$name . '[]';
			$html = "";
			$i = 0;
			foreach ($value as $val)
			{
				if ($i == 0)
				{
					if ($this->attribute("peruser") == 2)
					{
						$thisclass = str_replace(" xxx", " disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $fieldname, $attribs);
					}
					else
					{
						$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $attribs);
					}
				}
				else
				{
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam" . $i."  rsvp_" . $fieldname, $attribs);
				}
				$action = 'checkCoupon(event,$(' . $id . "_" . $i . '),\''.JURI::root().$pluginpath."checkcoupon.php".'\', \''.$client.'\', \''.$fieldid.'\', \''.$this->event->rp_id().'\, \''.$this->rsvpdata->id.'\', \''.$attdendeeid.'\')';
				$html .= '<input type="text" name="' . $elementname . '" id="' . $id . "_" . $i . '" value="' . $val . '" ' . $thisclass . ' ' . $size . ' />';
				$html .= ' <input type="button" onclick="'.$action.'" value="'.JText::_("RSVP_APPLY_COUPON",true).'" /> ';
				$i++;
			}
			$val = $this->attribute("default");
			$thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $attribs);
			$html .= '<input type="text" name="paramtmpl_' . $elementname . '" id="' . $id . '_xxx" value="' . $val . '" ' . $thisclass . ' ' . $size . ' />';
			//$html .= ' <input type="button" onclick="'.$action.'" value="'.JText::_("RSVP_APPLY_COUPON",true).'" /> ';
		}
		else
		{
			$elementname = $name;
			$thisclass = str_replace(" xxx", " ", $attribs);
			$action = 'checkCoupon(event,$(' . $id.' ),\''.JURI::root().$pluginpath."checkcoupon.php".'\', \''.$client.'\', \''.$fieldid.'\', \''.$this->event->rp_id().'\', \''.$this->rsvpdata->id.'\', \''.$attdendeeid.'\')';
			$html = '<input type="text" name="' . $elementname . '" id="' . $id . '" value="' . $value . '" ' . $thisclass . ' ' . $size . ' />';
			$html .= ' <input type="button" onclick="'.$action.'" value="'.JText::_("RSVP_APPLY_COUPON",true).'" /> ';
		}
		return $html;

	}

	// use this JS function to fetch the fee calculation script!

	function fetchBalanceScript( $value)
	{
		$id = $this->fieldname;
		
		// dynamic checking of coupon codes done using JSON NOT JAVASCRIPT for obvious reasons!
		$this->setPrices();
		if ($this->hasPrices)
		{
			$pricefunction = " function(name){return ".$id."discount;}";
			$peruser = $this->attribute("peruser");
			if (is_null($peruser))
			{
				$peruser = 0;
			}
			
			static $values;
			$name = $this->attribute("name");
			if (!isset($values))
			{
				$values = array();
			}
			if (!isset($values[$id]))
			{
				$values[$id] = array();
				foreach ($this->element->children() as $option)
				{
					$discount = (string)$option["price"];
					$text = (string)$option;
					$values[$id][$text] = $discount;
				}
			}
			if (!array_key_exists($value[0], $values[$id]))
			{
				$discval = 0;
			}
			else {
				$discval =  -$values[$id][$value[0]];
			}

			
			return "var ".$id."discount=".$discval.";  JevrFees.fields.push({'name':'" . $this->id. "',  'amount' :0, 'peruser' :" . $peruser . ", 'price' : " . $pricefunction . "});\n ";
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
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value, $this, false);

			$i = 0;
			$sum = 0;
			foreach ($value as $val)
			{
				$val = trim($val);
				if ($val == "#%^£xx£^%#" || $i > 2)
					continue;
				if (array_key_exists($val, $prices))
				{
					// coupons are negative prices
					$sum -= $prices[$val];
				}
				else
				{
					// Invalid coupon so ignore it
					$sum -= 0;
				}
			}
			return $sum;
		}
		else
		{
			$value = trim($value);
			if (array_key_exists($value, $prices))
			{
				// coupons are negative prices
				return  - $prices[$value];
			}
			else
			{
				// Invalid coupon so ignore it
				return -0;
			}
		}

	}

	public

	function convertValue($value)
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
				$discount	= (string) $option["price"];
				$text = (string)$option;
				$values[$name][$text] = $discount;
			}
		}
		if (!array_key_exists($value, $values[$name]))
		{
			return RsvpHelper::phpMoneyFormat(0);
		}
		$user = JFactory::getUser ();
		// only show coupon used to the event creator
		if ($user->id == $this->event->created_by()){
			return RsvpHelper::phpMoneyFormat(-$values[$name][$value]) . " (".$value.")";
		}
		else {
			return RsvpHelper::phpMoneyFormat(-$values[$name][$value]) ;
		}

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
		echo 'onkeyup="jevrcoupon.updatePreview( \'' . $id . '\');" '; //onblur="jevrcoupon.updatePreview( \''.$id.'\');"';
		return "";
		echo 'onkeyup="jevrcoupon.showNext(this, \'' . $id . '\', ' . $op . ');" onblur="jevrcoupon.showNext(this, \'' . $id . '\', ' . $op . ');"';

	}

}

