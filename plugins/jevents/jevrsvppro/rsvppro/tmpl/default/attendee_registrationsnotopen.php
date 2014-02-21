<?php
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

if ($this->jomsocial){
	echo  '<div class="cModule jevattendform"><h3><span>'.JText::_( 'JEV_ATTEND_THIS_EVENT' ).'</span></h3>'. JText::_("JEV_REGISTRATIONS_NOT_YET_OPEN")."</div>";
}
else {
	echo  JText::_("JEV_REGISTRATIONS_NOT_YET_OPEN");
}