<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

if (!JVersion::isCompatible("1.6.0"))
{

	class JElementJevsetupstatus extends JElement
	{

		var $_name = 'jevsetupstatus';

		function fetchElement($name, $value, &$node, $control_name)
		{
			return "";

		}

	}

}
else if (JVersion::isCompatible("1.6.0"))
{
	jimport('joomla.html.html');
	jimport('joomla.form.formfield');
	jimport('joomla.form.helper');

	class JFormFieldJevsetupstatus extends JFormField
	{

		var $_name = 'jevsetupstatus';

		public function getInput()
		{
			return "";

		}

	}

}