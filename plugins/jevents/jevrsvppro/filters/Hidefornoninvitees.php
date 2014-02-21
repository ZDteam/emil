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

defined('_JEXEC') or die( 'No Direct Access' );

// Strips out public events
class jevHidefornoninviteesFilter extends jevFilter
{
	function __construct($tablename, $filterfield, $isstring=true){
		$this->filterType="jevidenoninvitees";
		$this->filterNullValue="";
		parent::__construct($tablename,$filterfield, true);
	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";

		$user = JFactory::getUser();

		// if hidenoninvitees not defined for the event then show
		// always show to creator!
		// if not hidenoninvitees or no invites then show
		// if invites enabled and hidenoninvitees then only show to invitees

		if ($user->id==0) {
			$db = JFactory::getDBO();

			$emailaddress="FALSE";
			$params = JComponentHelper::getParams("com_rsvppro");

			if ($params->get("attendemails",0)){
				$em = JRequest::getString("em2",JRequest::getString("em",""));

				if ($em!=""){
					$emd=base64_decode($em);
					if (strpos($emd,":")>0){
						list($emailaddress,$code)=explode(":",$emd);
						if ($em != base64_encode($emailaddress.":".md5($params->get("emailkey","email key").$emailaddress)) && 
							$em != base64_encode($emailaddress.":".md5($params->get("emailkey","email key").$emailaddress."invited"))){
							$emailaddress="FALSE";
						}
					}
				}
			}

			$filter = "(atd.hidenoninvitees IS NULL
					OR ev.created_by=$user->id
					OR atd.hidenoninvitees=0 
					OR atd.invites=0 
					OR (atd.invites=1 AND atd.hidenoninvitees=1 AND inv.email_address=".$db->Quote($emailaddress)." AND ((atd.allinvites=1 AND inv.rp_id=0) OR (atd.allinvites=0 AND inv.rp_id=rpt.rp_id))))";

		}
		else {
			// If for specific repeats must specify them
			$filter = "(atd.hidenoninvitees IS NULL
					OR ev.created_by=$user->id
					OR atd.hidenoninvitees=0 
					OR atd.invites=0 
					OR (atd.invites=1 AND atd.hidenoninvitees=1 AND inv.user_id=$user->id  AND ((atd.allinvites=1 AND inv.rp_id=0) OR (atd.allinvites=0 AND inv.rp_id=rpt.rp_id))))";
		}
		return $filter;
	}

	// always used in conjunction with private events filter so no need for join
	function _createJoinFilter($prefix=""){
		if (!$this->filterField ) return "";
		$this->needsgroupby = true;

		return "#__jev_attendance as atd ON ev.ev_id=atd.ev_id LEFT JOIN #__jev_invitees as inv ON inv.at_id=atd.id";
	}

	function _createfilterHTML(){

		if (!$this->filterField) return "";

		$db = JFactory::getDBO();

		$filterList=array();
		$filterList["title"]="";
		$filterList["html"] = "<input type='text' name='".$this->filterType."_fv'  id='".$this->filterType."_fv'  class='evuserssearch'  value='".$this->filter_value."' />";

		return $filterList;

	}
}