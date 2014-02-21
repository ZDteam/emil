<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');
jimport( 'joomla.html.parameter');

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminPeopleViewPeople extends JViewLegacy
{

	private $firsttype = 0;

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
		JToolBarHelper::title(JText::_('PEOPLE_MANAGER'), 'generic.png');
		JToolBarHelper::publishList("people.publish");
		JToolBarHelper::unpublishList("people.unpublish");
		JToolBarHelper::deleteList("Are you sure you want to delete these people?", "people.delete");
		JToolBarHelper::editList("people.edit");
		JToolBarHelper::addNew("people.edit");
		if (JRequest::getString("tmpl", "") == "component")
		{
			JToolBarHelper::custom("people.select", "back", "back", "JEV_BACK", false);
		}
		if (JFactory::getApplication()->isAdmin() && JRequest::getString("tmpl", "") != "component")
		{
			JToolBarHelper::cancel('cpanel.show', 'Control Panel');
		}

		$this->showToolBar();

		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('JEVENTS_EXTRAS') . ' :: ' . JText::_('JEVENTS_EXTRAS'));

		$msg = JText::_("JEV_YOU_MUST_SELECT_A_TYPE", true);
		$script = "
			window.addEvent('domready',function(){
				document.getElement('.icon-32-new').getParent().addEvent('mousedown',function(){
					if ($('type_id').value==0){
						alert('$msg');
						return false;
					}
				});
			});
		";
		$document->addScriptDeclaration($script);
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
			if (JevPeopleHelper::canCreateGlobal())
			{
				JRequest::setVar("showglobal", 1);
			}
			else
			{
				JRequest::setVar("showglobal", 0);
			}
		}
		if (!$juser->authorise('core.manage', 'com_jevpeople'))
		{
			JRequest::setVar("showall", 1);
		}

		// Make sure the type filter is set to the first (if its blank)
		JRequest::setVar("type_id", $this->getFirstType());

		$model = & $this->getModel();
		$total = & $this->get('Total');
		$items = & $this->get('Data');
		$pagination = & $this->get('Pagination');

		// New custom fields
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$template = $compparams->get("template", "");
		if ($template != "" && $compparams->get("custinlist"))
		{
			$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
			if (file_exists($xmlfile))
			{
				$db = JFactory::getDBO();
				foreach ($items as &$item)
				{
					$db->setQuery("SELECT * FROM #__jev_customfields2 WHERE target_id=" . intval($item->pers_id) . " AND targettype='com_jevpeople'");
					$cfdata  = $db->loadObjectList('name');
					$customdata = array();
					foreach ($cfdata as $dataelem)
					{
						if (strpos($dataelem->name, ".") !== false)
						{
							$dataelem->name = str_replace(".", "_", $dataelem->name);
						}
						$customdata[$dataelem->name] = $dataelem->value;
					}

					$jcfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind($customdata);
					$jcfparams->setEvent($item);
					
					$customfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$customfields = array_merge($customfields, $jcfparams->renderToBasicArray('params', $group));
						}
					}
					
					$item->customfields = $customfields;
					unset($item);
				}
			}
		}

		// build list of categories
		$javascript = 'onchange="document.adminForm.submit();"';
		$compparams = JComponentHelper::getParams("com_jevpeople");

		$lists['typefilter'] = $this->typeFilter(false);

		$firsttype = $this->getFirstType();
		$typefilter = array(intval(JFactory::getApplication()->getUserStateFromRequest("type_type_id", 'type_id', $firsttype)));

		$lists['catid'] = $this->buildCategorySelect(intval($filter_catid), $javascript, "", true, false, 0, 'filter_catid', "com_jevpeople", $typefilter);
		$lists['catid'] = str_replace(JText::_('JEV_EVENT_ALLCAT'), JText::_('ALL_CATEGORIES'), $lists['catid']);

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

	function select($tpl = null)
	{

		JEVHelper::script('people.js', 'administrator/components/' . JEVEX_COM_COMPONENT . '/assets/js/', true);

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
		JToolBarHelper::title(JText::_('SELECT_PERSON'), 'generic.png');
		// Only offer management buttons if use is authorised
		if (JevPeopleHelper::canCreateOwn())
		{
			//JToolBarHelper::addNew("people.edit","Create Person");
			$this->toolbarButton("people.edit", "new", "new", JText::_('CREATE_PERSON'), false);
			$this->toolbarButton("people.overview", "config", "config", JText::_('MANAGE_PEOPLE'), false); }

		$this->showToolBar();

		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('JEVENTS_EXTRAS') . ' :: ' . JText::_('JEVENTS_EXTRAS'));

		$db =  JFactory::getDBO();
		$uri =  JFactory::getURI();

		$filter_perstype = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_perstype', 'filter_perstype', 0, 'int');
		$filter_catid = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_catid', 'filter_catid', 0, 'int');
		$filter_order = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order', 'filter_order', 'pers.ordering', 'cmd');
		$filter_order_Dir = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order_Dir', 'filter_order_Dir', '', 'word');
		$search = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_search', 'search', '', 'string');
		$search = JString::strtolower($search);

		// Get data from the model
		$model = & $this->getModel();
		$model->setState("select", true);
		$model->setState("perstype", $filter_perstype);

		$total = & $this->get('Total');
		$items = & $this->get('Data');
		$pagination = & $this->get('Pagination');

		// build list of categories
		$compparams = JComponentHelper::getParams("com_jevpeople");

		$lists['typefilter'] = $this->typeFilter(false);

		$firsttype = $this->getFirstType();
		$typefilter = array(intval(JFactory::getApplication()->getUserStateFromRequest("type_type_id", 'type_id', $firsttype)));

		$javascript = 'onchange="document.adminForm.submit();"';
		$lists['catid'] = $this->buildCategorySelect(intval($filter_catid), $javascript, "", true, false, 0, 'filter_catid', "com_jevpeople", $typefilter);
		$lists['catid'] = str_replace(JText::_('JEV_EVENT_ALLCAT'), JText::_('ALL_CATEGORIES'), $lists['catid']);

		$options = array();
		$options[] = JHTML::_('select.option', 0, JText::_('ANY_PERSON'));
		$options[] = JHTML::_('select.option', 1, JText::_('MY_PEOPLE'));
		$options[] = JHTML::_('select.option', 2, JText::_('COMMON_PEOPLE'));
		$lists["perstype"] = JHTML::_('select.genericlist', $options, 'filter_perstype', 'class="inputbox" size="1" onchange="form.submit();"', 'value', 'text', $filter_perstype);

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
		JLoader::register('jevpeopleCategory', JPATH_COMPONENT_ADMINISTRATOR . "/libraries/categoryClass.php");

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
		$text = !$edit ? JText::_('NEW') : JText::_('NEW');
		JToolBarHelper::title(JText::_('PERSON') . ': <small><small>[ ' . $text . ' ]</small></small>');
		$this->toolbarButton("people.save", "save", "save", "Save", false);
		if (!$edit)
		{
			$this->toolbarButton("people.cancel", "cancel", "cancel", "Cancel", false);
		}
		else
		{
			// for existing items the button is renamed `close`
			$this->toolbarButton("people.cancel", "cancel", "cancel", "Close", false);
		}

		$this->showToolBar();

		$compparams = JComponentHelper::getParams("com_jevpeople");
		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('JEVENTS_EXTRAS') . ' :: ' . JText::_('JEVENTS_EXTRAS'));

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		$db = & JFactory::getDBO();
		$uri = & JFactory::getURI();
		$user = & JFactory::getUser();
		$model = & $this->getModel();

		$lists = array();

		//get the person
		$person = & $this->get('data');
		$isNew = ($person->pers_id < 1);

		// get the type
		$typemodel = & $this->getModel("type", "PeopleTypesModel");
		$typeid = $person->type_id > 0 ? $person->type_id : $this->getFirstType();
		$person->type_id = $typeid;
		$typemodel->setId($typeid);
		$perstype = $typemodel->getData();

		if ($perstype->showaddress > 0)
		{

			$compparams = JComponentHelper::getParams("com_jevpeople");
			$googlekey = JevPeopleHelper::getApiKey();
			$googleurl = JevPeopleHelper::getApiUrl(); 
			$googlemapsurl = JevPeopleHelper::getMapsUrl(); 
			if ($googlekey != "")
			{
				JHTML::script($googleurl.'/maps/api/js?key=' . $googlekey . "&amp;sensor=false",  true);
			}
			else
			{
				JHTML::script($googleurl.'/maps/api/js?sensor=false', true);
			}
			
			if ($isNew || ($person->geolon == 0 && $person->geolat == 0))
			{
				$long = $compparams->get("long", 30);
				$lat = $compparams->get("lat", 30);
				$zoom = 10;
			}
			else
			{
				$long = $person->geolon;
				$lat = $person->geolat;
				$zoom = $person->geozoom;
			}
			$script = <<<SCRIPT
var globallong = $long;
var globallat = $lat;
var globalzoom = $zoom;
SCRIPT;
			$document->addScriptDeclaration($script);
		}


		JEVHelper::script('people.js', 'administrator/components/' . $option . '/assets/js/', true, false);

