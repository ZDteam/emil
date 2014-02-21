<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );


$capacity = $this->rsvpdata->capacity-$this->attendeeCount;

echo "<div class='jevcapacity'>".JText::sprintf("JEV_CAPACITY_REMAINING",$capacity)."</div>";