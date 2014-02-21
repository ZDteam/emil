<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

// special modal enlargement version
ob_start();
?>
function enlargeAttendForm() {
	$('largejevattendform').style.display='block';
	$('smalljevattendform').style.display='none';
}
function shrinkAttendForm() {
	$('largejevattendform').style.display='none';
	$('smalljevattendform').style.display='block';
}
<?php
$script = ob_get_clean();
$document = JFactory::getDocument();
$document->addScriptDeclaration($script);

ob_start();
?>
#jevmodalAttendForm {position:relative;margin:0px;padding:0px;}
#largejevattendform {position:absolute!important;right:0px;top:0px;display:none;z-Index:99;background-color:#fff;min-width:650px;min-height:200px;}
#smalljevattendform {}
.jevenlarge {float:right;}
.jevshrink {float:right;}
<?php
$css = ob_get_clean();
$document = JFactory::getDocument();
$document->addStyleDeclaration($css);

$script = "var urlroot = '".JURI::root()."';\n";
$script .= "var jsontoken = '".JSession::getFormToken()."';\n";
$document->addScriptDeclaration($script);

$html = "";
$user=JFactory::getUser();

$Itemid = JRequest::getInt("Itemid");
//list($year,$month,$day) = JEVHelper::getYMD();
//$link = $this->row->viewDetailLink($year,$month,$day,false, $Itemid);

$rp_id = intval($this->row->rp_id());
$atd_id = intval($this->rsvpdata->id);
$Itemid = JRequest::getInt("Itemid",1);
$link = "index.php?option=com_rsvppro&task=attendees.record&at_id=$atd_id&rp_id=$rp_id&Itemid=$Itemid";
if (JRequest::getCmd("tmpl","")=="component"){
	$link .= "&tmpl=component";
}

// Do we need the email address security code?
if ($this->emailaddress!=""){
	$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
	$link = $link."&em=".$code;
}
$link = JRoute::_($link);
$this->assign("link",$link);

$db= JFactory::getDBO();

// Until we incorporate registration deadline we stop registrations from the time the event starts
jimport('joomla.utilities.date');

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now  = $jnow->toUnix();

// Tell the user they are attending if they are attending
if ($this->attending){
	$html = $this->loadTemplate("youareattending");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==2){
	$html = $this->loadTemplate("youmaybeattending");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==3){
	$html = $this->loadTemplate("awaitingconfirmation");
}
else if(isset($this->attendee->attendstate) && $this->attendee->attendstate==4){
	$html = $this->loadTemplate("awaitingpayment");
}

// if we need the payment form or repayment form then display these instead.
if (JRequest::getInt("paymentform",0)==1 || JRequest::getInt("repaymentform",0)==1 ){
	if (isset($this->attendeeParams->outstandingBalances["feebalance"]) && floatval($this->attendeeParams->outstandingBalances["feebalance"])!=0){
		echo $html;
		return;
	}
}

// We see if regisrations are open
// if attendance tracked for the event as a whole then must compare the time of the start of the event
if ($this->rsvpdata->allrepeats ){
	$regclose = $this->rsvpdata->regclose=="0000-00-00 00:00:00"?$this->row->dtstart():strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen=="0000-00-00 00:00:00"?strtotime("-1 year"):strtotime($this->rsvpdata->regopen);
	if ($now > $regclose) {
		echo  $html . $this->loadTemplate("registrationsclosed");
		return;
	}
	else if ($now < $regopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen");
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
		echo  $html . $this->loadTemplate("registrationsclosed");
		return;
	}
	else if ($now < $adjustedregopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen");
		return;
	}
}


$modalhtml = $html;

// if there is an intro to the form display it here:
if ($this->rsvpdata->attendintro !=""){
	$modalhtml .= $this->loadTemplate("intro");
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
			$modalhtml .= $this->loadTemplate("eventfull");
		}
		else {
			$modalhtml .= $this->loadTemplate("eventfull");
			if ($attendeeCount<$this->rsvpdata->capacity + $this->rsvpdata->waitingcapacity){
				$modalhtml .= $this->loadTemplate("waitinglist");
			}
			else {
				if ($this->jomsocial && $modalhtml!=""){
					$modalhtml = '<div class="cModule jevattendform"><h3><span>'.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. $modalhtml."</div>";
				}
				echo $modalhtml;
				return;
			}
		}
	}
	else {
		$this->assign("attendeeCount",$attendeeCount);
		$modalhtml .=  $this->loadTemplate("capacityremaing");
	}
}
else {
		$this->assign("attendeeCount",0);	
}

if ($this->rsvpdata->allrepeats){
	$modalhtml .=  $this->loadTemplate("attendanceform_single");
}
// or just this repeat
else if ($this->row->hasrepetition()){
	$modalhtml .=  $this->loadTemplate("attendanceform_repeating");
}

if ($this->jomsocial && $modalhtml!=""){
	$shrink = "<a href='#' onclick='shrinkAttendForm();return false;' class='jevshrink'>[-]</a>";
	$modalhtml = '<div class="cModule" id="largejevattendform"><h3><span>'.$shrink.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. $modalhtml."</div>";
}

// I can't do it this way since the calendar field type will call the js initialiser and break!
//require_once JPATH_SITE.'/plugins/jevents/rsvppro/jsonwrapper.php';
//$document->addScriptDeclaration('var modalhtml='.json_encode($modalhtml).';');
if ( $modalhtml!=""){
	$modalhtml =  "<div id='jevmodalAttendForm'>".$modalhtml."</div>";
}


if ($this->jomsocial ){
	$enlarge = "<a href='#' onclick='enlargeAttendForm();return false;' class='jevenlarge'>[+]</a>";
	$html = $modalhtml.'<div class="cModule jevattendform"  id="smalljevattendform"><h3><span>'.$enlarge.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. $html."</div>";
}
else {
	$html = $html . $modalhtml;
}

echo $html;
