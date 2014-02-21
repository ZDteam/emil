<?php
/**
* @package		EasyBlog
* @copyright	Copyright (C) 2010 Stack Ideas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasyBlog is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
require( EBLOG_ADMIN_ROOT . DIRECTORY_SEPARATOR . 'views.php');

class EasyBlogViewSettings extends EasyBlogAdminView
{
	public function testMailboxConnection( $server, $port, $service, $ssl, $mailbox, $user, $pass )
	{
		$ajax		= new Ejax();

		// sanity check
		$filter		= JFilterInput::getInstance();
		$server		= $filter->clean($server, 'string');
		$port		= $filter->clean($port, 'integer');
		$service	= $filter->clean($service, 'string');
		$ssl		= $filter->clean($ssl, 'integer');
		$mailbox	= $filter->clean($mailbox, 'string');
		$server		= $filter->clean($server, 'string');
		$user		= $filter->clean($user, 'username');
		$pass		= $filter->clean($pass, 'string');

		// variable check
		if ($server=='' || $port=='' || $mailbox=='' || $user=='' || $pass=='')
		{
			$result	= JText::_( 'COM_EASYBLOG_EMAIL_CREDENTIALS_INCOMPLETE' );;
		}
		else
		{
			require_once(JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_easyblog' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'mailbox.php');
			$result	= EasyblogMailbox::testConnect($server, $port, $service, $ssl, $mailbox, $user, $pass);
		}

		$ajax->script("$('#remote_test_result').html('$result');");

		return $ajax->send();
	}
}
