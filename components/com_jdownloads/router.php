<?php
/**
* @version 1.5
* @package JDownloads
* @copyright (C) 2009 www.jdownloads.com
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
* 
*
*/  

defined( '_JEXEC' ) or die( 'Restricted access' );
// error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT) );
error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);

function jdownloadsBuildRoute(&$query) {
    global $jlistConfig;

    jimport('joomla.filter.output');
    $db = JFactory::getDBO();
        
    if ($jlistConfig === NULL){
        // get at first the data
        $jlistConfig = array();
        $db->setQuery("SELECT setting_name, setting_value FROM #__jdownloads_config");
        $jlistConfigObj = $db->loadObjectList();
        if(!empty($jlistConfigObj)){
            foreach ($jlistConfigObj as $jlistConfigRow){
                $jlistConfig[$jlistConfigRow->setting_name] = $jlistConfigRow->setting_value;
            }
        }
        $GLOBALS['jlistConfig'] = $jlistConfig;
    }
    
    $segments    = array();
    
    
    if (isset($query['view'])){
        if ($query['view'] != 'viewcategories'){
            $segments[] = $query['view'];
            unset($query['view']);
        } else {
            unset($query['view']);
        }
    }        
        
               
    if (isset($query['viewcategory'])){
        $segments[] = $query['viewcategory'];
        unset($query['viewcategory']);
    }
    if (isset($query['viewdownload']) || isset($query['view.download'])){
        $segments[] = $query['viewdownload'];
        unset($query['viewdownload']);
    } 
    if (isset($query['summary'])){
        $segments[] = $query['summary'];
        unset($query['summary']);
    }
    if (isset($query['finish'])){
        $segments[] = $query['finish'];
        unset($query['finish']);
    }    
    if (isset($query['search'])){
        $segments[] = $query['search'];
        unset($query['search']);
    }
    if (isset($query['searchresult'])){
        $segments[] = $query['searchresult'];
        unset($query['searchresult']);
    }  
    if (isset($query['upload'])){
        $segments[] = $query['upload'];
        unset($query['upload']);
    }          
    if (isset($query['edit'])){
        $segments[] = $query['edit'];
        unset($query['edit']);
    }
    if (isset($query['save'])){
        $segments[] = $query['save'];
        unset($query['save']);
    }
    if (isset($query['catid'])){
        if ($jlistConfig['use.sef.with.file.titles']){
            $db->setQuery("SELECT cat_title FROM #__jdownloads_cats WHERE cat_id = ".(int) $query['catid']);
            $segments[] = $query['catid'].':'.JFilterOutput::stringURLSafe($db->loadResult());
        } else {
            $segments[] = $query['catid'];
        }
        unset($query['catid']);
    }
    if (isset($query['cid'])){
        if ($jlistConfig['use.sef.with.file.titles']){
            $db->setQuery("SELECT file_title FROM #__jdownloads_files WHERE file_id = ".(int) $query['cid']);
            $segments[] = $query['cid'].':'.JFilterOutput::stringURLSafe($db->loadResult());
        } else {
            $segments[] = $query['cid'];
        }
        unset($query['cid']);
    }
    if (isset($query['m'])){
        $segments[] = $query['m'];
        unset($query['m']);
    }
    if (isset($query['list'])){
        $segments[] = $query['list'];
        unset($query['list']);
    }
    if (isset($query['user'])){
        $segments[] = $query['user'];
        unset($query['user']);
    }    
    
        
    return $segments;
}

function jdownloadsParseRoute($segments) {

       $vars = array();
       
       switch($segments[0])
       {
               case 'viewcategory':
                       $vars['view'] = 'viewcategory';
                       $catid = explode( ':', $segments[1] );
                       $vars['catid'] = (int) $catid[0];
                       break;
               case 'viewdownload':
               case 'view.download':
                       $vars['view'] = 'viewdownload';
                       if (isset($segments[2])){
                          $catid = explode( ':', $segments[1] );
                          $vars['catid'] = (int) $catid[0];
                          $id = explode( ':', $segments[2] );
                          $vars['cid'] = (int) $id[0];
                       } else {
                          $id = explode( ':', $segments[1] );
                          $vars['cid'] = (int) $id[0];
                       }   
                       if (isset($segments[3])){
                           $m = explode( ':', $segments[3] );
                           $vars['m'] = (int) $m[0];
                       }    
                       break;
               case 'summary':
                       $vars['view'] = 'summary';
                       $catid = explode( ':', $segments[1] );
                       $vars['catid'] = (int) $catid[0];
                       $cid = explode( ':', $segments[2] );
                       $vars['cid'] = (int) $cid[0];
                        if (isset($segments[3])){
                           $m = explode( ':', $segments[3] );
                           $vars['m'] = (int) $m[0];
                       }
                       break;
               case 'finish':
                       $vars['view'] = 'finish';
                       $vars['catid'] = (int) $segments[1];
                       $dummy = $segments[3];
                       if ($dummy < 5) {
                           $id = explode( ':', $segments[2] );
                           $vars['cid'] = (int) $id[0];
                           $m = explode( ':', $segments[3] );
                           $vars['m'] = (int) $m[0];     
                       } else {     
                            // only when multiple downloads with checkboxes
                            $list = explode( ',', $segments[2] );
                            $vars['list'] = implode(',',$list);
                            $vars['user'] = (int) $segments[3];
                       }
                       break;
               case 'search':
                       $vars['view'] = 'search';
                       break;
               case 'searchresult':
                       $vars['view'] = 'searchresult';
                       break;
               case 'upload':
                       $vars['view'] = 'upload';
                       break; 
               case 'edit':
                       $vars['view'] = 'edit';
                       $id = explode( ':', $segments[1] );
                       $vars['cid'] = (int) $id[0];
                       break;
               case 'save':
                       $vars['view'] = 'save';
                       $id = explode( ':', $segments[1] );
                       $vars['cid'] = (int) $id[0];
                       break;
               case 'report':
                       $vars['view'] = 'report';
                       $cid = explode( ':', $segments[1] );
                       $vars['cid'] = (int) $cid[0];
                       break;                                             
                       
               case 'viewcategories':
                       $vars['view'] = '';
                       break;
               
       }
       return $vars;
} 
?>