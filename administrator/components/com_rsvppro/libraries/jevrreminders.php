<?php
/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted Access' );


class JevRsvpReminders
{

	private $params;
	private $jomsocial = false;

	public function __construct($params, $jomsocial){
		$this->params = $params;
		$this->jomsocial = $jomsocial;

		include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/reminderhelper.php");
		$this->helper = new RsvpReminderHelper($this->params);
		
		jimport('joomla.application.component.view');

		$theme = JEV_CommonFunctions::getJEventsViewName();
		if (JVersion::isCompatible("1.6.0")){
			$this->_basepath = JPATH_SITE.'/plugins/jevents/jevrsvppro/rsvppro/';
		}
		else {
			$this->_basepath = JPATH_SITE.'/plugins/jevents/rsvppro/';
		}
		$this->view = new JViewLegacy(array( 'base_path'=>$this->_basepath,
		"template_path"=>$this->_basepath."tmpl/default"
		,"name"=>$theme
		));

		$this->view->addTemplatePath($this->_basepath."tmpl/".$theme);

		$this->view->addTemplatePath($this->_basepath."tmpl/".$theme);
		$this->view->addTemplatePath( JPATH_SITE .'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'."plg_rsvppro".'/'."default");
		$this->view->addTemplatePath( JPATH_SITE .'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'."plg_rsvppro".'/'.$theme);
		
		$this->view->assign("jomsocial",$this->jomsocial);
		$this->view->assignRef("params",$this->params);

		$this->view->setLayout("reminder");
		
	}

	public function reminderForm($row,$rsvpdata, $emailaddress){

		$html = "";
		if (!$rsvpdata->allowreminders){
			return $html;
		}

		$user=JFactory::getUser();

		if ($user->id==0 && !$this->params->get("remindemails",0)){
			return $html;
		}

		$reminded = $this->isReminded($rsvpdata, $row, $emailaddress);

		$this->view->assignRef("row",$row);
		$this->view->assignRef("rsvpdata",$rsvpdata);
		$this->view->assignRef("emailaddress",$emailaddress);
		$this->view->assignRef("reminded",$reminded);
		return $this->view->loadTemplate("reminderform");

	}


	// This redirects calls to the helper class is possible
	public function __call($name, $arguments){
		if (is_callable(array($this->helper, $name))){
			return call_user_func_array(array($this->helper, $name),$arguments);
		}
	}

}