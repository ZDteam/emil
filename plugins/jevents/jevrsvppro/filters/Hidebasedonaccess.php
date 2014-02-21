<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: Search.php 1410 2009-04-09 08:13:54Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('No Direct Access');

// Strips out public events
class jevHidebasedonaccessFilter extends jevFilter
{

	function __construct($tablename, $filterfield, $isstring=true)
	{
		$this->filterType = "jevidebaseonaccess";
		$this->filterNullValue = "";
		parent::__construct($tablename, $filterfield, true);

	}

	function _createFilter($prefix="")
	{
		if (!$this->filterField)
			return "";

		$user = JFactory::getUser();
		$filter = "(atd.sessionaccess=-1 OR atd.sessionaccess " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user)).")";
		return $filter;

	}

	// always used in conjunction with hide for non invirees filter so no need for join
	function _createJoinFilter($prefix="")
	{
		
	}

}