// fail if checked out not by 'me'
		if ($model->isCheckedOut($user->get('id')))
		{
			$msg = JText::sprintf('DESCBEINGEDITTED', JText::_('THE_PERSON'), $person->title);
			JFactory::getApplication()->redirect('index.php?option=' . $option, $msg);
		}

// Edit or Create?
		if (!$isNew)
		{
			$model->checkout($user->get('id'));
		}
		else
		{
			// initialise new record
			$person->published = 1;
			$person->approved = 1;
			$person->order = 0;
			$person->overlaps = 0;
			$person->mapicon = "blue-icon.png";
			$person->catid = JRequest::getVar('catid', 0, 'post', 'int');
			if (JevPeopleHelper::canCreateGlobal() && $compparams->get("commondefault", 0))
			{
				$person->global = 1;
			}
		}

		$lists['catid0'] = $this->buildCategorySelect($person->catid0, "", "", true, true, 0, 'catid0', 'com_jevpeople', array($typeid));
		$lists['catid0'] = str_replace(JText::_('JEV_EVENT_CHOOSE_CATEG'), JText::_('CHOOSE_CATEGORY'), $lists['catid0']);

		$lists['catid1'] = $this->buildCategorySelect($person->catid1, "", "", true, true, 0, 'catid1', 'com_jevpeople', array($typeid));
		$lists['catid1'] = str_replace(JText::_('JEV_EVENT_CHOOSE_CATEG'), JText::_('CHOOSE_CATEGORY'), $lists['catid1']);

		$lists['catid2'] = $this->buildCategorySelect($person->catid2, "", "", true, true, 0, 'catid2', 'com_jevpeople', array($typeid));
		$lists['catid2'] = str_replace(JText::_('JEV_EVENT_CHOOSE_CATEG'), JText::_('CHOOSE_CATEGORY'), $lists['catid2']);

		$lists['catid3'] = $this->buildCategorySelect($person->catid3, "", "", true, true, 0, 'catid3', 'com_jevpeople', array($typeid));
		$lists['catid3'] = str_replace(JText::_('JEV_EVENT_CHOOSE_CATEG'), JText::_('CHOOSE_CATEGORY'), $lists['catid3']);

		$lists['catid4'] = $this->buildCategorySelect($person->catid4, "", "", true, true, 0, 'catid4', 'com_jevpeople', array($typeid));
		$lists['catid4'] = str_replace(JText::_('JEV_EVENT_CHOOSE_CATEG'), JText::_('CHOOSE_CATEGORY'), $lists['catid4']);

