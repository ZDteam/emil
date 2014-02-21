<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport("joomla.form.form");

class JevRsvpParameter extends JForm
{

	private $rsvpdata;
	private $event;
	private $multiattendee;
	public $currentAttendees = 1;
	public $jevparams = array();

	function __construct($data, $path = '', $rsvpdata, $event)
	{
		$this->rsvpdata = $rsvpdata;
		$this->event = $event;
		$this->jevparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
		
		// we don't pass the path so we can populate this in our own way e.g. using non-xml data structure
		parent::__construct($data);
		if (is_numeric($path))
		{
			static $instances;
			static $rawinstances;
			if (!isset($instances))
			{
				$instances = array();
				$rawinstances = array();
			}
			if (!isset($instances[$path]))
			{
				include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/tables/template.php");
				include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/models/template.php");
				$options = array();
				$options["name"] = "TableTemplate";
				//$options["name"]
				$model = & JModelLegacy::getInstance("template", "TemplatesModel", $options);
				$model->setId($path);
				$template = $model->getData();
				$this->_rawtemplate = $template;
				if (is_string($template->params))
				{
					$template->params = json_decode($template->params);
				}
				$instances[$path] = $this->convertTemplateToXML($template);
				$rawinstances[$path] = $template;
			}
			else
			{
				$this->_rawtemplate = $rawinstances[$path];
			}
			//echo "<pre>".$instances[$path]."</pre>";
			$this->loadSetupXML($instances[$path]);
		}
		else if ($path)
		{
			$this->loadSetupFile($path);
		}

		if (is_string($data))
		{
			$data = new JRegistry($data);
			$data = $data->toArray();
		}
		$this->bind($data);

	}

	/**
	 * Magic function to clone the registry object.
	 *
	 * @return  JRegistry
	 *
	 * @since   11.1
	 */
	public function __clone()
	{
		// We want a new version of the data variable
		$this->data = null;
		$this->data = new JRegistry;

	}

	public function loadData($data, $rsvpdata, $event)
	{
		$this->rsvpdata = $rsvpdata;
		$this->event = $event;
		$this->_rawdata = $data;

		if (is_string($data))
		{
			$data = new JRegistry($data);
			$data = $data->toArray();
		}
		return $this->bind($data);

	}

	/**
	 * Loads an xml setup file and parses it
	 *
	 * @access	public
	 * @param	string	path to xml setup file
	 * @return	object
	 * @since	1.5
	 */
	function loadSetupFile($path)
	{
		$result = false;

		if ($path)
		{
			$xml = & JFactory::getXMLParser('Simple');

			if ($xml->loadFile($path))
			{
				if ($params = & $xml->document->params)
				{
					foreach ($params as $param)
					{
						$this->setXML($param);
						$result = true;
					}
				}
				if (isset($xml->document->ticket))
				{
					$this->ticket = & $xml->document->ticket;
					if (count($this->ticket) == 1)
					{
						$this->ticket = trim((string) $this->ticket[0]);
					}
					else
					{
						$this->ticket = "";
					}
				}
				else
				{
					$this->ticket = "";
				}
			}
		}
		else
		{
			$result = true;
		}

		return $result;

	}

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	public function render($name = 'params', $group = "xmlfile", $extras = array())
	{
		static $alreadycalled = 0;
		if ($alreadycalled)
		{
			return "";
		}
		$alreadycalled = 1;

		$hasfee = false;

		// setup required fields script
		$doc = JFactory::getDocument();
		$invalidEmailAddress = JText::_("RSVP_INVALID_EMAIL_ADDRESS", true);
		$script = <<<SCRIPT
var rsvpInvalidEmail = "$invalidEmailAddress";
window.addEvent("domready",function(){
	var form = document.updateattendance;
	if (form){
		$(form).addEvent('submit',function(event){
			if (!jevrsvpRequiredFields.verify(form)) {
				event || (event = new Event(window.event)); 
				try {
					event.stop();
				}
				catch (e) {
					event.stopImmediatePropagation();
				}
				return false;
			}
		});
		//jevrsvpRequiredFields

	};
});
SCRIPT;
		$doc->addScriptDeclaration($script);

		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}

		$html = array();
		$html [] = '<table width="100%" class="paramlist admintable" cellspacing="1">';

		if ($this->description)
		{
			// add the params description to the display
			$desc = JText::_($this->description);
			$html [] = '<tr><td class="paramlist_description" colspan="2">' . $desc . '</td></tr>';
		}

		$balancescripts = "";
		$user = $this->getUser();

