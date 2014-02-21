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

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevracymailingsubscription extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevracymailingsubscription';

	const name = 'jevracymailingsubscription';

	static function isEnabled()
	{
		return is_dir(JPATH_ADMINISTRATOR . '/components/com_acymailing');

	}

	function loadScript($field = false)
	{
		JHtml::script( 'administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevracynewsletter.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		if (!include_once(rtrim(JPATH_ADMINISTRATOR, '/') . '/' . 'components' . '/' . 'com_acymailing' . '/' . 'helpers' . '/' . 'helper.php'))
		{
			$html = 'This code can not work without the AcyMailing Component';
			return RsvpHelper::setField($id, $field, $html, self::name);
		}
		else
		{
			$listClass = acymailing_get('class.list');
			$allLists = $listClass->getLists();
			if (!$allLists || count($allLists) == 0)
			{
				$html = 'Please create an acyMailing List';
				return RsvpHelper::setField($id, $field, $html, self::name);
			}
		}

		$listid = "";
		$activeList = "";
		if ($field)
		{
			try {
				$params = json_decode($field->params);
				$listid = isset($params->listid) ? $params->listid : 0;
			}
			catch (Exception $e) {
				
			}
		}

		ob_start();
		?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRACYMAILINGSUBSCRIPTION"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<input type="hidden" name="dv[<?php echo $id; ?>]"  value="" />
			<div class="rsvpclear"></div>
			<div class="rsvplabel"><?php echo JText::_("RSVP_TEMPLATE_TYPE_NEWSLETTER_SELECTION"); ?></div>
			<select name="params[<?php echo $id; ?>][listid]" id="fieldname<?php echo $id; ?>"  onchange="jevracynewsletter.setvalue('<?php echo $id; ?>');">
				<?php
				foreach ($allLists as $row)
				{

					$selected = "";
					if ($field && $listid == $row->listid)
					{
						$selected = "selected='selected'";
						$activeList = $row->name;
					}
					?>

					<option value="<?php echo $row->listid; ?>" <?php echo $selected; ?> ><?php echo $row->name ?></option>
					<?php
				}
				?>
			</select>

			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			?>
			<input type="hidden" name="formonly[<?php echo $id; ?>]"   value="1" >
			<input type="hidden" name="showinform[<?php echo $id; ?>]"   value="1" >
			<input type="hidden" name="showinlist[<?php echo $id; ?>]"   value="0" >
			<input type="hidden" name="showindetail[<?php echo $id; ?>]"   value="0" >
			<?php
			RsvpHelper::accessOptions($id, $field);
			?>
			<div class="rsvpclear"></div>
		</div>
		<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<div id="pdv<?php echo $id; ?>">
				<?php
				echo $activeList;
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

		if (JRequest::getCmd("task") == "attendees.edit" && isset($this->attendee) && $this->attendee->user_id > 0 && $this->attendee->user_id != $user->get("id"))
		{
			$value = $this->attendee->user_id;
		}
		else if (JRequest::getCmd("task") == "attendees.edit" && isset($this->attendee) && $this->attendee->id > 0 && $this->attendee->user_id == 0)
		{
			$value = 0;
		}

		$showinform = $this->attribute("showinform") || $this->attribute("formonly");

		$listid = intval($this->attribute("listid"));
		$html = "";

		if ($showinform && $listid > 0)
		{

			if (!include_once(rtrim(JPATH_ADMINISTRATOR, '/') . '/' . 'components' . '/' . 'com_acymailing' . '/' . 'helpers' . '/' . 'helper.php'))
			{
				$html = 'This code can not work without the AcyMailing Component';
				return $html;
			}
			else
			{
				$listClass = acymailing_get('class.list');
				$allLists = $listClass->getLists('listid');
				if (!$allLists || count($allLists) == 0 || !array_key_exists($listid, $allLists))
				{
					$html = 'Please create an acyMailing List';
					return $html;
				}
			}

			$list = $allLists[$listid];

			if (!include_once(rtrim(JPATH_ADMINISTRATOR, '/') . '/' . 'components' . '/' . 'com_acymailing' . '/' . 'helpers' . '/' . 'helper.php'))
			{
				echo 'This code can not work without the AcyMailing Component';
				return false;
			}

			// subscribed already?
			$checked = "";
			$userClass = acymailing_get('class.subscriber');
			$attendee = false;
			if ($this->attendee && $this->attendee->user_id>0){
				$attendee = $this->attendee->user_id;
			}
			else if ($this->attendee && $this->attendee->email_address !=""){
				$attendee = $this->attendee->email_address;
			}
			if ($attendee){
				$subid = $userClass->subid($attendee);
				if ($subid){
					$subs = $userClass->getSubscription($subid, 'listid');
					if ($subs && array_key_exists($listid ,$subs ) && $subs[$listid]->status){
						$checked = " checked='checked'";
					}					
				}
			}
			else {
				// new attendees are checked by default
				$checked = " checked='checked'";
			}

			$html = "<label for='newslettersub'>$list->name <input type='checkbox' id='newslettersub' name='".$name . "[]' value='$listid' $checked /></label>";
		}

		return $html;

	}

	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$showinform = 1; // $xmlElement->attributes("showinform");

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
		return $value;

	}

	public  function postUpdateAction($node)
	{
		$attendeeparams = new JRegistry($this->attendee->params);		
var_dump($attendeeparams );
		$field = $this->attribute("name");
		$value = $attendeeparams->get($field);
		if (!$value){
			$value = array(0);
		}
		if (!is_array($value)){
			$value = array($value);
		}

		$newslettersub = JRequest::getVar("newslettersub", $value);

		if (!include_once(rtrim(JPATH_ADMINISTRATOR, '/') . '/' . 'components' . '/' . 'com_acymailing' . '/' . 'helpers' . '/' . 'helper.php'))
		{
			echo 'This code can not work without the AcyMailing Component';
			return false;
		}

		$listid = $node->attributes('listid');
		// subscribed already?
		$checked = "";
		$userClass = acymailing_get('class.subscriber');

		$attendee = false;
		if ($this->attendee && $this->attendee->user_id>0){
			$attendee = $this->attendee->user_id;
		}
		else if ($this->attendee && $this->attendee->email_address !=""){
			$attendee = $this->attendee->email_address;
		}
		if ($attendee){
			$subid = $userClass->subid($attendee);

			// create subscriber/member if needed
			if (!$subid){
				$myUser = new stdClass();
				if (intval($attendee)>0){
					$attendee = JFactory::getUser($attendee);
					$myUser->email = $attendee->email;
					$myUser->name = $attendee->name;
					$myUser->userid = $attendee->id;
					$myUser->confirmed = 1;
				}
				else {
					$myUser->email = $attendee;
					$myUser->confirmed = 1;
				}
				// save new subscriber
				$subid = $userClass->save($myUser);
			}
			$subs = $userClass->getSubscription($subid, 'listid');
			
			if ($subs ){
				$newSubscription = array();
				$newList = array();
				if (!in_array($listid, $newslettersub) && array_key_exists($listid ,$subs ) && $subs[$listid]->status ){
					// remove
					$newList['status'] = 0;
					$newSubscription[$listid] = $newList;
				}
				else if (in_array($listid, $newslettersub)  && (!array_key_exists($listid ,$subs )  || (array_key_exists($listid ,$subs ) &&  !$subs[$listid]->status))) {
					// add
					$newList['status'] = 1;
					$newSubscription[$listid] = $newList;
				}
				if (count($newSubscription)){
					$userClass->saveSubscription($subid,$newSubscription);
				}
			}	
		}
	}

}