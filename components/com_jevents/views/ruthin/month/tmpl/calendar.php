<?php 
defined('_JEXEC') or die('Restricted access');

$this->_header();

$this->_showNavTableBar();

$cfg	 = & JEVConfig::getInstance();

if ($cfg->get('rulistmonth',0)){
	echo $this->loadTemplate("bodylist");
}
else {
	echo $this->loadTemplate("body");
}

$this->_viewNavAdminPanel();

$this->_footer();