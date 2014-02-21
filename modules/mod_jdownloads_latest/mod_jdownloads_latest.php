<?php
/**
* @version $Id: mod_jdownloads_latest.php v2.0
* @package mod_jdownloads_latest
* @copyright (C) 2011 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Modul shows the newest added downloads from the component jDownloads. 
*   It is only for jDownloads 1.9 and later (Support: www.jDownloads.com)
*/

defined( '_JEXEC' ) or die( 'Restricted access' );
// Include the weblinks functions only once
require_once dirname(__FILE__).'/helper.php';



    $database = JFactory::getDBO();
    JHTML::_('behavior.tooltip');
    $config=JFactory::getConfig();
    $sef=$config->get("sef");
    $current_itemid = JRequest::getVar("Itemid");
    
    // get published root menu link
    $database->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=viewcategories' and published = 1");
    $root_itemid = $database->loadResult();

    $text_before           = trim($params->get( 'text_before' ) );
    $text_after            = trim($params->get( 'text_after' ) );
    $cat_id                = trim($params->get( 'cat_id' ) );
    $sum_view              = intval(($params->get( 'sum_view' ) ));
    $sum_char              = intval(($params->get( 'sum_char' ) ));
    $short_char            = $params->get( 'short_char' ) ; 
    $short_version         = $params->get( 'short_version' );
    $detail_view           = $params->get( 'detail_view' ) ; 
    $view_date             = $params->get( 'view_date' ) ;
    $view_date_same_line   = $params->get( 'view_date_same_line' ); 
    $date_format           = $params->get( 'date_format' ) ;
    $view_pics             = $params->get( 'view_pics' ) ;
    $view_pics_size        = $params->get( 'view_pics_size' ) ;
    $view_numerical_list   = $params->get( 'view_numerical_list' );
    $view_thumbnails       = $params->get( 'view_thumbnails' );
    $view_thumbnails_size  = $params->get( 'view_thumbnails_size' );
    $view_thumbnails_dummy = $params->get( 'view_thumbnails_dummy' );
    $date_alignment        = $params->get( 'date_alignment' ); 
    $cat_show              = $params->get( 'cat_show' );
    $cat_show_type         = $params->get( 'cat_show_type' );
    $cat_show_text         = $params->get( 'cat_show_text' );
    $cat_show_text_color   = $params->get( 'cat_show_text_color' );
    $cat_show_text_size    = $params->get( 'cat_show_text_size' );
    $cat_show_as_link      = $params->get( 'cat_show_as_link' ); 
    $view_tooltip          = $params->get( 'view_tooltip' ); 
    $view_tooltip_length   = intval($params->get( 'view_tooltip_length' ) ); 
    $alignment             = $params->get( 'alignment' );
    
    $thumbfolder = JURI::base().'images/jdownloads/screenshots/thumbnails/';
    $thumbnail = '';
    $border = ''; 
    
    $cat_show_text = trim($cat_show_text);
    if ($cat_show_text) $cat_show_text = ' '.$cat_show_text.' ';

    if ($sum_view == 0) $sum_view = 5;
    $option = 'com_jdownloads';
        
    $files = modJdownloadsLatestHelper::getList($params);

    if (!count($files)) {
	    return;
    }

    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

    require JModuleHelper::getLayoutPath('mod_jdownloads_latest',$params->get('layout', 'default'));
?>