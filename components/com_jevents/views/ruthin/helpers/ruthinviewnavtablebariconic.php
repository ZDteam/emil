<?php 
defined('_JEXEC') or die('Restricted access');

JLoader::register('DefaultViewNavTableBarIconic',JEV_VIEWS."/default/helpers/defaultviewnavtablebariconic.php");

class RuthinViewNavTableBarIconic extends DefaultViewNavTableBarIconic {

	var $view = null;
	
	function __construct($view, $today_date, $view_date, $dates, $alts, $option, $task, $Itemid ){
		//parent::DefaultViewNavTableBarIconic($view, $today_date, $view_date, $dates, $alts, $option, $task, $Itemid);
		$this->view = $view	;
		$this->transparentGif = JURI::root() . "components/".JEV_COM_COMPONENT."/views/".$this->view->getViewName()."/assets/images/transp.gif";
		$this->Itemid = JEVHelper::getItemid();
		$this->cat = $this->view->datamodel->getCatidsOutLink();
		$this->task = $task;
		
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$this->colourscheme = $params->get("colourscheme","red");

		$cfg = & JEVConfig::getInstance();
		$this->iconstoshow = $cfg->get('iconstoshow', array('byyear', 'bymonth', 'byweek', 'byday', 'search'));		
		
		if (JRequest::getInt( 'pop', 0 )) return;		
    	?>
    	<div class="ev_navigation">
    		<table  >
    			<tr align="center" valign="top">
    	    		<?php 
    	    		if($cfg->get('com_calUseIconic', 1) != 2 && $task!="range.listevents"){
    	    			echo $this->_lastYearIcon($dates, $alts);
    	    			echo $this->_lastMonthIcon($dates, $alts);
    	    		}
    	    		if (in_array("byyear", $this->iconstoshow)) echo $this->_viewYearIcon($view_date);
    	    		if (in_array("bymonth", $this->iconstoshow)) echo $this->_viewMonthIcon($view_date);
    	    		if (in_array("byweek", $this->iconstoshow)) echo $this->_viewWeekIcon($view_date);
    	    		if (in_array("byday", $this->iconstoshow)) echo $this->_viewDayIcon($today_date);
    	    		if (in_array("search", $this->iconstoshow)) echo $this->_viewSearchIcon($view_date);
    	    		if (in_array("bymonth", $this->iconstoshow)) echo $this->_viewJumptoIcon($view_date);
    	    		if($cfg->get('com_calUseIconic', 1) != 2 && $task!="range.listevents"){
	    	    		echo $this->_nextMonthIcon($dates, $alts);
    		    		echo $this->_nextYearIcon($dates, $alts);
    	    		}
    	    		?>
                </tr>
    			<tr class="icon_labels" align="center" valign="top">
    				<?php   if($cfg->get('com_calUseIconic', 1) != 2  && $task!="range.listevents"){ ?>
	        		<td colspan="2"></td>
	        		<?php } ?>
    				<?php if (in_array("byyear", $this->iconstoshow))   { ?><td><?php echo JText::_('JEV_VIEWBYYEAR');?></td><?php } ?>
    				<?php if (in_array("bymonth", $this->iconstoshow))   { ?><td><?php echo JText::_('JEV_VIEWBYMONTH');?></td><?php } ?>
    				<?php if (in_array("byweek", $this->iconstoshow))   { ?><td><?php echo JText::_('JEV_VIEWBYWEEK');?></td><?php } ?>
    				<?php if (in_array("byday", $this->iconstoshow))   { ?><td><?php echo JText::_('JEV_VIEWTODAY');?></td><?php } ?>
    				<?php if (in_array("search", $this->iconstoshow))   { ?><td><?php echo JText::_('JEV_SEARCH_TITLE');?></td><?php } ?>
    				<?php if (in_array("bymonth", $this->iconstoshow))   { ?><td><?php echo  JText::_('JEV_JUMPTO');?></td><?php } ?>
    				<?php   if($cfg->get('com_calUseIconic', 1) != 2 && $task!="range.listevents"){ ?>
	        		<td colspan="2"></td>
	        		<?php } ?>
                </tr>
                <?php
                if (in_array("bymonth", $this->iconstoshow))   echo $this->_viewHiddenJumpto($view_date);
                ?>
            </table>
        </div>
		<?php    	
	}
	
