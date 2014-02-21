<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class plgjeventsjevcalendarInstallerScript
{

	//
	// Joomla installer functions
	//
	
	function install($parent)
	{


		return true;

	}

	function uninstall($parent)
	{
		// No nothing for now, we want to keep the tables just incase they remove the plugin by accident. 

	}

	function update($parent)
	{
		// Nothing to do for now, tables should be created on install.

	}


	function postflight($type, $parent)
	{
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)
		echo '<h2>' . JText::_('PLG_INST_JEVENTS_JEVCALENDAR') . ' ' . $parent->get('manifest')->version . ' </h2>';
		echo '<strong>';

		if ($type == "update")
		{
			echo JText::_('PLG_INST_JEVENTS_JEVCALENDAR_SUCC1') . '<br/>';
			echo JText::_('JEV_SPECIFIED_CALENDARS_DESC');
		}
		else
		{
			echo JText::_('PLG_INST_JEVENTS_JEVCALENDAR_SUCC2') . '<br/>';
			echo JText::_('JEV_SPECIFIED_CALENDARS_DESC');
		}
		echo '</strong><br/><br/>';

	}

}
