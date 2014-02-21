<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$attendyes = $this->loadTemplate("attendanceform_attendyes");
$attendno = $this->loadTemplate("attendanceform_attendno");
$attendmaybe = $this->loadTemplate("attendanceform_attendmaybe");

// this is scenario where no checkboxes are input
if ($attendmaybe || $attendno || $this->attendstate==-1){
	if ($this->rsvpdata->allrepeats) {
	echo '<strong>' . ($this->row->hasrepetition() ? JText::_('JEV_ATTEND_ALL_REPEATS') : JText::_('JEV_ATTEND_THIS_EVENT')) . '</strong>' .			
 $attendyes . $attendno . $attendmaybe
 . '<br/>';
	}
	else {
	echo '<strong>' . JText::_('JEV_ATTEND_THIS_REPEAT') . '</strong>' .			
 $attendyes . $attendno . $attendmaybe
 . '<br/>';		
	}
}
else if ($this->attendstate==1 || $this->attendstate==4 || $this->attendstate==3 ){
	echo "<span style='display:none'>".$attendyes."</span>";
}
else {
	echo "<span>".$attendyes."</span>";
}
