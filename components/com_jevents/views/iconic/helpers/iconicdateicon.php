<?php 
defined('_JEXEC') or die('Restricted access');

function Iconicdateicon($view,$lines, $title, $href, $class="", $event=false, $Itemid=false){
	$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
	if ($event && $href && $params->get('iconlinkstoevent',0)){
		if (!$Itemid){
			$Itemid = JRequest::getInt("Itemid");
		}
		$href = $event->viewDetailLink($event->yup(),$event->mup(),$event->dup(), true, $Itemid);
	}
	if (count($lines)==2) list($line1,$line2) = $lines;
	else {
		$line2 = $lines[0];
		$line1=false;
	}
	if ($href!=""){
		if ($line1){
			return '<a class="jevdateicon '.$class.'" href="' . $href . '" title="' . $title . '"><span class="jevdateicon1">' . $line1.'</span><span class="jevdateicon2">'.$line2.'</span></a>'."\n";
		}
		else {
			return '<a class="jevdateicon '.$class.'" href="' . $href . '" title="' . $title . '"><span class="jevdateicon2">' . $line2.'</span></a>'."\n";
		}
	}
	else {
		if ($line2){
			return '<span class="jevdateicon '.$class.'" ><span class="jevdateicon1">' . $line1.'</span><span class="jevdateicon2">'.$line2.'</span></span>'."\n";
		}
		else {
			return '<span class="jevdateicon '.$class.'" ><span class="jevdateicon2">' . $line2.'</span></span>'."\n";
		}
	}
}