<?php 
defined('_JEXEC') or die('Restricted access');

$cfg	 = & JEVConfig::getInstance();

$data = $this->datamodel->getDayData( $this->year, $this->month, $this->day );
$this->data = $data;
$this->Redirectdetail();

$cfg = & JEVConfig::getInstance();
$Itemid = JEVHelper::getItemid();

// previous and following month names and links
$followingDay = $this->datamodel->getFollowingDay($this->year, $this->month, $this->day);
$precedingDay = $this->datamodel->getPrecedingDay($this->year, $this->month, $this->day);

?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme;?>'>
	<div class="jev_toprow">
	    <div class="jev_header">
		  <h2><span><?php echo JText::_( 'DAILY_VIEW' );?></span></h2>
		  <div class="today" ><span><?php echo JEventsHTML::getDateFormat( $this->year, $this->month, $this->day, 0) ;?></span></div>
		</div>
	    <div class="jev_header2">
			<div class="previousmonth" >
		      	<?php if ($precedingDay) echo "<a href='".$precedingDay."' title='".JText::_("PRECEEDING_Day")."' >".JText::_("PRECEEDING_Day")."</a>";?>
			</div>
			<div class="currentmonth">
				<?php echo JEventsHTML::getDateFormat( $this->year, $this->month, $this->day, 0) ;?>
			</div>
			<div class="nextmonth">
		      	<?php if ($followingDay) echo "<a href='".$followingDay."' title='".JText::_("FOLLOWING_Day")."' >". JText::_("FOLLOWING_Day")."</a>";?>
			</div>
			
		</div>
	</div>
    <div class="jev_clear" ></div>

    <?php
    // Timeless Events First
    if (count($data['hours']['timeless']['events'])>0){
    	$start_time = JText::_( 'TIMELESS' );

    	foreach ($data['hours']['timeless']['events'] as $row) {
    		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
    		if ($params->get("colourbar",0)) $listyle = 'style="border-color:'.$row->bgcolor().';"';
    		else $listyle = 'style="border:none"';
			?>
			<div class="jev_listrow">
	   			<div class="jevleft jevleft_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
	    		<?php 
	    		echo $this->dateicon(array("&nbsp;","&nbsp;"),"","","",$row);
		    	?>
				</div>
				<?php
				echo "<div  class='jevright' $listyle>";

				if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
					$this->viewEventRowNew ( $row);
					echo '&nbsp;::&nbsp;';
					$this->viewEventCatRowNew($row);
				}
				echo "</div>";
				echo "</div>";
    	}
    }

    for ($h=0;$h<24;$h++){
    	if (count($data['hours'][$h]['events'])>0){

    		foreach ($data['hours'][$h]['events'] as $row) {
	    		$start_time = JEVHelper::getTime($row->getUnixStartTime());
    			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
    			if ($params->get("colourbar",0)) $listyle = 'style="border-color:'.$row->bgcolor().';"';
    			else $listyle = 'style="border:none"';
				?>
				<div class="jev_listrow">
    			<div class="jevleft jevleft_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
		    		<?php 		    		
		    		echo $this->dateicon(array($start_time,"&nbsp"),"","","jevdayicon",$row);
		    		?>
				</div>
				<?php
				echo "<div  class='jevright' $listyle>";
				if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
					$this->viewEventRowNew ( $row);
					echo '&nbsp;::&nbsp;';
					$this->viewEventCatRowNew($row);
				}
				echo "</div>";
				echo "</div>";
    		}
    	}
    }
	?>
</div>
<div class="jev_clear" ></div>

