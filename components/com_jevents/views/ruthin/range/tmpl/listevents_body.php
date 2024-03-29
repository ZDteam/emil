<?php 
defined('_JEXEC') or die('Restricted access');

$data = $this->data;

$Itemid = JEVHelper::getItemid();

echo "<div id='cal_title'>". JText::_('JEV_EVENTSFOR') ."</div>\n";
?>
<table align="center" width="90%" cellspacing="0" cellpadding="5" class="ev_table jev_<?php echo $this->colourscheme;?>">
    <tr valign="top">
        <td colspan="2"  align="center" class="cal_td_daysnames  cal_td_daysnames_<?php echo $this->colourscheme;?>  jev_<?php echo $this->colourscheme;?>">
           <!-- <div class="cal_daysnames"> -->
            <?php echo JEventsHTML::getDateFormat( $this->startyear, $this->startmonth, $this->startday, 1 )  ;?>
            &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
            <?php echo JEventsHTML::getDateFormat( $this->endyear, $this->endmonth, $this->endday, 1 ) ;?>
            <!-- </div> -->
        </td>
    </tr>
    <?php
    $num_events = count($data['rows']);
    $chdate ="";
    if( $num_events > 0 ){

    	for( $r = 0; $r < $num_events; $r++ ){
    		$row = $data['rows'][$r];

    		$event_day_month_year 	= $row->dup() . $row->mup() . $row->yup();
    		// Ensure we reflect multiday setting
    		if (!$row->eventOnDate(JevDate::mktime(0,0,0,$row->mup(),$row->dup(),$row->yup()))) continue;

    		if(( $event_day_month_year <> $chdate ) && $chdate <> '' ){
    			echo '</ul></td></tr>' . "\n";
    		}

    		if( $event_day_month_year <> $chdate ){
    			$date =JEventsHTML::getDateFormat( $row->yup(), $row->mup(), $row->dup(), 1 );
    			echo '<tr><td class="ev_td_left">'.$date.'</td>' . "\n";
    			echo '<td align="left" valign="top" class="ev_td_right"><ul class="ev_ul">' . "\n";
    		}

    		$listyle = 'style="border-color:'.$row->bgcolor().';"';
    		echo "<li class='ev_td_li' $listyle>\n";
    		if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
    			$this->viewEventRowNew ( $row,'view_detail',JEV_COM_COMPONENT, $Itemid);
    		}
    		echo "</li>\n";

    		$chdate = $event_day_month_year;
    	}
    	echo "</ul></td></tr>\n";
    } 
    echo '</table><br />' . "\n";
    echo '</fieldset><br /><br />' . "\n";

    // Create the pagination object
    if ($data["total"]>$data["limit"]){
    	$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
    }