// build the html select list
		$lists['published'] = JHTML::_('select.booleanlist', 'published', 'class="inputbox"', $person->published);

// build the html select list
		$lists['global'] = JHTML::_('select.booleanlist', 'global', 'class="inputbox"', $person->global);
		$lists['overlaps'] = JHTML::_('select.booleanlist', 'overlaps', 'class="inputbox"', $person->overlaps);

		// list of media files
		jimport("joomla.filesystem.folder");
		$filelist = JFolder::files(JPATH_SITE . "/media/com_jevpeople/images/", "\.png");
		$files = array();
		foreach ($filelist as $file)
		{
			$files[] = array("val" => $file, "text" => $file);
		}
		$lists["mapicon"] = JHTML::_('select.genericlist', $files, 'mapicon', "", 'val', 'text', $person->mapicon);
		
// Person Type
		if ($typemodel = JModelLegacy::getInstance("types", "PeopleTypesModel"))
		{
			$typedata = $typemodel->getData();
		}

		$options = array();
//$options[] = JHTML::_('select.option', 0 ,JText::_( 'COM_JEVPEOPLE_SELECT_TYPE' ),'type_id', 'title');
		$options = array_merge($options, $typedata);
		$lists["type"] = JHTML::_('select.genericlist', $options, 'type_id', 'class="inputbox" size="1" onchange="alert(\'You must save the person and re-edit to show the correct category options\');" ', 'type_id', 'title', $person->type_id);

