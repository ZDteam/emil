<?php
/**
* @version $Id: mod_jdownloads_latest.php
* @package mod_jdownloads_latest
* @copyright (C) 2013 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Modul shows the newest added downloads from the component jDownloads. 
*   Support: www.jDownloads.com
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

class modJdownloadsLastDownloadedFilesHelper
{
	static function getList($params)
	{
		$database   = JFactory::getDBO(); 
		$user       = JFactory::getUser(); 
		$config     = JFactory::getConfig();
		$sef        = $config->get("sef");

		$app = JFactory::getApplication();
		$appParams = $app->getParams();
		
		$cat_id          = trim($params->get( 'cat_id' ) );
		$sum_view        = intval(($params->get( 'sum_view' ) ));
        $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
        $in_groups       = 0;           

		if ($sum_view == 0) $sum_view = 5;

        // get download logs
        // get more logs as necessary - is needed when jD user groups are used
        // is the result not enough set it higher 
        $sum_views = $sum_view + 10;
        
        $database->setQuery("SELECT * FROM #__jdownloads_log ORDER BY log_datetime DESC LIMIT $sum_views");
        $logs = $database->loadObjectList();
        if (!$logs){
            // no logs found - exit
            $html = '';
            $html = '<table width="100%" class="moduletable'.$moduleclass_sfx.'">';
            if ($user->aid == 2){
                // view admin info
                $html .= '<tr><td>'.JText::_('LOG_OPTION_NOT_ACTIVE').'</td></tr>';
            }
            $html .= '</table>';
            echo $html;
            return;
        } else {
            $logs_array = array();
            foreach ($logs as $log){
                  if ($log->log_user){  
                    $database->setQuery("SELECT name FROM #__users WHERE id = '$log->log_user'");
                    $log->log_username = $database->loadResult();
                  } else {
                    $log->log_username = JText::_('GUEST');
                  }  
                  $logs_array[] = $log->log_file_id;
            } 
            $logs_id = implode(',', $logs_array);
                   
        }    

        // get published root menu link
        $database->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=viewcategories' and published = 1");
        $root_itemid = $database->loadResult();    
        
        // get user categories access rights
            /* special user group:
             3 = author
             4 = editor
             5 = publisher
             6 = manager
             7 = admin
             8 = super admin - super user
         */ 
            
        $coreUserGroups = $user->getAuthorisedGroups();
        $aid = max ($user->getAuthorisedViewLevels());
        
        $access = '';
        if ($aid == 1) $access = '02'; // public
        if ($aid == 2) $access = '11'; // regged or member from custom joomla group
        if ($aid == 3 || in_array(3,$coreUserGroups) || in_array(4,$coreUserGroups) || in_array(5,$coreUserGroups) || in_array(6,$coreUserGroups)) $access = '22'; // special user
        if (in_array(8,$coreUserGroups) || in_array(7,$coreUserGroups)){
            // is admin or super user
            $access = '99';
        }
        if (!$access){
            if ($user->id){
                $access = '11';
            } else {
                $access = '02';
            }
        } 
        
        // get cat access groups
        if ($user->id){
            $database->setQuery("SELECT id FROM #__jdownloads_groups WHERE FIND_IN_SET($user->id, groups_members)");
            $in_groups = implode(',', $database->loadColumn());           
        } 
        if (!$in_groups) $in_groups = 999999;     
        
        // check that new field exists for groups
        $groups_exists  = false;
        $prefix         = $database->getPrefix();
        $tables         = $prefix.'jdownloads_cats';
        $result         = $database->getTableColumns( $tables );

        if ($result['cat_group_access']) $groups_exists = true; 

        // only given cat id's
        $catids = array(); 
        if ($cat_id != 0) {
            if ($groups_exists){
                $database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND cat_id IN ($cat_id)  OR parent_id IN ($cat_id) AND (cat_access <= '$access' OR cat_group_access IN ($in_groups))");
                // $database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND cat_id IN ($cat_id) AND (cat_access <= '$access' OR cat_group_access IN ($in_groups))");
            } else {
                $database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND cat_id IN ($cat_id) AND cat_access <= '$access'");
            }
        } else {
            // all categories
            if ($groups_exists){
                $database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND (cat_access <= '$access' OR cat_group_access IN ($in_groups))");
            } else {
               $database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND cat_access <= '$access'");
            }        
        }
        $catids = $database->loadColumn(0);

	    if ($catids){
            $catid = implode(',', $catids);
            $x = 0;
            foreach ($logs as $log){
                $database->setQuery('SELECT * FROM #__jdownloads_files WHERE published = 1 AND cat_id IN ('.$catid.') AND file_id = '.$log->log_file_id);
                if ($files[$x] = $database->loadObject()){ 
                $files[$x]->sort     = $x;
                $files[$x]->username = $log->log_username;
                $files[$x]->date     = $log->log_datetime;
                $x++;
                } else {
                  array_pop($files);
                }
            } 
            return $files;  
        }		
		    
	    }
}
?>