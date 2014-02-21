<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport("joomla.form.form");

class RsvpForm extends JForm
{

	private $event;

	/*
	 * 
	 * Element output
	  $result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
	  $result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
	  $result[2] = $descr;
	  $result[3] = $label;
	  $result[4] = $value;
	  $result[5] = $name;

	 */

	public function setEvent($event)
	{
		$this->event = $event;

	}

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	public function render($name = 'custom_', $group = 'default', &$customfields)
	{
		$fieldsetData = $this->getFieldsetInfo($group);
		if (!$fieldsetData)
		{
			return false;
		}

		// The description from the fieldset node!		
		if ($fieldsetData && $fieldsetData->attributes()->description != "")
		{
			// add the params description to the display
			$desc = JText::_($fieldsetData->attributes()->description);
			$customfield = array("label" => "", "input" => $desc);
			$customfields["customfield_" . $group] = $customfield;
		}
		$params = $this->getFieldset($group);
		if (!$params)
		{
			return false;
		}

		foreach ($params as $p => $field)
		{
			$customfield = array();

			// magic method to get the input
			$customfield["input"] = $field->input;

			// Jform takes care of the label and the tooltip
			$customfield["label"] = JText::_($field->label);
			
			$customfield["type"] = $field->type;
			$customfield["class"] =get_class($field);			
			$customfield["name"] = $field->id;

			// should we reset the label?
			$customfield["label"] = strip_tags($customfield["label"]) != "" ? $customfield["label"] : "";

			if (strpos($field->name, ".") === false && strpos($field->name, "#") === false)
			{
				// if its the fall back class then the type is not valid
				if (get_class($field)=="JFormFieldText" && $field->type!="Text"){
					//JError::raise(E_ERROR, 500, JText::sprintf("JEV_INVALID_FIELD_TYPE",$field->fieldname));
					echo JText::sprintf("JEV_INVALID_FIELD_TYPE",$field->fieldname)."<br/>";
				}
				$key = $field->fieldname;
				$customfields["customfield_" . $key] = $customfield;
			}
		}

		return;

	}

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	public function setupRender()
	{

		// Get all the categories and their parentage
		$db = JFactory::getDBO();
		if (JVersion::isCompatible("1.6.0"))
		{
			$db->setQuery("SELECT id, parent_id from #__categories where extension='com_jevents' and published=1");
		}
		else
		{
			$db->setQuery("SELECT id, parent_id from #__categories where section='com_jevents' and published=1");
		}
		$catlist = $db->loadObjectList("id");

		$cats = array();
		foreach ($catlist as $cat)
		{
			// extract the complete ancestry
			if (!array_key_exists($cat->id, $cats))
			{
				$cats[$cat->id] = array();
				$cats[$cat->id][] = $cat->id;
				$parent = ($cat->parent_id > 0 && array_key_exists($cat->parent_id, $catlist)) ? $catlist[$cat->parent_id] : false;
				while ($parent)
				{
					$cats[$cat->id][] = $parent->id;
					$parent = ($parent->parent_id > 0 && array_key_exists($parent->parent_id, $catlist)) ? $catlist[$parent->parent_id] : false;
				}
			}
		}
		// Must set this up for empty category too
		$cats[0] = array();
		$cats[][] = 0;

		$cats = json_encode($cats);

		// setup required fields script
		$doc = JFactory::getDocument();
		$script = <<<SCRIPT
var jevrsvpRequiredFields = {
	fields: new Array(),
	verify:function (form){
		var messages =  new Array();
		valid = true;
		jevrsvpRequiredFields.fields.each(function (item,i) {
			name = item.name;

		    // should we skip this test because of category restrictions?
			if (JevrCategoryFields.skipVerify(name))  return;
			var matches = new Array();
			\$$(form.elements).each (function (testitem,testi) {
				if(testitem.name == name ||  "custom_"+testitem.name == name || testitem.id == name  || ("#"+testitem.id) == name  || testitem.hasClass(name.substr(1))){
					matches.push(testitem);		
				};
			});
			var value = "";
			if(matches.length==1){
				value = matches[0].value;
			}
			// A set of radio checkboxes
			else if (matches.length>1){
				matches.each (function (match, index){
					if (match.checked) value = match.value; 
				});				
			}
			//if (elem) elem.value = item.value;
			if (value == item['default'] || value == ""){
				valid = false;
				// TODO add message together 
				if(item.reqmsg!=""){
					messages.push(item.reqmsg);
				}
			}
		});
		if (!valid){
			message = "";
			messages.each (function (msg, index){message += msg+"\\n";});
			alert(message); 
		}
		return valid;
	}
}
// Disabled for now
/*
window.addEvent("domready",function(){
	var form =document.adminForm;
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
*/


// category conditional fields
var JevrCategoryFields = {
	fields: [],
	cats: $cats,
	setup:function (){
		if (!$('catid')) return;
		var catidel = $('catid');
		var catid = catidel.value;

		// These are the ancestors of this cat
		this.fields.each(function (item,i) {
			var elem = $(document).getElement(".jevplugin_customfield_"+item.name);
			if (item.name.indexOf(".")==0 || item.name.indexOf("#")==0) {
				elem = $(document).getElement(item.name);
			}
			// This is the version that ignores parent category selections
			/*
			// only show it if the selected category is in the list
			if (\$$(item.catids).contains(catid)){
				if (window.ie) {
					elem.style.display="";
				}
				else {
					elem.style.display="table-row";
				}
			}
			else {				
				elem.style.display="none";
			}
			*/
			// hide the item by default
			elem.style.display="none";
			
			if (catidel.multiple){
				\$$(catidel.options).each(function(opt) {
					if (opt.selected) {
						catid = opt.value;
						var cats = this.cats[catid];
						\$$(cats).each (function(cat,i){
							if (\$$(item.catids).contains(cat)){
								//alert("matched "+cat + " cf "+item.catids);
								if (window.ie) {
									elem.style.display="";
								}
								else {
									elem.style.display="table-row";
								}
							}
						});
					}
				}.bind(this));
			}
			else {
				var cats = this.cats[catid];
				\$$(cats).each (function(cat,i){
					if (\$$(item.catids).contains(cat)){
						//alert("matched "+cat + " cf "+item.catids);
						if (window.ie) {
							elem.style.display="";
						}
						else {
							elem.style.display="table-row";
						}
					}
				});
			}

		}.bind(this));
	},
	skipVerify:function (fieldname){
		if (!$('catid')) return true;
		var catid = $('catid').value;
		var cats = JevrCategoryFields.cats[catid];
		var skip = false;
		this.fields.each(function (item,i) {
			if ('custom_'+item.name !== fieldname) return;
			skip = true;
			\$$(cats).each (function(cat,i){
				if (\$$(item.catids).contains(cat)){
					skip = false;
					return;
				}
			});
		});
		return skip;
	}	
};
window.addEvent("load",function(){
	if (JevrCategoryFields){
		JevrCategoryFields.setup();
		if (!$('catid')) return;
		$('catid').addEvent('change',function(){
			JevrCategoryFields.setup();
		});
		if (!$('ics_id')) return;
		$('ics_id').addEvent('change',function(){
			setTimeout("JevrCategoryFields.setup()",500);
		});
	}
});
SCRIPT;
		$doc->addScriptDeclaration($script);

	}

