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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrFieldText.php");
// I need the list to call the select list html
include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/jevrlist.php");

class JFormFieldJevrlist2 extends JevrFieldText
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrlist2';
	const name = 'jevrlist2';

	function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrlist.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrlist2"); ?><?php RsvpHelper::fieldId($id);?></div>
			<div class="rsvpclear"></div>

	<?php
		RsvpHelper::hidden($id, $field, self::name);
		RsvpHelper::label($id,  $field, self::name);
		RsvpHelper::tooltip($id, $field);

		if ($field)
		{
			try {
				$params = json_decode($field->params);
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

		<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_CAPACITY"); ?></div>
		<div class="rsvpinputs">
			<input type="text" name="params[<?php echo $id; ?>][capacity]" id="params<?php echo $id; ?>capacity" size="15"   value="<?php echo $capacity; ?>" />
		</div>
		<div class="rsvpclear"></div>

		<div class="rsvplabel"><?php echo JText::_("RSVP_INCLUDE_IN_CAPACITY"); ?></div>
		<div class="rsvpinputs">
			<label for="includeintotalcapacity1<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?></label>
			<input type="radio" name="params[<?php echo $id; ?>][includeintotalcapacity]"  id="includeintotalcapacity1<?php echo $id; ?>" value="1" <?php
		if ($includeintotalcapacity == 1)
		{
			echo 'checked="checked"';
		}
	?> />
		<label for="includeintotalcapacity0<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?></label>
		<input type="radio" name="params[<?php echo $id; ?>][includeintotalcapacity]" id="includeintotalcapacity0<?php echo $id; ?>" value="0" <?php
			   if ($includeintotalcapacity == 0)
			   {
				   echo 'checked="checked"';
			   }
	?> />
	</div>
	<div class="rsvpclear"></div>

	<div class="rsvplabel"><?php echo JText::_("RSVP_NO_CAPACITY_MESSAGE"); ?></div>
	<div class="rsvpinputs">
		<input type="text" name="params[<?php echo $id; ?>][nocapacitymessage]" id="params<?php echo $id; ?>nocapacitymessage" size="50"   value="<?php echo $nocapacitymessage; ?>" />
	</div>
	<div class="rsvpclear"></div>

	<div class="rsvplabel"><?php echo JText::_("RSVP_REDUCE_TOTAL_CAPACITY"); ?></div>
	<div class="rsvpinputs">
		<label for="reducevaluefortotalcapacity1<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?></label>
		<input type="radio" name="params[<?php echo $id; ?>][reducevaluefortotalcapacity]"  id="reducevaluefortotalcapacity1<?php echo $id; ?>" value="1" <?php
			   if ($reducevaluefortotalcapacity == 1)
			   {
				   echo 'checked="checked"';
			   }
	?> />
		<label for="reducevaluefortotalcapacity0<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?></label>
		<input type="radio" name="params[<?php echo $id; ?>][reducevaluefortotalcapacity]" id="reducevaluefortotalcapacity0<?php echo $id; ?>" value="0" <?php
			   if ($reducevaluefortotalcapacity == 0)
			   {
				   echo 'checked="checked"';
			   }
	?> />
	</div>
	<div class="rsvpclear"></div>

	<input type="hidden" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" value="-1" />

	<?php
			   RsvpHelper::required($id, $field);
			   RsvpHelper::requiredMessage($id, $field);
			RsvpHelper::conditional($id,  $field);
	?>
		   	<input type="hidden" name="peruser[<?php echo $id; ?>]" id="peruser<?php echo $id; ?>" value="0" />
	<?php
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
		   	<select name="pdv[<?php echo $id; ?>]" id="pdv<?php echo $id; ?>" >
		   		<option value="-1"><?php echo JText::_("JEV_SELECT_OPTION"); ?></option>
		   	</select>

		   </div>
		   <div class="rsvpclear"></div>
<?php
			   $html = ob_get_clean();

			   return RsvpHelper::setField($id, $field, $html, self::name);

		   }

	static function isEnabled(){
		return 0;
	}

		   function fetchElement($name, $value, &$node, $control_name)
		   {
			   $fieldname = $this->attribute("name");
			   $hascapacity = false;
			   $capacities = array();
			   $waiting = array();
			   $counts = array();
			   $allcounts = array();
			   $optionlabels = array();
			   $capacities[-1] = 0;
			   $waiting[-1] = 0;
			   $counts[-1] = 0;
			   $allcounts[-1] = 0;
			   $optionlabels[-1] = "";

			   $rsvpdata = $this->rsvpdata;
			   $rsvpparams = new JRegistry($this->rsvpdata->params);

			   $currentfieldname = $rsvpparams->get("fieldname_" . $fieldname, $node->attributes('label'));

			   $p = 0;
			   while ($label = $rsvpparams->get("label_" . $fieldname . "_" . $p, false))
			   {
				   $capacities[$p] = intval($rsvpparams->get("capacity_" . $fieldname . "_" . $p, 0));
				   $waiting[$p] = intval($rsvpparams->get("waiting_" . $fieldname . "_" . $p, 0));
				   $hascapacity = true;
				   $counts[$p] = 0;
				   $allcounts[$p] = 0;
				   $optionlabels[$p] = $rsvpparams->get("label_" . $fieldname . "_" . $p, "");
				   $p++;
			   }
			   for ($i = 0; $i < 20; $i++)
			   {
				   $capacities[$p + $i + 1] = $counts[$p + $i + 1] = 0;
				   $waiting[$p + $i + 1] = 0;
			   }
			   $storedRows = $p + 20;

			   // Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
			   $registry = & JRegistry::getInstance("jevents");
			   $rsvpdata = $registry->get("rsvpdata");
			   $row = $registry->get("event");

			   // Now find how much of the capacity is used up
			   $sql = "SELECT params FROM #__jev_attendees as a WHERE a.at_id=" . $rsvpdata->id . " AND a.attendstate=1";
			   if (!$rsvpdata->allrepeats)
			   {
				   $sql .= " and a.rp_id=" . $row->rp_id();
			   }
			   $db = JFactory::getDBO();
			   $db->setQuery($sql);
			   $attendeeData = $db->loadObjectList();
			   foreach ($attendeeData as $data)
			   {
				   $params = new JRegistry($data->params);
				   $pvalue = $params->get($name, -1);
				   if (!is_array($pvalue))
				   {
					   $pvalue = array($pvalue);
				   }
				   JArrayHelper::toInteger($pvalue);

				   for ($i = 0; $i < count($pvalue); $i++)
				   {
					   $pval = $pvalue[$i];
					   if (!isset($allcounts[$pval]))
						   $allcounts[$pval] = 0;
					   $allcounts[$pval] += 1;
				   }
			   }

			   // Now this record
			   if (!is_array($value))
			   {
				   $value = array($value);
			   }
			   JArrayHelper::toInteger($value);

			   for ($i = 0; $i < count($value); $i++)
			   {
				   $val = $value[$i];
				   if (!isset($counts[$val]))
				   {
					   $counts[$val] = 0;
				   }

				   $counts[$val] += 1;
			   }

			   // Add reference for use else where by other plugins etc
			   $node->capacities = $capacities;
			   $node->waiting = $waiting;
			   $node->allcounts = $allcounts;
			   $node->counts = $counts;
			   $node->optionlabels = $optionlabels;

			   $attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class="inputbox xxx"' );

			   $hasprice = false;
			   $hasSpaceLeft = false;
			   $options = array();
			   for ($p = -1; $p < $storedRows; $p++)
			   {
				   $text = $rsvpparams->get("label_" . $fieldname . "_" . $p, false);
				   if ($text)
				   {

					   // if (excluding this attendee) we are over capacity then do not offer it!
					   if ($capacities[$p] > 0 && ($allcounts[$p] - $counts[$p] ) >= ($capacities[$p] + $waiting[$p]) && !in_array($p, $value))
					   {
						   continue;
					   }

					   $hasSpaceLeft = true;

					   $spacesleft = 0;
					   if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] < $capacities[$p])
					   {
						   $spacesleft = $capacities[$p] - $allcounts[$p];
						   $text .= " (" . $spacesleft . ")";
					   }
					   else if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= ($capacities[$p] + $waiting[$p]))
					   {
						   $text .= " (0)";
					   }
					   else if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= $capacities[$p] && $waiting[$p] > 0)
					   {
						   $spacesleft = $capacities[$p] - $allcounts[$p] - (isset($allcounts[$p + 10000]) ? $allcounts[$p + 10000] : 0);
						   if ($value[0] != $p)
						   {
							   $text .= " (Waiting spaces available : " . (-$spacesleft) . " already waiting)";
						   }
					   }

					   // switching option values when into waiting zone to negative values
					   if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= $capacities[$p] && $value[0] != $p)
					   {
						   $htmloption = JHtml::_('select.option', 10000 + $p, JText::_($text));
					   }
					   // this is editing a waiting entry AFTER the list has been released so reset the value!
					   else if ($value[0] == $p + 10000)
					   {
						   $value[0] = $p;
						   $htmloption = JHtml::_('select.option', $p, JText::_($text));
					   }
					   else
					   {
						   $htmloption = JHtml::_('select.option', $p, JText::_($text));
					   }

					   $options[] = $htmloption;
				   }
			   }

			   if (!$hasSpaceLeft)
			   {
				   $return = $node->attributes('nocapacitymessage') ? $node->attributes('nocapacitymessage') : JText::_("JEV_Not_available");
				   $return .= '<input type="hidden" name="' . $name . '" id="' . $id . '" value="0" />';
				   $html = $return;
			   }
			   else
			   {
				   if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
				   {
					   //$this->fixValue($value, $node);

					   $elementname = $control_name . '[' . $name . '][]';
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
							   $thisclass = str_replace(" xxx", " rsvpparam rsvpparam$i  rsvp_" . $fieldname, $attribs);
						   }
						   $html .= JHtml::_('jevrList.genericlist', $options, $elementname, $thisclass, 'value', 'text', $val, $id . "_" . $i);
						   $i++;
					   }
					   $val = "";
					   $val = "#%^£xx£^%#";
					   $thisclass = str_replace(" xxx", " paramtmpl rsvp_" . $fieldname, $attribs);
					   $html .= JHtml::_('jevrList.genericlist', $options, "paramtmpl_" . $elementname, $thisclass, 'value', 'text', $val, $id . "_xxx");
				   }
				   else
				   {
					   $thisclass = str_replace(" xxx", " ", $attribs);
					   $html = JHtml::_('jevrList.genericlist', $options, '' . $name, $thisclass, 'value', 'text', $value, $id);
				   }
			   }

			   // Now the updatable bit!
			   $isEditor = JEVHelper::canEditEvent($this->event);

			   if (!$isEditor)
			   {
				   return $html;
			   }

			   static $scriptloaded;
			   if (!isset($scriptloaded))
			   {
  					if (JVersion::isCompatible("1.6.0")){
						$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
					}
					else {
						$pluginpath = 'plugins/jevents/rsvppro/';
					}
				   $url = JURI::root() .$pluginpath. "updatecapacities.php";
				   $updatedmessage = $this->attribute("updatedmessage");
				   if ($updatedmessage =="") $updatedmessage ="Fields updated";
				   $doc = JFactory::getDocument();
				   $rpid = $this->event->rp_id();
				   $script = <<<SCRIPT
function updateCapacities(fieldname, userid, atid){
	var requestObject = new Object();
	requestObject.error = false;
	requestObject.field = fieldname;
	requestObject.atid = atid;
	requestObject.task = "updateCapacityAndLabels";
	//requestObject.value = $$('jecf_updatecapacity_'+fieldname);
	var values = [];
	$$('.jecf_updatecapacity_'+fieldname).each(function(el,index){var val = {elval:el.value,elid:el.id,elname:el.name}; values.push(val);});
	requestObject.capacities = values;

 	values = [];
	$$('.jevfieldname_'+fieldname).each(function(el,index){var val = {label:el.value,fieldname:fieldname}; values.push(val);});
	requestObject.fieldname = values;

	requestObject.userid = userid;
	requestObject.rpid = $rpid;

	/*
	var jSonRequest = new Json.Remote('$url', {
		method:'get',
		onSuccess: function(json, responsetext){
			if (json.error){
				alert('Update Failed');
			}
			else {
				alert('$updatedmessage');
				document.location.reload();
			}
		},
		onFailure: function(){
			alert('Something went wrong...')
		}
	}).send(requestObject);
				   */
	
	var jSonRequest = new Request.JSON({
		'url':'$url',
		method:'get',
		onSuccess: function(json, responsetext){
			if (json.error){
				alert('Update Failed');
			}
			else {
				alert('$updatedmessage');
				document.location.reload();
			}
		},
		onFailure: function(){
			alert('Something went wrong...')
		}
	}).get({'json':JSON.encode(requestObject)});
	
}
SCRIPT;
				   $doc->addScriptDeclaration($script);
			   }
