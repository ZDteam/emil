<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

$user=JFactory::getUser();
if ($user->id==0 && !$this->params->get("remindemails",0) && $this->params->get("reminders",0)){
	$this->row->rsvp_reminderform = JText::_("JEV_LOGIN_TO_REQUEST_REMINDER");
	if ($this->jomsocial && $this->row->rsvp_reminderform!=""){
		$this->row->rsvp_reminderform =  '<div class="cModule jevremindform"><h3><span>'.JText::_("JEV_REMIND_ME").'</span></h3>'. $this->row->rsvp_reminderform."</div>";
	}
	$html = $this->row->rsvp_reminderform;
}
else if ($this->params->get("reminders",0)){

	$mainframe = JFactory::getApplication();$Itemid=JRequest::getInt("Itemid");
	list($year,$month,$day) = JEVHelper::getYMD();
	//$link = $this->row->viewDetailLink($year,$month,$day,false, $Itemid);
	$eventid = intval($this->row->ev_id());
	$link = JRoute::_("index.php?option=com_rsvppro&task=reminders.record&eventid=".$eventid,false);

	if ($this->rsvpdata->remindallrepeats){
		$html ='
<form action="'.$link.'"  method="post" >';
		$checkemail = "";
		if ($this->params->get("remindemails",0) && $user->id==0 ) {
			$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
			$checkemail  = "if (document.getElementById('jevattend_email').value=='') {alert('".JText::_("JEV_MISSING_EMAIL",true)."');return false};document.getElementById('jevremindemail').value=document.getElementById('jevattend_email').value;";
			// resuse the email address from the attendance form - link via javascript to attendance email address
			$registry	=& JRegistry::getInstance("jevents");

			if ($registry->get("showingemailaddress",false)){
				$html .= '
				<input type="hidden" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />';
			}
			else {
				$html .= '
				<label for="jevremindemail">'. JText::_("JEV_ATTEND_EMAIL").'</label>
				<input type="text" name="jevattend_email" id="jevattend_email" value="" size="50" /><br/>
				<input type="hidden" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />';
				if ($this->emailaddress!=""){
					$html .= '<input type="hidden" name="em" id="em" value="'.$code.'" />';
				}
			}
		}
		$html .='<label for="jevremind">'. (($this->row->hasrepetition() && $this->rsvpdata->remindallrepeats==2)?JText::_( 'JEV_REMIND_ME_ALL_REPEATS' ):JText::_( 'JEV_REMIND_ME_THIS_EVENT' )).'</label>			
    <input type="checkbox" name="jevremind" value="1" onclick="'.$checkemail.'form.submit();" '.($this->reminded?"checked='checked'":"").'/>
    <input type="hidden" name="jevremind_hidden" value="1" />    
    <noscript>
		<input type="text" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />
		<input type="submit" name="submit" value="'.JText::_("JEV_CONFIRM").'" />
	</noscript>
    <input type="hidden" name="Itemid" value="'.JRequest::getInt("Itemid",0).'"/>
</form>';
	}
	// or just this repeat
	else if ($this->row->hasrepetition()){
		$html ='
<form action="'.$link.'"  method="post" >';
		$checkemail = "";
		if ($this->params->get("remindemails",0) && $user->id==0 ) {
			$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
			$checkemail  = "if (document.getElementById('jevattend_email').value=='') {alert('".JText::_("JEV_MISSING_EMAIL",true)."');return false};document.getElementById('jevremindemail').value=document.getElementById('jevattend_email').value;";
			// resuse the email address from the attendance form - link via javascript to attendance email address
			$registry	=& JRegistry::getInstance("jevents");
			if ($registry->get("showingemailaddress",false)){
				$html .= '
				<label for="jevremindemail">'. JText::_("JEV_ATTEND_EMAIL").'</label>
				<input type="text" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  /><br/>';
			}
			else {
				$html .= '
				<label for="jevremindemail">'. JText::_("JEV_ATTEND_EMAIL").'</label>
				<input type="text" name="jevremindemail" id="jevremindemail" value="" size="50" /><br/>';
				if ($this->emailaddress!=""){
					$html .= '<input type="hidden" name="em" id="em" value="'.$code.'" />';
				}
			}
		}
		$html .='<label for="jevremind">'. JText::_("JEV_REMIND_THIS_REPEAT").'</label>
    <input type="checkbox" name="jevremind" value="1"  onclick="'.$checkemail.'form.submit();" '.($this->reminded?"checked='checked'":"").'/>
    <input type="hidden" name="jevremind_hidden" value="1" />
    <noscript><input type="submit" name="submit" value="'.JText::_("JEV_CONFIRM").'" /></noscript>
    <input type="hidden" name="Itemid" value="'.JRequest::getInt("Itemid",0).'"/>
</form>';

	}

	if ($this->jomsocial && $html!=""){
		$html =  '<div class="cModule jevremindform"><h3><span>'.JText::_("JEV_REMIND_ME").'</span></h3>'. $html."</div>";
	}
	$this->row->rsvp_reminderform = $html;
}



echo $html;
