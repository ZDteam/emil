<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: abstract.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//jimport( 'joomla.html.parameter');
//class JevParameter extends  JParameter {

class JevParameter extends  JRegistry {
	
	
	/**
	 * @var    string  The raw params string
	 * @since  11.1
	 */
	protected $_raw = null;

	/**
	 * @var    object  The XML params element
	 * @since  11.1
	 */
	protected $_xml = null;

	/**
	 * @var    array  Loaded elements
	 * @since  11.1
	 */
	protected $_elements = array();

	/**
	 * @var    array  Directories, where element types can be stored
	 * @since  11.1
	 */
	protected $_elementPath = array();

	
	public function __construct($data = '', $path = '')
	{
		parent::__construct($data, $path);
				
		// Set base path.
		$this->_elementPath[] = dirname(__FILE__) . '/element';
		
		if ($path)
		{
			$this->loadSetupFile($path);
		}

		$this->_raw = $data;		
	}
	
	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	function render($name = 'params', $group = 'xmlfile')
	{
		$fieldgroup = $this->getFieldset($group);
		if (!$fieldgroup)
		{
			return false;
		}

		$params = $this->getParams($name, $group);
		$html = array ();
		$html[] = '<table width="100%" class="paramlist admintable" cellspacing="1">';

		if ($description = (string)$fieldgroup>attributes()->description) {
			// add the params description to the display
			$desc	= JText::_($description);
			$html[]	= '<tr><td class="paramlist_description" colspan="2">'.$desc.'</td></tr>';
		}

		foreach ($params as $param)
		{
			$class="";
			$rawparam = false;
			// find extra non-standard information
			foreach ($fieldgroup as $kid)  {
				if (((string)$kid->attributes()->name)!="@spacer" && ((string)$kid->attributes()->name)==$param[5] && ((string)$kid->attributes()->label)==$param[3] && ((string)$kid->attributes()->description)==$param[2]){
					$class=$kid->attributes("class");
					$rawparam = $kid;
					break;
				}
			}
			if (strlen($class)>0){
				$class=" class='$class'";
			}
			$html[] = "<tr $class>";
			if ($param[0]) {
				$html[] = '<td width="40%" class="paramlist_key"><span class="editlinktip">'.$param[0].'</span></td>';
				$html[] = '<td class="paramlist_value">'.$param[1].'</td>';
			} else {
				$html[] = '<td class="paramlist_value" colspan="2">'.$param[1].'</td>';
			}

			$html[] = '</tr>';
		}

		if (count($params) < 1) {
			$html[] = "<tr><td colspan=\"2\"><i>".JText::_('There are no Parameters for this item')."</i></td></tr>";
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}

	
	public function getElementPaths(){
		return $this->_elementPath;
	}

	
	/*
	 *  Joomla 3.0 compatability methods
	 */
	public function bind($data){
		return parent::bindData($this->data, $data);
		
	}
	

	/**
	 * Loads an XML setup file and parses it.
	 *
	 * @param   string  $path  A path to the XML setup file.
	 *
	 * @return  object
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function loadSetupFile($path)
	{
		$result = false;

		if ($path)
		{
			//$xml = JFactory::getXMLParser('Simple');
			//if ($xml->loadFile($path))
			//{
			
			if ($xml = simplexml_load_file($path))
			{
				if ($params = @$xml->params)
				{
					foreach ($params as $param)
					{
						$this->setXML($param);
						$result = true;
					}
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
	 * Return the number of parameters in a group.
	 *
	 * @param   string  $group  An optional group. The default group is used if not supplied.
	 *
	 * @return  mixed  False if no params exist or integer number of parameters that exist.
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function getNumParams($group = 'xmlfile')
	{
		// Deprecation warning.
		JLog::add('JParameter::getNumParams is deprecated.', JLog::WARNING, 'deprecated');

		if (!isset($this->_xml[$group]) || !count($this->_xml[$group]->children()))
		{
			return false;
		}
		else
		{
			return count($this->_xml[$group]->children());
		}
	}

	/**
	 * Render all parameters.
	 *
	 * @param   string  $name   An optional name of the HTML form control. The default is 'params' if not supplied.
	 * @param   string  $group  An optional group to render.  The default group is used if not supplied.
	 *
	 * @return  array  An array of all parameters, each as array of the label, the form element and the tooltip.
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function getParams($name = 'params', $group = 'xmlfile')
	{

		// Deprecation warning.
		JLog::add('JParameter::getParams is deprecated.', JLog::WARNING, 'deprecated');

		if (!isset($this->_xml[$group]))
		{

			return false;
		}

		$results = array();
		foreach ($this->_xml[$group]->children() as $param)
		{
			$results[] = $this->getParam($param, $name, $group);
		}
		return $results;
	}

	/**
	 * Render a parameter type.
	 *
	 * @param   object  &$node         A parameter XML element.
	 * @param   string  $control_name  An optional name of the HTML form control. The default is 'params' if not supplied.
	 * @param   string  $group         An optional group to render.  The default group is used if not supplied.
	 *
	 * @return  array  Any array of the label, the form element and the tooltip.
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function getParam(&$node, $control_name = 'params', $group = 'xmlfile')
	{
		// Deprecation warning.
		JLog::add('JParameter::__construct is deprecated.', JLog::WARNING, 'deprecated');

		// Get the type of the parameter.
		$type = (string)$node->attributes()->type;

		$element = $this->loadElement($type);

		// Check for an error.
		if ($element === false)
		{
			$result = array();
			$result[0] = (string) $node->attributes()->name;
			$result[1] = JText::_('Element not defined for type') . ' = ' . $type;
			$result[5] = $result[0];
			return $result;
		}

		// Get value.
		$value = $this->get((string)$node->attributes()->name, (string)$node->attributes()->default, $group);

		return $element->render($node, $value, $control_name);
	}

	/**
	 * Loads an element type.
	 *
	 * @param   string   $type  The element type.
	 * @param   boolean  $new   False (default) to reuse parameter elements; true to load the parameter element type again.
	 *
	 * @return  object
	 *
	 * @deprecated  12.1
	 * @since   11.1
	 */
	public function loadElement($type, $new = false)
	{
		$signature = md5($type);

		if ((isset($this->_elements[$signature]) && !($this->_elements[$signature] instanceof __PHP_Incomplete_Class)) && $new === false)
		{
			return $this->_elements[$signature];
		}

		$elementClass = 'JElement' . $type;
		if (!class_exists($elementClass))
		{
			if (isset($this->_elementPath))
			{
				$dirs = $this->_elementPath;
			}
			else
			{
				$dirs = array();
			}

			$file = JFilterInput::getInstance()->clean(str_replace('_', "/", $type) . '.php', 'path');

			jimport('joomla.filesystem.path');
			if ($elementFile = JPath::find($dirs, $file))
			{
				include_once $elementFile;
			}
			else
			{
				$false = false;
				return $false;
			}
		}

		if (!class_exists($elementClass))
		{
			$false = false;
			return $false;
		}

		$this->_elements[$signature] = new $elementClass($this);

		return $this->_elements[$signature];
	}

	
}