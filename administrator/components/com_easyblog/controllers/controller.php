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

jimport('joomla.application.component.controller');

// Require parent controller
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'parent.php' );

class EasyBlogController extends EasyBlogParentController
{
	function __construct($config = array())
	{
		// Include the tables in path
		JTable::addIncludePath( EBLOG_TABLES );

		$doc	= JFactory::getDocument();
		$version	= str_ireplace( '.' , '' , EasyBlogHelper::getLocalVersion() );

		$doc->addStyleSheet( rtrim( JURI::root() , '/' ) . '/administrator/components/com_easyblog/assets/css/reset.css?' . $version );
		$doc->addStyleSheet( rtrim( JURI::root() , '/' ) . '/components/com_easyblog/assets/css/common.css?' . $version );
		$doc->addStyleSheet( rtrim( JURI::root() , '/' ) . '/administrator/components/com_easyblog/assets/css/style.css?' . $version );

		$url		= rtrim( JURI::root() , '/' );

		$currentURL		= isset( $_SERVER[ 'HTTP_HOST' ] ) ? $_SERVER[ 'HTTP_HOST' ] : '';

		if( !empty( $currentURL ) )
		{
			// When the url contains www and the current accessed url does not contain www, fix it.
			if( stristr($currentURL , 'www' ) === false && stristr( $url , 'www') !== false )
			{
				$url	= str_ireplace( 'www.' , '' , $url );
			}

			// When the url does not contain www and the current accessed url contains www.
			if( stristr( $currentURL , 'www' ) !== false && stristr( $url , 'www') === false )
			{
				$url	= str_ireplace( '://' , '://www.' , $url );
			}
		}

		$config 				= EasyBlogHelper::getConfig();
		$enableLightbox  		= $config->get( 'main_media_lightbox_preview' ) ? 'true' : 'false';
		$lightboxTitle 			= $config->get( 'main_media_show_lightbox_caption' ) ? 'true' : 'false';
		$enforceLightboxSize 	= $config->get( 'main_media_lightbox_enforce_size' ) ? 'true' : 'false';
		$lightboxWidth 			= $config->get( 'main_media_lightbox_max_width' );
		$lightboxHeight 		= $config->get( 'main_media_lightbox_max_height' );
		$lightboxStripExtension = $config->get( 'main_media_lightbox_caption_strip_extension' ) ? 'true' : 'false';

		$lightboxWidth 			= ( empty( $lightboxWidth ) ) ? '640' : $lightboxWidth;
		$lightboxHeight 		= ( empty( $lightboxHeight ) ) ? '480' : $lightboxHeight;

		$token 		= EasyBlogHelper::getHelper( 'Token' )->get();
		$url 		.= '/administrator/index.php?option=com_easyblog&' . $token . '=1';

		// @task: Legacy ejax global variables.
		$ajaxData	=  "/*<![CDATA[*/
	var eblog_site = '" . $url ."';
	var eblog_auth	= '" . $token . "';
	var lang_direction	= '" . $doc->direction . "';
	var eblog_lightbox_title = " . $lightboxTitle . ";
	var eblog_enable_lightbox = " . $enableLightbox . ";
	var eblog_lightbox_enforce_size = " . $enforceLightboxSize . ";
	var eblog_lightbox_width = " . $lightboxWidth . ";
	var eblog_lightbox_height = " . $lightboxHeight . ";
	var eblog_lightbox_strip_extension = " . $lightboxStripExtension . ";
/*]]>*/";

		$doc->addScriptDeclaration( $ajaxData );

		// @task: Load foundry bootstrap.
		require_once( JPATH_ROOT . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'foundry' . DIRECTORY_SEPARATOR . '2.1' . DIRECTORY_SEPARATOR . 'joomla' . DIRECTORY_SEPARATOR . 'bootstrap.php' );

		// @task: Set EasyBlog's environment
		$easyblogEnvironment = JRequest::getVar( 'easyblog_environment' , $config->get( 'easyblog_environment' ) );

		// @task: Create abstract component.
		$folder 	= ( $easyblogEnvironment == 'development' ) ? 'scripts_/' : 'scripts/';
		$doc->addScript( EBLOG_MEDIA_URI . $folder . 'abstract.js' );
		$doc->addScript( rtrim( JURI::root() , '/' ) . '/administrator/components/com_easyblog/assets/js/admin.js?' . $version );

		// @task: Load component bootstrap.
		ob_start();
			include( EBLOG_MEDIA . DIRECTORY_SEPARATOR . 'bootstrap.js' );
			$bootstrap = ob_get_contents();
		ob_end_clean();

		$doc->addScriptDeclaration( $bootstrap );

		// For the sake of loading the core.js in Joomla 1.6 (1.6.2 onwards)
		if( EasyBlogHelper::getJoomlaVersion() >= '1.6' )
		{
			JHTML::_('behavior.framework');
		}

		parent::__construct();
	}

