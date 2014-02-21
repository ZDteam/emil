<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: icalevent.php 1712 2010-03-04 07:33:11Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( '_JEXEC' ) or die( 'Restricted Access' );

jimport('joomla.application.component.controller');


class AdminSessionsController extends JControllerLegacy {

	/**
	 * Controler for Sessions 
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

	}

	/**
	 * List Sessions
	 *
	 */
	function overview( )
	{
		// get the view
		$this->view = & $this->getView("sessions","html");

		$mainframe = JFactory::getApplication();
		$option=JRequest::getCmd("option");

		$db	=& JFactory::getDBO();


		$search		= $mainframe->getUserStateFromRequest( "search{".RSVP_COM_COMPONENT."}", 'search', '' );
		$search		= $db->getEscaped( trim( strtolower( $search ) ) );
		
		$searchattendees = $mainframe->getUserStateFromRequest("atsearchattendees{" . RSVP_COM_COMPONENT . "}", 'searchattendees', '');
		$searchattendees = $db->getEscaped(trim(strtolower($searchattendees)));

		$searchinvitees = $mainframe->getUserStateFromRequest("atsearchinvitees{" . RSVP_COM_COMPONENT . "}", 'searchinvitees', '');
		$searchinvitees = $db->getEscaped(trim(strtolower($searchinvitees)));
		
		$limit		= intval( $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', 10 ));
		$limitstart = JRequest::getInt('limitstart', 0 );
		$created_by	=  intval($mainframe->getUserStateFromRequest( "createdbyrsvp", 'created_by', 0 ));
		$repeating = intval($mainframe->getUserStateFromRequest( "repeatingrsvp", 'repeating', 1 ));
		$hidepast = intval($mainframe->getUserStateFromRequest( "hidepastrsvp", 'hidepast', 1 ));

		$catid = intval(JFactory::getApplication()->getUserStateFromRequest("catidIcalEvents", 'catid', 0));
		$catidtop = $catid;
				
		$where = array();
		$join = array();
		
		if( $search ){
			$where[] = "LOWER(det.summary) LIKE '%$search%'";
		}

		if ($searchattendees)
		{
			$where[] = "(atdees.email_address LIKE '%$searchattendees%' OR jua.username LIKE '%$searchattendees%' OR jua.email LIKE '%$searchattendees%'  OR atdees.id = '$searchattendees' )";
			$join [] = " #__users AS jua ON jua.id = atdees.user_id"	;			

		}

		if ($searchinvitees)
		{
			$where[] = "(inv.email_address LIKE '%$searchinvitees%' OR jui.username LIKE '%$searchinvitees%' OR jui.email LIKE '%$searchinvitees%' )";
			$join [] = " #__users AS jui ON jui.id = atdees.user_id"	;			
		}
		
		
		if ($created_by!==0) {
			$where[] = "ev.created_by=".$db->Quote($created_by);
		}

		$where[] = "ev.ev_id IS NOT NULL";
		
		$user = JFactory::getUser();
		$params =  JComponentHelper::getParams(JEV_COM_COMPONENT);
		if ($params->get("multicategory",0)){
			$join[] = "\n #__jevents_catmap as catmap ON catmap.evid = rpt.eventid";
			$join[] = "\n #__categories AS catmapcat ON catmap.catid = catmapcat.id";
			$where[]= " catmapcat.access " . (version_compare(JVERSION, '1.6.0', '>=') ? ' IN (' . JEVHelper::getAid($user) . ')' : ' <=  ' . JEVHelper::getAid($user));
			$needsgroup = true;
		}		
		
		// category filter!
		if (JVersion::isCompatible("1.6.0"))
		{
			$authorisedonly = $params->get("authorisedonly", 0);
			$cats = $user->getAuthorisedCategories('com_jevents', 'core.create');
			if (isset($user->id) && !$user->authorise('core.create', 'com_jevents') && !$authorisedonly)
			{
				if (count($cats) > 0 && $catid < 1)
				{
					for ($i = 0; $i < count($cats); $i++)
					{
						if ($params->get("multicategory",0)){					
							$whereCats[$i] = "catmap.catid='$cats[$i]'";
						}
						else {
							$whereCats[$i] = "ev.catid='$cats[$i]'";
						}
					}
					$where[] = '(' . implode(" OR ", $whereCats) . ')';
				}
				else if (count($cats) > 0 && $catid > 0 && in_array($catid, $cats))
				{
					if ($params->get("multicategory",0)){					
						$where[] = "catmap.catid='$catid'";
					}
					else {
						$where[] = "ev.catid='$catid'";
					}
				}
				else
				{
					if ($params->get("multicategory",0)){					
						$where[] = "catmap.catid=''";
					}
					else {
						$where[] = "ev.catid=''";
					}
				}
			}
			else
			{
				if ($catid > 0)
				{
					if ($params->get("multicategory",0)){					
						$where[] = "catmap.catid='$catid'";						
					}
					else {
						$where[] = "ev.catid='$catid'";
					}
				}
			}
		}
		else
		{
			if ($catid > 0)
			{
				if ($params->get("multicategory",0)){					
					$where[] = "catmap.catid='$catid'";						
				}
				else {
					$where[] = "ev.catid='$catid'";
				}
			}
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

			$where[] = "((atd.allrepeats=1 and atd.allowregistration>0 ) OR (atd.invites=1 AND atd.allinvites=1))";
			
			$query = "SELECT count(distinct ev.ev_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=0"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=0"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
			$db->setQuery( $query);
			//echo $db->_sql;
			$total = $db->loadResult();

			echo $db->getErrorMsg();
			if( $limit > $total ) {
				$limitstart = 0;
			}

			$filter_order		= $mainframe->getUserStateFromRequest( $option.'loc_filter_order',		'filter_order',		'det.dtstart',	'cmd' );
			if ($filter_order=='rpt.startrepeat') $filter_order='det.dtstart';
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'loc_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

			$query = "SELECT ev.state as evstate, det.*, atd.* ,atd.id as atd_id, atdc.atdcount, count(distinct inv.id) as invcount, sum(atdees.waiting) as waitingcount, det.dtstart as startdate"
			. ", 0 as rp_id "
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=0"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=0"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=0"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
			. "\n GROUP BY ev.ev_id $orderby"
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


			$where[] = "(atd.allrepeats=0 OR atd.allinvites=0) AND (atd.allowregistration>0 OR atd.invites=1)";

			$query = "SELECT count(distinct rpt.rp_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=rpt.rp_id"
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
		$options[] = JHtml::_('select.option', '0', JText::_('JEV_No'));
		$options[] = JHtml::_('select.option', '1', JText::_('JEV_Yes'));
		$hidepast = JHtml::_('select.genericlist', $options, 'hidepast', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $hidepast );

		// get list of categories
		$attribs = 'class="inputbox" size="1" onchange="document.adminForm.submit();"';
		$showUnpublishedCategories=false;
		$clist = JEventsHTML::buildCategorySelect($catid, $attribs, null, $showUnpublishedCategories, false, $catidtop, "catid");

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );

		// Set the layout
		$this->view->setLayout('overview');

		$this->view->assign('rows',$rows);
		$this->view->assign('order',$filter_order);
		$this->view->assign('orderdir',$filter_order_Dir);
		$this->view->assign('userlist',$userlist);
		$this->view->assign('catlist',$clist);
		$this->view->assign('hidepast',$hidepast);
		$this->view->assign('repeating',$repeating);
		$this->view->assign('repeattypelist',$repeattypelist);
		$this->view->assign('search',$search);
		$this->view->assign('searchattendees',$searchattendees);
		$this->view->assign('searchinvitees',$searchinvitees);
		$this->view->assign('pageNav',$pageNav);

		$this->view->display();
	}

	function close(){
		ob_end_clean();
		?>
		<script type="text/javascript">
		window.parent.SqueezeBox.close();
		try {
			window.parent.closedialog();
		}
		catch (e){}
		</script>
		<?php
		exit();
	}

	function delete(){
		echo "Not implemented yet";return;
	}
}
