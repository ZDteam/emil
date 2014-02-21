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

class modJdownloadsLatestHelper
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
        $in_groups       = 0; 

		if ($sum_view == 0) $sum_view = 5;

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
            $in_groups = $database->loadColumn();
            if ($in_groups){
                $in_groups = implode(',', $in_groups); 
            } 
        } 
        if (!$in_groups) $in_groups = 9999999;
			
		// only given cat id's
		$catids = array(); 
		if ($cat_id != 0) {
			$database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND cat_id IN ($cat_id)  OR parent_id IN ($cat_id) AND (cat_access <= '$access' OR cat_group_access IN ($in_groups))");
			$catids = $database->loadColumn(0);
			if ($catids){
				$catid = implode(',', $catids);
				$database->setQuery('SELECT * FROM #__jdownloads_files WHERE published = 1 AND cat_id IN ('.$catid.') ORDER BY date_added DESC limit '.$sum_view);
			}
		} else {
			// all categories
			$database->setQuery("SELECT cat_id FROM #__jdownloads_cats WHERE published = 1 AND (cat_access <= '$access' OR cat_group_access IN ($in_groups))");
			$catids = $database->loadColumn(0);
			if ($catids){
				$catid = implode(',', $catids);
				$database->setQuery('SELECT * FROM #__jdownloads_files WHERE published = 1 AND cat_id IN ('.$catid.') ORDER BY date_added DESC limit '.$sum_view);
			}    
		}
		$files = $database->loadObjectList();
		return $files;
	}
}	
?>