	public function getFieldCountByFieldSet($fieldset)
	{
		$fields = $this->findFieldsByFieldset($fieldset);
		return $fields ? count($fields) : 0;

	}

	public function getFieldsetInfo($name)
	{
		// Initialise variables.
		$false = false;

		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof SimpleXMLElement))
		{
			return $false;
		}

		/*
		 * Get an array of <field /> elements that are underneath a <fieldset /> element
		 * with the appropriate name attribute, and also any <field /> elements with
		 * the appropriate fieldset attribute.
		 */
		$fieldsets = $this->xml->xpath('//fieldset[@name="' . $name . '"]');
		if ($fieldsets)
		{
			return $fieldsets[0];
		}
		return false;

	}

	public function renderToBasicArray( $group = 'xmlfile')
	{
		$fieldsetData = $this->getFieldsetInfo($group);
		if (!$fieldsetData)
		{
			return false;
		}

		$results = array();
		$fieldgroup = $this->getFieldset($group);

		if (!$params)
		{
			return false;
		}

		$doc = JFactory::getDocument();

		foreach ($fieldgroup as $p => &$field)
		{
			// attach the event to the $field
			$field->event = $this->event;

			if ($field->attribute('categoryrestrictions'))
			{
				$cats = explode(",", $field->attribute('categoryrestrictions'));
				JArrayHelper::toInteger($cats);

				if (isset($this->event) && is_a($this->event, "jIcalEventRepeat" ) && !in_array($this->event->catid(), $cats))
					continue;
			}
			$result = array();
			$result['type'] = $field->attribute('type');
			if (strpos($result['type'], "jevr") === 0)
			{
				$result['type'] = "jevcf" . substr($result['type'], 4);
			}

			//$result['value'] = $this->get($field->name, $field->attribute('default'), $group);
			$result['value'] = $field->value;
			$result['defaultvalue'] = $field->attribute('default');

			if (method_exists($field, "convertValue"))
				$result['value'] = $field->convertValue($result['value']);

			// reset the type - just in case a special type has changed the node attributes
			$result['type'] = $field->type;
			if (strpos($result['type'], "jevr") === 0)
			{
				$result['type'] = "jevcf" . substr($result['type'], 4);
			}

			$result['name'] = $field->attribute('name');
			$result['label'] = $field->attribute('label');
			$result['access'] = ($field->attribute('access') != null) ? $field->attribute('access') : 1;
			$result['readaccess'] = $field->attribute('readaccess');
			$result['hiddenvalue'] = $field->attribute('hiddenvalue');
			$result['userid'] = $field->attribute('userid');
			$result['allowhtml'] = $field->attribute('allowhtml');

			// if field is for attendees only then hide if necessary
			if ($field->attribute('attendeesonly') == 1)
			{
				$this->hideFromNonAttendees($result, $field);
			}

			$results[$result['name']] = $result;
		}
		unset($field);
		return $results;

	}

	public function constructFilters()
	{

		$this->filterElements = array();

		$groups = $this->getFieldsets();
		foreach ($groups as $group => $element)
		{
			$count = $this->getFieldCountByFieldSet($group);
			if ($count == 0)
			{
				continue;
			}
			$groupparams = $this->getFieldset($group);
			foreach ($groupparams as $p => $field)
			{
				if (!$field->attribute("filter"))
					continue;
				$type = $field->type;
				if (strpos($type, "jevr") === 0)
				{
					$type = "jevcf" . substr($type, 4);
				}
				// Must be a new one
				if (method_exists($field, "constructFilter"))
					$field->constructFilter($field);
				$this->filterElements[] = $field;
			}
		}
		return;

	}

	public function createFilters()
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createFilter"))
			{
				$result = $element->createFilter();
				if ($result)
					$results[] = $result;
			}
		}
		return implode(" AND ", $results);

	}

	public function createJoinFilters()
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createJoinFilter"))
			{
				$result = $element->createJoinFilter();
				if ($result)
					$results[] = $result;
			}
		}
		return implode(" LEFT JOIN ", $results);

	}

	public function setSearchKeywords(& $extrajoin)
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "setSearchKeywords"))
			{
				$result = $element->setSearchKeywords($extrajoin);
				if ($result)
					$results[] = $result;
			}
		}
		return $results;

	}

	public function createFiltersHTML()
	{
		$results = array();
		$results["merge"] = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createFilterHTML"))
				$results["merge"][] = $element->createFilterHTML();
		}
		return $results;

	}

	/* overloaded methods */

	/**
	 * Method to load the form description from an XML file.
	 *
	 * The reset option works on a group basis. If the XML file references
	 * groups that have already been created they will be replaced with the
	 * fields in the new XML file unless the $reset parameter has been set
	 * to false.
	 *
	 * @param   string  $file   The filesystem path of an XML file.
	 * @param   string  $reset  Flag to toggle whether form fields should be replaced if a field
	 *                          already exists with the same group/name.
	 * @param   string  $xpath  An optional xpath to search for the fields.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * CUSTOM VERSION - if we have an old xml file converts to new version
	 * 
	 * @since   11.1
	 */
	public function loadFile($file, $reset = true, $xpath = false)
	{
		// Check to see if the path is an absolute path.
		if (!is_file($file))
		{

			// Not an absolute path so let's attempt to find one using JPath.
			$file = JPath::find(self::addFormPath(), strtolower($file) . '.xml');

			// If unable to find the file return false.
			if (!$file)
			{
				return false;
			}
		}
		// Attempt to load the XML file.
		$xml = JFactory::getXML($file, true);

		if ($xml && $xml->getName() == "config")
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf("JEV_CUSTOM_FIELD_CONFIG_FILE_REGENERATED", basename($file), str_replace(".xml","_jform.xml",basename($file))),"warning");
			
			// rework the XML file 
			//  **** include name for default group if not set ****
			if (!$xml->params [0]->attributes("group"))
			{
				$xml->params [0]->addAttribute("group", "default");
			}

			// Eliminate fields with . in their names => replace with underscore e.g. field2.5
			$namechanges = array();
			$paramcount = phpversion() >= '5.3.0' ? $xml->params->count() : count($xml->params->children() );
			for ($p = 0; $p < $paramcount; $p++)
			{
				$pcount = isset($xml->params[$p]) ? (phpversion() >= '5.3.0' ? $xml->params[$p]->count() : count($xml->params[$p]->children())) : 0;
				for ($i = 0; $i < $pcount; $i++)
				{
					if (strpos($xml->params[$p]->param[$i]->attributes()->name, ".") !== false || strpos($xml->params[$p]->param[$i]->attributes()->name, " ") !== false)
					{
						$oldname =(string) $xml->params[$p]->param[$i]->attributes()->name;
						$newname = str_replace(array(".", " "), "_", $xml->params[$p]->param[$i]->attributes()->name);
						JFactory::getApplication()->enqueueMessage(JText::sprintf("JEV_CUSTOM_FIELD_RENAMED_AND_DATA_COPIED", $oldname,$newname));
						$xml->params[$p]->param[$i]->attributes()->name = $newname;
						$namechanges[$oldname]=$newname;
					}
				}
			}

			$tempXml = $xml->asXML();
			// do something to convert old xml files to new JForm versions
			// config -> fields ,  wrap fields in form
			$tempXml = str_replace("<config>", "<form><fields>", $tempXml);
			$tempXml = str_replace("</config>", "</fields></form>", $tempXml);

			// params- > fieldset, group -> name , addpath -> addfieldpath and
			$tempXml = str_replace("<params", "<fieldset", $tempXml);
			$tempXml = str_replace("params>", "fieldset>", $tempXml);
			$tempXml = str_replace(" group=", " name=", $tempXml);
			$tempXml = str_replace("addpath=", "addfieldpath=", $tempXml);

			// param => field
			$tempXml = str_replace("<param ", "<field ", $tempXml);
			$tempXml = str_replace("param>", "field>", $tempXml);

			// jevr -> jevcf throughout
			$tempXml = str_replace("type='jevr", "type='jevcf", $tempXml);
			$tempXml = str_replace('type="jevr', 'type="jevcf', $tempXml);

