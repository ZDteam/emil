<?php

/**
 * JEvents Component for Joomla 2.5.x
 *
 * @version     $Id: scriptfile.php 1430 2010-11-11 14:15:05Z royceharding $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2012 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class pkg_rsvpproInstallerScript
{
 

	// TODO enable plugins
	public function update()
	{

		return true;
	}

	public function install($adapter)
	{
		return true;
	}


	public function uninstall($adapter)
	{
		
	}
	
	/*
	 * enable the plugins
	 */
	function postflight($type, $parent) 
	{
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)

		if ($type == 'install') {	
			// enable plugin
			$db = JFactory::getDbo();
			$query = "UPDATE #__extensions SET enabled=1 WHERE folder='jevents' and type='plugin' and element='jevrsvppro'";
			$db->setQuery($query);
			$db->query();

			$query = "UPDATE #__extensions SET enabled=1 WHERE folder='rsvppro' and type='plugin' and element='manual'";
			$db->setQuery($query);
			$db->query();

			// clean up any old install with the wrong extension name in the package!
			$query = "DELETE FROM #__extensions WHERE type='package' AND element='com_jevrsvppro' ";
			$db->setQuery($query);
			$db->query();
			
		}
	}	

}