		foreach ($fieldgroup as $id => & $field)
		{
			// add the rsvpdata and events in case its needed by sophisticated element types
			$field->rsvpdata = $this->rsvpdata;
			$field->event = $this->event;
			$field->attendee = isset($this->attendee) ? $this->attendee : false;
			$field->outstandingBalances = isset($this->outstandingBalances) ? $this->outstandingBalances : null;
			$field->currentAttendees = isset($this->currentAttendees) ? $this->currentAttendees : 1;
			
			$param = $this->getInput($field->fieldname, $group, $field->value);

			if (!$param)
			{
				continue;
			}

			// check access
			$accessible = true;
			$levels = $user->getAuthorisedViewLevels();
			$nodeaccess = explode(",", $field->attribute("access", "1"));
			if (count(array_intersect($levels, $nodeaccess)) == 0)
			{
				$accessible = false;
			}

			// if access flag is 0 then members of this level are BLOCKED
			if ($field->attribute("accessflag", 1) == 0)
			{
				$accessible = !$accessible;
			}

			if (!$accessible)
			{
				continue;
			}

			if ($field->attribute("cf", '') == "")
			{
				$conditionalclass = "";
			}
			else
			{
				$conditionalclass = " conditionalhidden";
				$script = "JevrConditionalFields.fields.push({'name':'" . $field->fieldname . "', 'cf':'" . $field->attribute('cf') . "', 'cfvfv':'" . $field->attribute('cfvfv') . "'}); ";
				$doc = JFactory::getDocument();
				$doc->addScriptDeclaration($script);
			}

			if ($field->attribute("peruser", 0) == -1)
			{
				$html [] = '<tr class="param' . $field->fieldname . ' type1param fieldtype' . $field->attribute('type') . ' ' . $conditionalclass . '">';
			}
			// If the node only applies to second and subsequent attendees then hide initially
			else if ($field->attribute("peruser", 0) == 2)
			{
				$html [] = '<tr class="param' . $field->fieldname . ' type2param  fieldtype' . $field->attribute('type') . ' ">';
			}
			else if ($field->attribute("peruser", 0) == 1)
			{
				$html [] = '<tr class="param' . $field->fieldname . ' type1param  fieldtype' . $field->attribute('type') . ' ">';
			}
			else
			{
				$html [] = '<tr class="param' . $field->fieldname . ' type0param ' . $conditionalclass . ' fieldtype' . $field->attribute('type') . ' ">';
			}

			$required = $field->attribute('required');

			$tip = "";
			if ($field->attribute('tooltip', false))
			{
				//strip and add slashes to avoid double slashes!
				$tooltip = addslashes(stripslashes(htmlspecialchars($field->attribute('tooltip', false), ENT_QUOTES, 'UTF-8')));
				$title = addslashes(stripslashes(htmlspecialchars($field->attribute('label', false), ENT_QUOTES, 'UTF-8')));
				$tip = 'title="' . $title . '::' . $tooltip . '" class="editlinktip  hasTip"';
			}

			$label = $field->translateLabel ? JText::_($field->label) : $field->label;
			if ($label != "")
			{
				$html [] = '<td class="paramlist_key"><span ' . $tip . '>' . stripslashes($label) . '</span></td>';
				$html [] = '<td class="paramlist_value">' . stripslashes($param) . '</td>';
			}
			else
			{
				$html [] = '<td class="paramlist_value" colspan="2">' . stripslashes($param) . '</td>';
			}

			$value = $this->getValue($field->fieldname, $group, $field->attribute('default'));
			$this->fixValue($value, $field);

			if ($required)
			{
				if ($field->attribute('requiredmessage') == "")
				{
					$field->addAttribute('requiredmessage', "RSVP_REQUIREDFIELD_NOTCOMPLETED");
				}
				if (method_exists($field, "fetchRequiredScript"))
				{
					$script = $field->fetchRequiredScript($field->fieldname,  $group);
				}
				else
				{
					if ($field->attribute("peruser") == 1 || $field->attribute("peruser") == 2)
					{

						$i = 0;
						$script = "";
						foreach ($value as $val)
						{
							if ($val == "#%^£xx£^%#" || $i > 2)
								continue;
							$elementid = $field->id . '_' . $i;
							$elementname = $field->name."[]";
							//$script .= "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "', 'id':'" . $elementid . "',  'default' :'" . $field->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($field->attribute('requiredmessage'), true)) . "'}); ";
							$script .= "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "', 'id':'" . $elementid . "',  'default' :'" . $field->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($field->attribute('requiredmessage'), true)) . "'}); ";
							break;
							$i++;
						}
					}
					else
					{
						$elementid = $field->id;
						$elementname = $field->name;
						$script = "jevrsvpRequiredFields.fields.push({'name':'" . $elementname . "', 'id':'" . $elementid . "',  'default' :'" . $field->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($field->attribute('requiredmessage'), true)) . "'}); ";
					}
				}
				$doc->addScriptDeclaration($script);
			}

			// Get any percentage fee handlers e.g. payment method surcharges
			if (method_exists($field, "fetchSurchargeScript"))
			{
				$balancescripts .= $field->fetchSurchargeScript($field->fieldname, $field, $name, $value);
			}

			// Get any fee handlers
			if (method_exists($field, "fetchBalanceScript"))
			{
				$balancescripts .= $field->fetchBalanceScript($value);
			}

