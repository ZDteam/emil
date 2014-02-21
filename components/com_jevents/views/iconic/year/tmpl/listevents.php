<?php 
defined('_JEXEC') or die('Restricted access');

$this->_header();
$this->_showNavTableBar();

$cfg	 =  JEVConfig::getInstance();

if ($cfg->get('icscalable',0)==1 || $cfg->get("iconicwidth",905)=="scalable"){
	echo $this->loadTemplate("responsive");	
}
else {
	echo $this->loadTemplate("body");
}

$this->_viewNavAdminPanel();

$this->_footer();