//clean person data
		JFilterOutput::objectHTMLSafe($person, ENT_QUOTES, 'description');

		//$file = JPATH_COMPONENT . "/" . 'models' . "/" . 'person.xml';
	//	$params = new JRegistry($person->params, $file);

		$this->assignRef('lists', $lists);
		$this->assignRef('person', $person);
		//$this->assignRef('params', $params);
		$this->assignRef('perstype', $perstype);

		parent::display($tpl);

	}

	function detail($tpl = null)
	{

		JLoader::register('JEventsHTML', JPATH_SITE . "/components/com_jevents/libraries/jeventshtml.php");
		JLoader::register('jevpeopleCategory', JPATH_COMPONENT_ADMINISTRATOR . "/libraries/categoryClass.php");

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		// Set toolbar items for the page

		$compparams = JComponentHelper::getParams("com_jevpeople");

		//get the person
		$db = & JFactory::getDBO();
		$uri = & JFactory::getURI();
		$user = & JFactory::getUser();
		$model = & $this->getModel();

		$person = & $this->get('data');

		// get the type
		$typemodel = & $this->getModel("type", "PeopleTypesModel");
		$typeid = $person->type_id > 0 ? $person->type_id : $this->getFirstType();
		$typemodel->setId($typeid);
		$perstype = $typemodel->getData();

		$document = & JFactory::getDocument();
		$document->setTitle(JText::_('EVENT_PERSON') . " :: " . $person->title);

		$subtitle = str_replace(" ", "+", $person->title);

		$lists = array();
		if ($perstype->showaddress > 0)
		{

			$compparams = JComponentHelper::getParams("com_jevpeople");
			$googlekey = JevPeopleHelper::getApiKey(); 
			$googleurl = JevPeopleHelper::getApiUrl(); 
			$googlemapsurl = JevPeopleHelper::getMapsUrl(); 
			if ($googlekey != "")
			{
				JHTML::script($googleurl.'/maps/api/js?key=' . $googlekey . "&amp;sensor=false",  true);
			}
			else
			{
				JHTML::script($googleurl.'/maps/api/js?sensor=false', true);
			}

			$long = $person->geolon;
			$lat = $person->geolat;
			$zoom = $person->geozoom;

			$script = "var urlroot = '" . JURI::root() . "/media/com_jevlocations/images/';\n";
			$script.=<<<SCRIPT
var globallong = $long;
var globallat = $lat;
var globalzoom = $zoom;
var globaltitle = "$subtitle";
var googleurl = "$googleurl";
var googlemapsurl = "$googlemapsurl";
var maptype = "ROADMAP";
SCRIPT;
			$document->addScriptDeclaration($script);
		}
		JHTML::script( 'components/com_jevpeople/assets/js/persondetail.js');

		$dispatcher = & JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');
		$params = new JRegistry(null);
		$tmprow = new stdClass();
		$tmprow->text = $person->description;
		$dispatcher->trigger('onPrepareContent', array(&$tmprow, &$params, 0));
		$person->description = $tmprow->text;

		// New custom fields
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$template = $compparams->get("template", "");
		$person->customfields = array();
		if ($template != "")
		{
			$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $template;
			if (file_exists($xmlfile))
			{
				$db = JFactory::getDBO();
				$db->setQuery("SELECT * FROM #__jev_customfields2 WHERE target_id=" . intval($person->pers_id) . " AND targettype='com_jevpeople'");
				$cfdata  = $db->loadObjectList('name');
				$customdata = array();
				foreach ($cfdata as $dataelem)
				{
					if (strpos($dataelem->name, ".") !== false)
					{
						$dataelem->name = str_replace(".", "_", $dataelem->name);
					}
					$customdata[$dataelem->name] = $dataelem->value;
				}
				if (count($customdata)) {
					$jcfparams = JevCfForm::getInstance("com_jevpeople.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind($customdata);
					$jcfparams->setEvent($person);
					$customfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$customfields = array_merge($customfields, $jcfparams->renderToBasicArray('params', $group));
						}
					}
					
					$person->customfields = $customfields;
				}
			}
		}
		
		$this->assignRef('person', $person);
		$this->assignRef('perstype', $perstype);

		parent::display($tpl);

	}

	function upload($tpl = null)
	{
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

	function showToolBar()
	{
		$mainframe = JFactory::getApplication();
		if (JRequest::getVar("tmpl", "") == "component" || !JFactory::getApplication()->isAdmin())
		{
			?>
			<div id="toolbar-box" >
				<div class="t">
					<div class="t">
						<div class="t"></div>
					</div>
				</div>
				<div class="m">
					<?php
					$bar = & JToolBar::getInstance('toolbar');
					$barhtml = $bar->render();
					$barhtml = preg_replace('/onclick="(.*)" /', 'onclick="$1;return false;" ', $barhtml);
					echo $barhtml;
					if (JVersion::isCompatible("3.0")){
						$title = JFactory::getApplication()->JComponentTitle;
					}
					else {
						$title = JFactory::getApplication()->get('JComponentTitle');
					}
					echo $title;
					?>
					<div class="clr"></div>
				</div>
				<div class="b">
					<div class="b">
						<div class="b"></div>	
					</div>
				</div>
			</div>
			<?php
		}
		// Kepri doesn't load icons etc. when using tmpl=component - but we want them!
		if (JRequest::getVar("tmpl", "") == "component" && JFactory::getApplication()->isAdmin())
		{
			JHTML::stylesheet('administrator/templates/' . JFactory::getApplication()->getTemplate() . '/css/template.css');
		}

	}

	function _globalHTML(&$row, $i)
	{

		$img = $row->global ? 'tick.png' : 'publish_x.png';
		$alt = $row->global ? JText::_('GLOBAL') : JText::_('GLOBAL');

		$mainframe = JFactory::getApplication();
		if (JVersion::isCompatible("1.6.0"))
		{
			if ($mainframe->isAdmin())
			{
				$img = JHTML::_('image', 'admin/' . $img, $alt, NULL, true);
			}
			else
			{
				$img = '<img src="' . JURI::root() . 'administrator/templates/bluestork/images/admin/' . $img . '" border="0" alt="' . $alt . '" /></a>';
			}
		}
		else
		{
			if ($mainframe->isAdmin())
			{
				$img = '<img src="images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
			}
			else
			{
				$img = '<img src="' . JURI::root() . 'administrator/images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
			}
		}

		if (JevPeopleHelper::canCreateGlobal())
		{
			$action = $row->global ? JText::_('MAKE_PRIVATE') : JText::_('MAKE_PRIVATE');
			$task = $row->global ? "people.privatise" : "people.globalise";

			$href = '
		<a href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'' . $task . '\')" title="' . $action . '">
		' . $img;

			return $href;
		}
		return $img;

	}

	function typeFilter($asinput = false)
	{
		$typefilter = $this->getFirstType();

		$query = 'SELECT tp.type_id AS value, tp.title AS text FROM #__jev_peopletypes AS tp order by title';
		$db = & JFactory::getDBO();
		$db->setQuery($query);
		$options = $db->loadObjectList();

		array_unshift($options, JHTML::_('select.option', 0, JText::_('COM_JEVPEOPLE_SELECT_TYPE')));

		if (!$asinput)
			return JText::_('PEOPLE_TYPE') . " : " . JHTML::_('select.genericlist', $options, 'type_id', 'class="inputbox" size="1" onchange="if (document.getElementById(\'filter_catid\')) {document.getElementById(\'filter_catid\').value=0;};form.submit();"', 'value', 'text', $typefilter);
		else
			return JHTML::_('select.genericlist', $options, 'type_id', 'class="inputbox" size="1" ', 'value', 'text', $typefilter);

	}

	function getFirstType()
	{
		if (!$this->firsttype)
		{
			$query = 'SELECT * FROM #__jev_peopletypes AS tp order by title limit 1';
			$db = & JFactory::getDBO();
			$db->setQuery($query);
			$firsttype = $db->loadObject();
			$mainframe = JFactory::getApplication();
			$this->firsttype = intval(JFactory::getApplication()->getUserStateFromRequest("type_type_id", 'type_id', $firsttype->type_id));
		}
		return $this->firsttype;

	}

	/**
	 * Build HTML selection list of categories
	 *
	 * @param int $catid				Selected catid
	 * @param string $args				Additional HTML attributes for the <select> tag
	 * @param string $catidList			Restriction list of categories
	 * @param boolean $with_unpublished	Set true to build list with unpublished categories
	 * @param boolean $require_sel		First entry: true = Choose one category, false = All categories
	 * @param int $catidtop				Top level category ancestor
	 */
	function buildCategorySelect($catid, $args, $catidList=null, $with_unpublished=false, $require_sel=false, $catidtop=0, $fieldname="catid", $section, $types=array())
	{

		// need to declare this because of bug in Joomla  JHtml::_('select.options', on content pages - it loade the WRONG CLASS!
		if (JVersion::isCompatible("3.0")){
		//	include_once(JPATH_SITE . "/libraries/cms/html/category.php");
		}
		else {
			include_once(JPATH_SITE . "/libraries/joomla/html/html/category.php");
		}
		ob_start();
		$t_first_entry = ($require_sel) ? JText::_('JEV_EVENT_CHOOSE_CATEG') : JText::_('JEV_EVENT_ALLCAT');
		$options = $this->categoryOptions($section, $types);
		if ($catidList != null)
		{
			$cats = explode(',', $catidList);
			$count = count($options);
			for ($o = 0; $o < $count; $o++)
			{
				if (!in_array($options[$o]->value, $cats))
				{
					unset($options[$o]);
				}
			}
			$options = array_values($options);
		}
		?>
		<select name="<?php echo $fieldname; ?>" id="<?php echo $fieldname; ?>" <?php echo $args; ?> >
			<option value=""><?php echo $t_first_entry; ?></option>
			<?php echo JHtml::_('select.options', $options, 'value', 'text', $catid); ?>
		</select>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns an array of categories for the given extension.
	 *
	 * @param	string	The extension option.
	 * @param	array	An array of configuration options. By default, only published and unpulbished categories are returned.
	 *
	 * @return	array
	 */
	public static function categoryOptions($extension, $types = array())
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.id, a.title, a.level');
		$query->from('#__categories AS a');
		$query->where('a.parent_id > 0');
		$query->where('a.published = 1');
		if (count($types) > 0 && $types[0] != 0)
		{
			$where = array();
			foreach ($types as $type)
			{
				$where[] = "a.params like ('%\"type\":\"$type\"%')";
			}
			$query->where("(" . implode(" OR ", $where) . " )");
		}

		// Filter on extension.
		$query->where('extension = ' . $db->quote($extension));

		$query->order('a.lft');

		$db->setQuery($query);

		//echo (string) $query;
		$items = $db->loadObjectList();

		// Assemble the list options.
		$options = array();

		foreach ($items as &$item)
		{
			$repeat = ( $item->level - 1 >= 0 ) ? $item->level - 1 : 0;
			$item->title = str_repeat('- ', $repeat) . $item->title;
			$options[] = JHtml::_('select.option', $item->id, $item->title);
		}

		return $options;

	}

}
