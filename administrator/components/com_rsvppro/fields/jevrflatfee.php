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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/rsvppro.defines.php");
include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrflatfee extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrflatfee';
	const name = 'jevrflatfee';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrflatfee.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRFLATFEE"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field);
			RsvpHelper::tooltip($id, $field);
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FEE_VALUE"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" size="10.00"  value="<?php echo $field ? $field->defaultvalue : ""; ?>"  onchange="jevrflatfee.setvalue('<?php echo $id; ?>');"  onkeyup="jevrflatfee.setvalue('<?php echo $id; ?>');"/>
			</div>
			<div class="rsvpclear"></div>

			<?php
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
		<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<div id="pdv<?php echo $id; ?>">
				<?php echo $field ? $field->defaultvalue : ""; ?>
			</div>
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
		//$name, $value, &$node, $control_name;
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;
		
		$this->addAttribute("amount", $this->attribute("default"));
		$html = "<input type='hidden' name='" . $name . "' id='" .  $id . "' value='" . $this->attribute('amount') . "'/>";

		$attribs = ( $this->attribute('class')? 'class="' . $this->attribute('class') . ' xxx"' : 'class=" xxx"' );

		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{

			for ($i = 0; $i < (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1); $i++)
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
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam$i  rsvp_" . $fieldname, $attribs);
				}
				$html .= "<span $thisclass>" . RsvpHelper::phpMoneyFormat($this->attribute('amount')) . "</span>";
			}

			$thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $attribs);
			$html .= "<span $thisclass>" . RsvpHelper::phpMoneyFormat($this->attribute('amount')) . "</span>";
		}
		else
		{
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $attribs);
			$html .= "<span $thisclass>" . RsvpHelper::phpMoneyFormat($this->attribute('amount')) . "</span>";
		}
		return $html;

	}

	// use this JS function to fetch the fee calculation script!

	function fetchBalanceScript($value)
	{
		$this->addAttribute("amount", $this->attribute("default"));
		$peruser = $this->attribute("peruser");
		if (is_null($peruser))
		{
			$peruser = 0;
		}
		$amount = $this->attribute('amount');
		if (is_null($amount) || $amount=="")
		{
			$amount = 0;
		}
		return "JevrFees.fields.push({'name':'" . $this->id . "', 'amount' :" . $amount . ", 'peruser' :" . $peruser . ", 'byguest' :" . $peruser . "}); \n";

	}

	function fetchBalance()
	{
		$this->addAttribute("amount", $this->attribute("default"));
		return $this->attribute('amount') * $this->countVisibleNodes( $this->attendee);

	}

	public function convertValue($value)
	{
		$this->addAttribute("amount", $this->attribute("default"));
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			if (isset($this->attendee) && isset($this->attendee->guestcount))
			{
				$values = array();
				$val = RsvpHelper::phpMoneyFormat($this->attribute('amount'));
				$count = $this->attendee->guestcount;
				for ($i = 0; $i < $count; $i++)
				{
					if ($i == 0 && $this->attribute("peruser") == 2)
					{
						$values[] = "";
					}
					else
					{
						$values[] = $val;
					}
				}
				return $values;
			}
		}
		return RsvpHelper::phpMoneyFormat($this->attribute('amount'));

	}

	public function countVisibleNodes( $attendee)
	{
		$conditionnode = false;
		$cf = $this->attribute("cf");
		if ($cf !== "")
		{
			// search for field on which this node is conditioned
			foreach ($this->nodes as $cnode)
			{
				if ($cnode->fieldname == $cf)
				{
					$conditionnode = $cnode;
					break;
				}
			}
		}
		if ($conditionnode)
		{
			if (JVersion::isCompatible("1.6")){
				$attendeefields = json_decode($attendee->params);
			}
			else {
				$attendeefields = new JRegistry();
				$attendeefields->loadINI($attendee->params);
				$attendeefields = $attendeefields->toObject();
			}
			if (isset($attendeefields->$cf))
			{
				$cfieldvalue = $attendeefields->$cf;
				// global condition
				if ($conditionnode->attribute("peruser") == 0)
				{
					// condition is visible then use the guest count
					if ($cfieldvalue == $this->attribute("cfvfv"))
					{
						if ($this->attribute("peruser") == 1)
						{
							return $attendee->guestcount;
						}
						else if ($this->attribute("peruser") == 2)
						{
							return ($attendee->guestcount - 1);
						}
						else
						{
							return 1;
						}
					}
					else
					{
						return 0;
					}
				}
				// individual of guest condition
				else
				{
					if ($this->attribute("peruser") == 1)
					{
						// count the number matching the condition
						$visible = 0;
						for ($i = 0; $i < count($cfieldvalue); $i++)
						{
							$cfieldval = $cfieldvalue[$i];
							if ($cfieldval == $this->attribute("cfvfv"))
							{
								$visible++;
							}
						}
						return $visible;
					}
					// guest condition
					else if ($this->attribute("peruser") == 2)
					{
						// count the number matching the condition
						$visible = 0;
						for ($i = 1; $i < count($cfieldvalue); $i++)
						{
							$cfieldval = $cfieldvalue[$i];
							if ($cfieldval == $this->attribute("cfvfv"))
							{
								$visible++;
							}
						}
						return $visible;
					}
				}
			}
		}
		// fall back to guest count
		if ($this->attribute("peruser") == 1)
		{
			return $attendee->guestcount;
		}
		else if ($this->attribute("peruser") == 2)
		{
			return ($attendee->guestcount - 1);
		}
		else
		{
			return 1;
		}

	}

}