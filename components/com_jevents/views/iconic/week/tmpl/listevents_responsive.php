<?php 
defined('_JEXEC') or die('Restricted access');

$cfg = & JEVConfig::getInstance();
$option = JEV_COM_COMPONENT;
$Itemid = JEVHelper::getItemid();

$compname = JEV_COM_COMPONENT;
$viewname = $this->getViewName();
$viewpath = JURI::root() . "components/$compname/views/".$viewname."/assets";
$viewimages = $viewpath . "/images";

$view =  $this->getViewName();

$this->data = $data = $this->datamodel->getWeekData($this->year, $this->month, $this->day);

// previous and following month names and links
$followingWeek = $this->datamodel->getFollowingWeek($this->year, $this->month, $this->day);
$precedingWeek = $this->datamodel->getPrecedingWeek($this->year, $this->month, $this->day);

?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme;?>'>
	<div class="jev_toprow">
	    <div class="jev_header">
		  <h2><span><?php echo JText::_( 'WEEKLY_VIEW' );?></span></h2>
		  <div class="today" ><span><?php echo  $data['startdate'] . ' - ' . $data['enddate'] ;?></span></div>
		</div>
	    <div class="jev_header2">
			<div class="previousmonth" >
		      	<?php if($precedingWeek) echo "<a href='".$precedingWeek."' title='".JText::_("PRECEEDING_Week")."' >". JText::_("PRECEEDING_Week")."</a>";?>
			</div>
			<div class="currentmonth">
				<?php echo  $data['startdate'] . ' - ' . $data['enddate'] ;?>
			</div>
			<div class="nextmonth">
		      	<?php if ($followingWeek) echo "<a href='".$followingWeek."' title='".JText::_("FOLLOWING_Week")."' >". JText::_("FOLLOWING_Week");?></a>
			</div>
			
		</div>
	</div>
    <div class="jev_clear" ></div>
		
<?php
// version with separate day icons per event
for( $d = 0; $d < 7; $d++ ){
	$num_events	= count($data['days'][$d]['rows']);
	if ($num_events==0) continue;

	$datestp	= JevDate::mktime( 0, 0, 0,$data['days'][$d]['week_month'], $data['days'][$d]['week_day'], $data['days'][$d]['week_year'] );
	for( $r = 0; $r < $num_events; $r++ ){
		?>
		<div class="jev_listrow">
		<?php
		$row = $data['days'][$d]['rows'][$r];
		$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$data['days'][$d]['link'], "",$row);
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		if ($params->get("colourbar",0)) $listyle = 'style="border-color:'.$row->bgcolor().';"';
		else $listyle = 'style="border:none"';
		?>
		<div class="jevleft jevleft_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
		    <?php echo $day_link;?>
		</div>
		<?php
		echo "<div  class='jevright' $listyle>";
		if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
			$this->viewEventRowNew ( $row);
			echo "&nbsp;::&nbsp;";
			$this->viewEventCatRowNew($row);
		}
		echo "</div>";
		echo '</div>' . "\n";
	}
} // end for days 

// version that groups events by day
/*
for( $d = 0; $d < 7; $d++ ){
	$num_events	= count($data['days'][$d]['rows']);
	if ($num_events==0) continue;

	$datestp	= JevDate::mktime( 0, 0, 0,$data['days'][$d]['week_month'], $data['days'][$d]['week_day'], $data['days'][$d]['week_year'] );
	?>
	<div class="jev_listrow">
		<div class="jevleft jevleft_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
		    <?php echo $day_link;?>
		</div>
	<?php
	for( $r = 0; $r < $num_events; $r++ ){
		$row = $data['days'][$d]['rows'][$r];
		$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$data['days'][$d]['link'], "",$row);
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		if ($params->get("colourbar",0)) $listyle = 'style="border-color:'.$row->bgcolor().';"';
		else $listyle = 'style="border:none"';
		echo "<div  class='jevright' $listyle>";
		if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
			$this->viewEventRowNew ( $row);
			echo "&nbsp;::&nbsp;";
			$this->viewEventCatRowNew($row);
		}
		echo "</div>";
	}
	echo '</div>' . "\n";
} // end for days
*/
?>			
</div>
<div class="jev_clear" ></div>
	