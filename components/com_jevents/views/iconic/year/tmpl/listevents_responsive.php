<?php 
defined('_JEXEC') or die('Restricted access');

$cfg	 = & JEVConfig::getInstance();

// Note that using a $limit value of -1 the limit is ignored in the query
$this->data = $data = $this->datamodel->getYearData($this->year,$this->limit, $this->limitstart);

// previous and following month names and links
$followingYear = $this->getFollowingYear($this->year, $this->month, $this->day);
$precedingYear = $this->getPrecedingYear($this->year, $this->month, $this->day);

?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme;?>'>
	<div class="jev_toprow">
	    <div class="jev_header">
		  <h2><span><?php echo JText::_( 'YEARLY_VIEW' );?></span></h2>
		  <div class="today" ><span><?php echo $data["year"] ;?></span></div>
		</div>
	    <div class="jev_header2">
			<div class="previousmonth" >
		      	<?php if ($precedingYear) {
					echo "<a href='".$precedingYear."' title='".JText::_("PRECEEDING_Year")."' >". JText::_("PRECEEDING_Year")."</a>";
			}
			?>
			</div>
			<div class="currentmonth">
				<?php echo $data["year"] ;?>
			</div>
			<div class="nextmonth">
		      	<?php if ($followingYear) {
					echo "<a href='".$followingYear."' title='".JText::_("FOLLOWING_Year")."' >". JText::_("FOLLOWING_Year")."</a>";
				}
			?>
			</div>
			
		</div>
	</div>
    <div class="jev_clear" ></div>

<?php
for($month = 1; $month <= 12; $month++) {
	$num_events = count($data["months"][$month]["rows"]);
	if ($num_events>0){
		?>
		<div class="jev_daysnames jev_daysnames_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
	    <?php echo JEventsHTML::getDateFormat($this->year,$month,'',3);?>
		</div>
		<?php
		for ($r = 0; $r < $num_events; $r++) {
			if (!isset($data["months"][$month]["rows"][$r])) continue;
			$row =& $data["months"][$month]["rows"][$r];

			$Itemid = JEVHelper::getItemid();
			$cat = "";
			if ($this->datamodel->catidsOut != 0){
				$cat = '&catids='.$this->datamodel->catidsOut;
			}
			
			$datestp	= JevDate::mktime( 0, 0, 0,$row->mup(),$row->dup(),$row->yup());
			//$enddatestp = JevDate::mktime( 0, 0, 0,$row->mdn(),$row->ddn(), $row->ydn());
			//$days = JEV_CommonFunctions::jev_strftime("%d",$datestp) . " - " . JEV_CommonFunctions::jev_strftime("%d",$enddatestp);   
		         //$month  = JEV_CommonFunctions::jev_strftime("%b",$datestp);

			$lnk = JRoute::_( 'index.php?option='.JEV_COM_COMPONENT.'&task=day.listevents&year='.$row->yup().'&month='.$row->mup().'&day='.$row->dup().'&Itemid='.$Itemid . $cat);
			//$day_link = $this->dateicon(array($days,$month),JText::_('JEV_CLICK_TOSWITCH_DAY'),$link, "", $row);
			$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$lnk, "",$row);
			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
			if ($params->get("colourbar",0)) $listyle = 'style="border-color:'.$row->bgcolor().';"';
			else $listyle = 'style="border:none"';
			?>
			<div class="jev_listrow">
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
			echo '</div>';
		}
	}

}
?>
</div>
<div class="jev_clear" ></div>
<?php
$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
