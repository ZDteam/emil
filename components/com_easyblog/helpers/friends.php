<?php
/**
 * @package		EasyBlog
 * @copyright	Copyright (C) 2011 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * EasyBlog is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die('Restricted access');

class EasyBlogFriendsHelper
{
	/*
	 * Allows user to add the author as a friend.
	 *
	 * @param	int	$bloggerid	The blogger id.
	 */
	public function getHTML($bloggerid="")
	{
		$html	= '';
		$my		= JFactory::getUser();
		$config	= EasyBlogHelper::getConfig();

		if( $config->get( 'main_jomsocial_friends' ) && $my->id != $bloggerid )
		{
			$file_core		= JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_community' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'core.php';
			$file_messaging	= JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_community' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'friends.php';

			jimport( 'joomla.filesystem.file' );

			if( JFile::exists($file_core) && JFile::exists($file_messaging))
			{
				require_once( $file_core );
				require_once( $file_messaging );
				$user		= CFactory::getUser();

				$model		= CFactory::getModel( 'Friends' );
				$friends	= $model->getFriendIds( $bloggerid );

				if( !in_array( $my->id , $friends ) )
				{
					CFriends::load();

					$html = '<a href="javascript:void(0);" onclick="'.CFriends::getPopup( $bloggerid ).'" class="author-friend"><span>' . JText::_( 'COM_EASYBLOG_ADD_FRIEND' ) . '</span></a>';
				}
			}
		}

		return $html;
	}
}
