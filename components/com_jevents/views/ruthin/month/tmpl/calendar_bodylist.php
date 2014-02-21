<?php 
defined('_JEXEC') or die('Restricted access');

$cfg	 = & JEVConfig::getInstance();

// previous and following month names and links
$followingMonth = $this->datamodel->getFollowingMonth($this->data);
$precedingMonth = $this->datamodel->getPrecedingMonth($this->data);

$compname = JEV_COM_COMPONENT;
$viewname = $this->getViewName();
$viewpath = JURI::root() . "components/$compname/views/".$viewname."/assets";
$viewimages = $viewpath . "/images";

?>
<table align="center" cellspacing="0" cellpadding="5" class="ev_table">
    <tr valign="top">
        <td colspan="2"  align="center" class="cal_td_daysnames cal_td_daysnames_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>">
	<?php echo JText::_( 'MONTHLY_VIEW' );?> :: <?php echo $this->data['fieldsetText']; ?>
        </td>
    </tr>
<?php $datacount = count($this->data["dates"]);
for( $d = 0; $d < $datacount; $d++ ){
	if ($this->data["dates"][$d]["monthType"]!="current"){
		continue;		
	}
	$num_events	= count($this->data['dates'][$d]['events']);
	if ($num_events==0) continue;

	$day_link = '<a class="ev_link_weekday" href="' . $this->data['dates'][$d]['link'] . '" title="' . JText::_('JEV_CLICK_TOSWITCH_DAY') . '">'
	. JEventsHTML::getDateFormat( $this->data['dates'][$d]['year'], $this->data['dates'][$d]['month'], $this->data['dates'][$d]['d'], 2 ).'</a>'."\n";

	if( $this->data['dates'][$d]['today'])	$bg = 'class="ev_td_today"';
	else $bg = 'class="ev_td_left"';
	
	echo '<tr><td ' . $bg . '>' . $day_link . '</td>' . "\n";
	echo '<td class="ev_td_right">' . "\n";
	
	for( $r = 0; $r < $num_events; $r++ ){
		echo "<ul class='ev_ul'>\n";

		for( $r = 0; $r < $num_events; $r++ ){
			$row = $this->data['dates'][$d]['events'][$r];

			$listyle = 'style="border-color:'.$row->bgcolor().';"';
			echo "<li class='ev_td_li' $listyle>\n";

			if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0)){
				$this->viewEventRowNew ( $row);
				echo "&nbsp;::&nbsp;";
				$this->viewEventCatRowNew($row);
			}
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
	echo '</td>';
	echo '</tr>';
	
} // end for days 
?>
</table>