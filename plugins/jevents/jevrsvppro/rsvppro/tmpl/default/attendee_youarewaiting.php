<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

echo "<div class='jevwaitinglist' style='font-weight:bold;color:red;'>".JText::_( 'JEV_EVENT_YOU_ARE_WAITING' )."</div>";

$registry = & JRegistry::getInstance("jevents");
$registry->set("attendeeIsWaiting",true);