//			$xml = simplexml_load_string($tempXml, 'JXMLElement');

			$xmlfile = str_replace(".xml","_jform.xml",$file);
			jimport("joomla.filesystem.file");
			if (!JFile::exists($xmlfile)){
				JFile::write($xmlfile,$tempXml);
				// Also map the data
				$db = JFactory::getDbo();
				foreach ($namechanges as $oldname => $newname){
					$db->setQuery("UPDATE #__jev_customfields set name=".$db->quote($newname). " WHERE name=".$db->quote($oldname));
					$db->query();
				}
			}
			// reload the XML file
			$xml = JFactory::getXML($tempXml, false);
		}
		return $this->load($xml, $reset, $xpath);

	}

	/**
	 * Method to get an instance of a form.
	 *
	 * @param   string  $name     The name of the form.
	 * @param   string  $data     The name of an XML file or string to load as the form definition.
	 * @param   array   $options  An array of form options.
	 * @param   string  $replace  Flag to toggle whether form fields should be replaced if a field
	 * already exists with the same group/name.
	 * @param   string  $xpath    An optional xpath to search for the fields.
	 *
	 * @return  object  JForm instance.
	 *
	 * CUSTOM VERSION - gets instance of this class instead of JForm
	 * 
	 * @since   11.1
	 * @throws  Exception if an error occurs.
	 */
	public static function getInstance($name, $data = null, $options = array(), $replace = true, $xpath = false)
	{
		// Reference to array with form instances
		$forms = &self::$forms;

		// Only instantiate the form if it does not already exist.
		if (!isset($forms[$name]))
		{

			$data = trim($data);

			if (empty($data))
			{
				throw new Exception(JText::_('JLIB_FORM_ERROR_NO_DATA'));
			}

			// Instantiate the form.
			$forms[$name] = new RsvpForm($name, $options);

			// Load the data.
			if (substr(trim($data), 0, 1) == '<')
			{
				if ($forms[$name]->load($data, $replace, $xpath) == false)
				{
					throw new Exception(JText::_('JLIB_FORM_ERROR_XML_FILE_DID_NOT_LOAD'));

					return false;
				}
			}
			else
			{
				if ($forms[$name]->loadFile($data, $replace, $xpath) == false)
				{
					throw new Exception(JText::_('JLIB_FORM_ERROR_XML_FILE_DID_NOT_LOAD'));

					return false;
				}
			}
		}

		// make sure jevents custom fields path is included!
		$path = JPATH_ROOT . '/plugins/jevents/jevcustomfields/customfields/';
		self::addFieldPath($path);

		return $forms[$name];

	}

}