?>
<?php
			   $rows = $node->attributes('rows');
			   $cols = $node->attributes('cols');
			   $class = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' jecf_updatecapacity_' . $fieldname . '"' : 'class="text_area jecf_updatecapacity_' . $fieldname . '"' );
			   $buttonlabel = $this->attribute("buttonlabel");
			   $append = intval($this->attribute("append"));

			   $user = JFactory::getUser();

			   $html .= "<br/>";
			   $html .= "<fieldset style='border:solid 1px #ccc'><legend>" . JText::_("JEV_FIELD_CUSTOMISATION") . "</legend>";

			   $html .= "<table><tr>";
			   $html .= '<td><strong>Field Name : </strong></td><td><input type="text" id="jevfieldname_' . $fieldname . '" size="30"  class="jevfieldname_' . $fieldname . '" value ="' . $currentfieldname . '"></td></tr><table>';
			   $html .= "<table><tr>";
			   $html .= "<th>" . JText::_("JEV_FIELD_LABEL") . "</th><th>" . JText::_("JEV_FIELD_CAPACITY") . "</th><th>" . JText::_("JEV_FIELD_WAITING") . "</th>";
			   $html .= "</tr>";

			   $hademptyrow = false;
			   for ($p = -1; $p < $storedRows; $p++)
			   {
				   $capacity = intval($rsvpparams->get("capacity_" . $fieldname . "_" . $p, 0));
				   $waiting = intval($rsvpparams->get("waiting_" . $fieldname . "_" . $p, 0));
				   $text = $rsvpparams->get("label_" . $fieldname . "_" . $p, "");

				   if ($p == -1 && $text == "")
				   {
					   $text = JText::_("JEV_SELECT_OPTION");
				   }

				   $style = "";
				   $action = "";
				   if ($hademptyrow && $text == "")
				   {
					   $style = "style='display:none'";
				   }
				   if ($text == "")
				   {
					   $action = 'onkeypress="var sib=$(this).parentNode.parentNode.nextSibling;if (sib) sib.style.display=\'\';"';
				   }
				   if ($text == "")
					   $hademptyrow = true;

				   $html .= "<tr $style>";
				   $html .= '<td><input type="text" id="jevcaplabel_' . $fieldname . '_' . $p . '"  size="30"  ' . $action . '  ' . $class . '  value ="' . $text . '"></td>';
				   if ($p >= 0)
				   {
					   $html .= '<td><input type="text" id="jevcapcapacity_' . $fieldname . '_' . $p . '" size="6" ' . $action . ' ' . $class . '  value ="' . $capacity . '"></td>';
					   $html .= '<td><input type="text" id="jevwaiting_' . $fieldname . '_' . $p . '" size="6" ' . $action . ' ' . $class . '  value ="' . $waiting . '"></td>';
				   }
				   else
				   {
					   $html .= '<td><input type="hidden" id="jevcapcapacity_' . $fieldname . '_' . $p . '" ' . $class . '  value ="' . $capacity . '"></td>
					<td><input type="hidden" id="jevwaiting' . $fieldname . '_' . $p . '" ' . $class . '  value ="' . $waiting . '"></td>';
				   }
				   $html .= "</tr>";
			   }

			   $html .= "</table>";
			   $buttonlabel = JText::_("JEV_UPDATE_CAPACITIES");
			   $html .= '<input type="button" onclick="updateCapacities(\'' . $fieldname . '\', ' . $user->id . ', ' . $rsvpdata->id . ');return false;" value="' . JText::_($buttonlabel) . '"/>';
			   $html .= '</fieldset>';
			   $html = str_replace("\n", "", $html);

			   return $html;

		   }

		   function render(&$xmlElement, $value, $control_name = 'params')
		   {
			   $rsvpparams = new JRegistry($this->rsvpdata->params);
			   $currentfieldname = $rsvpparams->get("fieldname_" . $xmlElement->attributes()->name, $xmlElement->attributes('label'));

			   $name = $xmlElement->attributes()->name;
			   $label = $currentfieldname;
			   $descr = $xmlElement->attributes('description');
//make sure we have a valid label
			   $label = $label ? $label : $name;

			   if ($value >= 10000)
			   {
				   //$value-=10000;
				   $label .= " (waiting)";
			   }
			   $result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
			   $result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
			   $result[2] = $descr;
			   $result[3] = $label;
			   $result[4] = $value;
			   $result[5] = $name;

			   return $result;

		   }

		   public function convertValue($value, &$node)
		   {
			   // Set the correct field name
			   $rsvpparams = new JRegistry($this->rsvpdata->params);
			   $currentfieldname = $rsvpparams->get("fieldname_" . $this->attribute("name"), $node->attributes('label'));
			   $this->addAttribute('label', $currentfieldname);

			   if ($value >= 10000)
			   {
				   $value-=10000;
				   return $rsvpparams->get('label_' . $this->attribute("name") . "_" . $value, "") . " (waiting)";
			   }
			   else
			   {
				   return $rsvpparams->get('label_' . $this->attribute("name") . "_" . $value, "");
			   }
//}

		   }

		   public function totalCapacityContribution($value, &$node)
		   {
			   if ($value>=10000){
				   // These users are waiting for their option
				   return 0.0001;
			   }
			   return $value >= -1 ? 1 : 0;

		   }

		   function currentAttendeeCount($node, $value)
		   {
			   if (is_array($value) && count($value) > 1)
			   {
				   return count($value) - 1;
			   }
			   return 1;

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
					   $result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
				   }
				   else if ($k == "params")
				   {
					   if (is_string($field->params))
					   {
						   $field->params = @json_decode($field->params);
					   }
					   if (is_object($field->params))
					   {
						   foreach (get_object_vars($field->params) as $label=>$value)
						   {
							   $result[] = $label . '="' . addslashes(htmlspecialchars($value)) . '" ';
						   }
					   }
				   }
				   else {
					   $result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
				   }
			   }
			   $result[] = " />";
			   $xml = implode(" ", $result);
			   return $xml;

		   }

	   }

