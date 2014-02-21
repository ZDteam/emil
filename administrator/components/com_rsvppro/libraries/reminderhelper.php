<?php
/**
 * Copyright (C) 2010 GWE Systems Ltd
 *
 * All rights reserved.
 *
*/
defined('_JEXEC') or die( 'No Direct Access' );

class RsvpReminderHelper {

	private $params;

	public function __construct( $params){
		$this->params = $params;
	}

	public function remindUser($rsvpdata,$row, $user, $emailaddress =""){
		$db= JFactory::getDBO();
		// if anon user and email attendance is allowed then find accordingly
		if ($user->id==0 && $this->params->get("remindemails",0) && $emailaddress!=""){
			$sql = "SELECT * FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and email_address=".$db->Quote($emailaddress);
		}
		else if ($user->id>0) {
			$sql = "SELECT * FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and user_id=".$user->id;
		}
		else {
			return;
		}

		// if a single specific reminder then 
		if ($rsvpdata->remindallrepeats == 0){
			$sql .= " AND rp_id=".$row->rp_id();
		}

		$db->setQuery($sql);
		$reminder = $db->loadObject();
		if (!$reminder){

			JTable::addIncludePath(RSVP_TABLES);
			$remdata = & JTable::getInstance('jev_reminders');	
			
			// if specific repeat reminder OR a single reminder at the start
			if ($rsvpdata->remindallrepeats != 2){
				//$remdata = new JTable("#__jev_reminders","id",$db);
				$remdata->id=0;
				$remdata->at_id = $rsvpdata->id;
				if ($user->id==0 && $this->params->get("remindemails",0)){
					// Make sure no reminding blank emails
					if ($emailaddress=="") return "";
					$remdata->email_address = $emailaddress;
				}
				else {
					// Make sure no reminding blank emails
					if ($user->id==0) return "";
					$remdata->user_id = $user->id;

				}
				$remdata->rp_id=0;
				if ($rsvpdata->remindallrepeats==0){
					$remdata->rp_id=$row->rp_id();
				}
				$remdata->store();
			}
			// a reminder for each and every repeat!
			else if ($rsvpdata->remindallrepeats==2) {
				$remdata->id=0;
				$remdata->at_id = $rsvpdata->id;
				if ($user->id==0 && $this->params->get("remindemails",0)){
					// Make sure no reminding blank emails
					if ($emailaddress=="") return "";
					$remdata->email_address = $emailaddress;
				}
				else {
					// Make sure no reminding blank emails
					if ($user->id==0) return "";
					$remdata->user_id = $user->id;

				}
				$remdata->rp_id=0;
				//. Find all the repeat ids and insert the reminders
				$query = "SELECT rp_id FROM #__jevents_repetition as rpt "
					. "\n WHERE eventid = '" . $row->ev_id . "' ORDER BY rpt.startrepeat asc ";
				$db->setQuery($query);
				foreach ($db->loadColumn() as $rpid  ) {
					$remdata->rp_id=$rpid;
					$remdata->store();	
					$remdata->id=0;
				}
			}

		}
		$Itemid=JRequest::getInt("Itemid");
		list($year,$month,$day) = JEVHelper::getYMD();
		$link = $row->viewDetailLink($year,$month,$day,true, $Itemid);
		return $link;
	}

	public function unremindUser($rsvpdata, $row, $user, $emailaddress){
		$db= JFactory::getDBO();
		// if anon user and email attendance is allowed then find accordingly
		if ($user->id==0 && $this->params->get("remindemails",0)){
			$sql = "DELETE FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and email_address=".$db->Quote($emailaddress);
		}
		else {
			$sql = "DELETE FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and user_id=".$user->id;
		}
		if ($rsvpdata->remindallrepeats == 0){
			$sql .= " AND (rp_id=".$row->rp_id(). " OR rp_id=0)";
		}
		$db->setQuery($sql);
		$db->query();

	}

