<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted Access' );

jimport('joomla.application.component.model');

class TransactionsModelTransactions extends JModelLegacy
{

	/**
	 * Category ata array
	 *
	 * @var array
	 */
	var $_data = null;
	var $_feedata = null;

	private $fees = null;
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

		$query = ' SELECT trans.* FROM #__jev_rsvp_transactions AS trans '
		. $where
		. $orderby
		;
		return $query;
	}

	function _buildAttendeeQuery($attendee, $checkstate=true)
	{
		// Get the WHERE and ORDER BY clauses for the query
		$where		= $this->_buildAttendeeWhere($attendee, $checkstate);
		$orderby	= $this->_buildContentOrderBy( $checkstate);

		$query = ' SELECT trans.* FROM #__jev_rsvp_transactions AS trans '
		. $where
		. $orderby
		;
		return $query;
	}


	function _buildContentOrderBy( $checkstate=true)
	{
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");

		$filter_order		= $mainframe->getUserStateFromRequest( $option.'trans_filter_order',		'filter_order',		'trans.transaction_date',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'trans_filter_order_Dir',	'filter_order_Dir',	'DESC',			'word' );

		if (!$checkstate){
			$filter_order		= 	'trans.paymentstate DESC, trans.transaction_date DESC';
			$orderby 	= ' ORDER BY '.$filter_order;
		}
		else {
			$filter_order		= 	'trans.transaction_date';
			$filter_order_Dir	= 'DESC';
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;
		}


		return $orderby;
	}

	function _buildContentWhere()
	{
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");
		$db					=& JFactory::getDBO();
		$filter_state		= $mainframe->getUserStateFromRequest( $option.'trans_filter_state',		'filter_state',		'',				'word' );
		$search				= $mainframe->getUserStateFromRequest( $option.'trans_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );

		$where = array();

		if (trim($search)!="") {
			$where[] = 'LOWER(trans.title) LIKE '.$db->Quote( '%'.$db->getEscaped( $search, true ).'%', false );
		}
		if ( $filter_state ) {
			if ( $filter_state == 'P' ) {
				$where[] = 'trans.published = 1';
			} else if ($filter_state == 'U' ) {
				$where[] = 'trans.published = 0';
			}
		}

		$where 		= ( count( $where ) ? ' WHERE '. implode( ' AND ', $where ) : '' );

		return $where;
	}

	function _buildAttendeeWhere($attendee, $checkstate=true)
	{
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");
		$db					=& JFactory::getDBO();

		$where = array();
		if ($attendee && $attendee->id>0){
			$where[] = 'trans.attendee_id='.intval($attendee->id);
		}
		else {
			// no attendee so no results !
			$where[] = 0;
		}
		if ($checkstate){
			$where[] = 'trans.paymentstate=1';
		}

		$where 		= ( count( $where ) ? ' WHERE '. implode( ' AND ', $where ) : '' );

		return $where;
	}

	/**
	 * Method to get location item data
	 *
	 * @access public
	 * @return array
	 */
	function getFeesPaid(&$attendee, $checkstate=true)
	{
		// Lets load the content if it doesn't already exist
		if (empty($attendee->_feedata))
		{
			$query = $this->_buildAttendeeQuery($attendee, $checkstate);
			$attendee->_feedata = $this->_getList($query, 0, 0);
			$db =& JFactory::getDBO();
			echo $db->getErrorMsg();
		}
		if (!isset($attendee->fees)){
			if (!$attendee->_feedata || count($attendee->_feedata)==0){
				$attendee->fees= 0;
			}
			else {
				$attendee->fees= 0;
				foreach ($attendee->_feedata as $fee){
					$attendee->fees += $fee->amount;
				}
			}
		}

		return $attendee->fees;
	}

}
