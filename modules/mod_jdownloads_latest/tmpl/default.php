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

// this is a default layout and used tables - you can also select a alternate tableless layout in the module configuration

defined('_JEXEC') or die;

		echo '<table width="100%" class="moduletable'.$moduleclass_sfx.'">';
		if ($text_before <> ''){
			echo '<tr><td>'.$text_before.'</td></tr>';   
		}
		for ($i=0; $i<count($files); $i++) {
            $thumbnail = ''; 
            $version = $params->get('short_version', ''); 

            // short the file title?			
            if ($sum_char > 0){
				$gesamt = strlen($files[$i]->file_title) + strlen($files[$i]->release) + strlen($short_version) +1;
				if ($gesamt > $sum_char){
				   $files[$i]->file_title = JString::substr($files[$i]->file_title, 0, $sum_char).$short_char;
				   $files[$i]->release = '';
				}    
			} 
			
            // get for every item the menu link itemid when exists 
			$database->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=viewcategory&catid=".$files[$i]->cat_id."' and published = 1");
			$Itemid = $database->loadResult();
			if (!$Itemid){
				$Itemid = $root_itemid;
			}  

            // create the viewed category text   			
            if ($cat_show) {
				if ($cat_show_type == 'containing') {
					$database->setQuery('SELECT cat_title FROM #__jdownloads_cats WHERE cat_id = '.$files[$i]->cat_id);
					$cattitle = $database->loadResult();
					$cat_show_text2 = $cat_show_text.$cattitle;
				} else {
					$database->setQuery('SELECT cat_dir FROM #__jdownloads_cats WHERE cat_id = '.$files[$i]->cat_id);
					$catdir = $database->loadResult();
					$cat_show_text2 = $cat_show_text.$catdir;
				}
			} else {
				$cat_show_text2 = '';
			}    
						   
			// create the link
            if ($detail_view == '1'){
				$link = 'index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewdownload&catid='.$files[$i]->cat_id.'&cid='.$files[$i]->file_id;
			} else {    
				$link = 'index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewcategory&catid='.$files[$i]->cat_id;
			}    
			if ($sef==1){
				$link = JRoute::_($link);
			}
			
            if (!$files[$i]->release) $version = '';
			
			// add mime file pic
            $size = 0;
			$files_pic = '';
			$number = '';
			if ($view_pics){
				$size = (int)$view_pics_size;
				$files_pic = '<img src="'.JURI::base().'images/jdownloads/fileimages/'.$files[$i]->file_pic.'" align="top" width="'.$size.'" height="'.$size.'" border="0" alt="" /> '; 
			}
			if ($view_numerical_list){
				$num = $i+1;
				$number = "$num. ";
			}    
			
            // add description in tooltip 
            if ($view_tooltip){
				$link_text = '<a href="'.$link.'">'.JHTML::tooltip(strip_tags(substr($files[$i]->description,0,$view_tooltip_length)).$short_char,JText::_('MOD_JDOWNLOADS_LATEST_DESCRIPTION_TITLE'),$files[$i]->file_title.' '.$version.$files[$i]->release,$files[$i]->file_title.' '.$version.$files[$i]->release).'</a>';                
			} else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->file_title.' '.$version.$files[$i]->release.'</a>';
			}    
            
            echo '<tr valign="top"><td align="'.$alignment.'">'.$number.$files_pic.$link_text.'</td>';
            
			// add the creation date
            if ($view_date) {
				if ($files[$i]->date_added){
					if ($view_date_same_line){
						echo '<td align="'.$date_alignment.'"><small>'.JHTML::date($files[$i]->date_added, $date_format).'</small></td>'; 
					} else {
						echo '<tr valign="top"><td align="'.$date_alignment.'"><small>'.JHTML::date($files[$i]->date_added, $date_format).'</small></td>';
					}
				}    
			} 
			echo '</tr>';

            // add the first download screenshot when exists and activated in options
            if ($view_thumbnails){
                if ($files[$i]->thumbnail){
                    $thumbnail = '<img class="img" src="'.$thumbfolder.$files[$i]->thumbnail.'" align="top" style="padding:5px;" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" border="'.$border.'" alt="'.$files[$i]->file_title.'" />';
                } else {
                    // use placeholder
                    if ($view_thumbnails_dummy){
                        $thumbnail = '<img class="img" src="'.$thumbfolder.'no_pic.gif" align="top" style="padding:5px;" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" border="'.$border.'" alt="" />';    
                    }
                }
                if ($thumbnail) echo '<tr valign="top"><td align="'.$alignment.'">'.$thumbnail.'</td></tr>';
            } 
			
			// add category info 
            if ($cat_show_text2) {
				if ($cat_show_as_link){
					echo '<tr valign="top"><td align="'.$alignment.'" style="font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewcategory&catid='.$files[$i]->cat_id.'">'.$cat_show_text2.'</a></td></tr>';
				} else {    
					echo '<tr valign="top"><td align="'.$alignment.'" style="font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text2.'</td></tr>';
				}
			}    
		}
		if ($text_after <> ''){
			echo '<tr><td>'.$text_after.'</td></tr>';
		}
        echo '</table>';        
        ?>
