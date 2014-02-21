<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
JHtml::script( 'components/com_rsvppro/assets/js/tabs.js');
$script = "var urlroot = '".JURI::root()."';\n";
$script .= "var jsontoken = '".JSession::getFormToken()."';\n";

$document = JFactory::getDocument();
$document->addScriptDeclaration($script);

$html = "";
$user=JFactory::getUser();

$Itemid=JRequest::getInt("Itemid");

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

if (!$this->templateParams) return "";

// Is this a block overlaps sesssion - if so need to make sure the attendee is not already attending an overlapping session
if (isset($this->templateParams->blockoverlaps) && $this->templateParams->blockoverlaps){
	if ($this->attending  && isset($this->attendee) && $this->attendee->attendstate!=0) {
		return "attending";
	}
}

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now  = $jnow->toUnix();

// We see if regisrations are open
// if attendance tracked for the event as a whole then must compare the time of the start of the event
if ($this->rsvpdata->allrepeats ){
	$regclose = $this->rsvpdata->regclose=="0000-00-00 00:00:00"?$this->row->dtstart():strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen=="0000-00-00 00:00:00"?strtotime("-1 year"):strtotime($this->rsvpdata->regopen);
	if ($now > $regclose) {
		echo  $html . $this->loadTemplate("registrationsclosed") . $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $regopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen") . $this->loadTemplate("emptyattendanceform");
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
		echo  $html . $this->loadTemplate("registrationsclosed") . $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $adjustedregopen) {
		echo  $html . $this->loadTemplate("registrationsnotopen") . $this->loadTemplate("emptyattendanceform");
		return;
	}
}

$html = "This is an overlapping event";
echo $html;
