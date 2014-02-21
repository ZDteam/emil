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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrinstruction extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrinstruction';
	const name = 'jevrinstruction';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrinstruction.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRINSTRUCTION"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE"); ?></div>
			<div class="rsvpinputs">
				<textarea name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" onchange="jevrinstruction.setvalue('<?php echo $id; ?>');"  onkeyup="jevrinstruction.setvalue('<?php echo $id; ?>');"
						  rows="6" cols="30" ><?php echo $field ? $field->defaultvalue : ""; ?></textarea>
			</div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::conditional($id, $field);
			RsvpHelper::peruser($id, $field);
			RsvpHelper::formonly($id, $field, true);
			RsvpHelper::showinform($id, $field);
			RsvpHelper::showindetail($id, $field, false);
			RsvpHelper::showinlist($id, $field, false);
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

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		// convert <br /> tags so they are not visible when editing
		$value = str_replace('<br />', "\n", JText::_($value));

		//return '<div '.$class.' id="'.$id.'" >'.$value.'</div		

		$class = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class=" xxx"' );

		/*
		 * Required to avoid a cycle of encoding &
		 * html_entity_decode was used in place of htmlspecialchars_decode because
		 * htmlspecialchars_decode is not compatible with PHP 4
		 */
		if (is_array($value))
		{
			foreach ($value as &$val)
			{
				$val = html_entity_decode($val, ENT_QUOTES);				
			}
			unset($val);
		}
		else
		{
			$value = html_entity_decode($value, ENT_QUOTES);
		}
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
			$elementname = $name . '[]';
			$html = "";
			$i = 0;
			foreach ($value as $val)
			{
				if ($i == 0)
				{
					if ($this->attribute("peruser") == 2)
					{
						$thisclass = str_replace(" xxx", " disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
					}
					else
					{
						$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
					}
				}
				else
				{
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam" . $i . "  rsvp_" . $fieldname, $class);
				}
				$html .= '<div ' . $thisclass . ' id="' . $id . "_" . $i . '" >' . $val . '</div>';
				//$html .= '<input type="text" name="'.$elementname.'" id="'.$id."_".$i.'" value="'.$val.'" '.$thisclass.' '.$size.' />';
				$i++;
			}
			$val = $this->attribute("default");
			$thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $class);
			$html .= '<div ' . $thisclass . ' id="' . $id . '_xxx" >' . $val . '</div>';
			//$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' '.$size.' />';
		}
		else
		{
			$elementname = $name;
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
			$html = '<div ' . $thisclass . ' id="' . $id . '" >' . $value . '</div>';
			//$html = '<input type="text" name="'.$elementname.'" id="'.$id.'" value="'.$value.'" '.$thisclass.' '.$size.' />';
		}
		return $html;

	}

	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$name = $xmlElement->attributes()->name;
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');
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

	public function convertValue($value)
	{
		if ($this->attribute("peruser")==0) {
			return $value;
		}
		if (isset($this->attendee)){
			$values = array();
			$name = $this->attribute("name");
			for ($i = 0; $i < $this->attendee->guestcount; $i++)
			{
				if ($this->attribute("peruser")==2 && $i==0) {
					$values[$i]  = "";
					continue;
				}
				$values[$i] = $value;
			}
			return $values;
		}
		return $value;

	}

}