<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevptype.php 2035 2011-05-10 10:09:06Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldJevptype extends JFormFieldList
{

	protected $type = 'Jevptype';

	public function getOptions()
	{

		$query = 'SELECT tp.type_id AS value, tp.title AS text FROM #__jev_peopletypes AS tp order by title';
		$db =& JFactory::getDBO();
		$db->setQuery($query);
		$options = $db->loadObjectList();
		return $options;
		
		$file = JPATH_ADMINISTRATOR . '/components/com_jevpeople/elements/jevptype.php';
		if (file_exists($file) ) {
			include_once($file);
		} else {
			die ("JEvents People Fields\n<br />This module needs the JEvents People component");
		}		

	}

}