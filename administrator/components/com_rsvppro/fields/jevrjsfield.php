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

class JFormFieldJevrjsfield extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrjsfield';

	const name = 'jevrjsfield';

	private $isEditable = false;

	static function isEnabled()
	{
		// Test admin folder in case a template has created the frontend folder !!!
		return is_dir(JPATH_ADMINISTRATOR . '/components/com_community');

	}

	function loadScript($field = false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrjsfield.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRJSFIELD"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<input type="hidden" name="dv[<?php echo $id; ?>]"  value="" />

			<div class="rsvpclear"></div>
			<div class="rsvplabel"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRJSFIELD_SELECTION"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php RsvpHelper::fieldId($id); ?></div>
			<select name="params[<?php echo $id; ?>][fieldname]" id="fieldname<?php echo $id; ?>"  onchange="jevrjsfield.setvalue('<?php echo $id; ?>');">
				<?php
				// get the jomsocial language file - in the vain hope they have moved to Joomla 1.5 system
				$lang = & JFactory::getLanguage();
				$lang->load("com_community", JPATH_SITE);

				require_once ( JPATH_ROOT . '/' . 'components' . '/' . 'com_community' . '/' . 'defines.community.php');
				require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'error.php');
				//require_once (COMMUNITY_COM_PATH.'/'.'controllers'.'/'.'controller.php');
				require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'apps.php' );
				require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'core.php');

				//now we check whether there is any custom profile? if not, then we do the actual save here.
				$modelProfile = CFactory::getModel('profile');

				//get all published custom field for profile
				$filter = array('published' => '1', 'registration' => '1');
				$fields = & $modelProfile->getAllFields($filter);

				$rawrows = array();
				foreach ($fields as $group => $val)
				{
					if (isset($val->fields) && count($val->fields) > 0)
					{
						foreach ($val->fields as $fld)
						{
							$rawrows[] = $fld;
						}
					}
				}

				// strip out exlucded fields
				$rows = array();
				$exfields = array();
				if (JFile::exists(JPATH_ADMINISTRATOR . "/components/com_rsvppro/fields/jsexclusions.txt"))
				{
					$exfields = JFile::read(JPATH_ADMINISTRATOR . "/components/com_rsvppro/fields/jsexclusions.txt");
					$exfields = explode("\n", $exfields);
				}
				else if (JFile::exists(JPATH_ADMINISTRATOR . "/components/com_rsvppro/fields/jsexclusions.starter.txt"))
				{
					$exfields = JFile::read(JPATH_ADMINISTRATOR . "/components/com_rsvppro/fields/jsexclusions.starter.txt");
					$exfields = explode("\n", $exfields);
				}
				foreach ($rawrows as $row)
				{
					if (in_array($row->name, $exfields) || in_array($row->fieldcode, $exfields))
						continue;
					$rows[] = $row;
				}

				$fieldname = "";
				$activeField = "";
				if ($field)
				{
					try {
						$params = json_decode($field->params);
						$fieldname = isset($params->fieldname) ? $params->fieldname : "";
					}
					catch (Exception $e) {
						
					}
				}

				for ($i = 0; $i < count($rows); $i++)
				{
					$row = $rows[$i];

					$selected = "";
					if ($field && $fieldname == $row->fieldcode)
					{
						$selected = "selected='selected'";
						$activeField = $row->name;
					}
					?>

					<option value="<?php echo $row->fieldcode; ?>" <?php echo $selected; ?> ><?php echo $row->name ?></option>
			<?php
		}
		?>
			</select>

			<div class="rsvpclear"></div>

			<?php
			
			//RsvpHelper::required($id,  $field);
			//RsvpHelper::requiredMessage($id,  $field);
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			RsvpHelper::conditional($id, $field);
			$temp = new JFormFieldJevrjsfield();
			if ($temp->isEditable){
				RsvpHelper::peruser($id, $field);
			}
			else {
				
			}
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
		<?php
		echo $activeField;
		?>
			</div>
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function fetchElement($name, $value, &$node, $control_name)
	{
		$user = & JFactory::getUser();

		$size = ( $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : '' );
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').' xxx"' : 'class="text_area xxx"' );
		
		$rawvalue = $value;
		if ($this->isEditable && !is_null($rawvalue) && ($rawvalue != ""  ||  $user->id==0) && !(is_numeric($rawvalue) && $this->attendee && $rawvalue == $this->attendee->user_id) ) 
		{
		//	return "<input type='text' value='" . addslashes($rawvalue) . "' name='$control_name" . "[" . $name . "]' />";
		}

		if (JRequest::getCmd("task") == "attendees.edit" && isset($this->attendee) && $this->attendee->user_id > 0 && $this->attendee->user_id != $user->get("id"))
		{
			$value = $this->attendee->user_id;
		}
		else if (JRequest::getCmd("task") == "attendees.edit" && isset($this->attendee) && $this->attendee->id > 0 && $this->attendee->user_id == 0)
		{
			$value = 0;
		}

		$showinform = $this->attribute("showinform");

		$fieldname = $this->attribute("fieldname");
		$html = "";

		if ($showinform)
		{

			require_once ( JPATH_ROOT . '/' . 'components' . '/' . 'com_community' . '/' . 'defines.community.php');
			require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'error.php');
			//require_once (COMMUNITY_COM_PATH.'/'.'controllers'.'/'.'controller.php');
			require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'apps.php' );
			require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'core.php');

			if (is_null($value) || $value == "" || $value == "0" || $value == 0 || (is_array($value) &&  count($value)==1 && !$value[0]))
			{
				$user = JFactory::getUser();
				if (isset($this->attendee) && isset($this->attendee->user_id) && $this->attendee->user_id > 0)
				{
					$value = $this->attendee->user_id;
				}
				else if ($user->id == -1)
					return "";
				else
				{
					if (JRequest::getCmd("task") == "attendees.edit" && isset($this->attendee) && $this->attendee->user_id == 0)
					{
						return "";
					}
					else
					{
						$value = $user->id;
					}
				}
			}

			//$user = CFactory::getUser($user->id);

			$modelProfile = CFactory::getModel('profile');

			$fields = & $modelProfile->getViewableProfile($user->id);
			//$fields =& $modelProfile->getEditableProfile($value);

			$html = "";

			if (isset($fields['fields']) && count(isset($fields['fields'])) > 0)
			{
				foreach ($fields['fields'] as $group => $val)
				{
					foreach ($val as $fld)
					{
						if ($fld['fieldcode'] == $fieldname)
						{
							$html = $fld['value'];
							if (is_null($rawvalue) || $rawvalue == "" || $rawvalue == "0" || $rawvalue == 0)
							{
								$rawvalue = $fld['value']; 
								// if field has no value in profile then leave it blank
								if (is_null($rawvalue)){
									$rawvalue = "";
								}
								break;
							}
						}
					}
				}
			}
		}

		if ($user->id == 0 && !$this->isEditable)
		{
			$html .= "<input type='hidden' value='0' name='$control_name" . "[" . $name . "]' />";
		}
		else if ($this->isEditable)
		{
			if ($this->attribute("peruser")==1 || $this->attribute("peruser")==2){
				if (!is_array($value)){
					$value = array($value);
					$value[0]=$rawvalue; 									
				}
				if (count($value)<$this->currentAttendees){
					// flesh out the value if there are not the right number of items
					for ($i=0;$i<=$this->currentAttendees-count($value);$i++){
						$value[] = $this->attribute("default");
					}
				}
				
				$elementname = $name.'[]';
				$html = "";
				$i = 0;
				foreach ($value as $val){
					if ($i==0){
						if ($this->attribute("peruser")==2){
							$thisclass = str_replace(" xxx"," disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
						}
						else {
							$val = !is_null($val) ? addslashes($val) : $this->attribute("default");
							$thisclass = str_replace(" xxx"," rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
						}
					}
					else {
						$thisclass = str_replace(" xxx"," rsvpparam rsvpparam".$i."  rsvp_" . $fieldname,$class);
					}
					$html .= '<input type="text" name="'.$elementname.'" id="'.$id."_".$i.'" value="'.$val.'" '.$thisclass.' '.$size.'   />';
					$i++;
				}
				$val = $this->attribute("default");
				$thisclass = str_replace(" xxx"," paramtmpl rsvp_" . $fieldname,$class);
				$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' '.$size.'   />';
			}
			else {
				$html = "<input type='text' value='" . (!is_null($rawvalue) ? addslashes($rawvalue) : $user->id) . "' name='$control_name" . "[" . $name . "]' />";				
			}			
		}
		else
		{
			$html .= "<input type='hidden' value='" . $user->id . "' name='$control_name" . "[" . $name . "]' />";
		}

		return $html;

	}

	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$showinform = $xmlElement->attributes("showinform");

		$name = $xmlElement->attributes()->name;
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');
		if (!$showinform)
			$label = "";

		$result[0] = $label . " ";
		$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;

	}

	function toXML($field)
	{
		$result = array();
		$result[] = "<field ";
		if (is_string($field->params) && strpos($field->params, "{") === 0)
		{
			$field->params = json_decode($field->params);
		}
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
				if (is_object($field->params))
				{
					foreach (get_object_vars($field->params) as $label => $value)
					{
						$result[] = $label . '="' . addslashes(htmlspecialchars($value)) . '" ';
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

	public function convertValue($value)
	{
		if ($this->isEditable && !is_null($value) && $value != "" && !(is_numeric($value) && $this->attendee && $value == $this->attendee->user_id))
		{
			return $value;
		}
		if (!is_numeric($value))
		{
			if ($this->attendee->user_id > 0)
				$value = $this->attendee->user_id;
			else
				$value = 0;
		}

		$fieldname = $this->attribute("fieldname");

		require_once ( JPATH_ROOT . '/' . 'components' . '/' . 'com_community' . '/' . 'defines.community.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'error.php');
		//require_once (COMMUNITY_COM_PATH.'/'.'controllers'.'/'.'controller.php');
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'apps.php' );
		require_once (COMMUNITY_COM_PATH . '/' . 'libraries' . '/' . 'core.php');

		$baseurl = JURI::root();
		if (is_null($value) && !$this->attendee)
		{
			$html = "";
		}
		else
		{
			if (is_null($value) || $value == "")
			{
				if ($this->attendee->user_id > 0)
					$value = $this->attendee->user_id;
				else
					return "";
			}

			$user = CFactory::getUser($this->attendee->user_id > 0 ? $this->attendee->user_id : $value);

			$modelProfile = CFactory::getModel('profile');

			$fields = & $modelProfile->getViewableProfile($this->attendee->user_id > 0 ? $this->attendee->user_id : $value);

			$html = "";

			if (isset($fields['fields']) && count(isset($fields['fields'])) > 0)
			{
				foreach ($fields['fields'] as $group => $val)
				{
					foreach ($val as $fld)
					{
						if ($fld['fieldcode'] == $fieldname)
						{
							$html = $fld['value'];
						}
					}
				}
			}
		}
		if (JRequest::getCmd("task") == "attendees.export")
		{
			// there is a HTML error in some CB fields so missmatched tags and cannot use strip_tags
			//$value =  strip_tags($html);
			$search = array('@<*?[^<>]*?>@si');
			$value = preg_replace($search, '', $html);
			return $value;
		}
		return $html;

	}

}