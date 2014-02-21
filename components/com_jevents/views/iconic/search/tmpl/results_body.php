<?php 
defined('_JEXEC') or die('Restricted access');

$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
$useRegX = intval($params->get("regexsearch",0));
$this->data = $data = $this->datamodel->getKeywordData($this->keyword, $this->limit, $this->limitstart, $useRegX);

$Itemid = JEVHelper::getItemid();

$searchisValid =true;

$chdate	= '';
?>
<div id='jev_maincal' class='jev_listview'>
	<div class="jev_toprow jev_toprowcat">
	    <div class="jev_header jev_headercat">
		  <h2><?php echo JText::_("JEV_SEARCHRESULTS");?></h2>
		</div>
	</div>
    <div class="jev_clear" ></div>

	<div class="jev_listrow">
	   <div class='jev_catdesc'>
		<form action="<?php echo JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=search.results&Itemid=".$this->Itemid);?>" method="post" style="font-size:1;">
			<input type="hidden" name="popup" value="<?php echo JRequest::getInt("pop",0);?>" />
			<?php if (JRequest::getString("tmpl","")=="component"){
				echo '<input type="hidden" name="tmpl" value="component" />';
			} ?>
			<input type="text" name="keyword" size="30" maxlength="50" class="inputbox" value="<?php echo $this->keyword;?>" />
			<label for="showpast"><?php echo JText::_("JEV_SHOW_PAST");?></label>
			<input type="checkbox" id="showpast" name="showpast" value="1" <?php echo JRequest::getInt('showpast',0)?'checked="checked"':''?> />
			<input type="hidden" name="pop" value="<?php echo JRequest::getInt("pop",0);?>" />
			<input class="button" type="submit" name="push" value="<?php echo JText::_('JEV_SEARCH_TITLE'); ?>" />	
		</form>
		</div>
	</div>
	<?php

	if( $data['num_events'] > 0 ){
		for( $r = 0; $r < $data['num_events']; $r++ ){
			$row = $data['rows'][$r];

			$Itemid = JRequest::getInt("Itemid");
			$link = JRoute::_( 'index.php?option='.JEV_COM_COMPONENT.'&task=day.listevents&year='.$row->yup().'&month='.$row->mup().'&day='.$row->dup().'&Itemid='.$Itemid );

			$datestp	= JevDate::mktime( 0, 0, 0,$row->mup(), $row->dup(), $row->yup() );
			$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$link,"",$row);

			echo '<div class="jev_listrow">' . "\n";
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
				$this->viewEventRowNew ( $row,'view_detail',JEV_COM_COMPONENT, $Itemid);
				$this->viewEventCatRowNew($row);
			}
			echo "</div>";
			echo "</div>";
		}
	} else {
		echo '<div class="jev_listrow  jev_noresults">';
		if( $searchisValid ){
			echo JText::_('JEV_NO_EVENTFOR') . '&nbsp;<b>' . $this->keyword . '</b>';
		}else{
			echo '<b>' . $this->keyword . '</b>';
			$this->keyword = '';
		}
		echo '</div>' . "\n";

	}
?>
</div>
<?php
// Create the pagination object
if ($data["total"]>$data["limit"]){
	$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
}
