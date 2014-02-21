<?php 
defined('_JEXEC') or die('Restricted access');

$data = $this->data;

$Itemid = JEVHelper::getItemid();

?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme;?>'>
	<div class="jev_toprow">
	    <div class="jev_header">
		  <h2><span><?php echo JText::_("JEV_DATE_RANGE_VIEW");?></span></h2>
		  <div class="today" >
			<span>
            <?php echo JEventsHTML::getDateFormat( $this->startyear, $this->startmonth, $this->startday, 1 )  ;?>
            &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
            <?php echo JEventsHTML::getDateFormat( $this->endyear, $this->endmonth, $this->endday, 1 ) ;?>
			</span>
		   </div>
		</div>
	    <div class="jev_header2">
			<div class="previousmonth" >
			</div>
			<div class="currentmonth">
            <?php echo JEventsHTML::getDateFormat( $this->startyear, $this->startmonth, $this->startday, 1 )  ;?>
            &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
            <?php echo JEventsHTML::getDateFormat( $this->endyear, $this->endmonth, $this->endday, 1 ) ;?>
			</div>
			<div class="nextmonth">
			</div>
			
		</div>
	</div>
    <div class="jev_clear" ></div>

<?php
$num_events = count($data['rows']);
$chdate ="";
if( $num_events > 0 ){
	
	for( $r = 0; $r < $num_events; $r++ ){
		$row = $data['rows'][$r];

		$event_day_month_year 	= $row->dup() . $row->mup() . $row->yup();
		// Ensure we reflect multiday setting
		if (!$row->eventOnDate(JevDate::mktime(0,0,0,$row->mup(),$row->dup(),$row->yup()))) continue;
		
		$Itemid = JRequest::getInt("Itemid");
		$link = JRoute::_( 'index.php?option='.JEV_COM_COMPONENT.'&task=day.listevents&year='.$row->yup().'&month='.$row->mup().'&day='.$row->dup().'&Itemid='.$Itemid );

		$datestp	= JevDate::mktime( 0, 0, 0,$row->mup(), $row->dup(), $row->yup() );
		$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$link, "",$row);
	
		$date =JEventsHTML::getDateFormat( $row->yup(), $row->mup(), $row->dup(), 1 );
		?>
	<div class="jev_listrow">
	
	<?php	
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

			$this->viewEventRowNEW ($row);
			echo "&nbsp;::&nbsp;";
			$this->viewEventCatRowNEW ($row);
		}
		echo "</div>";
		
	?>	
	</div>
	<?php }
}
?>
</div>
<div class="jev_clear" ></div>
<?php
// Create the pagination object
if ($data["total"]>$data["limit"]){
	$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
}
