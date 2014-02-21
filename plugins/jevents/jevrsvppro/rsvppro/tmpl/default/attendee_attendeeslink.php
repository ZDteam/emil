<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$user = JFactory::getUser();
jimport('joomla.utilities.date');

$repeating = $this->rsvpdata->allrepeats;
$rp_id = $repeating ? 0 : intval($this->row->rp_id());
$atd_id = intval($this->rsvpdata->id);
$Itemid = JRequest::getInt("Itemid",1);
$link = "index.php?option=com_rsvppro&task=attendees.list&atd_id[]=$atd_id|$rp_id&Itemid=$Itemid&repeating=$repeating&limit=-10";
$link = JRoute::_($link);
	
$html = '<div class="rsvp_attendeeslink"><a href="' . $link . '" title="'.JText::_( 'RSVP_VIEW_ATTENDEE_LIST' ).'" target="_blank">' . JText::_( 'JEV_ATTENDEES' ) . '</a></div>';

echo $html;