	/**
	 * Override parent's display method
	 *
	 * @since 0.1
	 */
	function display( $cachable = false, $urlparams = false )
	{
		$document	= JFactory::getDocument();

		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->getName() );
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );

		$view		= $this->getView( $viewName, $viewType, '' );

		// Set the layout
		$view->setLayout($viewLayout);

		$format		= JRequest::getCmd( 'format' , 'html' );

		// Test if the call is for Ajax
		if( !empty( $format ) && $format == 'ejax' )
		{
			// Ajax calls.
			if(! JRequest::checkToken( 'GET' ) )
			{
				$ejax	= new Ejax();
				$ejax->script( 'alert("' . JText::_('Not allowed here') . '");' );
				$ejax->send();
			}


			$data		= JRequest::get( 'POST' );
			$arguments	= array();

			foreach( $data as $key => $value )
			{
				if( JString::substr( $key , 0 , 5 ) == 'value' )
				{
					if(is_array($value))
					{
						$arrVal    = array();
						foreach($value as $val)
						{
							$item   = $val;
							$item   = stripslashes($item);
							$item   = rawurldecode($item);
							$arrVal[]   = $item;
						}

						$arguments[]	= $arrVal;
					}
					else
					{
						$val			= stripslashes( $value );
						$val			= rawurldecode( $val );
						$arguments[]	= $val;
					}
				}


			}

			if(!method_exists( $view , $viewLayout ) )
			{
				$ejax	= new Ejax();
				$ejax->script( 'alert("' . JText::sprintf( 'Method %1$s does not exists in this context' , $viewLayout ) . '");');
				$ejax->send();

				return;
			}

			// Execute method
			call_user_func_array( array( $view , $viewLayout ) , $arguments );
		}
		else
		{
			// Non ajax calls.
			// Get/Create the model
			if ($model = $this->getModel($viewName))
			{
				// Push the model into the view (as default)
				$view->setModel($model, true);
			}

			ob_start();

			if( $viewLayout != 'default' )
			{
				if( $cachable )
				{
					$cache	= JFactory::getCache( 'com_easyblog' , 'view' );
					$cache->get( $view , $viewLayout );
				}
				else
				{
					if( !method_exists( $view , $viewLayout ) )
					{
						$view->display();
					}
					else
					{
						// @todo: Display error about unknown layout.
						$view->$viewLayout();
					}
				}
			}
			else
			{
				$view->display();
			}

			$html = ob_get_contents();
			ob_end_clean();

			$class 			= EasyBlogHelper::getJoomlaVersion() >= '3.0' ? 'eb-bootstrap' : 'eb-joomla';

			// @task: Set additional wrapper for dashboard views
			if( JRequest::getVar( 'view' ) == 'blog' )
			{
				$wrapper	= '<script type="text/javascript">';
				$wrapper	.= 'EasyBlog.require()';
				$wrapper	.= '	.script("dashboard")';
				$wrapper	.= '	.done(function($){';
				$wrapper	.= '		$("#ezblog-dashboard")';
				$wrapper	.= '			.implement(EasyBlog.Controller.Dashboard, {});';
				$wrapper	.= '	});';
				$wrapper	.= '</script>';
				$wrapper	.= '<div id="ezblog-dashboard" class="' . $class . '">';
				$wrapper	.= $html;


				$wrapper	.= '</div>';

				echo $wrapper;

			}
			else
			{
				echo '<div class="' . $class . '">';
				echo $html;
				echo '</div>';
			}

			echo '<span id="easyblog-token" style="display:none;"><input type="hidden" name="' . EasyBlogHelper::getHelper( 'Token' )->get() . '" value="1" /></span>';

			// Add necessary buttons to the site.
			if( method_exists( $view , 'registerToolbar' ) )
			{
				$view->registerToolbar();
			}

			// Override submenu if needed
			if( method_exists( $view , 'registerSubmenu' ) )
			{
				$this->_loadSubmenu( $view->getName() , $view->registerSubmenu() );
			}
		}
	}

	/**
	 * Overrides parent method
	 **/
	public static function getInstance( $controllerName, $config = array() )
	{
		static $instances;

		if( !$instances )
		{
			$instances	= array();
		}

		// Set the controller name
		$className	= 'EasyBlogController' . ucfirst( $controllerName );

		if( !isset( $instances[ $className ] ) )
		{
			if( !class_exists( $className ) )
			{
				jimport( 'joomla.filesystem.file' );
				$controllerFile	= EBLOG_CONTROLLERS . DIRECTORY_SEPARATOR . JString::strtolower( $controllerName ) . '.php';

				if( JFile::exists( $controllerFile ) )
				{
					require_once( $controllerFile );

					if( !class_exists( $className ) )
					{
						// Controller does not exists, throw some error.
						JError::raiseError( '500' , JText::sprintf('Controller %1$s not found' , $className ) );
					}
				}
				else
				{
					// File does not exists, throw some error.
					JError::raiseError( '500' , JText::sprintf('Controller %1$s.php not found' , $controllerName ) );
				}
			}

			$instances[ $className ]	= new $className($config);
		}
		return $instances[ $className ];
	}

	function _loadSubmenu( $viewName , $path = 'submenu.php' )
	{
		JHTML::_('behavior.switcher');

		//Build submenu
		$contents = '';
		ob_start();
		require_once( EBLOG_ADMIN_ROOT . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . DIRECTORY_SEPARATOR . 'tmpl' . DIRECTORY_SEPARATOR . $path );

		$contents = ob_get_contents();
		ob_end_clean();

		$document	= JFactory::getDocument();

		$document->setBuffer($contents, 'modules', 'submenu');
	}

	function ajaxGetSystemString()
	{
		$data = JRequest::getVar('data');
		echo JText::_(strtoupper($data));
	}

	function updatedb()
	{
		require_once(JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_easyblog' . DIRECTORY_SEPARATOR . 'install.defaultvalue.php');

		$type = 'error';
		if( !updateEasyBlogDBColumns() )
		{
			$message = 'Error updating DB columns';
			$this->setRedirect( 'index.php?option=com_easyblog' , $message , $type );
			return;
		}

		if( !truncateACLTable() )
		{
			$message = 'Error truncating ACL table';
			$this->setRedirect( 'index.php?option=com_easyblog' , $message , $type );
			return;
		}

		if( !updateACLRules() )
		{
			$message = 'Error updating ACL rules';
			$this->setRedirect( 'index.php?option=com_easyblog' , $message , $type );
			return;
		}

		migrateJomSocialStreamNameSpace();

		$type = 'message';
		$message = 'DB Updated';

		$this->setRedirect( 'index.php?option=com_easyblog' , $message , $type );
	}

	public function checkAccess( $acl )
	{
		// @rule: Test for user access if on 1.6 and above
		if( EasyBlogHelper::getJoomlaVersion() >= '1.6' )
		{
			if(!JFactory::getUser()->authorise('easyblog.manage.' . $acl , 'com_easyblog') )
			{
				JFactory::getApplication()->redirect( 'index.php?option=com_easyblog' , JText::_( 'JERROR_ALERTNOAUTHOR' ) , 'error' );
				JFactory::getApplication()->close();
			}
		}
	}
}
