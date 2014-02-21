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

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('calendar');

class JFormFieldJevrcalendar extends JFormFieldCalendar
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrcalendar';
	const name = 'jevrcalendar';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcalendar.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRCALENDAR"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			RsvpHelper::tooltip($id, $field);

			$format = "%Y-%m-%d";
			if ($field)
			{
				try {
					$params = json_decode($field->params);
					$format = $params->format;
				}
				catch (Exception $e) {
					$params = array();
				}
			}
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" size="<?php echo $field ? $field->size : 10; ?>"   value="<?php echo $field ? $field->defaultvalue : ""; ?>"  onchange="jevrcalendar.setvalue('<?php echo $id; ?>');"  onkeyup="jevrcalendar.setvalue('<?php echo $id; ?>');"/>
				<img  alt="calendar" src="<?php echo JURI::root(true); ?>/templates/system/images/calendar.png" class="calendar" />
			</div>
			<div class="rsvpclear"></div>

			<div class="rsvplabel"><?php echo JText::_("RSVP_CALENDAR_FORMAT"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][format]" id="dv<?php echo $id; ?>format" size="15"   value="<?php echo $format; ?>" />
			</div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::size($id, $field, self::name);
			RsvpHelper::maxlength($id, $field, self::name);
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
		<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppll" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<input type="text"  id="pdv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>"  size="<?php echo $field ? $field->size : 10; ?>"   />
			<img  alt="calendar" src="<?php echo JURI::root(true); ?>/templates/system/images/calendar.png" class="calendar" />
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

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

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		$format = ($this->attribute('format') ? $this->attribute('format') : '%Y-%m-%d');
		$class = ( $this->attribute('class') ? $this->attribute('class') . ' xxx' : "xinputbox xxx" );

		$html = "";
		
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
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam" . $i . " rsvp_" . $fieldname, $class);
				}
				$id = $id . '_' . $i;
				//$name = $elementname;

				//$html .= $this->calendar($val, $elementname, $id, $format, array('class' => $thisclass));
				$html .= JHtml::_('calendar', $val, $elementname, $id, $format, array('class' => $thisclass));
				$i++;
			}
			$val = $this->attribute("default");
			$thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $class);

			$id = $id . '_xxx';
			$name = 'paramtmpl_' . $elementname;
			
			//$html .= $this->calendar($val, $name, $id, $format, array('class' => $thisclass));
			$html .= JHtml::_('calendar', $val, $name, $id, $format, array('class' => $thisclass));
		}
		else
		{
			$elementname = $name;

			$val = empty($value) ? $this->attribute("default") : $value;
			$thisclass = str_replace(" xxx", " ", $class);

			$id = $id;
			$name = $elementname;

			$thisclass = str_replace(" xxx", " ", $class);
			//$html .= $this->calendar($val, $name, $id, $format, array('class' => $thisclass));
			$html .= JHtml::_('calendar', $val, $name, $id, $format, array('class' => $thisclass));
		}
		return $html;

	}

	private function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null)
	{
		static $done;

		if ($done === null)
		{
			$done = array();
			// Load the calendar behavior
			JHtml::_('behavior.calendar');
			JHtml::_('behavior.tooltip');

			$document = JFactory::getDocument();
			$script = <<<SCRIPT
 function clonecalendar(){
	var imgid = this.id;
	var fieldid = this.id.replace("_img","");

Calendar.setup({
				// Id of the input field
				inputField: fieldid,
				displayArea : fieldid+'div',
				// Format of the input field
				//ifFormat:  "%Y-%m-%d",
				ifFormat:  "$format",
				//   daFormat      | the date format that will be used to display the date in displayArea
				daFormat: "$format",
				// Trigger for the calendar (button ID)
				button: imgid,
				// Alignment (defaults to "Bl")
				align: "Tl",
				singleClick: true,
				firstDay: '.JFactory::getLanguage()->getFirstDay().'
				});	 
}
SCRIPT;
			$document->addScriptDeclaration($script);
		}

		$imgattribs = array('class' => 'calendar ' . $attribs["class"], 'id' => $id . '_img', 'onload' => 'clonecalendar.delay(1100,this);');

		$readonly = isset($attribs['readonly']) && $attribs['readonly'] == 'readonly';
		$disabled = isset($attribs['disabled']) && $attribs['disabled'] == 'disabled';
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}
		$titlevalue = "";
		if ($value != "")
		{
			try {
				$tempdate = is_callable("DateTime::createFromFormat") ? DateTime::createFromFormat(str_replace("%", "", $format), $value) : $this->datetotime($value,$format);
				$titlevalue = "";
				if ($tempdate)
				{
					$invalue = $tempdate->format("Y-m-d");
					$titlevalue = JHtml::_('date', $invalue);
				}
			}
			catch (Exception $e) {
				$titlevalue = "";
			}
		}

		return '<input type="text" title="' . $titlevalue . '" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" ' . $attribs . '  />' .
				//'<span id="'.$id.'div" >'.$dispvalue.'<span>'.
				JHtml::_('image', (JVersion::isCompatible("1.6")?'system/calendar.png':'templates/system/images/calendar.png'), JText::_('JLIB_HTML_CALENDAR'), $imgattribs, true)
		;

	}

	public function isVisible( $attendee, $guest)
	{
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		return RsvpAttendeeHelper::isVisibleStatic($this, $attendee, $guest, $this->nodes);

	}

	private function datetotime($date, $format = 'Y-M-D')
	{		
		$format = strtoupper(str_replace("%", "", $format));
		if ($format == 'Y-M-D')
			list($year, $month, $day) = explode('-', $date);
		if ($format == 'Y/M/D')
			list($year, $month, $day) = explode('/', $date);
		if ($format == 'Y.M.D')
			list($year, $month, $day) = explode('.', $date);

		if ($format == 'D-M-Y')
			list($day, $month, $year) = explode('-', $date);
		if ($format == 'D/M/Y')
			list($day, $month, $year) = explode('/', $date);
		if ($format == 'D.M.Y')
			list($day, $month, $year) = explode('.', $date);

		if ($format == 'M-D-Y')
			list($month, $day, $year) = explode('-', $date);
		if ($format == 'M/D/Y')
			list($month, $day, $year) = explode('/', $date);
		if ($format == 'M.D.Y')
			list($month, $day, $year) = explode('.', $date);


		$datetime = new DateTime ("$year-$month-$day");
		return $datetime;
	}

	public function addAttribute($name, $value)
	{
		// Add the attribute to the element, override if it already exists
		$this->element->attributes()->$name = $value;
	}	

	public function attribute($attr, $default=""){
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