<?php
/**
 * Copyright (C) 2010 GWE Systems Ltd
 *
 * All rights reserved.
 *
*/
defined('_JEXEC') or die( 'No Direct Access' );

class RsvpInviteeHelper {

	private $params;

	public function __construct( $params){
		$this->params = $params;
	}

	public function updateInvitees($rsvpdata, $row, $redirect =true){
		$user=JFactory::getUser();
		if ($user->id==0){
			return "";
		}
		if ($user->id==$row->created_by() ||  JEVHelper::isAdminUser($user)){

			$jevattend_hiddeninitees = JRequest::getInt("jevattend_hiddeninitees",0);
			if ($jevattend_hiddeninitees){

				$jevinvitees = JRequest::getVar("jevinvitee",array(),'post','array');
				// Numeric invites first for registered users
				$invitees = array();
				foreach ($jevinvitees as $invitee) {
					$id = intval( str_replace("rsvp_inv_","",$invitee));
					if ($id>0) 	$invitees[]=$id;
				}
				JArrayHelper::toInteger($invitees);
				$idlist = implode(",",$invitees);
				$db= JFactory::getDBO();
				// remove invitees not in the list
				if (count($invitees)>0){
					$sql = "DELETE FROM #__jev_invitees WHERE user_id NOT IN (".$idlist.") AND at_id=".intval($rsvpdata->id) . " AND email_address=''";
				}
				else {
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=".intval($rsvpdata->id). " AND email_address=''";
				}
				if (!$rsvpdata->allinvites && $row->hasrepetition()){
					$sql .= " AND rp_id=".$row->rp_id();
				}
				$db->setQuery($sql);
				$db->query();

				// Email based invites for non-registered users

				$db->setQuery("SELECT id , email_address from #__jev_invitees WHERE at_id=".$rsvpdata->id . " AND email_address<>''");
				$currentlist = $db->loadObjectList('email_address');

				$emailaddresses = array();
				$emailnames = array();
				$keeplist = array();
				foreach ($jevinvitees as $invitee) {
					$invitee = str_replace(array("rsvp_inv_","}", ")"),"",$invitee);
					$parts = explode("{",$invitee);
					if (count($parts)!=2) $parts = explode("(",$invitee);
					if (count($parts)!=2) continue;
					$emailaddresses[]=$parts[1];
					$emailnames[]=$parts[0];
					if (array_key_exists($parts[1],$currentlist)) {
						$keeplist[] = $currentlist[$parts[1]]->id;
						unset($currentlist[$parts[1]]);
					}
				}
				// remove invitees not in the list
				$currentids = array();
				foreach ($currentlist as $currentinvitee) {
					$currentids[]=$currentinvitee->id;
				}
				$idlist = implode(",",$currentids);

				$ids = explode(",",$idlist);
				JArrayHelper::toInteger($ids);
				$idlist = implode(",",$ids);

				$db= JFactory::getDBO();
				if (count($keeplist)>0){
					$keeplist = implode(",",$keeplist);
					$sql = "DELETE FROM #__jev_invitees WHERE id  NOT IN (".$keeplist.") AND at_id=".intval($rsvpdata->id ). " AND email_address<>''";

					if (!$rsvpdata->allinvites && $row->hasrepetition()){
						$sql .= " AND rp_id=".$row->rp_id();
					}
					$db->setQuery($sql);
					$db->query();

				}
				if (count($jevinvitees)==0){
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=".$rsvpdata->id. " AND email_address<>'' ";
					if (!$rsvpdata->allinvites && $row->hasrepetition()){
						$sql .= " AND rp_id=".$row->rp_id();
					}
					$db->setQuery($sql);
					$db->query();
				}

				// Are we saving the list of invitees
				if (JRequest::getString("rsvp_email","","post")=="savelist" ){
					$listname = trim(JRequest::getString("jevrsvp_listid",""));
					if ($listname!=""){
						$db = JFactory::getDBO();
						// does the list exist already
						$db->setQuery("SELECT * FROM #__jev_invitelist WHERE user_id=".$user->id." AND  listname=".$db->Quote($listname));
						$list = $db->loadObject();
						if ($list){
							$listid = $list->id;
						}
						else {
							$db->setQuery("REPLACE INTO #__jev_invitelist SET user_id=".$user->id.", listname=".$db->Quote($listname));
							$db->query();
							$listid = $db->insertid();
						}

						// empty the current list members
						$db->setQuery("DELETE FROM #__jev_invitelist_member WHERE list_id=".$listid);
						$db->query();
						
						// if its an empty list then remove it
						if (count($jevinvitees)==0){
							// empty the current list members
							$db->setQuery("DELETE FROM #__jev_invitelist WHERE id=".$listid);
							$db->query();
							JFactory::getApplication()->enqueueMessage(JText::_("RSVP_INVITEE_LIST_DELETED"),"error");
							
						}
					}
				}

				// insert new records
				foreach ($invitees as $invitee) {

					$iuser = JFactory::getUser($invitee);

					if (JRequest::getString("rsvp_email","","post")=="savelist" && isset($listid) && $listid>0 ){
						$db = JFactory::getDBO();
						JTable::addIncludePath(RSVP_TABLES);
						$listmember = & JTable::getInstance('jev_invitelist_member');			
						//$listmember = new JTable("#__jev_invitelist_member","id",$db);
						$listmember->list_id = $listid;
						$listmember->user_id = $iuser->id;
						$listmember->store();
					}

					$currentinvitee = $this->fetchInvitee($row, $rsvpdata, $invitee);
					if (!$currentinvitee){
						JTable::addIncludePath(RSVP_TABLES);
						$currentinvitee = & JTable::getInstance('jev_invitees');			
						//$currentinvitee = new JTable("#__jev_invitees","id",$db);
						$currentinvitee->id=0;
						$currentinvitee->user_id=$invitee;
						if (!$rsvpdata->allinvites && $row->hasrepetition()){
							$currentinvitee->rp_id = $row->rp_id();
						}
						else {
							$currentinvitee->rp_id = 0;
						}
						$currentinvitee->at_id=$rsvpdata->id;
						if (class_exists("JevDate")) {
							$datenow = JevDate::getDate();
						}
						else {
							$datenow = JFactory::getDate();
						}
						$currentinvitee->invitedate	= $datenow->toMySQL();

						$currentinvitee->save(array());

						$currentinvitee->attending = false;
						$currentinvitee->iid=$currentinvitee->id;

						// email new invitees
						if (JRequest::getString("rsvp_email","","post")=="email" || JRequest::getString("rsvp_email","","post")=="reemail" || JRequest::getString("rsvp_email","","post")=="failed"){
							if ($iuser){
								list ($message,$subject) = $this->processMessage($rsvpdata,$row, $iuser->name,true, $iuser);
								$bcc = $this->getBCC($iuser->id);
								$success = $this->sendMail($user->email,$user->name,$iuser->email,$subject,$message,1, null, $bcc);

								$mainframe = JFactory::getApplication();
								if ($success===true){
									$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO",$iuser->name));
									$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=".$currentinvitee->iid;
									$db->setQuery($sql);
									$db->query();
								}
								else {
									$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO",$iuser->name),"error");
									$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=".$currentinvitee->iid;
									$db->setQuery($sql);
									$db->query();
								}

							}
						}
					}
					else {
						// re-send email invitations
						if (JRequest::getString("rsvp_email","","post")=="reemail" || JRequest::getString("rsvp_email","","post")=="failed"){
							if (JRequest::getString("rsvp_email","","post")=="failed" && $currentinvitee->sentmessage==1){
								continue;
							}
							// Do not send message to confirmed attendees
							if ($currentinvitee->attending) {
								continue;
							}
							list ($message,$subject) =   $this->processMessage($rsvpdata,$row, $currentinvitee->name,true,$currentinvitee);
							$bcc = null;
							if ($invitee) {
								$bcc = $this->getBCC($invitee);
							}
							$success = $this->sendMail($user->email,$user->name,$currentinvitee->email,$subject,$message,1, null, $bcc);

							$mainframe = JFactory::getApplication();
							if ($success===true){
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO",$currentinvitee->name));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else {
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO",$currentinvitee->name),"error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
						}

					}
				}

				// Now process email address based invites
				for ($i=0;$i<count($emailaddresses);$i++){
					$emailaddress = $emailaddresses[$i];
					$emailname = $emailnames[$i];

					if (JRequest::getString("rsvp_email","","post")=="savelist" && isset($listid) && $listid>0 ){
						$db = JFactory::getDBO();
						JTable::addIncludePath(RSVP_TABLES);
						$listmember = & JTable::getInstance('jev_invitelist_member');			
						$listmember->list_id = $listid;
						$listmember->email_address = $emailaddress;
						$listmember->email_name = $emailname;
						$listmember->store();
					}

					$currentinvitee = $this->fetchInviteeByEmail($row, $rsvpdata, $emailaddress, true);
					if (!$currentinvitee){
						JTable::addIncludePath(RSVP_TABLES);
						$currentinvitee = & JTable::getInstance('jev_invitees');			
						$currentinvitee->id=0;
						$currentinvitee->email_address=$emailaddress;
						$currentinvitee->email_name=$emailname;
						if (!$rsvpdata->allinvites && $row->hasrepetition()){
							$currentinvitee->rp_id = $row->rp_id();
						}
						else {
							$currentinvitee->rp_id = 0;
						}
						$currentinvitee->at_id=$rsvpdata->id;
						if (class_exists("JevDate")) {
							$datenow =JevDate::getDate();
						}
						else {
							$datenow = JFactory::getDate();
						}
						$currentinvitee->invitedate	= $datenow->toMySQL();

						$currentinvitee->save(array());

						$currentinvitee->attending = false;
						$currentinvitee->iid=$currentinvitee->id;

						// email new invitees
						if (JRequest::getString("rsvp_email","","post")=="email" || JRequest::getString("rsvp_email","","post")=="reemail" || JRequest::getString("rsvp_email","","post")=="failed"){
							list ($message,$subject) = $this->processMessage($rsvpdata,$row, $emailname,false,$currentinvitee);
							$success = $this->sendMail($user->email,$user->name,$emailaddress,$subject,$message,1);

							$mainframe = JFactory::getApplication();
							if ($success===true){
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO",$emailname));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else {
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO",$emailname),"error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}

						}
					}
					else {
						// re-send email invitations
						if (JRequest::getString("rsvp_email","","post")=="reemail" || JRequest::getString("rsvp_email","","post")=="failed"){
							if (JRequest::getString("rsvp_email","","post")=="failed" && $currentinvitee->sentmessage==1){
								continue;
							}
							// Do not send message to confirmed attendees
							if ($currentinvitee->attending) {
								continue;
							}
							list ($message,$subject) =   $this->processMessage($rsvpdata,$row, $currentinvitee->email_name,false,$currentinvitee);
							$success = $this->sendMail($user->email,$user->name,$emailaddress,$subject,$message,1);

							$mainframe = JFactory::getApplication();
							if ($success===true){
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO",$currentinvitee->email_address));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else {
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO",$currentinvitee->email_address),"error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=".$currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
						}

					}
				}

				if (!$redirect){
					return true;
				}
				$mainframe = JFactory::getApplication();
				if ($mainframe->isAdmin()){
					$repeating = JRequest::getInt("repeating",0);
					$atd_id = JRequest::getVar("atd_id","post","array");
					if (!isset($atd_id[0]) || strpos($atd_id[0],"|")===false){
						JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
					}
					list($atd_id, $rp_id) = explode("|",$atd_id[0]);

					$atd_id = intval($atd_id);
					$rp_id = intval($rp_id);

					$link = "index.php?option=com_rsvppro&task=invitees.overview&atd_id[]=$atd_id|$rp_id&repeating=$repeating";
				}
				else {
					$Itemid=JRequest::getInt("Itemid");
					list($year,$month,$day) = JEVHelper::getYMD();
					$link = $row->viewDetailLink($year,$month,$day,true, $Itemid);
				}

				if ($redirect) $mainframe->redirect($link,JText::_("JEV_INVITES_UPDATED"));

			}
		}
		return true;
	}

	public function isInvitee($row, $rsvpdata, $allowSuperAdmin=true, $emailaddress=""){
		$user=JFactory::getUser();
		if ($user->id==$row->created_by() || ($allowSuperAdmin &&  JEVHelper::isAdminUser($user))){
			return true;
		}

		// call fetchInvitee (not user restricited)
		$invitee = $this->fetchInvitee($row, $rsvpdata, $user->id, true, $emailaddress);
		if (is_null($invitee)){
			$invitee = $this->fetchInviteeByEmail($row, $rsvpdata, $emailaddress, true);
		}
		return !is_null($invitee);

	}

	public  function fetchInvitee($row,$rsvpdata, $userid,$open=false, $emailaddress="") {
		$user=JFactory::getUser();
		if ($user->id==0){
			return null;
		}
		if ($open || $user->id==$row->created_by() ||  JEVHelper::isAdminUser($user)){
			$db= JFactory::getDBO();
			$sql = "SELECT i.*, u.*, u.id as uid, a.id as attending , a.attendstate, i.id as iid FROM #__jev_invitees as i"
			." LEFT JOIN #__users as u ON u.id=i.user_id"
			." LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
			." WHERE i.at_id=".$rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition()){
				$sql .= " AND i.rp_id=".$row->rp_id();
			}
			$sql .= " AND i.user_id=".$userid;
			$sql .= " ORDER BY i.sentmessage DESC, i.viewedevent DESC ";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList();
			if (is_null($invitees)) return $invitees;
			// clean up bad data
			if (count($invitees)>1){
				$ids = array();
				for ($i=1;$i<count($invitees);$i++) {
					$ids[] = $invitees[$i]->iid;
				}
				$db->setQuery("DELETE FROM #__jev_invitees  WHERE id IN (".implode(",",$ids).")");
				$db->query();
			}
			return isset($invitees[0])?$invitees[0]:null;
		}
		return null;
	}

	public function fetchInviteeByEmail($row,$rsvpdata, $emailaddress,$open=false) {
		$user=JFactory::getUser();
		/*
		if ($user->id==0){
		return null;
		}
		*/
		if ($emailaddress=="") return null;
		if ($open || $user->id==$row->created_by() ||  JEVHelper::isAdminUser($user)){
			$db= JFactory::getDBO();
			$sql = "SELECT i.*, a.id as attending , a.attendstate, i.id as iid FROM #__jev_invitees as i"
			." LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id  AND a.email_address=i.email_address"
			." WHERE i.at_id=".$rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition()){
				$sql .= " AND i.rp_id=".$row->rp_id();
			}
			$sql .= " AND i.email_address=".$db->Quote($emailaddress);
			$sql .= " ORDER BY i.sentmessage DESC, i.viewedevent DESC ";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList();
			if (is_null($invitees)) return $invitees;
			// clean up bad data
			if (count($invitees)>1){
				$ids = array();
				for ($i=1;$i<count($invitees);$i++) {
					$ids[] = $invitees[$i]->iid;
				}
				$db->setQuery("DELETE FROM #__jev_invitees  WHERE id IN (".implode(",",$ids).")");
				$db->query();
			}
			return isset($invitees[0])?$invitees[0]:null;
		}
		return null;
	}

