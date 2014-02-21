<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: typeController.php 1117 2008-07-06 17:20:59Z tstahl $
 * @package     JEvents
 * @copyright   Copyright (C) 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://joomlacode.org/gf/project/jevents
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin');

class AdminTypesController extends JControllerAdmin
{

	var $component = null;
	var $typeTable = null;
	var $typeClassname = null;

	/**
	 * Controler for the Control Panel
	 * @param array		configuration
	 */
	function __construct($config = array())
	{

		parent::__construct($config);
		$this->registerTask('list', 'overview');
		$this->registerDefaultTask("overview");

		$this->component = JEVEX_COM_COMPONENT;
		$this->typeTable = "#__jev_peopletypes";
		$this->typeClassname = "JevPeopleType";

	}

	function overview()
	{
		$user = & JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_jevpeople'))
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "Not Authorised- must be able to manage component");
			return;
		}

		$this->setupLayouts();

		// get the view
		$viewName = "types";
		$this->view = & $this->getView($viewName, "html");

		// Set the layout
		$this->view->setLayout('overview');
		$this->view->assign('title', JText::_('PEOPLE_TYPES_LIST'));
		$jevuser = JEVHelper::getAuthorisedUser();
		$this->view->assign('jevuser', $jevuser);

		// Get/Create the model
		if ($model = & $this->getModel($viewName, "PeopleTypesModel"))
		{
			// Push the model into the view (as default)
			$this->view->setModel($model, true);
		}

		$this->view->overview();

	}

	function edit()
	{

		$user = & JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_jevpeople'))
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "Not Authorised- must be able to manage component");
			return;
		}

		// get the view
		$viewName = "types";
		$this->view = & $this->getView($viewName, "html");

		JRequest::setVar('hidemainmenu', 1);

		$returntask = JRequest::getVar('returntask', "types.overview");
		if ($returntask != "types.list" && $returntask != "types.overview" && $returntask != "types.select")
		{
			$returntask = "types.overview";
		}
		$this->view->assign('returntask', $returntask);

		// Set the layout
		$this->view->setLayout('edit');
		$this->view->assign('title', JText::_('PERSON_EDIT'));
		$jevuser = JEVHelper::getAuthorisedUser();
		$this->view->assign('jevuser', $jevuser);

		// Get/Create the model
		if ($model = & $this->getModel("type", "PeopleTypesModel"))
		{
			// Push the model into the view (as default)
			$this->view->setModel($model, true);
		}

		$this->view->edit();

	}

	function cancel()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$user = & JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_jevpeople'))
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "Not Authorised- must be able to manage component");
			return;
		}

		$returntask = JRequest::getVar('returntask', "types.overview");
		if ($returntask != "types.list" && $returntask != "types.overview")
		{
			$returntask = "types.overview";
		}
		$tmpl = "";

		if (method_exists($this, str_replace("types.", "", $returntask)))
		{
			$returntask = str_replace("types.", "", $returntask);
			return $this->$returntask();
		}
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}
		$link = JRoute::_('index.php?option=com_jevpeople&task=' . $returntask . $tmpl);
		$this->setRedirect($link);

	}

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$user = & JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_jevpeople'))
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "Not Authorised- must be able to manage component");
			return;
		}

		$post = JRequest::get('post');
		$cid = JRequest::getVar('cid', array(0), 'post', 'array');
		$post['type_id'] = (int) $cid[0];

		$model = & $this->getModel("type", "PeopleTypesModel");

		if ($model->store($post))
		{
			$msg = JText::_('PERSON_TYPE_SAVED');
		}
		else
		{
			$msg = JText::_('ERROR_SAVING_PERSON_TYPE') . " - " . $model->getError();
		}

		$returntask = JRequest::getVar('returntask', "types.overview");
		if ($returntask != "types.list" && $returntask != "types.overview")
		{
			$returntask = "types.overview";
		}
		if (method_exists($this, str_replace("types.", "", $returntask)))
		{
			$returntask = str_replace("types.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$link = JRoute::_('index.php?option=com_jevpeople&task=' . $returntask . $tmpl);
		$this->setRedirect($link, $msg);

	}

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$user = & JFactory::getUser();
		if (!$user->authorise('core.manage', 'com_jevpeople'))
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "Not Authorised- must be able to manage component");
			return;
		}

		$cid = JRequest::getVar('cid', array(), 'post', 'array');
		JArrayHelper::toInteger($cid);

		if (count($cid) < 1)
		{
			JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_DELETE'));
		}

		$model = & $this->getModel("type", "PeopleTypesModel");
		if (!$model->delete($cid))
		{
			echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
		}

		$returntask = JRequest::getVar('returntask', "types.overview");
		if ($returntask != "types.list" && $returntask != "types.overview" && $returntask != "types.select")
		{
			$returntask = "types.overview";
		}
		if (method_exists($this, str_replace("types.", "", $returntask)))
		{
			$returntask = str_replace("types.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=types.list' . $tmpl));

	}

	private function setupLayouts()
	{
		// Make sure DB is up to date - with definitions for layout defaults		
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jev_peopletypes");
		$ptypes = $db->loadObjectList("type_id");

		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' ");
		$defaults = $db->loadObjectList('name');

		$layoutTypes = array("detail", "bloglist");

		foreach ($ptypes as $type_id => $ptype)
		{

			if (!isset($defaults['com_jevpeople.people.' . $type_id . '.detail']))
			{
				$db->setQuery("INSERT INTO  #__jev_defaults set name='com_jevpeople.people.$type_id.detail',
							title=" . $db->Quote(JText::sprintf("JEV_PEOPLE_DETAIL_PAGE", $ptype->title)) . ",
							subject='',
							value='',                                                                                                                              
							state=0");
				$success = $db->query();
			}
			else if ($defaults['com_jevpeople.people.' . $type_id . '.detail']->title == "JEV_PEOPLE_DETAIL_PAGE" || $defaults['com_jevpeople.people.' . $type_id . '.detail']->title == "Managed People/Resources Details for %s")
			{
				$db->setQuery("UPDATE #__jev_defaults set 
							title=" . $db->Quote(JText::sprintf("JEV_PEOPLE_DETAIL_PAGE", $ptype->title)) . "
							WHERE id=" . $defaults['com_jevpeople.people.' . $type_id . '.detail']->id);
				$success = $db->query();
			}
			if (!isset($defaults['com_jevpeople.people.' . $type_id . '.bloglist']))
			{
				$db->setQuery("INSERT INTO  #__jev_defaults set name='com_jevpeople.people.$type_id.bloglist',
							title=" . $db->Quote(JText::sprintf("JEV_PEOPLE_BLOGLIST_PAGE", $ptype->title)) . ",
							subject='',
							value='',
							state=0");
				$success = $db->query();
			}
			else if ($defaults['com_jevpeople.people.' . $type_id . '.bloglist']->title == "JEV_PEOPLE_BLOGLIST_PAGE" || $defaults['com_jevpeople.people.' . $type_id . '.bloglist']->title == "Managed People/Resources Details for %s")
			{
				$db->setQuery("UPDATE #__jev_defaults set 
							title=" . $db->Quote(JText::sprintf("JEV_PEOPLE_BLOGLIST_PAGE", $ptype->title)) . "
							WHERE id=" . $defaults['com_jevpeople.people.' . $type_id . '.bloglist']->id);
				$success = $db->query();
			}
		}

		// remove dead layouts
		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' ");
		$defaults = $db->loadObjectList('name');
		foreach ($defaults as $def)
		{
			foreach ($layoutTypes as $layoutType)
			{
				$type = str_replace('com_jevpeople.people.', '', $def->name);
				$type = str_replace('.' . $layoutType, '', $type);
				if (!array_key_exists($type, $ptypes))
				{
					$db->setQuery("DELETE FROM #__jev_defaults where name='com_jevpeople.people.$type.$layoutType'");
					$success = $db->query();
				}
			}
		}
		// clean up duplicates - keep the first instance always
		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' order by id asc");
		$defaults = $db->loadObjectList();
		$types = array();
		foreach ($defaults as $def)
		{
			if (in_array($def->name, $types))
			{
				//echo "remove $def->name : $def->title<br/>";
				$db->setQuery("DELETE FROM #__jev_defaults where id=" . $def->id);
				$success = $db->query();
			}
			$types[] = $def->name;
		}

		//We check selected options of the layouts and update parameters of the layout
		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' ");
		$defaults = $db->loadObjectList('name');

		foreach ($ptypes as $type_id => $ptype)
		{

			// We update parameters
			unset($params);

			foreach ($layoutTypes as $layoutType)
			{
				$params = new JRegistry($defaults['com_jevpeople.people.' . $type_id . '.' . $layoutType]->params);
				$params->set("showaddress", $ptype->showaddress);

				$db->setQuery("UPDATE #__jev_defaults set 
							params='" . $params->toString() . "'
							WHERE id=" . $defaults['com_jevpeople.people.' . $type_id . '.' . $layoutType]->id);
				$success = $db->query();
			}
		}

	}

}
