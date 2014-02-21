<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1703 2010-02-16 12:23:46Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminSessionsViewSessions extends RSVPAbstractView
{
	function overview($tpl = null)
	{
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_SESSIONS'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_SESSIONS' ), 'jevents' );

		//JToolBarHelper::editList('icalevent.edit',"RSVP_Edit_Event");
		JToolBarHelper::deleteList('RSVP_DELETE_SESSION_MESSAGE','sessions.delete',"RSVP_DELETE_SESSION");
		JToolBarHelper::spacer();
		JToolBarHelper::custom( 'cpanel.cpanel', 'default.png', 'default.png', 'JEV_ADMIN_CPANEL', false );

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');
	}

	function toolbarConfirmButton($task = '',  $msg='',  $icon = '', $iconOver = '', $alt = '', $listSelect = true){
		include_once(JEV_ADMINPATH."libraries/jevbuttons.php");
		$bar = & JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton( 'Jevconfirm', $msg, $icon, $alt, $task, $listSelect ,false,"document.adminForm.updaterepeats.value" );

	}


	function editRepeatLink($row) {
		list($year,$month,$day) = JEVHelper::getYMD();
		$link =  "index.php?option=".JEV_COM_COMPONENT."&task=".$row->editTask().'&evid='. $row->id()."&year=$year&month=$month&day=$day";
		return $link;
	}

	function editLink($row) {
		$link = "index.php?option=".JEV_COM_COMPONENT."&task=icalevent.edit&evid=". $row->ev_id();
		return $link;
	}

}