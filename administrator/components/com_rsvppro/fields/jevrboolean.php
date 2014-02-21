<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrFieldRadio.php");
// need this for this list  interface
include_once("jevrlist.php");

class JFormFieldJevrboolean extends JevrFieldRadio
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrboolean';
	const name = 'jevrboolean';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrboolean.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRBOOLEAN"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			RsvpHelper::tooltip($id, $field);
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE"); ?></div>
			<div class="rsvpinputs">
				<label for="dv1<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?></label>
				<input type="radio" name="dv[<?php echo $id; ?>]"  id="dv1<?php echo $id; ?>" value="1" <?php if ($field && $field->defaultvalue == 1)
				echo 'checked="checked"'; if (!$field)
				echo 'checked="checked"'; ?> onclick="jevrboolean.settrue('<?php echo $id; ?>');" />
				<label for="dv0<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?></label>
				<input type="radio" name="dv[<?php echo $id; ?>]" id="dv0<?php echo $id; ?>" value="0" <?php if ($field && $field->defaultvalue == 0)
			echo 'checked="checked"'; ?> onclick="jevrboolean.setfalse('<?php echo $id; ?>');"/>
			</div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::required($id, $field);
			RsvpHelper::requiredMessage($id, $field);
			RsvpHelper::peruser($id, $field);
			RsvpHelper::conditional($id, $field);
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
			<label for="pdv0<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?></label>
			<input type="radio" name="pdv[<?php echo $id; ?>]"  id="pdv1<?php echo $id; ?>" value="1"  <?php if ($field && $field->defaultvalue == 1)
			echo 'checked="checked"'; if (!$field)
			echo 'checked="checked"'; ?> />
			<label for="pdv1<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?></label>
			<input type="radio" name="pdv[<?php echo $id; ?>]" id="pdv0<?php echo $id; ?>" value="0" <?php if ($field && $field->defaultvalue == 0)
			echo 'checked="checked"'; ?> />
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	public function xgetInput() {
		if (strpos($this->element['class'],"btn-group")===false){
			$this->element['class'] .= " btn-group";
		}
		return parent::getInput();
	}
	
	public function getOptions()
	{		
		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);
		
		$options = array ();
		$options[] = JHTML::_('select.option', 0, JText::_("Jev_No"));
		$options[] = JHTML::_('select.option', 1, JText::_("jev_Yes"));

		return $options;

		
	}	

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		if (strpos($this->element['class'],"btn-group")===false){
			$this->element['class'] .= " btn-group";
		}

		$class = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class=" xxx"' );

		// Must load admin language files
		$lang = & JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$options = array();
		$options[] = JHtml::_('select.option', 1, JText::_("JEV_YES"));
		$options[] = JHtml::_('select.option', 0, JText::_("JEV_NO"));

		$html = "";
		$peruser = $this->attribute("peruser");
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			if (is_object($value))
			{
				$value = get_object_vars($value);
			}
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
			$html = "";
			$i = 0;
			foreach ($value as $val)
			{
				$elementname =  $name . '[' . $i . ']';
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
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam$i  rsvp_" . $fieldname, $class);
				}
				$attribs = $thisclass;
				$inputattribs = " onclick='JevrConditionFieldState.changeState(this.getParent(), \"" . $this->fieldname . "\");' ";
				$attribs .= " id = 'rsvp_" . $fieldname . "_span_$i' ";
				$html .= JHtml::_('jevrList.radiolist', $options, '' . $elementname, $attribs, 'value', 'text', $val,  $id. "_" . $i, $inputattribs);
				$script = "JevrConditionFieldState.fields.extend( { '" . $this->fieldname . "_" . $i . "' : {'name':'" . $this->fieldname . "', 'value':'" . $val . "', 'guestcount':'" . $i . "',  'peruser' :" . $peruser . "}}); ";
				$doc = JFactory::getDocument();
				$doc->addScriptDeclaration($script);
				$i++;
			}
			$val = $this->attribute("default");
			$elementname = $name . '[\'xxxyyyzzz\']';
			$thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $class);
			$attribs = $thisclass;
			$inputattribs = $thisclass . " onclick='JevrConditionFieldState.changeState(this.getParent(),\"" . $this->fieldname . "\");' ";
			$attribs .= " id = 'rsvp_" .$fieldname . "_span_xxxyyyzzz' ";
			$html .= JHtml::_('jevrList.radiolist', $options, 'paramtmpl_' . $elementname, $attribs, 'value', 'text', $val,  $id. "_xxx", $inputattribs);
			$script = "JevrConditionFieldState.fields.extend( { '" . $this->fieldname . "_xxxyyyzzz' : {'name':'" . $this->fieldname. "', 'value':'" . $val . "', 'guestcount':'xxxyyyzzz',  'peruser' :" . $peruser . "}}); ";
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration($script);
		}
		else
		{
			$elementname =  $name ;
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
			$val = $value;
			if (!$this->attendee)
			{
				$val = $this->attribute("default");
			}
			$attribs = $thisclass;
			$inputattribs = " onclick='JevrConditionFieldState.changeState(this.getParent(),\"" . $this->fieldname . "\");' ";
			// per user is 0 so don't put a guest number on the element!
			$attribs .= " id = 'rsvp_" . $fieldname . "_span_' ";
			$html .= JHtml::_('jevrList.radiolist', $options, '' . $elementname, $attribs, 'value', 'text', $val, $id, $inputattribs);
			$script = "JevrConditionFieldState.fields.extend( { '" . $this->fieldname . "' : {'name':'" . $this->fieldname . "', 'value':'" . $val . "',  'peruser' :" . $peruser . "}}); ";
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration($script);
		}

		return $html;

	}

	function fetchRequiredScript()
	{
		$elementid = $this->id;
		$elementname = $this->name;

		return "jevrsvpRequiredFields.fields.push({'name':'" . $elementname. "', 'type':'radio', 'id':'" . $elementid . "', 'default' :'" . $this->attribute("default") . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";

	}

	public function RADIOfetchRequiredScript($controlname,  $name)
	{

		$script = "";
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value, $node);

			$i = 0;
			foreach ($value as $val)
			{
				if ($val == "#%^£xx£^%#" || $i > 2)
					continue;
				$elementid = $name . $controlname . '_' . $i;
				$elementname = $name . "[" . $controlname . "][xxxyyyzzz]";
				$script .= "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "', 'type':'radio', 'id':'" . $elementid . "',  'default' :'" . $this->attribute("default") . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
				break;
				$i++;
			}
		}
		else
		{
			$elementid = $name . $controlname;
			$elementname = $name . "[" . $controlname . "]";
			$script = "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "','type':'radio', 'id':'" . $elementid . "',  'default' :'" . $this->attribute("default") . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
		}
		return $script;

	}

	public function convertValue($value)
	{
		static $values;
		if (!isset($values))
		{
			$values = array();
			$values[0] = JText::_("JEV_NO");
			$values[1] = JText::_("JEV_YES");
		}
		return $values[$value];

	}

}