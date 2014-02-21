<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: abstract.php 1400 2009-03-30 08:45:17Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML Abstract view class for the component frontend
 *
 * @static
 */
JLoader::register('JEventsDefaultView',JEV_VIEWS."/default/abstract/abstract.php");

class JEventsRuthinView extends JEventsDefaultView 
{
	var $jevlayout = null;
	var $colourscheme = "red";

	function __construct($config = null)
	{
		parent::__construct($config);

		$this->jevlayout="ruthin";	

		$this->addHelperPath(dirname(__FILE__)."/../helpers/");
		$this->addHelperPath( JPATH_BASE.'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'.JEV_COM_COMPONENT.'/'."helpers");
		
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		JEVHelper::componentStylesheet($this);
		JEVHelper::componentStylesheet($this,"w".$params->get("ruthinwidth",905).".css");

		if ($params->get("darktemplate",0)) JEVHelper::componentStylesheet($this,"dark.css");

		$document =& JFactory::getDocument();
		$stylelink = '<!--[if lte IE 6]>' ."\n";
		$stylelink .= '<link rel="stylesheet" href="'.JURI::root().'components/com_jevents/views/ruthin/assets/css/ie6.css" />' ."\n";
		$stylelink .= '<![endif]-->' ."\n"; 
		$document->addCustomTag($stylelink);
		
		$this->colourscheme = $params->get("colourscheme","red");

	}
/*
	function viewNavTableBarIconic( $today_date, $this_date, $dates, $alts, $option, $task, $Itemid ){
		$this->loadHelper("RuthinViewNavTableBarIconic");
		$var = new RuthinViewNavTableBarIconic($this, $today_date, $this_date, $dates, $alts, $option, $task, $Itemid );
	}
*/
}
