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
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');
jimport( 'joomla.html.toolbar' );

class EasyBlogViewLatest extends EasyBlogView
{
	function display( $tmpl = null )
	{
		$config		= EasyBlogHelper::getConfig();
		$jConfig	= EasyBlogHelper::getJConfig();

		if( !$config->get( 'main_rss') )
		{
			return;
		}

		require_once( EBLOG_HELPERS . DIRECTORY_SEPARATOR . 'date.php' );
		require_once( EBLOG_HELPERS . DIRECTORY_SEPARATOR . 'helper.php' );
		require_once( EBLOG_HELPERS . DIRECTORY_SEPARATOR . 'string.php' );
		require_once( EBLOG_CLASSES . DIRECTORY_SEPARATOR . 'adsense.php' );

		$sort		= JRequest::getCmd('sort', $config->get( 'layout_postorder' ) );

        $model		= $this->getModel( 'Blog' );
        $data		= $model->getBlogsBy('', '', $sort , 0 , EBLOG_FILTER_PUBLISHED, null, true);

		$document	= JFactory::getDocument();
		$document->link	= EasyBlogRouter::_('index.php?option=com_easyblog&view=latest');
		$document->setTitle( JText::_( 'COM_EASYBLOG_FEEDS_LATEST_TITLE' ) );
		$document->setDescription( JText::sprintf( 'COM_EASYBLOG_FEEDS_LATEST_DESC' , JURI::root() ) );

		if(!empty($data))
		{
        	$modelPT	= $this->getModel( 'PostTag' );

			for( $i = 0; $i < count( $data ); $i++ )
			{
				$row	=& $data[ $i ];

				$blog 	= EasyBlogHelper::getTable( 'Blog' );
				$blog->load( $row->id );

				$user   = JFactory::getUser($row->created_by);
				$profile = EasyBlogHelper::getTable( 'Profile', 'Table' );
				$profile->load( $user->id );

				$created			= EasyBlogDateHelper::dateWithOffSet($row->created);
				$formatDate         = true;
				if(EasyBlogHelper::getJoomlaVersion() >= '1.6')
				{
				    $langCode   = EasyBlogStringHelper::getLangCode();
				    if($langCode != 'en-GB' || $langCode != 'en-US')
						$formatDate = false;
				}
				//$row->created       = ( $formatDate ) ? $created->toFormat( $config->get('layout_dateformat', '%A, %d %B %Y') ) : $created->toFormat();
                $row->created       = $created->toMySQL();
				if( $config->get( 'main_rss_content' ) == 'introtext' )
				{
					$row->text			= ( !empty( $row->intro ) ) ? $row->intro : $row->content;
				}
				else
				{
				    $row->text			= $row->intro . $row->content;
				}
				$row->text          = EasyBlogHelper::getHelper( 'Videos' )->strip( $row->text );
				$row->text			= EasyBlogGoogleAdsense::stripAdsenseCode( $row->text );

				$category	= EasyBlogHelper::getTable( 'Category', 'Table' );
				$category->load( $row->category_id );

				// Assign to feed item
				$title	= $this->escape( $row->title );
				$title	= html_entity_decode( $title );

				$image 				= '';
				if( $blog->getImage() )
				{
					$image 			= '<img src="' . $blog->getImage()->getSource( 'frontpage' ) . '" />';
				}

				// load individual item creator class
				$item				= new JFeedItem();
				$item->title 		= $title;
				$item->link 		= EasyBlogRouter::_('index.php?option=com_easyblog&view=entry&id=' . $row->id );
				$item->description 	= $image . $row->text;
				$item->date			= $row->created;
				$item->category   	= $category->title;
				$item->author		= $profile->getName();

				if( $jConfig->get( 'feed_email' ) != 'none' )
				{
					if( $jConfig->get( 'feed_email' ) == 'author' )
					{
						$item->authorEmail	= $user->email;
					}
					else
					{
						$item->authorEmail	= $jConfig->get( 'mailfrom' );
					}
				}


				$document->addItem( $item );
			}
		}
	}
}
