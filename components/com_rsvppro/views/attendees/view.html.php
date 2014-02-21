<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(JPATH_COMPONENT_ADMINISTRATOR."/views/".basename(dirname(__FILE__))."/".basename(__FILE__));

/**
 * HTML View class for the component
 *
 * @static
 */
class FrontAttendeesViewAttendees extends AdminAttendeesViewAttendees
{
	function __construct($config = array()){
		parent::__construct($config);
		include_once(JPATH_ADMINISTRATOR . '/' . "includes" . '/' . "toolbar.php");
		JHtml::stylesheet( 'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvppro.css' );
		JHtml::stylesheet(JURI::root()."administrator/components/com_rsvppro/assets/pagination/css/pagination.css");

		JHtml::stylesheet(JURI::root()."components/com_rsvppro/assets/adminsim/css/admin.css");
		
	}


	function overview($tpl = null)
	{
		$document =& JFactory::getDocument();
		$params = JComponentHelper::getParams('com_rsvppro');
		$document->setTitle($params->get('page_title',JText::_('RSVP_ATTENDEES')));

		include_once(JPATH_COMPONENT_ADMINISTRATOR."/libraries/JevPagination.php");
		$this->pageNav = new JevPagination( $this->pageNav->total, $this->pageNav->limitstart, $this->pageNav->limit,true);

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		$this->assignRef("params",$params);
		
		parent::overview($tpl);
	}
	
}