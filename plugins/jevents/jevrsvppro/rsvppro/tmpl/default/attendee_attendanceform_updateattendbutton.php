<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$html ='
	<input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	<input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	<input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
	<br/>';

if (isset($this->attendee) && $this->attendee !== false)
{
	$html .='
		<input type="submit" value="' . JText::_( 'JEV_CLICK_TO_UPDATE' ) . '" onclick="' . $this->checkemail . '" id="jevattendsubmit" />';
}
else
{
	// should we show click to attend button of click to submit button
	$attendyes = $this->loadTemplate("attendanceform_attendyes");
	$attendno = $this->loadTemplate("attendanceform_attendno");
	$attendmaybe = $this->loadTemplate("attendanceform_attendmaybe");

	// this is scenario where no checkboxes are input
	if ($attendmaybe || $attendno || $this->attendstate==-1){
		$action  = JText::_("JEV_CLICK_TO_SUBMIT");
	}
	else {
		$action  = JText::_("JEV_CLICK_TO_ATTEND");
	}

	$style = "";
	if ($this->attendstate == -1)
	{
		$style = 'style="display:none"';
	}
	$html .='
		<input type="submit" value="' . $action . '" onclick="' . $this->checkemail . '" ' . $style . ' id="jevattendsubmit" />';
}

echo $html;