	function _genericMonthNavigation($dates, $alts, $which){
		$lang = &JFactory::getLanguage();	
		switch ($which){
			case "prev2":
				$icon=$lang->isRTL()?"DoubleRight.png":"DoubleLeft.png";
				break;
			case "prev1":
				$icon=$lang->isRTL()?$icon="Right.png":"Left.png";
				break;
			case "next1":
				$icon=$lang->isRTL()?$icon="Left.png":"Right.png";
				break;
			case "next2":
				$icon=$lang->isRTL()?$icon="DoubleLeft.png":"DoubleRight.png";
				break;
		}
		
		$cfg = & JEVConfig::getInstance();
		$task = $this->task;
		$link = 'index.php?option=' . JEV_COM_COMPONENT . '&task=' . $task . $this->cat . '&Itemid=' . $this->Itemid. '&';

		$gg	="<img border='0' src='". JURI::root()."components/".JEV_COM_COMPONENT."/views/".$this->view->getViewName()."/assets/images/".$icon."' alt='".$alts[$which]."'/>";

		$thelink = '<a href="'.JRoute::_($link.$dates[$which]->toDateURL()).'" title="'.$alts[$which].'">'.$gg.'</a>'."\n";
		if ($dates[$which]->getYear()>=$cfg->get('com_earliestyear') && $dates[$which]->getYear()<=$cfg->get('com_latestyear')){
		?>
    	<td width="10" align="center" valign="middle"><?php echo $thelink; ?></td>
		<?php		
		}
		else {
		?>
    	<td width="10" align="center" valign="middle"></td>
		<?php		
		}
	}

	function _viewYearIcon($today_date) {
		?>
		<td class="iconic_td" align="center" valign="middle">
    		<div  class="ev_icon_yearly nav_bar_cal ev_icon_yearly_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>"><a href="<?php echo JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . $this->cat . '&task=year.listevents&'. $today_date->toDateURL() . '&Itemid=' . $this->Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYYEAR');?>"> 
    			<img src="<?php echo $this->transparentGif;?>" alt="<?php echo JText::_('JEV_VIEWBYYEAR');?>"/></a>
    		</div>
        </td>
        <?php
	}

	function _viewMonthIcon($today_date) {
		?>
    	<td class="iconic_td" align="center" valign="middle">
    		<div class="ev_icon_monthly nav_bar_cal ev_icon_monthly_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>" ><a href="<?php echo JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . $this->cat . '&task=month.calendar&'. $today_date->toDateURL() . '&Itemid=' . $this->Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYMONTH');?>">
    			<img src="<?php echo $this->transparentGif;?>" alt="<?php echo JText::_('JEV_VIEWBYMONTH');?>"/></a>
    		</div>
        </td>
        <?php
	}
	
	function _viewWeekIcon($today_date) {
		?>
		<td class="iconic_td" align="center" valign="middle">
			<div class="ev_icon_weekly nav_bar_cal ev_icon_weekly_<?php echo $this->colourscheme;?>  jev_<?php echo $this->colourscheme;?>"><a href="<?php echo JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . $this->cat . '&task=week.listevents&'. $today_date->toDateURL() . '&Itemid=' . $this->Itemid );?>" title="<?php echo  JText::_('JEV_VIEWBYWEEK');?>">
			<img src="<?php echo $this->transparentGif;?>" alt="<?php echo JText::_('JEV_VIEWBYWEEK');?>"/></a>
			</div>
        </td>
        <?php
	}

	function _viewDayIcon($today_date) {
		?>
		<td class="iconic_td" align="center" valign="middle">
			<div  class="ev_icon_daily nav_bar_cal ev_icon_daily_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>" ><a href="<?php echo JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . $this->cat . '&task=day.listevents&'. $today_date->toDateURL() . '&Itemid=' . $this->Itemid );?>" title="<?php echo  JText::_('JEV_VIEWTODAY');?>"><img src="<?php echo $this->transparentGif;?>" alt="<?php echo JText::_('JEV_VIEWBYDAY');?>"/></a>
			</div>
        </td>
        <?php
	}

	function _viewSearchIcon($today_date) {
		?>
		<td class="iconic_td" align="center" valign="middle">
			<div class="ev_icon_search nav_bar_cal ev_icon_search_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>"><a href="<?php echo JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . $this->cat . '&task=search.form&'. $today_date->toDateURL() . '&Itemid=' . $this->Itemid );?>" title="<?php echo  JText::_('JEV_SEARCH_TITLE');?>"><img src="<?php echo $this->transparentGif;?>" alt="<?php echo JText::_('JEV_SEARCH_TITLE');?>"/></a>
			</div>
        </td>                
        <?php
	}

	function _viewJumptoIcon($today_date) {
		?>
		<td class="iconic_td" align="center" valign="middle">
			<div class="ev_icon_jumpto nav_bar_cal ev_icon_jumpto_<?php echo $this->colourscheme;?> jev_<?php echo $this->colourscheme;?>"><a  href="#"  onclick="jtdisp = document.getElementById('jumpto').style.display;document.getElementById('jumpto').style.display=(jtdisp=='none')?'block':'none';return false;" title="<?php echo   JText::_('JEV_JUMPTO');?>"><img src="<?php echo $this->transparentGif;?>" alt="<?php echo  JText::_('JEV_JUMPTO');?>"/></a>
			</div>
        </td>                
        <?php
	}
	

}