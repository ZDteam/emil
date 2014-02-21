<?php

/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminTypesViewTypes extends JViewLegacy
{

	function overview($tpl = null)
	{

		JLoader::register('JEventsHTML', JPATH_SITE . "/components/com_jevents/libraries/jeventshtml.php");

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");
		JHTML::stylesheet(  'administrator/components/'.$option.'/assets/css/jevpeople.css' );	 	
		if (!JVersion::isCompatible("3.0.0")){
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin16.css');
		}
		else {
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin.css' );
		}

		// Set toolbar items for the page
		JToolBarHelper::title(JText::_('TYPES_MANAGER'), 'generic.png');
		JToolBarHelper::addNew("types.edit");
		JToolBarHelper::deleteList("Are you sure you want to delete these types?", "types.delete");
		JToolBarHelper::editList("types.edit");
		if (JFactory::getApplication()->isAdmin())
		{
			JToolBarHelper::cancel('cpanel.show', 'Control Panel');
		}

		$this->showToolBar();

		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('JEV_PEOPLE') . ' :: ' . JText::_('JEV_PEOPLE'));

		$db = & JFactory::getDBO();
		$uri = & JFactory::getURI();

		$filter_state = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_state', 'filter_state', '', 'word');
		$filter_catid = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_catid', 'filter_catid', 0, 'int');
		$filter_order = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order', 'filter_order', 'pers.ordering', 'cmd');
		$filter_order_Dir = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order_Dir', 'filter_order_Dir', '', 'word');
		$search = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_search', 'search', '', 'string');
		$search = JString::strtolower($search);

		// Get data from the model
		$params = & JComponentHelper::getParams('com_jevents');
		$authorisedonly = $params->get("authorisedonly", 0);
		$juser = & JFactory::getUser();
		if ($authorisedonly)
		{
			JRequest::setVar("showglobal", $this->jevuser && $this->jevuser->cancreateglobal);
		}
		else
		{
			if ($juser->authorise('core.manage', 'com_jevpeople'))
			{
				JRequest::setVar("showall", 1);
			}
			if (JevPeopleHelper::canCreateGlobal())
			{
				JRequest::setVar("showglobal", 1);
			}
			else
			{
				JRequest::setVar("showglobal", 0);
			}
		}
		$model = & $this->getModel();
		$items = & $this->get('Data');
		$total = & $this->get('Total');
		$pagination = & $this->get('Pagination');

		// build list of categories
		$javascript = 'onchange="document.adminForm.submit();"';
		$compparams = JComponentHelper::getParams("com_jevpeople");

		// state filter
		$lists['state'] = JHTML::_('grid.state', $filter_state);

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// search filter
		$lists['search'] = $search;

		$this->assignRef('user', JFactory::getUser());
		$this->assignRef('lists', $lists);
		$this->assignRef('items', $items);
		$this->assignRef('pagination', $pagination);

		parent::display($tpl);

	}

	function edit($tpl = null)
	{

		JLoader::register('JEventsHTML', JPATH_SITE . "/components/com_jevents/libraries/jeventshtml.php");
		JLoader::register('jevtypesCategory', JPATH_COMPONENT_ADMINISTRATOR . "/libraries/categoryClass.php");

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");
		JHTML::stylesheet(  'administrator/components/'.$option.'/assets/css/jevpeople.css' );	 	
		if (!JVersion::isCompatible("3.0.0")){
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin16.css');
		}
		else {
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin.css' );
		}

		// Set toolbar items for the page
		$edit = JRequest::getVar('edit', true);
		$text = !$edit ? JText::_('NEW') : JText::_('EDIT');
		JToolBarHelper::title(JText::_('TYPE') . ': <small><small>[ ' . $text . ' ]</small></small>');
		$this->toolbarButton("types.save", "save", "save", "Save", false);
		if (!$edit)
		{
			$this->toolbarButton("types.cancel", "cancel", "cancel", "Cancel", false);
		}
		else
		{
			// for existing items the button is renamed `close`
			$this->toolbarButton("types.cancel", "cancel", "cancel", "Close", false);
		}

		$this->showToolBar();

		$compparams = JComponentHelper::getParams("com_jevpeople");
		$googlekey = $compparams->get("googlemapskey", "");
		$googleurl = $compparams->get("googlemaps", 'http://maps.google.com');
		if ($googlekey != "")
		{
			JHTML::script($googleurl.'/maps/api/js?key=' . $googlekey . "&amp;sensor=false",  true);
		}
		else
		{
			JHTML::script($googleurl.'/maps/api/js?sensor=false', true);
		}

		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('JEV_PEOPLE') . ' :: ' . JText::_('JEV_PEOPLE'));

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		$db = & JFactory::getDBO();
		$uri = & JFactory::getURI();
		$user = & JFactory::getUser();
		$model = & $this->getModel();

		//get the type
		$type = & $this->get('data');
		$isNew = ($type->type_id < 1);

		//clean type data
		JFilterOutput::objectHTMLSafe($type, ENT_QUOTES, 'description');

		$db = JFactory::getDBO();

		$options = array();
		$options[] = JHTML::_('select.option', 0, JText::_('NO_CATEGORISATION'));
		$options[] = JHTML::_('select.option', 1, JText::_('SINGLE_CATEGORY'));
		$options[] = JHTML::_('select.option', 2, JText::_('MULTIPLE_CATEGORIES'));
		$multicat = JHTML::_('select.genericlist', $options, 'multicat', 'class="inputbox" size="1" ', 'value', 'text', $type->multicat);

		$options = array();
		$options[] = JHTML::_('select.option', 0, JText::_('ONE_PER_EVENT'));
		$options[] = JHTML::_('select.option', 1, JText::_('MULTIPLE_PER_EVENT'));
		$multiple = JHTML::_('select.genericlist', $options, 'multiple', 'class="inputbox" size="1" ', 'value', 'text', $type->multiple);

		$options = array();
		$options[] = JHTML::_('select.option', 0, JText::_('JNO'));
		$options[] = JHTML::_('select.option', 1, JText::_('JYES'));
		$showaddress = JHTML::_('select.genericlist', $options, 'showaddress', 'class="inputbox" size="1" ', 'value', 'text', $type->showaddress);

		if (JVERSION::isCompatible("1.6.0")) {
			$options = array();
			$options[] = JHTML::_('select.option', 0, JText::_('JNO'));
			$options[] = JHTML::_('select.option', 1, JText::_('JYES'));
			$selfallocate = JHTML::_('select.genericlist', $options, 'selfallocate', 'class="inputbox" size="1" ', 'value', 'text', $type->selfallocate);

			$options = array();
			$attr  ="multiple=multiple' size='7' ";
			if (isset($type->allowedgroups) && $type->allowedgroups!=""){
				$value = json_decode($type->allowedgroups);
			}
			else {
				$value = array();
			}
			
			$allowedgroups = JHtml::_('access.usergroup', "allowedgroups[]", $value, $attr, $options);
		}

		$maxnumber = "<input type='text' name='maxperevent' value='" . intval($type->maxperevent) . "' />";
		
		// Must load admin language files
		$lang = & JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		JLoader::register('JEventsCategory', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/categoryClass.php");
		$categories = JEventsCategory::categoriesTree();
		$catvalues = $type->categories;
		$lists['categories'] = JHTML::_('select.genericlist', $categories, 'categories[]', 'multiple="multiple" size="15"', 'value', 'text', explode("|", $catvalues));

		// get calendars
		$sql = "SELECT label as text, ics_id as value FROM #__jevents_icsfile where icaltype=2";
		$db->setQuery($sql);
		$calendars = $db->loadObjectList();
		$calvalues = $type->calendars;
		$lists['calendars'] = JHTML::_('select.genericlist', $calendars, 'calendars[]', 'multiple="multiple" size="15"', 'value', 'text', explode("|", $calvalues));

		$this->assignRef('lists', $lists);
		$this->assignRef('catvalues', $catvalues);
		$this->assignRef('calvalues', $calvalues);

		$this->assignRef('type', $type);
		$this->assignRef('multiple', $multiple);
		$this->assignRef('maxnumber', $maxnumber);
		$this->assignRef('multicat', $multicat);
		$this->assignRef('showaddress', $showaddress);
		if (JVERSION::isCompatible("1.6.0")) {
			$this->assignRef('selfallocate', $selfallocate);
			$this->assignRef('allowedgroups', $allowedgroups);
		}

		parent::display($tpl);

	}

	function toolbarButton($task = '', $icon = '', $iconOver = '', $alt = '', $listSelect = true)
	{
		// joomla 3.0
		if (JVersion::isCompatible ("3.0")){
			JLoader::register('JToolbarButtonJev', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
			JLoader::register('JToolbarButtonJevlink', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
			JLoader::register('JToolbarButtonJevconfirm', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
		}
		else {
			include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevbuttons.php");
		}
		$bar = & JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton('Jev', $icon, $alt, $task, $listSelect);

	}

	function toolbarConfirmButton($task = '', $msg='', $icon = '', $iconOver = '', $alt = '', $listSelect = true)
	{
		// joomla 3.0
		if (JVersion::isCompatible ("3.0")){
			JLoader::register('JToolbarButtonJev', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
			JLoader::register('JToolbarButtonJevlink', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
			JLoader::register('JToolbarButtonJevconfirm', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevtoolbarbuttons.php");
		}
		else {
			include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/jevbuttons.php");
		}
		$bar = & JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton('Jevconfirm', $msg, $icon, $alt, $task, $listSelect);

	}

	// abstract method
	function showToolBar()
	{
		
	}

	function _globalHTML(&$row, $i)
	{
		$img = $row->global ? 'tick.png' : 'publish_x.png';
		$alt = $row->global ? JText::_('GLOBAL') : JText::_('GLOBAL');

		$mainframe = JFactory::getApplication();
		if (JFactory::getApplication()->isAdmin())
		{
			$img = '<img src="images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
		}
		else
		{
			$img = '<img src="../administrator/images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
		}

		return $img;

	}

}