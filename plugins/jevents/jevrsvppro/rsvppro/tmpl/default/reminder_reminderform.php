<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );

$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress));

$html="";
$user=JFactory::getUser();

JPluginHelper::importPlugin("rsvppro");
$dispatcher =& JDispatcher::getInstance();
$results = $dispatcher->trigger('onGetReminderForm', array ( & $html, $this));

foreach ($results as $result){ 
	// one of the plugins has blocked the remidner so output the HTML and return
	if (!$result){		
		echo $html;
		return;
	}
}

if ($user->id==0 && !$this->params->get("remindemails",0) && $this->params->get("reminders",0)){
	$this->row->rsvp_reminderform = JText::_( 'JEV_LOGIN_TO_REQUEST_REMINDER' );
	if ($this->jomsocial && $this->row->rsvp_reminderform!=""){
		$this->row->rsvp_reminderform =  '<div class="cModule jevremindform"><h3><span>'.JText::_( 'JEV_REMIND_ME' ).'</span></h3>'. $this->row->rsvp_reminderform."</div>";
	}
	$html = $this->row->rsvp_reminderform;
}
else if ($this->params->get("reminders",0)){

	$Itemid=JRequest::getInt("Itemid");
	list($year,$month,$day) = JEVHelper::getYMD();
	//$link = $this->row->viewDetailLink($year,$month,$day,false, $Itemid);
	$eventid = intval($this->row->ev_id());
	$rp_id = intval($this->row->rp_id());
	$link = "index.php?option=com_rsvppro&task=reminders.record&eventid=".$eventid."&rp_id=".$rp_id."&Itemid=$Itemid";
	if (JRequest::getCmd("tmpl","")=="component"){
		$link .= "&tmpl=component";
	}
	$link = JRoute::_($link  ,false);

	// make messages translateable
	$trans = JText::_("JEV_COULD_NOT_RECORD_REMINDER", true);
	if (strpos($trans, "JEV_")!==false){
		$trans = "Could not record reminder";
	}
        $script = "JevRsvpLanguage.strings['JEV_COULD_NOT_RECORD_REMINDER']='" .  $trans . "';";
        $document = JFactory::getDocument();
        $document->addScriptDeclaration($script);
	
	
	if ($this->rsvpdata->remindallrepeats){
		$html ='
<form action="'.$link.'"  method="post"  name="jevreminderform"   id="jevreminderform">';
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
				<div class="jevremindemail">
				<label for="jevremindemail">'. JText::_( 'JEV_ATTEND_EMAIL' ).'</label>
				<input type="text" name="jevattend_email" id="jevattend_email" value="" size="50" /><br/>
				<input type="hidden" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />';
				if ($this->emailaddress!=""){
					$html .= '<input type="hidden" name="em" id="em" value="'.$code.'" />';
				}
				$html .= '</div>';
			}
			$html .= '<input type="hidden" name="em" id="em" value="' . $code . '" />';
		}
		$html .='<label for="jevremind">'. (($this->row->hasrepetition() && $this->rsvpdata->remindallrepeats==2)?JText::_( 'JEV_REMIND_ME_ALL_REPEATS' ):JText::_( 'JEV_REMIND_ME_THIS_EVENT' )).'</label>
    <input type="checkbox" name="jevremind" value="1" onclick="'.$checkemail.'updateReminder();" '.($this->reminded?"checked='checked'":"").'/>
    <input type="hidden" name="jevremind_hidden" value="1" />    
    <noscript>
		<input type="text" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />
		<input type="submit" name="submit" value="'.JText::_( 'JEV_CONFIRM' ).'" />
	</noscript>
</form>';
	}
	// or just this repeat
	else if ($this->row->hasrepetition()){
		$html ='
<form action="'.$link.'"  method="post"  name="jevreminderform"   id="jevreminderform">';
		$checkemail = "";
		if ($this->params->get("remindemails",0) && $user->id==0 ) {
			$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
			$checkemail  = "if (document.getElementById('jevattend_email').value=='') {alert('".JText::_("JEV_MISSING_EMAIL",true)."');return false};document.getElementById('jevremindemail').value=document.getElementById('jevattend_email').value;";
			$document = JFactory::getDocument();
			$script = <<<SCRIPT
window.addEvent("domready",function(){
	var form = document.updateattendance;
	if (form){
		$(form).addEvent('submit',function(event){ $checkemail });
	};
});
SCRIPT;
			$document->addScriptDeclaration($script);
			
			// resuse the email address from the attendance form - link via javascript to attendance email address
			$registry	=& JRegistry::getInstance("jevents");
			if ($registry->get("showingemailaddress",false)){
				$html .= '
				<div class="jevremindemail">
				<label for="jevremindemail">'. JText::_( 'JEV_ATTEND_EMAIL' ).'</label>
				<input type="text" name="jevremindemail" id="jevremindemail" value="'.$this->emailaddress.'"  />
				</div>';
			}
			else {
				$html .= '
				<div class="jevremindemail">
				<label for="jevremindemail">'. JText::_( 'JEV_ATTEND_EMAIL' ).'</label>
				<input type="text" name="jevremindemail" id="jevremindemail" value="" size="50" /><br/>';
				if ($this->emailaddress!=""){
					$html .= '<input type="hidden" name="em" id="em" value="'.$code.'" />';
				}
				$html .= '</div>';
			}
			$html .= '<input type="hidden" name="em" id="em" value="' . $code . '" />';
		}
		$html .='<label for="jevremind">'. JText::_( 'JEV_REMIND_THIS_REPEAT' ).'
    <input type="checkbox" name="jevremind" value="1"  onclick="'.$checkemail.'updateReminder();" '.($this->reminded?"checked='checked'":"").'/></label>
    <input type="hidden" name="jevremind_hidden" value="1" />
    <noscript><input type="submit" name="submit" value="'.JText::_( 'JEV_CONFIRM' ).'" /></noscript>
</form>';

	}

	if ($this->jomsocial && $html!=""){
		$html =  '<div class="cModule jevremindform"><h3><span>'.JText::_( 'JEV_REMIND_ME' ).'</span></h3>'. $html."</div>";
	}
	$this->row->rsvp_reminderform = $html;
}



echo $html;
