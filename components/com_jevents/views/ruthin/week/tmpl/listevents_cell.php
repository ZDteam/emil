<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

include_once(JEV_VIEWS."/default/month/tmpl/calendar_cell.php");

class EventCalendarCell_ruthin extends EventCalendarCell_default{
	function calendarCell(&$currentDay,$year,$month,$i, $slot){

		$cfg = & JEVConfig::getInstance();

		$event = $currentDay["rows"][$i];
		// Event publication infomation
		$event_up   = new JEventDate( $event->startDate() );
		$event_down = new JEventDate( $event->endDate());

		// BAR COLOR GENERATION
		$bgeventcolor = JEV_CommonFunctions::setColor($event);

		// this file controls the events component month calendar display cell output.  It is separated from the
		// showCalendar function in the events.php file to allow users to customize this portion of the code easier.
		// The event information to be displayed within a month day on the calendar can be modified, as well as any
		// overlay window information printed with a javascript mouseover event.  Each event prints as a separate table
		// row with a single column, within the month table's cell.

		// On mouse over date formats
		// Note that the date formats for the events can be easily changed by modifying the sprintf formatting
		// string below.  These are used for the default overlay window.  As well, the JevDate::strftime() function could
		// also be used instead to provide more powerful date formatting which supports locales if php function
		// set_locale() is being used.

		// define start and end
		$blocks = 1;
		if (array_key_exists($slot,$currentDay["slots"])){
			$blocks =$currentDay["slots"][$slot][1];
		}

		$cellStart	= '<div class="eventfull"><div class="eventstyle" ' ;
		$cellStyle	= '';
		$cellEnd		= '</div></div>' . "\n";

		// add the event color as the column background color
		include_once(JPATH_ADMINISTRATOR."/components/".JEV_COM_COMPONENT."/libraries/colorMap.php");

		//$colStyle .= $bgeventcolor ? ' background-color:' . $bgeventcolor . ';' : '';
		//$colStyle .= $bgeventcolor ? 'color:'.JevMapColor($bgeventcolor) . ';' : '';

		// MSIE ignores "inherit" color for links - stupid Microsoft!!!
		//$linkStyle = $bgeventcolor ? 'style="color:'.JevMapColor($bgeventcolor) . ';"' : '';
		$linkStyle = "";

		// The title is printed as a link to the event's detail page
		$link = $this->event->viewDetailLink($year,$month,$currentDay['week_day'],false);
		$link = JRoute::_($link.$this->_datamodel->getCatidsOutLink());

		$title          = $event->title();
		
		// if title is too long, cut 'em for display
		$tmpTitle = $title;
		if (!$cfg->get('icignoretrunc',1)){
			if (!isset($this->event->truncatedtitle)) {
				$longestword = $cfg->get('wordlength',10)*$blocks;
				$words = explode(" ",$title);
				foreach ($words as &$word) {
					if (JString::strlen($word)>$longestword){
						$word = JString::substr($word,0,$longestword-2)."..";
					}
				}
				unset($word);
				$tmpTitle = implode(" ",$words);
				if( JString::strlen( $tmpTitle ) >= $cfg->get('com_calCutTitle',50)*$blocks){
					$tmpTitle = JString::substr( $title, 0, $cfg->get('com_calCutTitle',50)*$blocks ) . ' ...';
				}
				$tmpTitle = JEventsHTML::special($tmpTitle);
				$this->event->truncatedtitle = $tmpTitle;
			}
			else {
				$tmpTitle = $this->event->truncatedtitle ;
			}
		}

		// [new mic] if amount of displaing events greater than defined, show only a scmall coloured icon
		// instead of full text - the image could also be "recurring dependig", which means
		// for each kind of event (one day, multi day, last day) another icon
		// in this case the dfinition must moved down to be more flexible!

		// [tstahl] add a graphic symbol for all day events?
		$tmp_start_time = (($this->start_time == $this->stop_time && !$this->event->noendtime()) || $this->event->alldayevent()) ? '' : $this->start_time;

		$templatedcell = false;
		
		if( $currentDay['countDisplay'] < $cfg->get('com_calMaxDisplay',5)){
			ob_start();
			$templatedcell = $this->loadedFromTemplate('month.calendar_cell', $this->event, 0);
			$res = ob_get_clean();
			if ($templatedcell){
				$templatedcell = $res;
			}			
			else {
				if ($this->_view){
					$this->_view->assignRef("link",$link);
					$this->_view->assignRef("linkStyle",$linkStyle);
					$this->_view->assignRef("tmp_start_time",$tmp_start_time);
					$this->_view->assignRef("tmpTitle",$tmpTitle);
				}
				$title_event_link = $this->loadOverride("cellcontent");
				// allow fallback to old method
				if ($title_event_link==""){
					$title_event_link = "\n".'<a class="cal_titlelink" href="' . $link . '" '.$linkStyle.'>'
					. ( $cfg->get('com_calDisplayStarttime') ? $tmp_start_time : '' ) . ' ' . $tmpTitle . '</a>' . "\n";
				}
				$cellStyle .= "border-left-color:$bgeventcolor;border-bottom-color:$bgeventcolor;";
			}
		}else{
			$eventIMG	= '<img align="left" src="' . JURI::root()
			. 'components/'.JEV_COM_COMPONENT.'/images/event.png" alt="" style="height:12px;width:8px;border:1px solid white;background-color:'.$bgeventcolor.'" />';

			$title_event_link = "\n".'<a class="cal_titlelink" href="' . $link . '">' . $eventIMG . '</a>' . "\n";
			$cellStyle .= ' float:left;width:10px;';
		}

		$cellString	= '';
		// allow template overrides for cell popups
		// only try override if we have a view reference
		if ($this->_view){
			$this->_view->assignRef("ecc",$this);
			$this->_view->assignRef("cellDate",$currentDay["cellDate"]);
		}

		if( $cfg->get("com_enableToolTip",1)) {
			if ($cfg->get("tooltiptype",'overlib')=='overlib'){
				$tooltip = $this->loadOverride("overlib");
				// allow fallback to old method
				if ($tooltip==""){
					$tooltip=$this->calendarCell_popup($currentDay["cellDate"]);
				}
				$tooltip = $this->correctTooltipLanguage($tooltip);
				$cellString .= $tooltip;
			}
			else {
				// TT background
				if( $cfg->get('com_calTTBackground',1) == '1' ){
					$bground =  $this->event->bgcolor();
					$fground =  $this->event->fgcolor();
				}
				else {
					$bground =  "#000000";
					$fground =   "#ffffff";

				}

				$toolTipArray = array('className'=>'jevtip');
				JHTML::_('behavior.tooltip', '.hasjevtip', $toolTipArray);

				$tooltip = $this->loadOverride("tooltip");
				// allow fallback to old method
				if ($tooltip==""){
					$tooltip = $this->calendarCell_tooltip($currentDay["cellDate"]);
				}
				$tooltip = $this->correctTooltipLanguage($tooltip);

				if (strpos($tooltip,"templated")===0 ) {
					$title = substr($tooltip,9);
					$cellString = "";
				}
				else {
					$cellString .= '<div class="jevtt_text" >'.$tooltip.'</div>';
					$title = '<div class="jevtt_title" style = "color:'.$fground.';background-color:'.$bground.'">'.$this->title.'</div>';
				}
				
				if ($templatedcell){
					$templatedcell = str_replace("[[TOOLTIP]]", htmlspecialchars($title.$cellString,ENT_QUOTES), $templatedcell);
					$time = $cfg->get('com_calDisplayStarttime')?$tmp_start_time:"";
					$templatedcell = str_replace("[[EVTTIME]]", $time, $templatedcell);
					return  $templatedcell;
				}
				
				$html =  $cellStart . ' style="' . $cellStyle . '">' . $this->tooltip( $title.$cellString, $title_event_link) . $cellEnd;

				return $html;
			}

		}
		if ($templatedcell)
		{
			$templatedcell = str_replace("[[TOOLTIP]]", htmlspecialchars($title . $cellString, ENT_QUOTES), $templatedcell);
			$time = $cfg->get('com_calDisplayStarttime') ? $tmp_start_time : "";
			$templatedcell = str_replace("[[EVTTIME]]", $time, $templatedcell);
			return $templatedcell;
		}

		// return the whole thing
		return $cellStart . ' style="' . $cellStyle . '" ' . $cellString . ">\n" . $title_event_link . $cellEnd;
	}

}?>
