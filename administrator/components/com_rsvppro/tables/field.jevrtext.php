<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(dirname(__FILE__)."/field.php");

/**
* Template Field class
*
*/
class TableJevrtext extends TableField
{

	/**
	* Overloaded bind function
	*
	*/
	function bind($array, $ignore='', $fieldid)
	{
		$success = parent::bind($array, $ignore, $fieldid);
		return $success;
	}

}
