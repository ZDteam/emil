<?php
/**
* @package		EasyBlog
* @copyright	Copyright (C) 2010 Stack Ideas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasyBlog is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Restricted access');

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'table.php' );

class EasyBlogTableReport extends EasyBlogTable
{
	var $id			= null;
	var $obj_id		= null;
	var $obj_type 	= null;
	var $created_by	= null;
	var $created	= null;
	var $reason		= null;
	var $ip 		= null;

	private $author = null;

	/**
	 * Constructor for this class.
	 *
	 * @return
	 * @param object $db
	 */
	function __construct(& $db )
	{
		parent::__construct( '#__easyblog_reports' , 'id' , $db );
	}

	public function getAuthor()
	{
		if( !isset( $this->author ) || is_null( $this->author ) )
		{
			$profile 	= EasyBlogHelper::getTable('Profile' );
			$profile->load( $this->created_by );
			$this->author	= $profile;
		}
		return $this->author;
	}

	public function store()
	{
		$config 	= EasyBlogHelper::getConfig();
		$maxTimes 	= $config->get( 'main_reporting_maxip' );

		// @task: Run some checks on reported items and
		if( $maxTimes > 0 )
		{
			$db 	= EasyBlogHelper::db();
			$query 	= 'SELECT COUNT(1) FROM ' . EasyBlogHelper::getHelper( 'SQL' )->nameQuote( $this->_tbl ) . ' '
					. 'WHERE ' . EasyBlogHelper::getHelper( 'SQL' )->nameQuote( 'obj_id' ) . ' = ' . $db->Quote( $this->obj_id ) . ' '
					. 'AND ' . EasyBlogHelper::getHelper( 'SQL' )->nameQuote( 'obj_type' ) . ' = ' . $db->Quote( $this->obj_type ) . ' '
					. 'AND ' . EasyBlogHelper::getHelper( 'SQL' )->nameQuote( 'ip' ) . ' = ' . $db->Quote( $this->ip );

			$db->setQuery( $query );
			$total 	= (int) $db->loadResult();

			if( $total >= $maxTimes )
			{
				JFactory::getLanguage()->load( 'com_easyblog' , JPATH_ROOT );
				$this->setError( JText::_( 'COM_EASYBLOG_REPORT_ALREADY_REPORTED' ) );
				return false;
			}
		}

		return parent::store();
	}
}