	public function remindUsers($rsvpdata, $row, $autoremind){
		// Do not check forced reminders here!
		if (($autoremind!=3 && $autoremind!=4) || $rsvpdata->allowreminders == 0) return;

		if (isset($row->ev_id)){
			$ev_id = $row->ev_id;

			// get the data and query models
			$dataModel = new JEventsDataModel("JEventsAdminDBModel");
			$queryModel = new JEventsDBModel($dataModel);

			// get event by event id
			$jevent = $queryModel->getEventById(intval($ev_id), 1, "icaldb");		
		}
		else {
			$jevent = $row;
		}
			
		$db= JFactory::getDBO();
		// delete existing reminders
		$sql = "DELETE FROM #__jev_reminders WHERE at_id=".$rsvpdata->id;

		if ($rsvpdata->remindallrepeats==0){
			$sql .= " AND rp_id=".$jevent->rp_id();
		}
		$db->setQuery($sql);
		$db->query();

		if ($autoremind==4){
			if ($jevent->created_by()==0){
				return;
			}
			// event creators
			if ($rsvpdata->remindallrepeats==0 || $rsvpdata->remindallrepeats==1){
				$sql = "INSERT INTO #__jev_reminders (at_id, user_id, rp_id) VALUES( ".$rsvpdata->id. ",".$jevent->created_by().", ". ($rsvpdata->remindallrepeats?"0":$jevent->rp_id()).")";
				$db->setQuery($sql);
				$db->query();
			}
			else {
				//. Find all the repeat ids and insert the reminders
				$query = "SELECT rp_id FROM #__jevents_repetition as rpt "
					. "\n WHERE eventid = '" . $row->ev_id . "' ORDER BY rpt.startrepeat asc ";
				$db->setQuery($query);
				foreach ($db->loadColumn() as $rpid  ) {
					$sql = "INSERT INTO #__jev_reminders (at_id, user_id, rp_id) VALUES( ".$rsvpdata->id. ",".$jevent->created_by().", ". $rpid.")";
					$db->setQuery($sql);
					$db->query();					
				}

			}
		}
		else if ($autoremind==3){
			// all registered users
			if ($rsvpdata->remindallrepeats==0 || $rsvpdata->remindallrepeats==1){
				$sql = "INSERT INTO #__jev_reminders (at_id, user_id, rp_id) SELECT ".$rsvpdata->id. ", id, ". ($rsvpdata->remindallrepeats?"0":$jevent->rp_id());
				$sql .= " FROM #__users where id>0 AND block=0";
				$db->setQuery($sql);
				$db->query();
			}
			else {
				//. Find all the repeat ids and insert the reminders
				$query = "SELECT rp_id FROM #__jevents_repetition as rpt "
					. "\n WHERE eventid = '" . $row->ev_id . "' ORDER BY rpt.startrepeat asc ";
				$db->setQuery($query);
				foreach ($db->loadColumn() as $rpid  ) {
					$sql = "INSERT INTO #__jev_reminders (at_id, user_id, rp_id) SELECT ".$rsvpdata->id. ", id, ". $rpid;
					$sql .= " FROM #__users where id>0 AND block=0";
					$db->setQuery($sql);
					$db->query();					
				}
			}
		}

		return true;
	}
	
	public function isReminded($rsvpdata, $row, $emailaddress){

		$user=JFactory::getUser();
		if ($user->id==0 && $emailaddress==""){
			return false;
		}
		if ($user->id==0 && $emailaddress!=""){
			$db= JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and email_address=".$db->Quote($emailaddress);
			if ($rsvpdata->remindallrepeats==0 || $rsvpdata->remindallrepeats==2){
				$sql .= " AND rp_id=".$row->rp_id();
			}
			$db->setQuery($sql);

			$remindees  = $db->loadObject();
			if ($remindees){
				$reminded = true;
			}
			else {
				$reminded= false;
			}
			return $reminded;
		}
		$db= JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_reminders WHERE at_id=".$rsvpdata->id." and user_id=".$user->id;
		if ($rsvpdata->remindallrepeats==0 || $rsvpdata->remindallrepeats==2){
			$sql .= " AND rp_id=".$row->rp_id();
		}
		$db->setQuery($sql);
		$remindees  = $db->loadObject();
		if ($remindees){
			$reminded = true;
		}
		else {
			$reminded= false;
		}
		return $reminded;
	}

    public function getEmailAddress($em = "em") {
        $emailaddress = "";
        if ($this->params->get("attendemails", 0)) {
            $em = JRequest::getString($em, "");

            if ($em != "") {
                $emd = base64_decode($em);
                if (strpos($emd, ":") > 0) {
                    list ( $emailaddress, $code ) = explode(":", $emd);
                    if ($em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress))) {
                        $emailaddress = "";
                    }
                }
            }
        }
        return $emailaddress;
    }


}