<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$showno = false;
if ($this->params->get("allowno", 0)){
	$showno = true;
}
// can cancel and is, maybe or paid attendee
if ($this->rsvpdata->allowcancellation && $this->attending && $this->attendee->attendstate >0 ) {
	$showno = true;
}
// if can cancel AND change and NOT attending then allow option to reattend
if ($this->rsvpdata->allowcancellation && $this->rsvpdata->allowchanges && $this->attending && $this->attendee->attendstate == 0 ) {
	$showno = true;
}

if ($this->params->get("cancellation_unpaid_only") && isset( $this->attendee->outstandingBalances) && $this->attendee->outstandingBalances["feebalance"]<=0) {
	$showno = false;
}

echo $showno ?  '<label for="jevattend_no"><input type="radio" name="jevattend" id="jevattend_no" value="0"  ' . ($this->attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_( 'JEV_ATTEND_NO' ) . '</label>' : '';