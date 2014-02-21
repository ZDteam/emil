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

class JFormFieldJevrcbfield extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrcbfield';
	const name = 'jevrcbfield';

	static function isEnabled()
	{
		return is_dir(JPATH_SITE . '/components/com_comprofiler');

	}

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcbfield.js');

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
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRCBFIELD"); ?><?php RsvpHelper::fieldId($id);?></div>
	<input type="hidden" name="dv[<?php echo $id; ?>]"  value="" />
	<select name="params[<?php echo $id; ?>][fieldname]" id="fieldname<?php echo $id; ?>"  onchange="jevrcbfield.setvalue('<?php echo $id; ?>');">
		<?php
		// get the community builder language file - in the vain hope they have moved to Joomla 1.5 system
		$lang = & JFactory::getLanguage();
		$lang->load("com_comprofiler", JPATH_SITE);

		// as a fallback
		$backlang = JVersion::isCompatible("1.6.0") ? strtolower( preg_replace( '/^(\w+).*$/i', '\1', $lang->getName() ) ) : $lang->getBackwardLang();
		$cblanguagePath = JPATH_SITE . '/components/com_comprofiler/plugin/language';
		if (file_exists($cblanguagePath . "/$backlang/backlang.php"))
		{
			include_once( $cblanguagePath . "/$backlang/backlang.php");
		}
		else
		{
			include_once( $cblanguagePath . "/default_language/default_language.php" );
		}

		$db = JFactory::getDBO();
		$db->setQuery("SELECT title, name FROM #__comprofiler_fields WHERE ( name != 'NA' ) ");
		$rawrows = $db->loadObjectList();

		// strip out exlucded fields
		$rows = array();
		$exfields = array();
		if (JFile::exists(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/cbexclusions.txt")){
			$exfields = JFile::read(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/cbexclusions.txt");
			$exfields = explode("\n", $exfields);
		}
		else if (JFile::exists(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/cbexclusions.starter.txt")){
			$exfields = JFile::read(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/cbexclusions.starter.txt");
			$exfields = explode("\n", $exfields);			
		}
		foreach ($rawrows as $row){
			if (in_array($row->title, $exfields) || in_array($row->name, $exfields)) continue;
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

		if (!$db->getErrorNum())
		{
			//foreach ($rows as $row) {
			for ($i = 0; $i < count($rows); $i++)
			{
				$row = $rows[$i];
				if (defined($row->title))
				{
					$cbFieldName = constant($row->title);
				}
				else
				{
					$cbFieldName = $row->title;
				}

				$selected = "";
				if ($field && $fieldname == $row->name)
				{
					$selected = "selected='selected'";
					$activeField = $cbFieldName;
				}
		?>

					<option value="<?php echo $row->name; ?>" <?php echo $selected; ?> ><?php echo$cbFieldName ?></option>
	<?php
			}
		}
	?>
		</select>

		<div class="rsvpclear"></div>

<?php
		RsvpHelper::hidden($id, $field, self::name);
		RsvpHelper::label($id,  $field, self::name);
		RsvpHelper::conditional($id,  $field);
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
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>'><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
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

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		$user = & JFactory::getUser();

		if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->user_id>0 && $this->attendee->user_id != $user->get("id")) {
			$value = $this->attendee->user_id;
		}
		else if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->id>0 && $this->attendee->user_id==0) {
			$value = 0;
		}
		
		$showinform = $this->attribute("showinform");

		$fieldname = $this->attribute("fieldname");
		$html = "";

		if ($showinform)
		{

			/**
			 * CB framework
			 * @global CBframework $_CB_framework
			 */
			global $_CB_framework;
			/** @global array $ueConfig */
			global $ueConfig;
			include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

			$_CB_framework->cbset('_ui', JFactory::getApplication()->isAdmin() ? 2 : 1); // we're in 1: frontend, 2: admin back-end

			if ($_CB_framework->getCfg('debug'))
			{
				ini_set('display_errors', true);
				error_reporting(E_ALL);
			}

			cbimport('language.front');
			cbimport('cb.tabs');
			cbimport('cb.imgtoolbox');

			global $_PLUGINS;
			$_PLUGINS->loadPluginGroup('user');

			include_once(JPATH_SITE . "/components/com_comprofiler/plugin/user/plug_cbcore/cb.core.php");

			if (is_null($value) || $value == "" || $value == "0" || $value == 0)
			{
				$user = JFactory::getUser();
				if (isset($this->attendee) && isset($this->attendee->user_id) && $this->attendee->user_id > 0)
				{
					$value = $this->attendee->user_id;
				}
				else if ($user->id == 0)
					return "";
				else {
					if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->user_id == 0) {
						return "";
					}
					else {
						$value = $user->id;
					}
				}
			}

			$user = & JFactory::getUser($value);
			if (!isset($user->cbProfile))
			{
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery('SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
						'FROM #__comprofiler AS cbprofile ' .
						'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
						' WHERE ( cbprofile.user_id = \'' . $value . '\' ) ');
				$user->cbProfile = $db->loadObject();
			}

			static $fieldclasses;
			if (!isset($fieldclasses))
			{
				$db = JFactory::getDBO();
				$db->setQuery('SELECT * FROM #__comprofiler_fields AS fld');
				$fieldclasses = $db->loadObjectList('name');
			}

			if ($user->cbProfile)
			{

				if (isset($user->cbProfile->$fieldname))
				{
					if (array_key_exists($fieldname, $fieldclasses))
					{
						$cbUser = CBuser::getInstance($user->id);
						//$html = $cbUser->getField( $fieldname, "", "html", "span", 'profile');
						$html = @$cbUser->getField($fieldname, "", "html", "span", 'adminfulllist');
						if (trim($html) == "")
						{
							$html = $user->cbProfile->$fieldname;
						}
					}
					else
					{
						$html = $user->cbProfile->$fieldname;
					}
				}
				else
				{
					$cbUser = CBuser::getInstance($user->id);
					//$html = $cbUser->getField($fieldname, "", "html", "span", 'profile');
					$html = @$cbUser->getField( $fieldname, "", "html", "span", 'adminfulllist');
					//$html = $cbUser->getField( $fieldname, "", "htmledit", "span", 'adminfulllist');
					//$html = $cbUser->getField( $fieldname, "", "fieldslist", "span", 'adminfulllist');
					if (trim($html) == "" && isset($user->$fieldname))
					{
						$html = $user->$fieldname;
					}
				}
			}
			else
			{
				$html = "";
			}
		}

		if ($user->id == 0)
		{
			$html .= "<input type='hidden' value='0' name='$name' />";
		}
		else
		{
			$html .= "<input type='hidden' value='" . $user->id . "' name='$name' />";
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
				   foreach (get_object_vars($field->params) as $label=>$value)
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
		$fieldname = $this->attribute("fieldname");

		/**
		 * CB framework
		 * @global CBframework $_CB_framework
		 */
		global $_CB_framework;
		/** @global array $ueConfig */
		global $ueConfig;
		if (defined('JPATH_ADMINISTRATOR'))
		{
			include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
		}
		else
		{
			include_once( $mainframe->getCfg('absolute_path') . '/administrator/components/com_comprofiler/plugin.foundation.php' );
		}

		$_CB_framework->cbset('_ui', 1); // we're in 1: frontend, 2: admin back-end

		if ($_CB_framework->getCfg('debug'))
		{
			ini_set('display_errors', true);
			error_reporting(E_ALL);
		}

		cbimport('language.front');
		cbimport('cb.tabs');
		cbimport('cb.imgtoolbox');

		global $_PLUGINS;
		$_PLUGINS->loadPluginGroup('user');

		include_once(JPATH_SITE . "/components/com_comprofiler/plugin/user/plug_cbcore/cb.core.php");

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
			$user = & JFactory::getUser($value);
			if (!isset($user->cbProfile))
			{
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery('SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
						'FROM #__comprofiler AS cbprofile ' .
						'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
						' WHERE ( cbprofile.user_id = \'' . $value . '\' ) ');
				$user->cbProfile = $db->loadObject();
			}

			static $fieldclasses;
			if (!isset($fieldclasses))
			{
				$db = JFactory::getDBO();
				$db->setQuery('SELECT * FROM #__comprofiler_fields AS fld');
				$fieldclasses = $db->loadObjectList('name');
			}
			if ($user->cbProfile)
			{

				if (isset($user->cbProfile->$fieldname))
				{
					if (array_key_exists($fieldname, $fieldclasses))
					{
						$cbUser = CBuser::getInstance($user->id);
						//$html = $cbUser->getField( $fieldname, "", "html", "span", 'profile');
						$html = @$cbUser->getField($fieldname, "", "html", "span", 'adminfulllist');
						if (trim($html) == "")
						{
							$html = $user->cbProfile->$fieldname;
						}
					}
					else
					{
						$html = $user->cbProfile->$fieldname;
					}
				}
				else
				{
					$cbUser = CBuser::getInstance($user->id);
					$html = @$cbUser->getField($fieldname, "", "html", "span", 'profile');
					//$html = $cbUser->getField( $fieldname, "", "html", "span", 'adminfulllist');
					if (trim($html) == "" && isset($user->$fieldname))
					{
						$html = $user->$fieldname;
					}
				}
			}
			else
			{
				$html = "";
			}
		}
		if (JRequest::getCmd("task")=="attendees.export"){
			// there is a HTML error in some CB fields so missmatched tags and cannot use strip_tags
			//$value =  strip_tags($html);
			$search = array('@<*?[^<>]*?>@si');
			$value = preg_replace($search, '', $html); 			
			return $value;
		}
		
		if (!isset($this->attendee->guestcount)){
			$this->attendee->guestcount=1;
		}

		 if ($this->attribute("peruser")==1 && $this->attendee->guestcount>0){
			return array_fill(0, $this->attendee->guestcount, $html);
		}
		else if ($this->attribute("peruser")==2 && $this->attendee->guestcount>0){
			$return = array_fill(0, $this->attendee->guestcount, $html);
			//$return[0]="";
			return $return;
		} 
		return $html;

	}

}