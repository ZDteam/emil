<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1399 2009-03-30 08:31:52Z geraint $
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
class AdminCPanelViewCPanel extends RSVPAbstractView 
{
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function cpanel($tpl = null)
	{
		jimport('joomla.html.pane');
		
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_RSVP') . ' :: ' .JText::_('Control_Panel'));
		
		// Set toolbar items for the page
		JToolBarHelper::title( JText::_('RSVP_RSVP') .' :: '. JText::_( 'Control_Panel' ), 'jevents' );

		RsvpproHelper::addSubmenu();		
		
		//JToolBarHelper::help( 'screen.cpanel', true);
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		
		JHtml::_('behavior.tooltip');
	}	

	
}