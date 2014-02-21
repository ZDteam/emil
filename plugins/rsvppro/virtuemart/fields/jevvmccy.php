<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldJevVmccy extends JFormFieldList
{

	protected $type = 'jevvmccy';

	protected function getOptions()
	{
		$db = JFactory::getDbo();
		$sql = "SELECT currency_code_3 AS value, currency_name AS text FROM #__virtuemart_currencies ORDER BY currency_name asc";
		$db->setQuery($sql);
		$currencies = $db->loadObjectList();
		return $currencies;

	}
	

}