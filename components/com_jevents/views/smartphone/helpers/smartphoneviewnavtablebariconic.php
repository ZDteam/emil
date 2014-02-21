<?php 
defined('_JEXEC') or die('Restricted access');

class SmartphoneViewNavTableBarIconic {

	var $view = null;
	
	function __construct($view, $today_date, $view_date, $dates, $alts, $option, $task, $Itemid ){
		global $catidsOut;
		
		if (JRequest::getInt( 'pop', 0 )) return;
				
		$cfg = & JEVConfig::getInstance();
		$compname = JEV_COM_COMPONENT;

		$this->iconstoshow = $cfg->get('iconstoshow', array('byyear', 'bymonth', 'byweek', 'byday', 'search'));
		$viewimages = JURI::root() . "components/".JEV_COM_COMPONENT."/views/".$view->getViewName()."/assets/images";

		$cat		= "";
		$hiddencat	= "";
		if ($catidsOut!=0){
			$cat = '&catids=' . $catidsOut;
			$hiddencat = '<input type="hidden" name="catids" value="'.$catidsOut.'"/>';
		}

		$link = 'index.php?option=' . $option . '&task=' . $task . $cat . '&Itemid=' . $Itemid. '&';
    	?>
    	<table class="jevicons"  border="0" cellpadding="10" cellspacing="0" >
    	<tr>
    		<td class="tableh1" align="center">
    		<table border="0" cellpadding="0" cellspacing="0">
    			<tr>
					<?php if (in_array("byyear", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=year.listevents&'. $view_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYYEAR');?>"  class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-flyer.gif" alt="Flat View" /><br/>
							<?php echo JText::_('JEV_VIEWBYYEAR');?></a>
					</td>
					<?php } ?>
					<?php if (in_array("bymonth", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=month.calendar&'. $view_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYMONTH');?>" class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-calendarview.gif" alt="<?php echo JText::_( 'MONTHLY_VIEW' );?>" /><br/>
							<?php echo  JText::_('JEV_VIEWBYMONTH');?></a>
					</td>
					<?php } ?>
					<?php if (in_array("byweek", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=week.listevents&'. $view_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYWEEK');?>" class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-weekly.gif" alt="Weekly View" /><br/>
							<?php echo  JText::_('JEV_VIEWBYWEEK');?></a>
					</td>
					<?php } ?>
					<?php if (in_array("byday", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=day.listevents&'. $today_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_VIEWTODAY');?>" class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-daily.gif" alt="Daily View" /><br/>
							<?php echo JText::_('JEV_VIEWTODAY');?></a>
					</td>
					<?php } ?>
					<?php if ($cfg->get('com_hideshowbycats', 0) == '0') { ?>
					<?php if (in_array("bycat", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=cat.listevents&'. $view_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYCAT');?>" class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-cats.gif" alt="Categories" /><br/>
							<?php echo  JText::_('JEV_VIEWBYCAT');?></a>
					</td>
					<?php } ?>
					<?php } ?>
					<?php if (in_array("search", $this->iconstoshow))   { ?>
					<td class="buttontext" nowrap="nowrap" >
						<a href="<?php echo JRoute::_( 'index.php?option=' . $option . $cat . '&task=search.form&'. $view_date->toDateURL() . '&Itemid=' . $Itemid );?>" title="<?php echo  JText::_('JEV_SEARCH_TITLE');?>" class="buttontext">
							<img src="<?php echo $viewimages;?>/icon-search.gif" alt="Search" /><br/>
							<?php echo JText::_('JEV_SEARCH_TITLE');?></a>
					</td>
					<?php } ?>
				</tr>
			</table>

        </td>
        </tr></table>
		<?php    	
		
	}

}