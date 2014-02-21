<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class plgjeventsjevanonuserInstallerScript
{
	//
	// Joomla installer functions
	//
	
	function install($parent)
	{
		
		$this->createTables();
		
		//Whoops! must disable auto enable for now. We need to update the database default params at the same time, or add more fallbacks in code.
		
		// New install, lets enable the plugin! 
		//$db = & JFactory::getDBO();
		//$db->setDebug(0);
		//$sql = "UPDATE #__extensions SET enabled=1 WHERE element='agendaminutes'";
		//$db->setQuery($sql);
		//$db->query();
		//echo $db->getErrorMsg();
		
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
	
	function createTables() {

		$db = & JFactory::getDBO();
		$charset = ($db->hasUTF()) ? 'DEFAULT CHARACTER SET `utf8`' : '';
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_anoncreator(
	id int(11) NOT NULL auto_increment,
	ev_id int(11) NOT NULL default 0,
	name varchar(255) NOT NULL default '',
	email varchar(255) NOT NULL default '',
	PRIMARY KEY  (id),
	INDEX (ev_id)
)  $charset;
SQL;
	$db->setQuery($sql);
		if (!$db->query()){
			echo $db->getErrorMsg();
		}
	
	}
	function postflight($type, $parent) 
    {
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)
		echo '<h2>'.JText::_('JEV_ANON_PLUGIN') . ' ' . $parent->get('manifest')->version.' </h2>';
		echo '<strong>';

		if ($type == "update") {
			echo JText::_('JEV_ANON_INSTALL_SUCCESS_1') . '<br/>';
			echo JText::_('JEV_ANON_PLUGIN_DESC');
		} else {
			echo JText::_('JEV_ANON_INSTALL_SUCCESS_2') . '<br/>';
			echo JText::_('JEV_ANON_PLUGIN_DESC');
		}
		echo '</strong><br/><br/>';
	}
}
