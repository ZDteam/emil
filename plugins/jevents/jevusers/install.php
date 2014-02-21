<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class plgjeventsjevusersInstallerScript
{

	//
	// Joomla installer functions
	//
	
	function install($parent)
	{

		$this->createTables();

		return true;

	}

	function uninstall($parent)
	{
		// No nothing for now, we want to keep the tables just incase they remove the plugin by accident. 

	}

	function update($parent)
	{
		$this->createTables();

		$db = & JFactory::getDBO();

		$sql = "SHOW COLUMNS FROM `#__jev_usereventsmap`";
		$db->setQuery( $sql );
		$cols = $db->loadObjectList();
		$uptodate = false;
		foreach ($cols as $col) {
			if ($col->Field=="groupid"){
				$uptodate = true;
				break;
			}
		}
		if(!$uptodate){
			$sql = "Alter table #__jev_usereventsmap ADD COLUMN groupid int(12) NOT NULL default 0 ";
			$db->setQuery($sql);
			if (!$db->query()){
				echo $db->getErrorMsg();
			}

			$sql = "alter table #__jev_usereventsmap DROP PRIMARY KEY";
			$db->setQuery( $sql );
			if (!$db->query()){
				echo $db->getErrorMsg();
			}

			$sql = "alter table #__jev_usereventsmap ADD PRIMARY KEY (`user_id`,`evdet_id`, `groupid`)";
			$db->setQuery( $sql );
			if (!$db->query()){
				echo $db->getErrorMsg();
			}

		}
		
		return true;

	}

	function createTables()
	{

		$db = & JFactory::getDBO();
		$charset = ($db->hasUTF()) ? 'DEFAULT CHARACTER SET `utf8`' : '';
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_usereventsmap(
	user_id int(12) NOT NULL  default 0,
	evdet_id int(12) NOT NULL  default 0,
	privateevent tinyint(2) NOT NULL default 1,
	groupid int(12) NOT NULL default 0,

	PRIMARY KEY (user_id,evdet_id, groupid),
	INDEX  (evdet_id),
	INDEX  (user_id)
) $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

	}

}

