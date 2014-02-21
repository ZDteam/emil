<?php

// No direct access
defined('_JEXEC') or die;

/**
 * JEvents component helper.
 *
 * @package		RSVP Pro
 * @since		1.6
 */
class RsvpproHelper
{

	public static $extention = 'com_rsvppro';

	/**
	 * Configure the Linkbar.
	 *
	 * @param	string	The name of the active view.
	 */
	public static function addSubmenu($vName = "")
	{
		$task = JRequest::getCmd("task", "cpanel.cpanel");
		$option = JRequest::getCmd("option", "com_categories");
		
		if ($option == 'com_categories') {
			$doc =& JFactory::getDocument();
			if (!JVersion::isCompatible("3.0")) {
				$hide_options = '#toolbar-popup-options {'
				. 'display:none;'
				. '}'; 
			} else {
				$hide_options = '#toolbar-options {'
				. 'display:none;'
				. '}'; 
			}
			$doc->addStyleDeclaration( $hide_options );
		}
		
		if ($vName == "")
		{
			$vName = $task;
		}		
		JSubMenuHelper::addEntry(
				JText::_('JEV_CONTROL_PANEL'), 'index.php?option=com_jevents', $vName == 'cpanel.cpanel'
		);
	
		$juser =  JFactory::getUser();
		$authorised = false;
		if ($juser->authorise('core.admin', 'com_rsvppro'))
		{
			$authorised = true;
		}
		
		// could be called from categories component
		JLoader::register('JEVHelper',JPATH_SITE."/components/com_jevents/libraries/helper.php");		

		JSubMenuHelper::addEntry(
				JText::_('RSVP_TEMPLATES'), "index.php?option=$option&task=templates.list" , $vName == 'templates.list'
		);

		JSubMenuHelper::addEntry(
				JText::_('RSVP_SESSIONS'), "index.php?option=$option&task=sessions.list" , $vName == 'sessions.list'
		);
		
		if ($authorised)
		{
			JSubMenuHelper::addEntry(
				JText::_('RSVP_CONFIGURATION'),  "index.php?option=" . RSVP_COM_COMPONENT . "&task=params.edit", $vName == 'params.edit'
			);
		}
		
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param	int		The category ID.
	 * @param	int		The article ID.
	 *
	 * @return	JObject
	 */
	public static function getActions($categoryId = 0, $articleId = 0)
	{
		$user = JFactory::getUser();
		$result = new JObject;

		if (empty($articleId) && empty($categoryId))
		{
			$assetName = 'com_rsvppro';
		}
		else if (empty($articleId))
		{
			$assetName = 'com_rsvppro.category.' . (int) $categoryId;
		}
		else
		{
			$assetName = 'com_rsvppro.article.' . (int) $articleId;
		}

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.createreg', 'core.createglobal', 'core.sendmessage'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;

	}

}