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


class AdminInviteesController extends JControllerLegacy {

	protected $params;
	protected $jomsocial = false;

	/**
	 * Controler for Sessions 
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'update',  'updateInvitees' );
		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE.'/components/com_community/community.php')){
			if (JComponentHelper::isEnabled("com_community")) {
				$this->jomsocial = true;
			}
		}

		$plugin =& JPluginHelper::getPlugin('jevents', 'jevrsvppro');
		$this->params = JComponentHelper::getParams("com_rsvppro");

		include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/inviteehelper.php");
		$this->helper = new RsvpInviteeHelper($this->params);

	}


	/**
	 * List Invitees
	 *
	 */
	function overview( )
	{
		// get the view
		$this->view = & $this->getView("invitees","html");

		$this->view->assign('params', JComponentHelper::getParams("com_rsvppro"));
		
		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");

		$db	=& JFactory::getDBO();

		$atd_id = JRequest::getVar("atd_id","post","array");
		if (!isset($atd_id[0]) || strpos($atd_id[0],"|")===false){
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|",$atd_id[0]);

		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$repeating = JRequest::getInt('repeating', 0 );

		$limit		= intval( $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', 10 ));
		$limitstart = intval( $mainframe->getUserStateFromRequest( "view{".RSVP_COM_COMPONENT."}limitstart", 'limitstart', 0 ));

		$where = array();
		$join = array();

		$where[] = "ev.ev_id IS NOT NULL";
		$where[] = "atd.id = $atd_id";
		$where[] = "inv.rp_id = $rp_id";

		$filter_order		= $mainframe->getUserStateFromRequest( $option.'inv_filter_order',		'filter_order',		'atd.id',	'cmd' );
		if ($filter_order=="invcount" ||  $filter_order=="ev.created_by") $filter_order='atd.id';
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'inv_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
		$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

		$search		= $mainframe->getUserStateFromRequest( "search{".RSVP_COM_COMPONENT."}", 'search', '' );
		$search		= $db->getEscaped( trim( strtolower( $search ) ) );

		if( $search ){
			$where[] = "(inv.email_address LIKE '%$search%' OR ju.username LIKE '%$search%' OR ju.email LIKE '%$search%' )";
		}
		
		if ($repeating){
			$where[] = "atd.allinvites=1 AND atd.invites>0 ";
		}
		else {
			$where[] = "atd.allinvites=0  AND atd.invites>0 ";
		}

		$query = "SELECT count(distinct inv.id)"
		. "\n FROM #__jevents_vevent AS ev "
		. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
		. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
		. "\n LEFT JOIN #__jev_invitees AS inv ON inv.at_id = atd.id"
		. "\n LEFT JOIN #__users AS ju ON ju.id = inv.user_id"
		. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
		. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
		$db->setQuery( $query);
		//echo $db->_sql;
		$total = $db->loadResult();

		echo $db->getErrorMsg();
		if( $limit > $total ) {
			$limitstart = 0;
		}
		
		$query = "SELECT det.*, atd.* , atd.id as atd_id, atdc.atdcount, inv.*,inv.id as inv_id,   a.id as attending, a.attendstate, "
		. " CASE WHEN inv.user_id=0 THEN inv.email_address ELSE CONCAT_WS(' - ',ju.username,ju.email) END as attendee "
		. "\n FROM #__jevents_vevent as ev "
		. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
		. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
		. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id"
		. "\n LEFT JOIN #__jev_invitees AS inv ON inv.at_id = atd.id"
		." LEFT JOIN #__jev_attendees as a ON a.user_id=inv.user_id AND a.at_id=inv.at_id AND a.rp_id=inv.rp_id"
		. "\n LEFT JOIN #__users AS ju ON ju.id = inv.user_id"
		. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
		. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
		. "\n GROUP BY inv.id $orderby"
		;

		if ($limit>0){
			$query .= "\n LIMIT $limitstart, $limit";
		}
		$db->setQuery( $query );

		$rows = $db->loadObjectList();

		echo $db->getErrorMsg();

		foreach ($rows as $key=>$val) {

		}

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=".intval($atd_id);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		
		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		// Find the first repeat
		$vevent = $this->dataModel->queryModel->getEventById(  $rsvpdata->ev_id, false, "icaldb");
		if ($rp_id==0){
			$repeat = $vevent->getFirstRepeat();
		}
		else {
			list($year,$month,$day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"])) $repeat = $repeatdata["row"];
			
		}
		
		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );

		// Set the layout
		$this->view->setLayout('overview');

		$this->view->assign('atd_id',$atd_id);
		$this->view->assign('rp_id',$rp_id);
		$this->view->assign('rows',$rows);
		$this->view->assign('rsvpdata',$rsvpdata);
		$this->view->assign('repeat',$repeat);
		$this->view->assign('vevent',$vevent);
		$this->view->assign('search',$search);
		$this->view->assign('order',$filter_order);
		$this->view->assign('orderdir',$filter_order_Dir);
		$this->view->assign('repeating',$repeating);
		$this->view->assign('pageNav',$pageNav);

		$this->view->display();
	}

	
	public function updateInvitees(){
		$user=JFactory::getUser();
		if ($user->id==0){
			return "";
		}

		$redirect = true;
		
        $db = & JFactory::getDBO ();
		$at_id = JRequest::getInt("at_id",-1);
        $sql = "SELECT * FROM #__jev_attendance WHERE id=" . $at_id;
        $db->setQuery($sql);
        $rsvpdata = $db->loadObject();
		if (!$rsvpdata) return false;

		$rp_id = JRequest::getInt("rp_id",-1);
		$datamodel = new JEventsDataModel();
		$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
		if (!$row) return false;

		return $this->helper->updateInvitees($rsvpdata, $row, $redirect);

	}
	
	
}
