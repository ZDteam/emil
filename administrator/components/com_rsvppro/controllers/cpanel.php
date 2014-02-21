<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: cpanel.php 1429 2009-04-28 16:45:57Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

jimport('joomla.application.component.controller');

class AdminCpanelController extends JControllerLegacy
{

	/**
	 * Controler for the Control Panel
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('show', 'cpanel');
		$this->registerDefaultTask("cpanel");

	}

	public function cpanel()
	{

		// get the view
		$this->view = & $this->getView("cpanel", "html");

		// Set the layout
		$this->view->setLayout('cpanel');
		$this->view->assign('title', JText::_("Control_Panel"));

		$this->view->display();

	}
}
