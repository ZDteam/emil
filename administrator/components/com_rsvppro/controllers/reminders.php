<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( '_JEXEC' ) or die( 'Restricted Access' );

jimport('joomla.application.component.controller');


class AdminRemindersController extends JControllerLegacy {

	/**
	 * Controler for Sessions 
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerDefaultTask("record");
		$this->registerTask( 'record',  'recordReminder' );

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

	}


}
