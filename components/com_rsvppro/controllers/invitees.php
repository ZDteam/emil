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

include_once(RSVP_ADMINPATH."/controllers/invitees.php");

class FrontInviteesController extends AdminInviteesController {


	function __construct($config = array())
	{
		parent::__construct($config);

		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);

		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE.'/components/com_community/community.php')){
			if (JComponentHelper::isEnabled("com_community")) {
				$this->jomsocial = true;
			}
		}

	}


}