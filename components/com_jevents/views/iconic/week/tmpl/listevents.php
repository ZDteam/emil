<?php 
defined('_JEXEC') or die('Restricted access');

$this->_header();
$this->_showNavTableBar();

$cfg	 =  JEVConfig::getInstance();

if ($cfg->get('icscalable',0)==1 || $cfg->get("iconicwidth",905)=="scalable"){
	if ($cfg->get('ictabularweek',0) ){
		echo $this->loadTemplate("bodygridresponsive");
	}
	else {
		echo $this->loadTemplate("responsive");	
	}
}
else if ($cfg->get('ictabularweek',0) ){
	echo $this->loadTemplate("bodygrid");
}
else {
	echo $this->loadTemplate("body");
}

$this->_viewNavAdminPanel();

$this->_footer();

