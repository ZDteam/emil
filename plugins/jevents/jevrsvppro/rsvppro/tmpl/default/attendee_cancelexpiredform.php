<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

// Cancellation date has passed 
JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );

$html = "";
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

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now  = $jnow->toUnix();

// Tell the user they are attending only if cancellation is not allowed and they are attending
if (!$this->canCancel && $this->attending  && $this->attendee->attendstate==1){
	$html .= $this->loadTemplate("youareattending");
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
else if (($this->rsvpdata->allowcancellation || $this->rsvpdata->allowchanges ) && $this->attending  && ($this->attendee->attendstate==1 || $this->attendee->attendstate==4)){
	if ($this->templateParams){
		if (isset($this->templateParams->whentickets) && count($this->templateParams->whentickets)>0 && ($this->rsvpdata->allowcancellation || $this->rsvpdata->allowchanges )){
			$html .= $this->loadTemplate("ticket");
		}
	}
}

// Output any 'oppotunities to cancel gone' message here 
//$html .= "<h2>Oppotunities to cancel gone</h2>";

echo $html;
