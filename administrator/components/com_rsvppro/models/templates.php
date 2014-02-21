<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted Access' );

jimport('joomla.application.component.model');

class TemplatesModelTemplates extends JModelLegacy
{

	/**
	 * Category ata array
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Category total
	 *
	 * @var integer
	 */
	var $_total = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	var $_pagination = null;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();

		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");

		// Get the pagination request variables
		$limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart	= $mainframe->getUserStateFromRequest( $option.'.limitstart', 'limitstart', 0, 'int' );

		// In case limit has been changed, adjust limitstart accordingly
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
	}

	/**
	 * Method to get location item data
	 *
	 * @access public
	 * @return array
	 */
	function getData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{
			$query = $this->_buildQuery();
			$this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
			$db =& JFactory::getDBO();
			echo $db->getErrorMsg();
		}

		return $this->_data;
	}

	/**
	 * Method to get the total number of location items
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_total))
		{
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	/**
	 * Method to get a pagination object for the locations
	 *
	 * @access public
	 * @return integer
	 */
	function getPagination()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			$mainframe = JFactory::getApplication();
			if ($mainframe->isAdmin()){
				jimport('joomla.html.pagination');
				$this->_pagination = new JPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit') );
			}
			else {
				include_once(JPATH_COMPONENT_ADMINISTRATOR."/libraries/JevPagination.php");
				$this->_pagination = new JevPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit'),true);
			}
		}

		return $this->_pagination;
	}


	function _buildQuery()
	{
		// Get the WHERE and ORDER BY clauses for the query
		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = ' SELECT tmpl.* FROM #__jev_rsvp_templates AS tmpl '
		. $where
		. $orderby
		;
		return $query;
	}


	function _buildContentOrderBy()
	{
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");

		$filter_order		= $mainframe->getUserStateFromRequest( $option.'tmpl_filter_order',		'filter_order',		'tmpl.title',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'tmpl_filter_order_Dir',	'filter_order_Dir',	'ASC',			'word' );

		$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

		return $orderby;
	}

	function _buildContentWhere()
	{
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");
		$db					=& JFactory::getDBO();
		$filter_state		= $mainframe->getUserStateFromRequest( $option.'tmpl_filter_state',		'filter_state',		'',				'word' );
		$filter_order		= $mainframe->getUserStateFromRequest( $option.'tmpl_filter_order',		'filter_order',		'tmpl.title',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'tmpl_filter_order_Dir',	'filter_order_Dir',	'ASC',				'word' );
		$search				= $mainframe->getUserStateFromRequest( $option.'tmpl_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );
		$customised			= $mainframe->getUserStateFromRequest( $option.'rsvp_customised',			'customised',			0,				'int' );
		
		$where = array();

		if (trim($search)!="") {
			$where[] = 'LOWER(tmpl.title) LIKE '.$db->Quote( '%'.$db->getEscaped( $search, true ).'%', false );
		}
		if ( $filter_state ) {
			if ( $filter_state == 'P' ) {
				$where[] = 'tmpl.published = 1';
			} else if ($filter_state == 'U' ) {
				$where[] = 'tmpl.published = 0';
			}
		}
		$user= JFactory::getUser();
		if (!JevTemplateHelper::canCreateGlobal()){
			$where[] = 'tmpl.global = 0 ';
			$where[] = 'tmpl.created_by =  '.$user->id;
		}
		else {
			$where[] = ' (tmpl.global = 1  OR tmpl.created_by =  '.$user->id. ')';			
		}

		if ($customised){
			$where[] = 'tmpl.istemplate = 0';
		}
		else {
			$where[] = 'tmpl.istemplate = 1';
		}
		
		$where 		= ( count( $where ) ? ' WHERE '. implode( ' AND ', $where ) : '' );

		return $where;
	}

}
