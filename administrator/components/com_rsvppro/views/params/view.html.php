<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1512 2009-07-15 10:51:30Z geraint $
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
class AdminParamsViewParams extends RSVPAbstractView
{
	
	function edit()
	{

		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}
		
		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_CONFIGURATION'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_('RSVP_RSVP') .' :: '. JText::_( 'RSVP_CONFIGURATION' ), 'jevents' );		

		JToolBarHelper::apply('params.apply');
		JToolBarHelper::save('params.save');
		JToolBarHelper::cancel('cpanel.cpanel');

		$model = $this->getModel();

		JHtml::_('behavior.tooltip');

		// Get the actions for the asset.
		$actions = JAccess::getActions(RSVP_COM_COMPONENT, "component");

		jimport('joomla.form.form');

		// Add the search path for the admin component config.xml file.
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/' . RSVP_COM_COMPONENT);

		// Get the form.
		$modelForm = $model->getForm();

		//$component = isset($this->component)?$this->component : $this->get('Component');
		
		$component = $this->get('Component');
		// Bind the form to the data.
		if ($modelForm && $component->params)
		{
			$modelForm->bind($component->params);
		}

		$this->assignRef("form", $modelForm);
		$this->assignRef("component", $component);

		// Set the layout
		if (JVersion::isCompatible("3.0"))
		{
			$this->setLayout('edit');
		}
		else
		{
			$this->setLayout('edit16');
		}

	}
	
	
}