			$html [] = '</tr>';
		}
		unset($field);
		$doc->addScriptDeclaration($balancescripts);

		if (count($extras) > 1)
		{
			$html [] = '<tr class=" type0param">';
			$html [] = '<td  class="paramlist_key">' . stripslashes($extras [0]) . '</td>';
			$html [] = '<td class="paramlist_value">' . $extras [1] . '</td>';
			$html [] = '</tr>';
		}

		$html [] = '</table>';

		return implode("\n", $html);

	}

	public function getRawParam(&$field, $control_name = 'params', $group = 'xmlfile')
	{
		//get the type of the parameter
		$type = $field->attribute('type');

		//remove any occurance of a mos_ prefix
		$type = str_replace('mos_', '', $type);

		// set the rsvpdata for reference
		$field->rsvpdata = $this->rsvpdata;
		$field->event = $this->event;
		$field->attendee = isset($this->attendee) ? $this->attendee : false;
		$field->outstandingBalances = isset($this->outstandingBalances) ? $this->outstandingBalances : null;
		$field->currentAttendees = isset($this->currentAttendees) ? $this->currentAttendees : 1;

		// error happened
		if ($field === false)
		{
			$result = array();
			$result[0] = $field->fieldname;
			$result[1] = JText::_('Element not defined for type') . ' = ' . $type;
			$result[3] = $field->attribute('label');
			$result[5] = $result[0];
			return $result;
		}

		//get value
		if (isset($this->attendee) && $this->attendee && method_exists($field, "prehandleValues"))
		{
			$val = $this->getValue($field->fieldname, $group, array());
			$value = $field->prehandleValues($val, $field, $this->attendee, false);
		}
		else
		{
			$value = $this->getValue($field->fieldname, $group, $field->attribute('default'));
		}

		$rawParam = new stdClass();
		$rawParam->value = $value;
		$rawParam->element = $field;
		$rawParam->node = $field;
		return $rawParam;

	}

	function getRawParams($name = 'params', $group = 'xmlfile')
	{
		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}
		$results = array();
		foreach ($fieldgroup as $id => $param)
		{
			$results[] = $this->getRawParam($param, $name);
		}
		return $results;

	}

	public function renderToBasicArray($group = 'xmlfile', $attendee = false)
	{
		$group = 'xmlfile';

		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return array();
		}

		static $cachedData = array();
		static $cachedMultiAttendee = array();

		$cacheid = $attendee ? $attendee->id : "false";
		$cacheid .= isset($this->outstandingBalances) ? serialize($this->outstandingBalances) : "false";
		if (isset($cachedData[$cacheid]))
		{
			$data = unserialize(gzuncompress($cachedData[$cacheid]));
			$this->multiattendee = $cachedMultiAttendee[$cacheid];
			return $data;
		}

		$results = array();

		if (!isset($this->multiattendee))
		{
			$this->multiattendee = false;
		}

		foreach ($fieldgroup as & $field)
		{

			$result = array();
			$result['name'] = $field->fieldname;
			$result['fieldname'] = $field->attribute('fieldname');
			$result['type'] = $field->attribute('type');
			$result['label'] = $field->attribute('label');
			$result['capacity'] = intval($field->attribute('capacity'));
			$result['includeintotalcapacity'] = $field->attribute('includeintotalcapacity');
			$result['reducevaluefortotalcapacity'] = $field->attribute('reducevaluefortotalcapacity');
			$result['conditionalfield'] = $field->attribute('cf');
			$result['conditionalfieldvalue'] = $field->attribute('cfvfv');
			$result['accessflag'] = $field->attribute('accessflag', 1);
			$result['access'] = $field->attribute('access', "1");
			$result['reducetotalcapacity'] = 0;
			$result['capacitycount'] = 0;
			$result['maxuses'] = intval($field->attribute('maxuses'));

			if ($field->attribute('peruser', 0) > 0)
			{
				$this->multiattendee = true;
				$result['peruser'] = $field->attribute('peruser');
			}
			else if ($field->attribute('peruser', 0) < 0)
			{
				$result['peruser'] = $field->attribute('peruser');
			}
			else
			{
				$result['peruser'] = 0;
			}

			if ($attendee)
			{
				// handle checkbox field being blank if nothing checked
				$emptyarray = array();
				$result['value'] = $this->getValue($field->attribute('name'), $group, $field->attribute('peruser')?array_pad($emptyarray, $attendee->guestcount,""):"");
			}
			else
			{
				$result['value'] = $this->getValue($field->fieldname,  $group, $field->attribute('default'));
			}
			if (is_object($result['value']))
			{
				$result['value'] = get_object_vars($result['value']);
			}
			$result['showinlist'] = $field->attribute('showinlist');
			$result['showindetail'] = $field->attribute('showindetail');
			$result['showinform'] = $field->attribute('showinform');
			$result['formonly'] = $field->attribute('formonly');

			$result['isname'] = $field->attribute('isname') ? $field->attribute('isname') : 0;

			// store raw value before it is converted to meaningful format - use arrayvalues because sometimes the indexes are strings
			$result['rawvalue'] = is_array($result['value']) ? array_values($result['value']) : $result['value'];

			// add the rsvpdata and events in case its needed by sophisticated element types
			$field->rsvpdata = $this->rsvpdata;
			$field->event = $this->event;
			$field->attendee = $attendee;
			// use nodefieldname instead!
			$field->nodefieldname = $result['name'];
			
			$field->currentAttendees = isset($this->currentAttendees) ? $this->currentAttendees : 1;

			// add reference to nodes into element - conditional fields require this
			$field->nodes = $fieldgroup;

			$user = $this->getUser($attendee);

			// check access
			$accessible = true;
			// The user may have been deleted
			if ($user)
			{
				$levels = $user->getAuthorisedViewLevels();
				$nodeaccess = explode(",", $field->attribute("access", "1"));
			}
			else
			{
				$levels = array();
				$nodeaccess = array(1);
			}
			if (count(array_intersect($levels, $nodeaccess)) == 0)
			{
				$accessible = false;
			}

			// if access flag is 0 then members of this level are BLOCKED
			if ($field->attribute("accessflag", 1) == 0)
			{
				$accessible = !$accessible;
			}
			if (!$accessible)
			{
				$result['value'] = "";
			}

			// Values are only relevant if we have an attendee record and the field is accessible by this attendee!
			if ($attendee && method_exists($field, "convertValue") && $accessible)
			{
				if ($field->attribute('peruser') > 0 && $attendee->guestcount > 1)
				{
					// Checkboxes etc. are a real pain!!!
					if (method_exists($field, "prehandleValues"))
					{
						$values = $field->prehandleValues($result['value'], $field, $attendee);
						$result['value'] = $values;
					}
					else if (is_array($result['value']))
					{
						$values = array();
						foreach ($result['value'] as $g => $val)
						{
							$values[] = ($val == "" || !$field->isVisible( $attendee, $g)) ? "" : $field->convertValue($val);
							$g++;
						}
						$result['value'] = $values;
					}
					else
					{
						// We need an array of values but don't have one!
						// sometimes (e.g. flat fees) will generate the array for us
						$convertedValue = $result['value'] == "" ? "" : $field->convertValue($result['value']);
						if (is_array($convertedValue))
						{
							$result['value'] = $convertedValue;
							// are these values visible ?
							$values = array();
							foreach ($result['value'] as $g => $val)
							{
								$values[] = !is_null($val) && $field->isVisible( $attendee, $g) ? $val : "";
								$g++;
							}
							$result['value'] = $values;
						}
						else
						{
							// still no luck bailout with a warning and use the current value repeated!
							$values = array();

							JError::RaiseWarning(300, "We have a problem with the values of " . $field->name);
							if ($attendee)
							{
								for ($i = 0; $i < $attendee->guestcount; $i++)
								{
									$values[] = $field->isVisible( $attendee, $i) ? $convertedValue : "";
								}
							}
							$result['value'] = $values;
						}
					}
				}
				else
				{
					if (is_array($result['value']))
					{
						$values = array();
						// This is something like a checkbox with multiple values
						foreach ($result['value'] as $val)
						{
							$values[] = $val == "" || !$field->isVisible($attendee, 0) ? "" : $field->convertValue($val);
						}
						// if not guests then this array is multiple choices per attendee so convert to comma separated value
						if ($field->attribute('peruser') == 0)
						{
							$values = implode(",", $values);
						}
						$result['value'] = $values;
					}
					else
					{
						if ($field->attribute('type') == 'jevrbalance' || $field->attribute('type') == 'jevrcbavatar')
						{
							// balances always show - no conditions
							$result['value'] = $field->convertValue($result['value']);
						}
						else
						{
							$result['value'] = $result['value'] == "" || !$field->isVisible($attendee, 0) ? "" : $field->convertValue($result['value']);
						}
					}
				}
			}
			else
			{
				// do we need to control conditional visibility
				// are these values visible ?
				if ($field->attribute("peruser") > 0 && is_array($result['value']))
				{
					$values = array();
					foreach ($result['value'] as $g => $val)
					{
						$values[] = $field->isVisible($field, $attendee, $g) ? $val : "";
						$g++;
					}
					$result['value'] = $values;
				}
				else if ($field->attribute("peruser") == 0 && !is_array($result['value']))
				{
					$result['value'] = $field->isVisible($field, $attendee, 0) ? $result['value'] : "";
				}
			}

			// store the accessibility for use in listing attendees
			$result['accessible'] = $accessible;

			// In case the label is rewritten
			$result['label'] = $field->attribute('label');

			$results[$result['name']] = $result;
		}
		unset($field);
		
		if (is_callable("gzcompress"))
		{
			$cachedData[$cacheid] = gzcompress(serialize($results));
			$cachedMultiAttendee[$cacheid] = $this->multiattendee;
		}
		return $results;

	}

