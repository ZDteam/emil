<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

include_once(RSVP_ADMINPATH."/controllers/sessions.php");

class FrontSessionsController extends AdminSessionsController   {

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Load abstract "view" class
		$cfg = & JEVConfig::getInstance();
		$theme = JEV_CommonFunctions::getJEventsViewName();
		JLoader::register('JEvents'.ucfirst($theme).'View',JEV_VIEWS."/$theme/abstract/abstract.php");
		
		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
	}

	// frontend version is not interested invitations initially
	function overview( )
	{

		$user = JFactory::getUser();

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		
		// get the view
		$this->view = & $this->getView("sessions","html");

		$mainframe = JFactory::getApplication();
		$Itemid=JRequest::getInt("Itemid");
		$option = JRequest::getCmd("option");
		$db	=& JFactory::getDBO();

		$search		= $mainframe->getUserStateFromRequest( "search{".RSVP_COM_COMPONENT."}", 'search', '' );
		$search		= $db->getEscaped( trim( strtolower( $search ) ) );
		$limit		= intval( $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', 10 ));
		$limitstart = JRequest::getInt('limitstart', 0 );
		$created_by	=  intval($mainframe->getUserStateFromRequest( "createdbyrsvp", 'created_by', 0 ));
		$repeating = intval($mainframe->getUserStateFromRequest( "repeatingrsvp", 'repeating', 1 ));
		$hidepast = intval($mainframe->getUserStateFromRequest( "hidepastrsvp", 'hidepast', 1 ));

		$where = array();
		$join = array();

		if( $search ){
			$where[] = "LOWER(det.summary) LIKE '%$search%'";
		}

		
		if ($created_by!==0 ) {
			$where[] = "ev.created_by=".$db->Quote($created_by);
		}
		else if ( $params->get("personallist")==4) {
			$where[] = "ev.created_by=".$db->Quote($user->id);			
		}

		$where[] = "ev.ev_id IS NOT NULL";
		
		if ($params->get("personallist") && $user->id>0){
			// yes, maybe, pending, unpaid or overpaid
			$approved = "attendstate IN (1,2,3,4,5) AND ";
			if ($params->get("personallist") ==2 || $params->get("personallist") ==3){
				// yes or pending payment
				$approved = "attendstate IN (1,4) AND ";
			}
			// attended by someone / anyone at all
			if ($params->get("personallist")==3){
				if ($repeating){
					$where[] = "atd.id IN (SELECT distinct at_id FROM #__jev_attendees AS atdees WHERE $approved 1 )";
				}
				else {
					// for individual repeats
					$where[] = "rpt.rp_id IN (SELECT distinct rp_id FROM #__jev_attendees AS atdees WHERE $approved rp_id>0)";
				}				
			}
			else if ($params->get("personallist")<3) {
				if ($repeating){
					$where[] = "atd.id IN (SELECT at_id FROM #__jev_attendees AS atdees WHERE $approved user_id=".$user->id.")";
				}
				else {
					// for individual repeats
					$where[] = "rpt.rp_id IN (SELECT rp_id FROM #__jev_attendees AS atdees WHERE $approved user_id=".$user->id." AND rp_id>0)";
				}
			}
		}
		if ($params->get("personallist") && $user->id==0){
			$where[] = '0';
		}
		
		$user = JFactory::getUser();
		// if user is not logged in then restrict to sessions they can see
		if ($user->get('id')==0 && !$params->get("attendemails")){
			$where[] = " 0";
		}

		// by invitation only
		if ($user->get('id')==0 ){
			$where[] = " atd.allowregistration=1";
		}
		// admin users can see all sessions!
		else if (!JEVHelper::isAdminUser ()) {
			$where[] = " ((atd.allowregistration=1) OR (atd.allowregistration=2 AND (inv2.id IS NOT NULL OR ev.created_by=".$user->get('id').")))";
			$join[] = "#__jev_invitees as inv2 ON inv2.at_id=atd.id AND inv2.user_id=".$user->get('id');
		}
		
		if (!JEVHelper::isAdminUser($user)){
			// we will strip these out at display time
			//$where[] = "(atd.showattendees=1 OR ev.created_by=".intval($user->id).")";
		}

		// get the total number of records in two blocks
		// first the whole event sessions
		if ($repeating){
			if ($hidepast){
				if (class_exists("JevDate")) {
					$datenow = JevDate::getDate("-1 day");
				}
				else {
					$datenow = JFactory::getDate("-1 day");
				}
				$where[] = "\n det.dtstart>'".$datenow->toUnix()."'";
			}

			$where[] = "atd.allrepeats=1 AND atd.allowregistration>0 ";
			$where[] = "ev.ev_id is not null ";
			// fix the access
			$where[] = "ev.access " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user));
			$where[] = "(atd.sessionaccess=-1 OR atd.sessionaccess " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user)).")";
					
			$query = "SELECT count(distinct ev.ev_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
			$db->setQuery( $query);
			//echo (string) $db->getQuery();
			$total = $db->loadResult();

			echo $db->getErrorMsg();
			if( $limit > $total ) {
				$limitstart = 0;
			}

			$filter_order		= $mainframe->getUserStateFromRequest( $option.'loc_filter_order',		'filter_order',		'det.dtstart',	'cmd' );
			if ($filter_order=='rpt.startrepeat') $filter_order='det.dtstart';
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'loc_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

			$query = "SELECT ev.ev_id as evd, ev.state as evstate, det.*, atd.* ,atd.id as atd_id, atdc.atdcount, count(distinct inv.id) as invcount, sum(atdees.waiting) as waitingcount, det.dtstart as startdate"
			. ", 0 as rp_id "
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=0"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=0"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=0"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
			. "\n GROUP BY ev.ev_id $orderby"
			;

			if ($limit>0){
				$query .= "\n LIMIT $limitstart, $limit";
			}
			$db->setQuery( $query );
			//echo (string) $db->getQuery();
			$rows = $db->loadObjectList();

			echo $db->getErrorMsg();

			foreach ($rows as $key=>$val) {
				// set state variable to the event value not the event detail value
				$rows[$key]->state = $rows[$key]->evstate;

				// Find the first repeat
				$vevent = $this->dataModel->queryModel->getEventById(  $val->ev_id, false, "icaldb");
				// trap orphans!
				if (is_null($vevent)) {
					unset($rows[$key]);
					continue;
				}
				$repeat = $vevent->getFirstRepeat();

				$rows[$key]->repeat = $repeat;
				$rows[$key]->starttime = $repeat->getUnixStartTime();
			}

		}
		else {
			if ($hidepast){
				if (class_exists("JevDate")) {
					$datenow = JevDate::getDate("-1 day");
				}
				else {
					$datenow = JFactory::getDate("-1 day");
				}
				$where[] = "\n rpt.endrepeat>'".$datenow->toMysql()."'";
			}


			$where[] = "atd.allrepeats=0 AND atd.allowregistration>0";
			// fix the access
			$where[] = "ev.access " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user));
			$where[] = "(atd.sessionaccess=-1 OR atd.sessionaccess " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user)).")";

			$query = "SELECT count(distinct rpt.rp_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
			$db->setQuery( $query);
			//echo $db->_sql;
			$total = $db->loadResult();

			echo $db->getErrorMsg();
			if( $limit > $total ) {
				$limitstart = 0;
			}

			$filter_order		= $mainframe->getUserStateFromRequest( $option.'loc_filter_order',		'filter_order',		'rpt.startrepeat',	'cmd' );
			if ($filter_order=='det.dtstart') $filter_order='rpt.startrepeat';
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'loc_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

			$query = "SELECT rpt.rp_id,  ev.state as evstate, det.*, atd.* ,atd.id as atd_id, atdc.atdcount, count(distinct inv.id) as invcount, sum(atdees.waiting) as waitingcount,  rpt.startrepeat as startdate"
			. ", rpt.rp_id "
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=rpt.rp_id"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
			. "\n GROUP BY rpt.rp_id $orderby"
			;

			if ($limit>0){
				$query .= "\n LIMIT $limitstart, $limit";
			}
			$db->setQuery( $query );

			$rows = $db->loadObjectList();

			echo $db->getErrorMsg();

			foreach ($rows as $key=>$val) {
				// set state variable to the event value not the event detail value
				$rows[$key]->state = $rows[$key]->evstate;

				// Find the first repeat
				$repeat = $this->dataModel->queryModel->listEventsById(  $val->rp_id, false, "icaldb");
				// trap orphans!
				if (is_null($repeat)) {
					unset($rows[$key]);
					continue;
				}

				$rows[$key]->repeat = $repeat;
				$rows[$key]->starttime = $repeat->getUnixStartTime();
			}

		}

		// reset for any missing orphan rows
		$rows = array_values($rows);

		// get list of creators
		$sql = "SELECT distinct u.id, u.* FROM #__jevents_vevent as jev LEFT JOIN #__users as u on u.id=jev.created_by";
		$db=& JFactory::getDBO();
		$db->setQuery( $sql );
		$users = $db->loadObjectList();
		$userOptions = array();
		$userOptions[] = JHtml::_('select.option', 0, JText::_("JEV_EVENT_CREATOR") );
		foreach( $users as $user )
		{
			$userOptions[] = JHtml::_('select.option', $user->id, $user->name. " ($user->username)" );
		}
		$userlist = JHtml::_('select.genericlist', $userOptions, 'created_by', 'class="inputbox" size="1"  onchange="document.adminForm.submit();"', 'value', 'text', $created_by);

		$options = array();
		$options[] = JHtml::_('select.option', '0', JText::_('RSVP_SEPARATE_REPEATS'));
		$options[] = JHtml::_('select.option', '1', JText::_('RSVP_SINGLE_EVENTS'));
		$repeattypelist = JHtml::_('select.genericlist', $options, 'repeating', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $repeating );

		$options = array();
		$options[] = JHtml::_('select.option', '0', JText::_('No'));
		$options[] = JHtml::_('select.option', '1', JText::_('Yes'));
		$hidepast = JHtml::_('select.genericlist', $options, 'hidepast', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $hidepast );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );

		// Set the layout
		$this->view->setLayout('overview');

		$this->view->assign('rows',$rows);
		$this->view->assign('order',$filter_order);
		$this->view->assign('orderdir',$filter_order_Dir);
		$this->view->assign('userlist',$userlist);
		$this->view->assign('hidepast',$hidepast);
		$this->view->assign('repeating',$repeating);
		$this->view->assign('repeattypelist',$repeattypelist);
		$this->view->assign('search',$search);
		$this->view->assign('pageNav',$pageNav);

		$this->view->display();
	}
	
	function delete(){
		echo "Not implemented yet";return;
	}

}