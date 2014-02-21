<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
JHtml::script( 'components/com_rsvppro/assets/js/tabs.js');

$html = "";
$user=JFactory::getUser();

$Itemid=JRequest::getInt("Itemid");
//list($year,$month,$day) = JEVHelper::getYMD();
//$link = $this->row->viewDetailLink($year,$month,$day,false, $Itemid);
$rp_id = intval($this->row->rp_id());
$atd_id = intval($this->rsvpdata->id);
$link = "index.php?option=com_rsvppro&task=attendees.record&at_id=$atd_id&rp_id=$rp_id&Itemid=$Itemid";
if (JRequest::getCmd("tmpl","")=="component"){
	$link .= "&tmpl=component";
}

// Do we need the email address security code?
if ($this->emailaddress!=""){
	$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
	$link = $link."&em=".$code;
}
$link = JRoute::_($link, false);
$this->assign("link",$link);

$db= JFactory::getDBO();

// Until we incorporate registration deadline we stop registrations from the time the event starts
jimport('joomla.utilities.date');

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now  = $jnow->toUnix();

if (!isset($this->templateInfo )) {
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) &&  $xmlfile>0){
		$db = JFactory::getDbo();
		$db->setQuery("Select * from #__jev_rsvp_templates where id=" . intval($xmlfile));
		$this->templateInfo = $db->loadObject();
		if ($this->templateInfo){
			$this->templateParams = $this->templateInfo->params;
			$this->templateParams = json_decode($this->templateParams);
		}
		else {
			$this->templateParams= false;
		}		
	}	
	else {
		$this->templateParams= false;
	}
}

// Is the user waiting
if ($this->attendee->waiting){
	if ($this->templateParams && isset($this->templateParams->waitingnopay) && $this->templateParams->waitingnopay){
		$html .= $this->loadTemplate("youarewaiting");	
	}
}

// Tell the user they are attending only if cancellation is not allows and they are attending
if (!$this->rsvpdata->allowcancellation && $this->attending  && $this->attendee->attendstate==1){
	$html .= $this->loadTemplate("youareattending");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==2){
	$html .= $this->loadTemplate("youmaybeattending");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==3){
	$html .= $this->loadTemplate("awaitingconfirmation");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==4){
	$html .= $this->loadTemplate("awaitingpayment");
}
else  if (($this->rsvpdata->allowcancellation || $this->rsvpdata->allowchanges ) && $this->attending  && ($this->attendee->attendstate==1 || $this->attendee->attendstate==4)){
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile)){
		if ($this->templateParams && isset($this->templateParams->whentickets) && count($this->templateParams->whentickets)>0){
			$html .= $this->loadTemplate("ticket");
		}
	}
}


// if we need the payment form or repayment form then display these instead.
if (JRequest::getInt("paymentform",0)==1 || JRequest::getInt("repaymentform",0)==1 ){
	if (isset($this->attendeeParams->outstandingBalances["feebalance"]) && floatval($this->attendeeParams->outstandingBalances["feebalance"])!=0){
		echo $html. $this->loadTemplate("emptyattendanceform");
		return;
	}
}

// We see if regisrations are open
// if attendance tracked for the event as a whole then must compare the time of the start of the event
if ($this->rsvpdata->allrepeats ){
	$regclose = $this->rsvpdata->regclose=="0000-00-00 00:00:00"?$this->row->dtstart():strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen=="0000-00-00 00:00:00"?strtotime("-1 year"):strtotime($this->rsvpdata->regopen);
	if ($now > $regclose) {
		echo  $html . $this->loadTemplate("registrationsclosed"). $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $regopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen"). $this->loadTemplate("emptyattendanceform");
		return;
	}
}
// otherwise the start of the repeat
else {
	$regclose = $this->rsvpdata->regclose=="0000-00-00 00:00:00"?$this->row->dtstart():strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen=="0000-00-00 00:00:00"?strtotime("-1 year"):strtotime($this->rsvpdata->regopen);
	$eventstart = $this->row->dtstart();
	$repeatstart = $this->row->getUnixStartTime();
	$adjustedregclose = $regclose + ($repeatstart - $eventstart);
	$adjustedregopen = $regopen + ($repeatstart - $eventstart);
	if ($now >$adjustedregclose){
		echo  $html . $this->loadTemplate("registrationsclosed"). $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $adjustedregopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen"). $this->loadTemplate("emptyattendanceform");
		return;
	}
}

// if there is an intro to the form display it here:
if ($this->rsvpdata->attendintro !=""){
	$html .= $this->loadTemplate("intro");
}

// if tracking capacity find how many spaces are used up/left
if ($this->params->get("capacity",0) && $this->rsvpdata->capacity>0) {

	$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=".$this->rsvpdata->id;
	if (!$this->rsvpdata->allrepeats){
		$sql .= " and a.rp_id=".$this->row->rp_id();
	}
	$db->setQuery($sql);
	$attendeeCount = $db->loadResult();

	if ($attendeeCount>=$this->rsvpdata->capacity){

		// I need the attendance form if I'm administering and attending the event otherwise I can't cancel attendees!
		if ($user->id==$this->row->created_by() || JEVHelper::isAdminUser($user) || $this->attending){
			$html .= $this->loadTemplate("eventfull");
		}
		else {
			$html .= $this->loadTemplate("eventfull");
			if ($attendeeCount<$this->rsvpdata->capacity + $this->rsvpdata->waitingcapacity){
				$html .= $this->loadTemplate("waitinglist");
			}
			else {
				if ($this->jomsocial && $html!=""){
					$html = '<div class="cModule jevattendform"><h3><span>'.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. $html."</div>";
				}
				echo $html. $this->loadTemplate("emptyattendanceform");
				return;
			}
		}
	}
	else {
		$this->assign("attendeeCount",$attendeeCount);
		$html .=  $this->loadTemplate("capacityremaing");
	}
}

if ($this->rsvpdata->allrepeats){
	$html .=  $this->loadTemplate("changeform_single");
}
// or just this repeat
else if ($this->row->hasrepetition()){
	$html .=  $this->loadTemplate("changeform_repeating");
}


if ($this->jomsocial && $html!=""){
	$html = '<div class="cModule jevattendform"><h3><span>'.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. $html."</div>";
}

echo $html;
