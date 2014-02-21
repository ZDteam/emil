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
class AdminInviteesViewInvitees extends RSVPAbstractView
{

	function overview($tpl = null)
	{
		$this->addTemplatePath($this->_basePath."/views/invitees/tmpl/");
		
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('JEV_INVITEES'));

		// Set toolbar items for the page
		if (isset($this->repeat)){
			JToolBarHelper::title( JText::_( 'JEV_INVITEES' )." : ".$this->repeat->title(), 'jevents' );
		}
		else {
			JToolBarHelper::title( JText::_( 'JEV_INVITEES' ), 'jevents' );
		}

		JToolBarHelper::custom( 'cpanel.cpanel', 'default.png', 'default.png', 'JEV_ADMIN_CPANEL', false );
		
		JLoader::register('JevRsvpInvitees',JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/jevrinvitees.php");

		jimport('joomla.filesystem.file');
		$this->jomsocial = false;
		if (JFile::exists(JPATH_SITE.'/components/com_community/community.php')){
			if (JComponentHelper::isEnabled("com_community")) {
				$this->jomsocial = true;
			}
		}

		$this->cbuilder = false;
		$this->groupjive = false;
		if (JFile::exists(JPATH_SITE.'/components/com_comprofiler/comprofiler.php')){
			if (JComponentHelper::isEnabled("com_comprofiler")) {
				$this->cbuilder = true;
				if (JFile::exists(JPATH_SITE."/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.php")){
					$this->groupjive = true;
				}								
			}
		}
		
		JSubMenuHelper::addEntry(
			JText::_('RSVP_SESSIONS'), 
			"index.php?option=com_rsvppro&task=sessions.list", 
			false
		);
		
		$this->jevrinvitees = new JevRsvpInvitees($this->params, $this->jomsocial, $this->cbuilder, $this->groupjive);
		
	}

	


}