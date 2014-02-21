<?php
/**
* @version $Id: mod_jdownloads_last_downloaded_files.php 
* @package mod_jdownloads_last_downloaded_files.php
* @copyright (C) 2008/2012 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Modul shows the last downloaded files from the component jDownloads. 
*   It is only for jDownloads 1.9.x and later (Support: www.jDownloads.com)
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

// Include the functions only once
require_once dirname(__FILE__).'/helper.php';

	$database   = JFactory::getDBO(); 
	$user       = JFactory::getUser(); 
	$Itemid     = JRequest::getVar("Itemid");
	$config     = JFactory::getConfig();
	$sef        = $config->get("sef");
    $current_itemid = JRequest::getVar("Itemid");
    
    JHTML::stylesheet( 'mod_jdownloads_last_downloaded_files.css','modules/'.$module->module.'/'); 
    
    // get published root menu link
    $database->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=viewcategories' and published = 1");
    $root_itemid = $database->loadResult();
    
    //$moduleclass_sfx = '';    

	$text_before     = trim($params->get( 'text_before' ) );
	$text_after      = trim($params->get( 'text_after' ) );
	$cat_id          = trim($params->get( 'cat_id' ) );
	$sum_view        = intval(($params->get( 'sum_view' ) ));
	$sum_char        = intval(($params->get( 'sum_char' ) ));
	$short_char      = ($params->get( 'short_char' ) ); 
	$short_version   = ($params->get( 'short_version' ) );
	$detail_view     = ($params->get( 'detail_view' ) ); 
	$view_date       = ($params->get( 'view_date' ) );
	$view_date_same_line = ($params->get( 'view_date_same_line' ) );
	$view_date_text  = ($params->get( 'view_date_text' ) );
	$date_format     = ($params->get( 'date_format' ) );
	$date_alignment  = ($params->get( 'date_alignment' ) );
	$view_user       = ($params->get( 'view_user' ) ); 
	$view_user_by    = ($params->get( 'view_user_by' ) ); 
	$view_pics       = ($params->get( 'view_pics' ) );
	$view_pics_size  = ($params->get( 'view_pics_size' ) );
	$view_numerical_list = ($params->get( 'view_numerical_list' ) ); 
	$cat_show    	 = ($params->get( 'cat_show' ) );
	$cat_show_type	 = ($params->get( 'cat_show_type' ) );
	$cat_show_text   =  ($params->get( 'cat_show_text' ) );
	$cat_show_text_color   = ($params->get( 'cat_show_text_color' ) );
	$cat_show_text_size    = ($params->get( 'cat_show_text_size' ) );
	$cat_show_as_link      = ($params->get( 'cat_show_as_link' ) ); 
	$view_tooltip          = ($params->get( 'view_tooltip' ) ); 
	$view_tooltip_length   = intval(($params->get( 'view_tooltip_length' ) ));
	$alignment       = ($params->get( 'alignment' ) ); 
	//$moduleclass_sfx = ($params->get( 'moduleclass_sfx' ) );

    $cat_show_text = trim($cat_show_text);
    if ($cat_show_text) $cat_show_text = ' '.$cat_show_text.' ';

    if ($sum_view == 0) $sum_view = 5;
    $option = 'com_jdownloads';

    $thumbfolder = JURI::base().'images/jdownloads/screenshots/thumbnails/';
    $thumbnail = '';
    $border = ''; 
    
    $cat_show_text = trim($cat_show_text);
    if ($cat_show_text) $cat_show_text = ' '.$cat_show_text.' ';

    if ($sum_view == 0) $sum_view = 5;
    $option = 'com_jdownloads';
        
    $files = modJdownloadsLastDownloadedFilesHelper::getList($params);

    if (!count($files)) {
	    return;
    }

    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

    require JModuleHelper::getLayoutPath('mod_jdownloads_last_downloaded_files',$params->get('layout', 'default'));

?>