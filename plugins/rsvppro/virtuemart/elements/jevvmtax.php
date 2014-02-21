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

class JElementJevvmtax extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'jevvmtax';

	function fetchElement($name, $value, &$node, $control_name)
	{

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_virtuemart", JPATH_ADMINISTRATOR);

		$db =& JFactory::getDBO();

		// assemble menu items to the array
		$options 	= array();
		$options[]	= JHtml::_('select.option', '0', '- '.JText::_('JEV_SELECT_TAX_RATE').' -');
		
		// load the list of menu types
		// TODO: move query to model
		$query = 'SELECT * FROM #__vm_tax_rate ORDER BY tax_rate_id';
		$db->setQuery( $query );
		$rates = $db->loadObjectList();

		foreach ($rates as &$rate) {
			$options[] = JHtml::_('select.option',  $rate->tax_rate_id, $rate->tax_country." (".$rate->tax_state.") ".($rate->tax_rate*100)."%", 'value', 'text');
		}

		return JHtml::_('select.genericlist',  $options, ''.$control_name.'['.$name.']', 'class="inputbox"', 'value', 'text', $value, $control_name.$name);
	}
	
}