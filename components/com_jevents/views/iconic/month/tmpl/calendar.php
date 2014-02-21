<?php 
defined('_JEXEC') or die('Restricted access');

list($usec, $sec) = explode(" ", microtime());
$starttime = (float) $usec + (float) $sec;

$this->_header();

list ($usec, $sec) = explode(" ", microtime());
$time_end = (float) $usec + (float) $sec;
//echo  "JEvents header = ".round($time_end - $starttime, 4)."<br/>";

$this->_showNavTableBar();

list ($usec, $sec) = explode(" ", microtime());
$time_end = (float) $usec + (float) $sec;
//echo  "JEvents _showNavTableBar/per loadTemplate = ".round($time_end - $starttime, 4)."<br/>";

$cfg	 = & JEVConfig::getInstance();

if ($cfg->get('iclistmonth',0)){
	echo $this->loadTemplate("bodylist");
}
else if ($cfg->get('icscalable',0)==1 || $cfg->get("iconicwidth",905)=="scalable"){
	echo $this->loadTemplate("responsive");	
}
else {
	echo $this->loadTemplate("body");
}

list ($usec, $sec) = explode(" ", microtime());
$time_end = (float) $usec + (float) $sec;
//echo  "JEvents loadTemplate = ".round($time_end - $starttime, 4)."<br/>";

$this->_viewNavAdminPanel();

$this->_footer();

list ($usec, $sec) = explode(" ", microtime());
$time_end = (float) $usec + (float) $sec;
//echo  "JEvents to the end of tmpl  = ".round($time_end - $starttime, 4)."<br/>";
