<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevcategory.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

if (!class_exists('VmConfig'))
    require(JPATH_ROOT . '/' . 'administrator' . '/' . 'components' . '/' . 'com_virtuemart' . '/' . 'helpers' . '/' . 'config.php');
if (!class_exists('ShopFunctions'))
    require(JPATH_VM_ADMINISTRATOR . '/' . 'helpers' . '/' . 'shopfunctions.php');
if (!class_exists('TableCategories'))
    require(JPATH_VM_ADMINISTRATOR . '/' . 'tables' . '/' . 'categories.php');

class JElementJevvmcats extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'jevvmcats';

	function fetchElement($name, $value, &$node, $control_name)
	{

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_virtuemart", JPATH_ADMINISTRATOR);

		//return JHtml::_('select.genericlist',  $options, ''.$control_name.'['.$name.']', 'class="inputbox"', 'value', 'text', $value, $control_name.$name);
		
		$multiple	= ( $node->attributes('multiple') ? 'multiple="multiple"' : "");
		$size	= ( $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : "");
		
		$id   = $control_name.$name;
		$name = $control_name.$name;
		
		JPlugin::loadLanguage('com_virtuemart', JPATH_ADMINISTRATOR);
		$categorylist = ShopFunctions::categoryListTree($value);
		$class = '';
		$html = '<select class="inputbox"   name="' . $name . '" '.$multiple.' '.$size.'>';
		$html .= '<option value="0">' . JText::_('COM_VIRTUEMART_CATEGORY_FORM_TOP_LEVEL') . '</option>';
		$html .= str_replace('\"selected\"' , '"selected"', $categorylist);
		$html .="</select>";
		return $html;
		
	}
	
}