// this version loads the data from the data passed in
	public function renderInputDataToBasicArray($attendee)
	{
		$group = 'xmlfile';

		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}

		$results = array();
		foreach ($fieldgroup as & $field)
		{

			$result = array();
			$result['name'] = $field->fieldname;
			$result['type'] = $field->attribute('type');
			$result['label'] = $field->attribute('label');
			$result['capacity'] = intval($field->attribute('capacity'));
			$result['includeintotalcapacity'] = $field->attribute('includeintotalcapacity');
			$result['reducevaluefortotalcapacity'] = $field->attribute('reducevaluefortotalcapacity');
			$result['conditionalfield'] = $field->attribute('cf');
			$result['conditionalfieldvalue'] = $field->attribute('cffv');

			if ($field->attribute('peruser', 0) > 0)
			{
				$this->multiattendee = true;
				$result['peruser'] = $field->attribute('peruser');
			}
			else if ($field->attribute('peruser', 0) < 0)
			{
				$result['peruser'] = $field->attribute('peruser');
			}
			else
			{
				$result['peruser'] = 0;
			}

			$result['value'] = $this->getValue($field->fieldname, $group, $field->attribute('default'));
			$result['formonly'] = $field->attribute('formonly');

			// add the rsvpdata and events in case its needed by sophisticated element types
			$field->rsvpdata = $this->rsvpdata;
			$field->event = $this->event;
			$field->attendee = $attendee;
			// use nodefieldname instead!
			$field->nodefieldname = $field->fieldname;
			

			// Values are only relevant if we have an attendee record
			if ($attendee && method_exists($field, "convertValue"))
			{
				if ($field->attribute('peruser') > 0 && $attendee->guestcount > 1)
				{
					if (is_array($result['value']))
					{
						$values = array();
						foreach ($result['value'] as $val)
						{
							$values[] = $field->convertValue($val);
						}
						$result['value'] = $values;
					}
					else
					{
						// We need an array of values but don't have one!
						// sometimes (e.g. flat fees) will generate the array for us
						$convertedValue = $field->convertValue($result['value']);
						if (is_array($convertedValue))
						{
							$result['value'] = $convertedValue;
						}
						else
						{
							// still no luck bailout with a warning and use the current value repeated!
							$values = array();

							JError::RaiseWarning(300, "We have a problem with the values of " . $field->name);
							if ($attendee)
							{
								for ($i = 0; $i < $attendee->guestcount; $i++)
								{
									$values[] = $convertedValue;
								}
							}
							$result['value'] = $values;
						}
					}
				}
				else
				{
					if (is_array($result['value']))
					{
						$values = array();
						foreach ($result['value'] as $val)
						{
							$values[] = $field->convertValue($val);
						}
						$result['value'] = $values;
					}
					else
					{
						$result['value'] = $field->convertValue($result['value']);
					}
				}
			}

//			if (method_exists($field, "convertValue"))				$result['value'] = $field->convertValue($result['value']);
			// In case the label is rewritten
			$result['label'] = $field->attribute('label');

			$results[$result['name']] = $result;
		}
		unset($field);
		return $results;

	}

	public function outstandingBalance(&$attendee)
	{
		$group = 'xmlfile';

		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}
		$results = array();
		$totalfee = 0;
		$feepaid = $this->feesPaid($attendee);
		$feebalance = 0;

		$user = $this->getUser($attendee);

		$deposit = false;
		$hasfees = false;

		foreach ($fieldgroup as & $field)
		{

			// check access
			$accessible = true;
			$levels = $user->getAuthorisedViewLevels();
			$fieldaccess = (string) $field->attribute("access", "1");
			$fieldaccess = explode(",", $fieldaccess);
			if (count(array_intersect($levels, $fieldaccess)) == 0)
			{
				$accessible = false;
			}

			// if access flag is 0 then members of this level are BLOCKED
			if ($field->attribute("accessflag", 1) == 0)
			{
				$accessible = !$accessible;
			}

			if (!$accessible)
			{
				continue;
			}


			// add reference to nodes into element - conditional fields require this
			$field->nodes = $fieldgroup;

			if (method_exists($field, "fetchBalance"))
			{
				// add the rsvpdata and events in case its needed by sophisticated element types
				$field->rsvpdata = $this->rsvpdata;
				$field->event = $this->event;
				$field->attendee = $attendee;
				// use nodefieldname instead!
				$field->nodefieldname = $field->fieldname;
				$amount = $field->fetchBalance();
				//$totalfee += round($amount,2);
				$totalfee += $amount;
			}

			if (method_exists($field, "fetchDeposit"))
			{
				list($deposit, $deposittype, $depositamount) = $field->fetchDeposit( $attendee);
			}

			if ($field->attribute('type') == "jevrpaymentoptionlist" || $field->attribute('type') == "jevrpaymentradiolist")
			{
				$hasfees = true;
			}
		}
		unset($field);

		// Handle percentage surcharges at the end
		foreach ($fieldgroup as & $field)
		{

			// check access
			$accessible = true;
			$levels = $user->getAuthorisedViewLevels();
			$fieldaccess = (string) $field->attribute("access", "1");
			$fieldaccess = explode(",", $fieldaccess);
			if (count(array_intersect($levels, $fieldaccess)) == 0)
			{
				$accessible = false;
			}


			// if access flag is 0 then members of this level are BLOCKED
			if ($field->attribute("accessflag", 1) == 0)
			{
				$accessible = !$accessible;
			}

			if (!$accessible)
			{
				continue;
			}

			// add reference to nodes into element - conditional fields require this
			$field->nodes = $fieldgroup;

			if (method_exists($field, "fetchSurcharge"))
			{
				// add the rsvpdata and events in case its needed by sophisticated element types
				$field->rsvpdata = $this->rsvpdata;
				$field->event = $this->event;
				$field->attendee = $attendee;
				// use nodefieldname instead
				$field->nodefieldname = $field->fieldname;
				$surcharge = 1 + $field->fetchSurcharge($field, $attendee) / 100.0;
				$totalfee *= $surcharge;
			}
		}
		unset($field);

		$feebalance = $totalfee - $feepaid;

		$results["feebalance"] = $feebalance;
		$results["feepaid"] = $feepaid;
		$results["totalfee"] = $totalfee;
		if ($totalfee > 0)
		{
			$hasfees = true;
		}
		$results["transactions"] = $attendee->_feedata;
		$results["deposit"] = 0;
		$results["hasfees"] = $hasfees;

		if ($deposit)
		{
			// fixed deposit
			if ($deposittype == 1)
			{
				$results["deposit"] = $depositamount * $totalfee / 100.0;
			}
			else
			{
				$results["deposit"] = $depositamount;
			}
		}

		// if feebalance is positive then set status to awaiting payment - but only for those who are confirmed attendees!
		$db = JFactory::getDBO();
		if ($attendee && $attendee->id > 0)
		{
			// allow for rounding problems !
			if ($feebalance > 0.00001)
			{
				$db->setQuery("UPDATE #__jev_attendees set attendstate=4 WHERE id=" . $attendee->id . " AND attendstate=1");
				$db->query();
			}
			else
			{
				$db->setQuery("UPDATE #__jev_attendees set attendstate=1 WHERE id=" . $attendee->id . " AND attendstate=4");
				$db->query();
			}
		}

		$this->outstandingBalances = $results;
		$attendee->outstandingBalances = $results;
		$this->attendee = $attendee;

		return $results;

	}

	public function paymentMethod(&$attendee)
	{
		$Itemid = JRequest::getInt("Itemid");
		$group = 'xmlfile';
		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}
		if (!isset($attendee->params))
		{
			return "";
		}

		foreach ($fieldgroup as $field)
		{
			if ($field->attribute('type') == "jevrpaymentoptionlist" || $field->attribute('type') == "jevrpaymentradiolist")
			{
				$params = new JRegistry($attendee->params);
				$name = $field->attribute("name");
				$value = $params->get($name);

				// Show only the selected option!
				$html .= $field->fetchPaymentChoices($name, $value, false);

				break;
			}
		}

	}

	public function paymentForm(&$attendee)
	{
		$Itemid = JRequest::getInt("Itemid");
		$group = 'xmlfile';
		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return "";
		}

		if (!isset($attendee->params))
		{
			return "";
		}

		foreach ($fieldgroup as & $field)
		{
			if ($field->attribute('type') == "jevrpaymentoptionlist" || $field->attribute('type') == "jevrpaymentradiolist")
			{

				$params = new JRegistry($attendee->params);
				$name = $field->attribute("name");
				$value = $params->get($name);
				$gateway = $params->get($name);
				$html = '
<form action="' . JRoute::_('index.php?option=com_rsvppro&task=accounts.paymentpage&gateway=' . $gateway . '&Itemid=' . $Itemid) . '" method="POST" name="jevrpaymentform">
	<input type="hidden" name="invoiceid" value="' . $attendee->id . '" />
	<input type="hidden" name="rsvpid" value="' . $attendee->at_id . '" />
	<input type="hidden" name="amount" value="' . $attendee->outstandingBalances["feebalance"] . '" />
	<input type="hidden" name="em" value="' . JRequest::getString("em", JRequest::getString("em2", "")) . '" />
		';

				$html .= JHtml::_('form.token');
				// Show only the selected option!
				$html .= $field->fetchPaymentChoices($name, $value, $field, false);
				$html .= '
	<div class="button2-left paybalance" style="margin-right:10px">
		<div class="blank">
			<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("RSVP_PAY_BALANCE") . '" href="#' . JText::_("RSVP_PAY_BALANCE") . '"  onclick="document.jevrpaymentform.submit();return false;">
				' . JText::_("RSVP_PAY_BALANCE") . '
			</a>
		</div>
	</div>
	<br style="clear:left" />
</form>';
				return $html;
			}
		}
		unset($field);
		return "";

	}

	public function repaymentForm(&$attendee)
	{
		$html = "<strong class='refund'>Request balance refund - not implemented yet</strong><br/>";
		return $html;

	}

	private function feesPaid(&$attendee)
	{
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/models/transactions.php");
		$model = new TransactionsModelTransactions();
		$fees = $model->getFeesPaid($attendee);
		$attendee->feepaid = $fees;
		return $fees;

	}

	public function getTicket($attendee, $rsvpdata, $event)
	{

		$feesAndBalances = $this->outstandingBalance($attendee);

		// set reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $event);

		$data = $this->renderToBasicArray("xmlfile", $attendee);

		$ticket = "";
		for ($i = 0; $i < $attendee->guestcount; $i++)
		{
			$tickettemplate = $this->ticket;
			// do event based fields first
			$tickettemplate = str_replace("{EVENT}", $event->title(), $tickettemplate);
			$tickettemplate = str_replace("{LOCATION}", $event->location(), $tickettemplate);


			if (isset($attendee->outstandingBalances) && isset($attendee->outstandingBalances['hasfees']) && $attendee->outstandingBalances['hasfees'])
			{
				$tickettemplate = str_ireplace("{FEEPAID}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feepaid']), $tickettemplate);
				$tickettemplate = str_ireplace("{BALANCE}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feebalance']), $tickettemplate);
				$tickettemplate = str_ireplace("{TOTALFEE}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['totalfee']), $tickettemplate);
				$tickettemplate = str_ireplace("{FEESPAID}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feepaid']), $tickettemplate);
				$tickettemplate = str_ireplace("{TOTALFEES}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['totalfee']), $tickettemplate);
			}
			else {
				$tickettemplate = str_ireplace("{FEEPAID}", "", $tickettemplate);
				$tickettemplate = str_ireplace("{BALANCE}", "", $tickettemplate);
				$tickettemplate = str_ireplace("{TOTALFEE}", "", $tickettemplate);
				$tickettemplate = str_ireplace("{FEESPAID}", "", $tickettemplate);
				$tickettemplate = str_ireplace("{TOTALFEES}", "", $tickettemplate);
			}

			$code = "access_code" . "-" . $attendee->id . "-" . ($i + 1);
			$tickettemplate = str_ireplace("{BARCODE}", "<img src='" . JURI::root() . "components/com_rsvppro/assets/images/image.php?bc=" . $code . "' alt='Barcode'/>", $tickettemplate);

			$regex = "#{DATE}(.*?){/DATE}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				$date = new JevDate($event->getUnixStarttime());
				$tickettemplate = preg_replace($regex, $date->toFormat($matches[1]), $tickettemplate);
			}
			$regex = "#{TIME}(.*?){/TIME}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				$date = new JevDate($event->getUnixStarttime());
				$tickettemplate = preg_replace($regex, $date->toFormat($matches[1]), $tickettemplate);
			}
			$regex = "#{REGDATE}(.*?){/REGDATE}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				$date = new JDate($attendee->created);
				$tickettemplate = preg_replace($regex, $date->toFormat($matches[1]), $tickettemplate);
			}
			$regex = "#{REGEMAIL}(.*?){/REGEMAIL}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				if ($attendee->user_id>0){
					$atuser = JFactory::getUser($attendee->user_id);
					$email = $atuser->email;
				}
				else {
					$email = $attendee->email_address;
				}
				$tickettemplate = preg_replace($regex, $email, $tickettemplate);
			}			
			$regex = "#{REGID}(.*?){/REGID}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				$tickettemplate = preg_replace($regex, sprintf($matches[1], $attendee->id), $tickettemplate);
			}
			$regex = "#{GUESTNUM}(.*?){/GUESTNUM}#s";
			preg_match($regex, $tickettemplate, $matches);
			if (count($matches) == 2)
			{
				$tickettemplate = preg_replace($regex, sprintf($matches[1], $i + 1), $tickettemplate);
			}

			$userdet = JFactory::getUser($event->created_by());
			if ($this->jevparams->get('contact_display_name', 0) == 1)
			{
				$contactlink = $userdet->name;
			}
			else
			{
				$contactlink = $userdet->username;
			}
			
			$tickettemplate = str_replace("{CREATOR}", $contactlink, $tickettemplate);			
			
			// reset the fields to correct raw format
			$tickettemplate = preg_replace("/({[^}]*:label:)/U", '{fieldlabel:', $tickettemplate);
			$tickettemplate = preg_replace("/({[^}]*:field:)/U", '{field:', $tickettemplate);

			// replace the fields
			foreach ($data as $field)
			{
				if ($field['peruser'] <= 0 && $i > 0)
				{
					$value = "";
					$label = "";
				}
				else if ($field['peruser'] == 2 && $i == 0)
				{
					$value = "";
					$label = "";
				}
				else
				{
					$value = $field["value"];
					if (is_array($value) && count($value) == $attendee->guestcount)
					{
						$value = $value[$i];
					}
					$label = $field["label"];
				}
				//$tickettemplate = str_replace("{field:".$field["name"]."}", $value, $tickettemplate);
				//$tickettemplate = str_replace("{fieldlabel:".$field["name"]."}", $label, $tickettemplate);

				$fieldname = "name";
				if ($field['type'] == "jevrbalance")
				{
					$fieldname = "fieldname";
				}
				if ($label != "")
				{
					$tickettemplate = preg_replace("/{fieldlabel:(.*)?#" . $field[$fieldname] . "#(.*)?}/U", '${1}' . $label . '$2', $tickettemplate);
				}
				else
				{
					$tickettemplate = preg_replace("/{fieldlabel:.*#" . $field[$fieldname] . "#.*}/U", '', $tickettemplate);
				}
				if ($value != "")
				{
					$tickettemplate = preg_replace("/{field:(.*)?#" . $field[$fieldname] . "#(.*)?}/U", '${1}' . $value . '$2', $tickettemplate);
				}
				else
				{
					$tickettemplate = preg_replace("/{field:.*#" . $field[$fieldname] . "#.*}/U", '', $tickettemplate);
				}
			}
			if ($i > 0)
			{
				$ticket .= "<hr/>";
			}
			$ticket .= $tickettemplate;
		}

		return $ticket;

	}

	/**
	 * function to tell me if this session supports multi-user fields
	 */
	public function isMultiAttendee()
	{
		if (!isset($this->multiattendee))
		{
			$this->renderToBasicArray('xmlfile');
		}
		return $this->multiattendee;

	}

	public function curentAttendeeCount()
	{
		$group = 'xmlfile';
		if (!$this->isMultiAttendee())
		{
			return 1;
		}
		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return 1;
		}

		if (isset($this->attendee) && isset($this->attendee->guestcount))
		{
			$this->currentAttendees = $this->attendee->guestcount;
			return $this->currentAttendees;
		}
		// This should now be redundant
		$currentCount = 1;
		foreach ($fieldgroup as $field)
		{
			if (method_exists($field, "currentAttendeeCount"))
			{
				$value = $this->getValue($field->fieldname, $group, $field->attribute('default'));
				$count = $field->currentAttendeeCount($field, $value);
				$currentCount = $count > $currentCount ? $count : $currentCount;
			}
		}
		$this->currentAttendees = $currentCount;
		return $currentCount;

	}

	protected function fixValue(&$value, $field)
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
				$value[] = $field->attribute('default');
			}
		}

	}

	public function calculateRowContributionsToCapacity(& $result, $type, & $attendee)
	{

		if (!$attendee)
		{
			// Now find how much of the capacity is used up
			// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
			$registry = & JRegistry::getInstance("jevents");
			$rsvpdata = $registry->get("rsvpdata");
			$row = $registry->get("event");

			$db = JFactory::getDBO();
			static $templateParams;
			if (!isset($templateParams))
			{
				$templateParams = false;
				if ($rsvpdata->template != "")
				{
					$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

					$templateParams = $db->loadObject();
					if ($templateParams)
					{
						$templateParams = json_decode($templateParams->params);
					}
				}
			}
			if (!$templateParams || !isset($templateParams->unpaidcapacity) || $templateParams->unpaidcapacity == 0)
			{
				$sql = "SELECT params, guestcount  FROM #__jev_attendees as a  WHERE a.at_id=" . $rsvpdata->id . " AND attendstate=1";
			}
			else
			{
				$sql = "SELECT params, guestcount  FROM #__jev_attendees as a  WHERE a.at_id=" . $rsvpdata->id . " AND (attendstate=1 || attendstate=4)";
			}

			if (!$rsvpdata->allrepeats)
			{
				$sql .= " and a.rp_id=" . $row->rp_id();
			}
			$db->setQuery($sql);
			$attendeeData = $db->loadObjectList();
			$attendee = $attendeeData;
		}
		else
		{
			$attendeeData = array();
			$attendeeData[] = & $attendee;
		}
		
		$field = $this->loadFieldType($type);

		if ($result['capacity'] > 0 || method_exists($field, "totalCapacityContribution") || $rsvpdata->capacity > 0)
		{
			$count = 0;
			for ($i = 0; $i < count($attendeeData); $i++)
			{
				$aparams = new JRegistry($attendeeData[$i]->params);
				$pvalues = $aparams->get($result['name']);
				// These may be multiple guests so treat as an array
				if (!is_array($pvalues))
				{
					$pvalues = array($pvalues);
				}
				foreach ($pvalues as $pvalue)
				{
					$pvalue = intval($pvalue);
					if (method_exists($field, "totalCapacityContribution"))
					{
						$pvalue = $field->totalCapacityContribution($pvalue, $field);
					}
					// waiting elements may return 0.0001 so reset to integer here!
					$pvalue = intval($pvalue);

					// only reduce total capacity by attendees/guests that have non-zero values
					if ($pvalue > 0)
					{

						$count+= $pvalue;
						//if (!isset($attendeeData[$i]->_reductionapplied))	{
						// reduce the count for each quest
						$result['reducetotalcapacity'] += $result['reducevaluefortotalcapacity'];

						//$attendeeData[$i]->_reductionapplied = 1;
						//}
					}
				}
			}
			$result['capacitycount'] = $count;
		}

	}

	protected function loadSetupXML($xmlstring)
	{
		$result = false;

		if ($xmlstring)
		{
			$data = JFactory::getXML($xmlstring, false);
			// Load the custom fields
			$this->load($data, true, "/form");
			$this->ticket = trim((string) $data->ticket);
			$this->description = trim((string) $data->description);
			$result = true;
		}
		else
		{
			$result = false;
		}

		return $result;

	}

	protected function convertTemplateToXML($template)
	{
		//$xml = file_get_contents(JPATH_SITE."/plugins/jevents/rsvppro/params/fields2.xml");
		$xml = array();
		$xml[] = "<?xml version='1.0' encoding='utf-8'?>";
		$xml[] = '<form>';
		$xml[] = '<fields name="xmlfile" >';
		$xml[] = '<fieldset name="xmlfile" addfieldpath="/administrator/components/com_rsvppro/fields/" >';
		$xml[] = '<description>';
		$xml[] = '<![CDATA[';
		$xml[] = $template->description;
		$xml[] = ']]>';
		$xml[] = '</description>';
		foreach ($template->fields as $field)
		{
			$element = & $this->loadFieldType($field->type);
			$xml[] = $element->toXml($field);
		}

		$xml[] = '</fieldset>';
		$xml[] = '</fields>';

		if ($template->withticket && $template->ticket != "")
		{
			$xml[] = '<ticket>';
			$xml[] = '<![CDATA[';
			$xml[] = $template->ticket;
			$xml[] = ']]>';
			$xml[] = '</ticket>';
		}

		$xml[] = '</form>';

		return implode("\n", $xml);

	}

	protected function getUser($attendee = false)
	{
		$userid = 0;
		if ($attendee)
		{
			$userid = $attendee->user_id;
		}
		else if (isset($this->attendee->user_id) && $this->attendee->user_id > 0)
		{
			$userid = $this->attendee->user_id;
		}
		else if (isset($this->potentialAttendee->id))
		{
			$userid = $this->potentialAttendee->id;
		}

		$user = JFactory::getUser($userid);

		if (JVersion::isCompatible("1.6.0"))
		{
			return $user;
		}

		if ($user->get('id') == 0)
		{
			$user->set('guest', 1);
			$user->set('aid', 0);
		}
		else
		{
			// Get an ACL object
			$acl = & JFactory::getACL();
			if (!isset($user->usertype))
			{
				$grp = $acl->getAroGroup($user->get('id'));
				$grpname = $grp->name;
			}
			else
			{
				$grpname = $user->usertype;
			}

			//Mark the user as logged in
			$user->set('guest', 0);
			$user->set('aid', 1);

			static $parentageOK;
			if (!isset($parentageOK))
			{
				$parentageOK = array();
			}
			if (!isset($parentageOK[$grpname]))
			{
				// Fudge Authors, Editors, Publishers and Super Administrators into the special access group
				if ($acl->is_group_child_of($grpname, 'Registered') ||
						$acl->is_group_child_of($grpname, 'Public Backend'))
				{
					$parentageOK[$grpname] = true;
				}
				else
				{
					$parentageOK[$grpname] = false;
				}
			}
			if ($parentageOK[$grpname])
			{
				$user->set('aid', 2);
			}
		}
		return $user;

	}

	// post confirmation actions in parameter fields e.g. signup for newsletters etc.
	public function postUpdateActions($rsvpdata, $row, $attendee, $onWaitingList)
	{
		$name = 'params';
		$group = 'xmlfile';

		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}

		foreach ($fieldgroup as & $field)
		{

			$user = JFactory::getUser($attendee->user_id);
			// check access
			$accessible = true;
			$levels = $user->getAuthorisedViewLevels();
			$nodeaccess = explode(",", $field->attribute("access", array(1)));
			if (count(array_intersect($levels, $nodeaccess)) == 0)
			{
				$accessible = false;
			}

			// if access flag is 0 then members of this level are BLOCKED
			if ($field->attribute("accessflag", 1) == 0)
			{
				$accessible = !$accessible;
			}

			if (!$accessible)
			{
				continue;
			}


			// add reference to nodes into element - conditional fields require this
			$field->nodes = $fieldgroup;

			if (method_exists($field, "postUpdateAction"))
			{
				// add the rsvpdata and events in case its needed by sophisticated element types
				$field->rsvpdata = $this->rsvpdata;
				$field->event = $this->event;
				$field->attendee = $attendee;
				$field->onWaitingList = $onWaitingList;
				$field->name = $field->fieldname;
				$field->postUpdateAction($field);
			}
		}
		unset($field);
		return true;

	}

	public function getNumParams($group = 'xmlfile')
	{
		return count($this->getFieldset($group));

	}

	/*
	 *  Joomla 3.0 compatability methods
	 */

	// special version to keep static data intact!
	public function & getFieldset($set = null)
	{
		//return  parent::getFieldset($set);
		
		$templateid = isset($this->_rawtemplate->id) ? $this->_rawtemplate->id : 0;

		/*
		static $data = array();
		if (!isset($data[$set.$templateid]))
		{
			$data[$set.$templateid] = parent::getFieldset($set);
		}
		return $data[$set.$templateid];
		*/
		
		if (!isset($this->templateFieldsetData[$set.$templateid]))
		{
			$this->templateFieldsetData[$set.$templateid] = parent::getFieldset($set);
		}
		return $this->templateFieldsetData[$set.$templateid];
		
	}

	// get static data
	public function & getField($name, $group = null, $value = null)
	{
		$fieldSetData = $this->getFieldset($group);
		
		if (array_key_exists($group."_".$name, $fieldSetData)){
			$field = $fieldSetData[$group."_".$name];
		}
		if ($value ==  $field->value){
			return $field;
		}
		
		return parent::getField($name, $group, $value);
	}

	/**
	 * Sets the XML object from custom XML files.
	 *
	 * @param   JSimpleXMLElement  &$xml  An XML object.
	 *
	 * @return  void
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function setXML(&$xml)
	{

		// Deprecation warning.
		JLog::add('JParameter::setXML is deprecated.', JLog::WARNING, 'deprecated');

		if (is_object($xml))
		{
			if ($groupname = (string) $xml->attributes()->group)
			{
				$this->_xml[$groupname] = $xml;
			}
			else
			{
				$this->_xml['xmlfile'] = $xml;
			}

		}

	}

	
	/**
	 * Method to bind data to the form.
	 *
	 * @param   mixed  $data  An array or object of data to bind to the form.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public function bind($data)
	{
		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof SimpleXMLElement))
		{
			return false;
		}

		// The data must be an object or array.
		if (!is_object($data) && !is_array($data))
		{
			return false;
		}

		// Convert the input to an array.
		if (is_object($data))
		{
			if ($data instanceof JRegistry)
			{
				// Handle a JRegistry.
				$data = $data->toArray();
			}
			elseif ($data instanceof JObject)
			{
				// Handle a JObject.
				$data = $data->getProperties();
			}
			else
			{
				// Handle other types of objects.
				$data = (array) $data;
			}
		}

		// Process the input data.
		foreach ($data as $k => $v)
		{

			if ($this->findField($k, "xmlfile"))
			{
				// If the field exists set the value.
				$this->data->set("xmlfile.".$k, $v);
			}
			elseif (is_object($v) || JArrayHelper::isAssociative($v))
			{
				// If the value is an object or an associative array hand it off to the recursive bind level method.
				$this->bindLevel($k, $v);
			}
		}

		return true;
	}
	

}
