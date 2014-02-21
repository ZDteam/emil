<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.model');

/**
 * People Component People Model
 *
 */
class PeopleModelPeople extends JModelLegacy
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

		$mainframe=JFactory::getApplication();$option=JRequest::getCmd("option");

		// Get the pagination request variables
		$limit		= JFactory::getApplication()->getUserStateFromRequest( 'global.list.limit', 'limit', JFactory::getApplication()->getCfg('list_limit'), 'int' );
		$limitstart	= JFactory::getApplication()->getUserStateFromRequest( $option.'.limitstart', 'limitstart', 0, 'int' );

		// In case limit has been changed, adjust limitstart accordingly
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
	}

	/**
	 * Method to get person item data
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
	 * Get list of items for public list in frontend 
	 *
	 * @return unknown
	 */
	function getPublicData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_publicdata))
		{
			$query = $this->_buildPublicQuery();
			$this->_publicdata = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
			$db =& JFactory::getDBO();
			echo $db->getErrorMsg();
		}

		return $this->_publicdata;
	}
	
	/**
	 * Method to get the total number of person items
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
	 * Method to get the total number of location items
	 *
	 * @access public
	 * @return integer
	 */
	function getPublicTotal()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_total))
		{
			$query = $this->_buildPublicQuery();
			$this->_total = $this->_getListCount($query);
		}
		// should we reset the list to the start?
		if ($this->getState("limitstart")>0  && $this->_total  < $this->getState("limitstart")){
			 $this->setState("limitstart",0);
			JRequest::setVar("limitstart",0);
		}

		return $this->_total;
	}
	
	/**
	 * Method to get a pagination object for the people
	 *
	 * @access public
	 * @return integer
	 */
	function getPagination()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			$mainframe=JFactory::getApplication();
			if (JFactory::getApplication()->isAdmin()){
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

	/**
	 * Method to get a pagination object for the locations
	 *
	 * @access public
	 * @return integer
	 */
	function getPublicPagination()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			$mainframe=JFactory::getApplication();
			if (JFactory::getApplication()->isAdmin()){
				jimport('joomla.html.pagination');
				$this->_pagination = new JPagination( $this->getPublicTotal(), $this->getState('limitstart'), $this->getState('limit') );
			}
			else {
				include_once(JPATH_COMPONENT_ADMINISTRATOR."/libraries/JevPagination.php");
				$this->_pagination = new JevPagination( $this->getPublicTotal(), $this->getState('limitstart'), $this->getState('limit'),true);
			}
		}

		return $this->_pagination;
	}
	
	function _buildQuery()
	{
		// Get the WHERE and ORDER BY clauses for the query
		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = ' SELECT pers.*, pt.title as typename, cat0.title as catname0,cat1.title as catname1,cat2.title as catname2,cat3.title as catname3,cat4.title as catname4'
		. ' FROM #__jev_people AS pers '
		. ' LEFT JOIN #__jev_peopletypes AS pt ON pt.type_id = pers.type_id'
		. ' LEFT JOIN #__categories AS cat0 ON cat0.id = pers.catid0'
		. ' LEFT JOIN #__categories AS cat1 ON cat1.id = pers.catid1'
		. ' LEFT JOIN #__categories AS cat2 ON cat2.id = pers.catid2'
		. ' LEFT JOIN #__categories AS cat3 ON cat3.id = pers.catid3'
		. ' LEFT JOIN #__categories AS cat4 ON cat4.id = pers.catid4'
		. $where
		. $orderby
		;
		return $query;
	}

	function _buildPublicQuery()
	{
		// Get the WHERE and ORDER BY clauses for the query
		$where		= $this->_buildPublicContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = ' SELECT pers.*, pt.title as typename, cat0.title as catname0,cat1.title as catname1,cat2.title as catname2,cat3.title as catname3,cat4.title as catname4'
		. ' FROM #__jev_people AS pers '
		. ' LEFT JOIN #__jev_peopletypes AS pt ON pt.type_id = pers.type_id'
		. ' LEFT JOIN #__categories AS cat0 ON cat0.id = pers.catid0'
		. ' LEFT JOIN #__categories AS cat1 ON cat1.id = pers.catid1'
		. ' LEFT JOIN #__categories AS cat2 ON cat2.id = pers.catid2'
		. ' LEFT JOIN #__categories AS cat3 ON cat3.id = pers.catid3'
		. ' LEFT JOIN #__categories AS cat4 ON cat4.id = pers.catid4'
		. $where
		. ' GROUP BY pers.pers_id'
		. $orderby
		;
		return $query;
	}

	
	function _buildContentOrderBy()
	{
		$mainframe=JFactory::getApplication();$option=JRequest::getCmd("option");

		$compparams = JComponentHelper::getParams("com_jevpeople");
		$order = $compparams->get("ordering", "alpha");
		$order = ($order=="alpha")?"pers.title":"pers.ordering";

		$filter_order		= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_order',		'filter_order',		$order,	'cmd' );
		$filter_order_Dir	= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_order_Dir',	'filter_order_Dir',	'',				'word' );

		if ($filter_order == 'pers.title'){
			$orderby 	= ' ORDER BY pers.type_id,  pers.title '.$filter_order_Dir;
		} else 	if ($filter_order == 'pers.ordering'){
			$orderby 	= ' ORDER BY pers.type_id,  pers.ordering '.$filter_order_Dir;
		} else {
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir.' ,  pers.type_id,  pers.title ';
		}

		return $orderby;
	}

	function _buildContentWhere()
	{
		$mainframe=JFactory::getApplication();$option=JRequest::getCmd("option");
		$db					=& JFactory::getDBO();
		$filter_type		= JFactory::getApplication()->getUserStateFromRequest( $option.'type_id',				'type_id',			'',				'int' );
		$filter_state		= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_state',		'filter_state',		'',				'word' );
		$filter_catid		= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_catid',		'filter_catid',		0,				'int' );
		$search				= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );

		$where = array();

		$compparams = JComponentHelper::getParams("com_jevpeople");
		if ($filter_catid > 0) {
			$where[] = '( pers.catid0 = '.(int) $filter_catid
			. ' OR pers.catid1 = '.(int) $filter_catid
			. ' OR pers.catid2 = '.(int) $filter_catid
			. ' OR pers.catid3 = '.(int) $filter_catid
			. ' OR pers.catid4 = '.(int) $filter_catid .")";
		}
		if ((int) $filter_type > 0) {
			$where[] = ' pers.type_id = '.(int) $filter_type;
		}
		if ($search || JRequest::getVar("jform", false)) {
			// NFODC change
			/* OLD VERSION
			$where[] = ' (LOWER(pers.title) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.city) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.state) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.country) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			. ')';
			 * END OLD VERSION
			 */
			
			/*
			 * Special code to allow filtering based on custom field values - disabled for now.  will need to test and reimplement when someone wants it
			 */
			$ids = array(-1);
			
			$compparams = JComponentHelper::getParams("com_jevpeople");
			$template = $compparams->get("template","");
			$customfields = array();
			$ids = array(-1);
			if ($template!=""){
				$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
				if (file_exists($xmlfile)){
					
					$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					
					$db = JFactory::getDBO();
					
					$allfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$allfields = array_merge($allfields, $jcfparams->renderToBasicArray('params', $group));
						}
					}
					
					foreach ($allfields as $node){
						$type = $node["type"];
						if ($type=="jevrmultilist"){
							die ("Must fix multilist in managed people");
							foreach ($node->children() as $opt)
							{
								if (strpos(strtolower($opt->data()),strtolower($search))!==false){
									$val	= intval($opt->attributes('value'));
									$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$node->attributes('name')."' AND (value='$val' OR value LIKE ('$val,%') or value LIKE('%,$val') or value LIKE('%,$val,%')) ");
									$newids = $db->loadColumn();
									if (count($newids)){
										$ids = array_merge($ids, $newids);
									}
								}
							}
						}
					}
				}
			}
			 
			$where[] = ' (LOWER(pers.title) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.city) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.state) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.country) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.pers_id) IN ('.implode(",",$ids).')'
			. ')';

			// end NFODC change
		}

		if ( $filter_state ) {
			if ( $filter_state == 'P' ) {
				$where[] = 'pers.published = 1';
			} else if ($filter_state == 'U' ) {
				$where[] = 'pers.published = 0';
			}
		}

		$canShowGlobal = JRequest::getVar("showglobal",true);
		$canShowAll = JRequest::getVar("showall",false);
		$user =& JFactory::getUser();
		if (!$canShowAll){
			$where[] = ' (pers.global = 1 OR pers.created_by='.$user->id.')';
		}
		if (!$canShowGlobal){
			$where[] = ' pers.created_by='.$user->id;
		}
		else if ($this->getState("select")){
			$loctype = $this->getState("loctype");
			switch ($loctype){
				case 0:
					$where[] = ' (pers.global = 1 OR pers.created_by='.$user->id.')';
					break;
				case 1;
				$where[] = ' pers.created_by='.$user->id;
				break;
				case 2;
				$where[] = ' pers.global = 1';
				break;
			}
		}

		$where 		= ( count( $where ) ? ' WHERE '. implode( ' AND ', $where ) : '' );

		return $where;
	}
	
	function _buildPublicContentWhere()
	{
		$mainframe=JFactory::getApplication();$option=JRequest::getCmd("option");
		$db					=& JFactory::getDBO();
		$filter_type		= JFactory::getApplication()->getUserStateFromRequest( $option.'type_id',				'type_id',			'',				'int' );
		$filter_state		= 'P';//JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_state',		'filter_state',		'P',				'word' );
		$filter_catid		= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_filter_catid',		'filter_catid',		0,				'int' );
		$search				= JFactory::getApplication()->getUserStateFromRequest( $option.'pers_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );

		$where = array();

		$compparams = JComponentHelper::getParams("com_jevpeople");

		$cats = $compparams->get("jevpcat","");
		if ($cats=="" && $filter_catid > 0) {
			$where[] = '( pers.catid0 = '.(int) $filter_catid
			. ' OR pers.catid1 = '.(int) $filter_catid
			. ' OR pers.catid2 = '.(int) $filter_catid
			. ' OR pers.catid3 = '.(int) $filter_catid
			. ' OR pers.catid4 = '.(int) $filter_catid .")";
		}
		else if ($cats!="" ) {
			if (!is_array($cats)){
				$cats = array($cats);
			}
			// make sure we don't have an empty array
			$cats[] = -1;
			$cats0 = implode(",",$cats);
			$cats = array_diff($cats,array(0));
			$cats = implode(",",$cats);
			// Note we must search for non set first cats only otherwise we'll get them all
			$where[] = '( pers.catid0 IN('.$cats0.')'
			. ' OR pers.catid1 IN('.$cats.')'
			. ' OR pers.catid2 IN('.$cats.')'
			. ' OR pers.catid3 IN('.$cats.')'
			. ' OR pers.catid4 IN('.$cats.'))';
		}

		$types= $compparams->get("type","");
		if ($types==0 && $filter_type > 0) {
			$where[] = ' pers.type_id = '.(int) $filter_type;
		}
		else if ($types!="") {
			if (!is_array($types)){
				$types = array($types);
			}
			// make sure we don't have an empty array
			$types[] = -1;
			$types = implode(",",$types);
			$where[] = ' pers.type_id IN ( '.$types.')';
		}
		
		if (JRequest::getVar("jform", false)){
			
			$ids = array(-1);
			
			$compparams = JComponentHelper::getParams("com_jevpeople");
			$template = $compparams->get("template","");
			$customfields = array();
			$ids = array();
			if ($template!=""){
				$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
				if (file_exists($xmlfile)){
					
					$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind(JRequest::getVar("jform"));
					
					$db = JFactory::getDBO();
					
					$allfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$fields = $jcfparams->getFieldset($group);
							foreach ($fields as $p => $field){
								if ($field->attribute("filter")){
									$fieldname = $field->attribute('name');
									$fieldtype = $field->attribute('type');
									
									if ($fieldtype=="jevcfmultilist" || $fieldtype=="jevcflist") {

										if ($field->value != $field->attribute('default')) {
											$val = intval($field->value);
											$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$fieldname."' AND value=$val ");
											$newids = $db->loadColumn();

											if (count($newids)){
												$ids = array_merge($ids, $newids);
											}
										}
									}
									else if ($field->value != $field->attribute('default')) {
										$val1 =$db->quote($field->value);
										$val2 =$db->quote($field->value."%");
										$val3 =$db->quote("%".$field->value);
										$val4 =$db->quote("%".$field->value."%");
										$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$fieldname."' AND (value=$val1 OR value LIKE ($val3) or value LIKE($val3) or value LIKE($val4)) ");
										$newids = $db->loadColumn();

										if (count($newids)){
											$ids = array_merge($ids, $newids);
										}
									}									
									
								}
							}
						}
					}
					

				}
			}
			if (count($ids)){
				$where[] = ' LOWER(pers.pers_id) IN ('.implode(",",$ids).')';				 
			}			
		}
		
		if ($search ) {
			/* OLD VERSION
			$where[] = ' (LOWER(pers.title) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.city) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.state) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.country) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			. ')';
			 * END OLD VERSION
			 */
			
			/*
			 * Special code to allow filtering based on custom field values 
			 */
			$ids = array(-1);
			
			$compparams = JComponentHelper::getParams("com_jevpeople");
			$template = $compparams->get("template","");
			$customfields = array();
			$ids = array(-1);
			if ($template!=""){
				$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
				if (file_exists($xmlfile)){
					
					$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind(JRequest::getVar("jform"));
					
					$db = JFactory::getDBO();
					
					$allfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$fields = $jcfparams->getFieldset($group);
							foreach ($fields as $p => $field){
								if ($field->attribute("filter")){
									$fieldname = $field->attribute('name');
									$fieldtype = $field->attribute('type');
									
									if ($fieldtype=="jevcfmultilist" || $fieldtype=="jevcflist") {

										if ($field->value != $field->attribute('default')) {
											$val = intval($field->value);
											$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$fieldname."' AND value=$val ");
											$newids = $db->loadColumn();

											if (count($newids)){
												$ids = array_merge($ids, $newids);
											}
										}
										else if ($search) {
											foreach ($field->getOptions() as $opt)
											{
												if (strpos(strtolower($opt->text),strtolower($search))!==false){
													$val	= intval($opt->value);
													$val1 =$db->quote($val);
													$val2 =$db->quote($val."%");
													$val3 =$db->quote("%".$val);
													$val4 =$db->quote("%".$val."%");
													$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$fieldname."' AND (value=$val1 OR value LIKE ($val3) or value LIKE($val3) or value LIKE($val4)) ");
													$newids = $db->loadColumn();

													if (count($newids)){
														$ids = array_merge($ids, $newids);
													}
												}
											}
										}
									}
									else if ($search) {
										$val1 =$db->quote($search);
										$val2 =$db->quote($search."%");
										$val3 =$db->quote("%".$search);
										$val4 =$db->quote("%".$search."%");
										$db->setQuery("SELECT target_id FROM #__jev_customfields2 where targettype='com_jevpeople' AND name='".$fieldname."' AND (value=$val1 OR value LIKE ($val3) or value LIKE($val3) or value LIKE($val4)) ");
										$newids = $db->loadColumn();

										if (count($newids)){
											$ids = array_merge($ids, $newids);
										}
									}									
									
								}
							}
						}
					}
					

				}
			}
			$where[] = ' (LOWER(pers.title) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.city) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.state) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.country) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.postcode) LIKE '.$db->Quote( '%'.$db->escape( $search, true ).'%', false )
			.' OR LOWER(pers.pers_id) IN ('.implode(",",$ids).')'
			. ')';

		}
		if (Jrequest::getString("pcode_filter","")!=""){
			$where[] = ' LOWER(pers.postcode ) = '.$db->Quote($db->escape( Jrequest::getString("pcode_filter",""), true ), false );			
		}

		if ( $filter_state ) {
			if ( $filter_state == 'P' ) {
				$where[] = 'pers.published = 1';
			} else if ($filter_state == 'U' ) {
				$where[] = 'pers.published = 0';
			}
		}


		$canShowGlobal = JRequest::getVar("showglobal",true);
		$canShowAll = JRequest::getVar("showall",false);
		$user =& JFactory::getUser();
		if (!$canShowAll){
			$where[] = ' (pers.global = 1 OR pers.created_by='.$user->id.')';
		}
		if (!$canShowGlobal){
			$where[] = ' pers.created_by='.$user->id;
		}
		else if ($this->getState("select")){
			$loctype = $this->getState("loctype");
			switch ($loctype){
				case 0:
					$where[] = ' (pers.global = 1 OR pers.created_by='.$user->id.')';
					break;
				case 1;
				$where[] = ' pers.created_by='.$user->id;
				break;
				case 2;
				$where[] = ' pers.global = 1';
				break;
			}
		}

		$where 		= ( count( $where ) ? ' WHERE '. implode( ' AND ', $where ) : '' );

		return $where;
	}
	
	// VERY CRUDE TEST
	function hasEvents($pers_id, $startdate, $enddate) {
		$db	=& JFactory::getDBO();
		$query = "SELECT count(ev.ev_id) "
		. "\n FROM #__jevents_repetition as rpt"
		. "\n LEFT JOIN #__jevents_vevent as ev ON rpt.eventid = ev.ev_id"
		. "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
		. "\n LEFT JOIN #__jev_peopleeventsmap AS map ON map.evdet_id=det.evdet_id"
		. "\n WHERE ev.state=1"
		. "\n AND rpt.endrepeat >= '".$startdate."' AND rpt.startrepeat <= '".$enddate."'"
		. "\n AND map.pers_id=$pers_id LIMIT 1";
		$db->setQuery($query);
		return $db->loadResult();
	}
	

	function saveorder($cid = array(), $order)
	{
		$row =& $this->getTable('person');
		$groupings = array();

		// update ordering values
		for( $i=0; $i < count($cid); $i++ )
		{
			$row->load( (int) $cid[$i] );
			// track types
			$groupings[] = $row->type_id;

			if ($row->ordering != $order[$i])
			{
				$row->ordering = $order[$i];
				if (!$row->store()) {
					$this->setError($this->_db->getErrorMsg());
					return false;
				}
			}
		}

		// execute updateOrder for each parent group
		$groupings = array_unique( $groupings );
		foreach ($groupings as $group){
			$row->reorder('type_id = '.(int) $group);
		}

		return true;
	}

}
