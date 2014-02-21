<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

include_once(RSVP_ADMINPATH."/controllers/templates.php");

class FrontTemplatesController extends AdminTemplatesController   {

	function __construct($config = array())
	{
		parent::__construct($config);
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR."/models/");

		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Load abstract "view" class
		$cfg = & JEVConfig::getInstance();
		$theme = JEV_CommonFunctions::getJEventsViewName();
		JLoader::register('JEvents'.ucfirst($theme).'View',JEV_VIEWS."/$theme/abstract/abstract.php");
		
		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
	}


}