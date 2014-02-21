<?php 
defined('_JEXEC') or die('Restricted access');

$cfg	 = & JEVConfig::getInstance();

$data = $this->datamodel->getCatData( $this->catids,$cfg->get('com_showrepeats',0), $this->limit, $this->limitstart);
$this->data = $data;

$Itemid = JEVHelper::getItemid();
?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme;?>'>
	<div class="jev_toprow jev_toprowcat">
	    <div class="jev_header jev_headercat">
		  <h2><?php echo JText::_( 'CATEGORY_VIEW' );?></h2>
		  <div class="today" > <?php $this->viewNavCatText( $this->catids, JEV_COM_COMPONENT, 'cat.listevents', $this->Itemid );?></div>
		</div>
	</div>
    <div class="jev_clear" ></div>

	<div class="jev_listrow">
<div class="jev_daysnames jev_daysnames_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
<?php echo $data['catname'];?>
</div>
<?php
if (strlen($data['catdesc'])>0){
	echo "<div class='jev_catdesc'>".$data['catdesc']."</div>";
}
?>
	</div><?php

$num_events = count($data['rows']);
$chdate ="";
if( $num_events > 0 ){

	for( $r = 0; $r < $num_events; $r++ ){
		$row = $data['rows'][$r];

		$Itemid = JRequest::getInt("Itemid");
		$link = JRoute::_( 'index.php?option='.JEV_COM_COMPONENT.'&task=day.listevents&year='.$row->yup().'&month='.$row->mup().'&day='.$row->dup().'&Itemid='.$Itemid );

		$datestp	= JevDate::mktime( 0, 0, 0,$row->mup(), $row->dup(), $row->yup() );
		$day_link = $this->dateicon(explode(":",JEV_CommonFunctions::jev_strftime("%d:%b",$datestp)),JText::_('JEV_CLICK_TOSWITCH_DAY'),$link, "", $row);
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
		<div  class='jevright' <?php echo $listyle;?>>
		<?php
		if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
			$this->viewEventRowNew ( $row,'view_detail',JEV_COM_COMPONENT, $Itemid);
		}
		?>
		</div>
		</div>
	<?php

	}
} else {
	?>
	<div class="jev_listrow jev_noresults">
	<?php

	if( count($this->catids)==0 || $data['catname']==""){
		echo JText::_('JEV_EVENT_CHOOSE_CATEG') . '';
	} else {
		echo JText::_('JEV_NO_EVENTFOR') . '&nbsp;<b>' . $data['catname']. '</b>';
	}
	?>
	</div>
	<?php
}

?>
<div class="jev_clear" ></div>
</div>
<?php
// Create the pagination object
if ($data["total"]>$data["limit"]){
	$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
}