	public function fetchInvitees($row,$rsvpdata) {
		$user=JFactory::getUser();
		if ($user->id==0){
			return array();
		}
		if ($user->id==$row->created_by() ||  JEVHelper::isAdminUser($user)){
			$db= JFactory::getDBO();
			// First of all the registered invitees
			$sql = "SELECT i.*, u.*, a.id as attending, a.attendstate FROM #__jev_invitees as i"
			." LEFT JOIN #__users as u ON u.id=i.user_id"
			." LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
			." WHERE i.at_id=".$rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition()) {
				$sql .= " AND i.rp_id=".$row->rp_id();
			}
			$sql .= " AND u.id IS NOT NULL";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList('username');

			// Then the email based invitees
			$sql = "SELECT i.*, a.id as attending, a.attendstate FROM #__jev_invitees as i"
			." LEFT JOIN #__jev_attendees as a ON LOWER(a.email_address)=LOWER(i.email_address) AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
			." WHERE i.at_id=".$rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition()) {
				$sql .= " AND i.rp_id=".$row->rp_id();
			}
			$sql .= " AND i.email_address <> ''";
			$db->setQuery($sql);
			$invitees2 = $db->loadObjectList('email_address');

			$invitees = array_merge($invitees, $invitees2);
			ksort($invitees);
			return $invitees;
		}
		return array();
	}


	public function recordViewed($rsvpdata, $row) {
        $user = JFactory::getUser ();
        if ($user->id == 0) {

            // record as email address viewing
            $emailaddress = $this->getEmailAddress("em");
            if (!$emailaddress)
                $emailaddress = $this->getEmailAddress("em2");
            if (!$emailaddress)
                return false;

            $db = JFactory::getDBO ();
            $sql = "UPDATE #__jev_invitees set viewedevent=1  WHERE user_id =0 AND email_address=" . $db->Quote($emailaddress) . " AND at_id=" . $rsvpdata->id;
            if (!$rsvpdata->allinvites && $row->hasrepetition()) {
                $sql .= " AND rp_id=" . $row->rp_id();
            }
            $db->setQuery($sql);
            $db->query();

            return true;
        }
        if (!$rsvpdata->invites)
            return true;

        $db = JFactory::getDBO ();
        $sql = "UPDATE #__jev_invitees set viewedevent=1  WHERE user_id =" . $user->id . " AND at_id=" . $rsvpdata->id;
        if (!$rsvpdata->allinvites && $row->hasrepetition()) {
            $sql .= " AND rp_id=" . $row->rp_id();
        }
        $db->setQuery($sql);
        $db->query();

        return true;
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

	private function processMessage($rsvpdata,& $row, $name, $requirelogin=false,$currentinvitee){
		$output = array();
		$output[] = $this->parseMessage($rsvpdata->message,$rsvpdata,$row, $name, $requirelogin,$currentinvitee);
		$output[] = $this->parseMessage($rsvpdata->subject,$rsvpdata,$row, $name, $requirelogin,$currentinvitee);
		return $output;
	}

	private function parseMessage($message,$rsvpdata,& $row, $name, $requirelogin=false,$currentinvitee){
		
		$params = JComponentHelper::getParams("com_rsvppro");
		// do we run through the jevents plugins
		if ($params->get("remindplugins", 0)) {
			JPluginHelper::importPlugin('jevents');
			$dispatcher	=& JDispatcher::getInstance();
			JRequest::setVar("repeating",$rsvpdata->allrepeats);
			JRequest::setVar("atd_id",array($rsvpdata->id."|".$row->rp_id()));

			$dispatcher->trigger( 'onDisplayCustomFields', array( &$row) );
		}					
		
		$user=JFactory::getUser();
		$message = str_replace("{NAME}",$name,$message);
		$message = str_replace("{EVENT}",$row->title(),$message);		

		if ($row->created_by()>0){
			$creator=JFactory::getUser($row->created_by());
			$creator=$creator->name;
		}
		else {
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=".intval($row->ev_id()));
			$anonrow = @$db->loadObject();
			if ($anonrow){
				$creator=$anonrow->name;
			}
			else {
				$creator="unknown";
			}
		}

		$message = str_replace("{CREATOR}",$creator,$message);
		$message = str_replace("{REPEATSUMMARY}",$row->repeatSummary(),$message);
		$message = str_replace("{DESCRIPTION}",$row->content(),$message);
		$message = str_replace("{EXTRA}",$row->extra_info(),$message);
		$message = str_replace("{CONTACT}",$row->contact_info(),$message);
		$message = str_replace("{USERNAME}",isset($currentinvitee->username)?$currentinvitee->username:$name,$message);

		$regex = "#{DATE}(.*?){/DATE}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches)==2) {
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartDate());
			$message = preg_replace( $regex, $date->toFormat($matches[1]), $message );
		}

		$regex = "#{TIME}(.*?){/TIME}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}
                
		$regex = "#{LINK}(.*?){/LINK}#s";
		preg_match($regex, $message, $matches);
		if (count($matches)==2) {
			$Itemid=JRequest::getInt("Itemid");
			list($year,$month,$day) = JEVHelper::getYMD();
			// Do NOT use SEF because not consistent between frontend and backend!
			$link = $row->viewDetailLink($year,$month,$day,false, $Itemid);
			// make into frontend link!
			if (strpos($link, "/administrator")===0){
				$link = substr($link, 14);
			}
			$uri	         =& JURI::getInstance();
			//$prefix = $uri->toString( array('scheme','host', 'port'));
			$prefix = JURI::root();

			/*
			if (strpos($link,"/")===0) {
			$link = substr($link,1);
			}
			*/
			// backend doesn't add the / in the URL so fix this
			if (substr($link,0,1)!="/" && substr($prefix, strlen($prefix)-1,1)!="/"){
				$prefix .= "/";
			}
			else if (substr($link,0,1)=="/" && substr($prefix, strlen($prefix)-1,1)=="/"){
				$prefix = substr($prefix, 0, strlen($prefix)-1);
			}
			$link = $prefix.$link;

			if ($requirelogin){
				if (strpos($link,"?")>0){
					$link .= "&login=1";
				}
				else {
					$link .= "?login=1";
				}
			}
			if (isset($currentinvitee->user_id) && $currentinvitee->user_id==0){
				$params = JComponentHelper::getParams("com_rsvppro");

				if ($params->get("attendemails",0)){
					$emailaddress = $currentinvitee->email_address;

					$em = base64_encode($emailaddress.":".md5($params->get("emailkey","email key").$emailaddress. "invited"));

					// use em2 since em implies attendance confirmation !!
					if (strpos($link,"?")>0){
						$link .= "&em2=$em";
					}
					else {
						$link .= "?em2=$em";
					}
				}

			}
			$message = preg_replace( $regex, "<a href='$link'>".$matches[1]."</a>", $message );
		}

		// convert relative to absolute URLs
		$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#','$1="'.JURI::root(),$message);
		$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:))/#','$1="'.JURI::root(),$message);

		$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#","$1='".JURI::root(),$message);
		$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:))/#","$1='".JURI::root(),$message);

		include_once(JPATH_SITE . "/components/com_jevents/views/default/helpers/defaultloadedfromtemplate.php");
		ob_start();
		DefaultLoadedFromTemplate(false, false, $row, 0, $message);
		$newmessage = ob_get_clean();
		if ($newmessage != "" && strpos($newmessage, "<script ")===false)
		{
			$message = $newmessage;
		}
		
		return $message;

	}

	public function getBCC($userid) {
		$bcc = null;
		$params = JComponentHelper::getParams("com_rsvppro");
		if ($userid>0 && $params->get("cbbcc")!="") {
			$bccfield = $params->get("cbbcc");
			$db = JFactory::getDBO();
			$sql = "select $bccfield from #__comprofiler where user_id = $userid";
			$db->setQuery($sql);
			$bcc = $db->loadResult();
		}
		return $bcc;
	}

	public function sendMail ($from, $fromname, $recipient, $subject, $body, $mode=0, $cc=null, $bcc=null, $attachment=null, $replyto=null, $replytoname=null)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$from = $params->get("overridesenderemail", $from);
		$fromname = $params->get("overridesendername", $fromname);
                               
                if ($params->get('invites_ical_mail') == 1) {
                    //NOTE this below message is for TESTING ONLY - We need to pull the VALID VCAL into here?
                    $message="
BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART:20100616T080000Z
DTEND:20100616T090000Z
DTSTAMP:20100616T075116Z
ORGANIZER;CN=Tony Partridge:mailto:tony@jevents.net
UID:12345678
ATTENDEE;PARTSTAT=NEEDS-ACTION;RSVP= TRUE;CN=Sample:mailto:sample@test.com
DESCRIPTION:Complete event on http://www.sample.com/get_event.php?id=12345678
LOCATION: Isle of Man
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:Test New Ical Mail sender
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR";
                    $mail = JFactory::getMailer();
                    $mail->Encoding = "7bit";
                    $mail->ContentType = "text/calendar";

                    return $mail->sendMail($from, $fromname, $recipient, $subject, $message, 0, $cc, $bcc, $attachment,$replyto, $replytoname);  
                    
                }
		elseif ($params->get('invites_ical_mail') == 0) {

		$mail = JFactory::getMailer();
		return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
               }

        }
	
}