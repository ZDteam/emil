<?php
/**
* @version $Id: mod_jdownloads_last_downloaded_files.php
* @package mod_jdownloads_last_downloaded_files
* @copyright (C) 2008/2012 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Modul shows the last downloaded files from the component jDownloads. 
*   Support: www.jDownloads.com
*/

// this is a default layout and used tables - you can also create a alternate layout and select it afterwards in the module configuration

defined('_JEXEC') or die;
    
    $html = '';
    $html = '<table width="100%" class="moduletable'.$moduleclass_sfx.'">';
    
    $sum_files = count($files);
    if ($sum_view > $sum_files) $sum_view = $sum_files;
    
    if ($files) {
        if ($text_before <> ''){
            $html .= '<tr><td class="td_jd_ldf_before">'.$text_before.'</td></tr>';   
        }
        for ($i=0; $i<$sum_view; $i++) {
            $version = $short_version;
            if ($sum_char > 0){
                $gesamt = strlen($files[$i]->file_title) + strlen($files[$i]->release) + strlen($short_version) +1;
                if ($gesamt > $sum_char){
                   $files[$i]->file_title = JString::substr($files[$i]->file_title, 0, $sum_char).$short_char;
                   $files[$i]->release = '';
                }    
            }
            
            $database->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=viewcategory&catid=".$files[$i]->cat_id."' and published = 1");
            $Itemid = $database->loadResult();
            if (!$Itemid){
                $Itemid = $root_itemid;
            }  
                
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

            if ($detail_view == '1'){
                $link = 'index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewdownload&catid='.$files[$i]->cat_id.'&cid='.$files[$i]->file_id;
            } else {    
                $link = 'index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewcategory&catid='.$files[$i]->cat_id;
            }    
            if ($sef==1){
                $link = JRoute::_($link);
            }
            if (!$files[$i]->release) $version = '';
            
            // build icon
            $size = 0;
            $files_pic = '';
            $number = '';
            if ($view_pics){
                $size = (int)$view_pics_size;
                $files_pic = '<img src="'.JURI::base().'images/jdownloads/fileimages/'.$files[$i]->file_pic.'" align="top" width="'.$size.'" height="'.$size.'" border="0" alt="" /> '; 
            }
            // build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            if ($view_tooltip){
                $sum_char_desc = strlen($files[$i]->description);
                if ($sum_char_desc > $view_tooltip_length){
                    $files[$i]->description = substr($files[$i]->description,0,$view_tooltip_length).$short_char;
                }    
                $link_text = '<a href="'.$link.'">'.JHTML::tooltip(strip_tags($files[$i]->description),JText::_('MOD_JDOWNLOADS_LAST_DOWNLOADED_FILES_DESCRIPTION_TITLE'),$files[$i]->file_title.' '.$version.$files[$i]->release,$files[$i]->file_title.' '.$version.$files[$i]->release).'</a>';                
            } else {    
                $link_text = '<a href="'.$link.'">'.$files[$i]->file_title.' '.$version.$files[$i]->release.'</a>';
            }    
            $html .= '<tr valign="top"><td align="'.$alignment.'">'.$number.$files_pic.$link_text.'</td>';
            
            if ($view_date) {
                    if ($view_date_text) $view_date_text .= '&nbsp;';
                    if ($view_date_same_line){
                        if ($view_user){
                            $html .= '<td align="'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.JHTML::Date($files[$i]->date,$date_format,false).$view_user_by.' '.$files[$i]->username.'</td>';
                        } else {
                            $html .= '<td align="'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.JHTML::Date($files[$i]->date,$date_format,false).'</td>';
                        }    
                    } else {
                        if ($view_user){
                            $html .= '</tr><tr><td align="'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.JHTML::Date($files[$i]->date,$date_format,false).$view_user_by.' '.$files[$i]->username.'</td>';
                        } else {
                            $html .= '</tr><tr><td align="'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.JHTML::Date($files[$i]->date,$date_format,false).'</td>';
                        }    
                    }    
            } else {
                if ($view_user){
                    $html .= '</tr><tr><td align="'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_user_by.' '.$files[$i]->username.'</td>';
                }
            }    
            $html .= '</tr>'; 
            if ($cat_show_text2) {
                if ($cat_show_as_link){
                    $html .= '<tr valign="top"><td align="'.$alignment.'" style="font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;Itemid='.$Itemid.'&amp;view=viewcategory&catid='.$files[$i]->cat_id.'">'.$cat_show_text2.'</a></td></tr>';
                } else {    
                    $html .= '<tr valign="top"><td align="'.$alignment.'" style="font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text2.'</td></tr>';
                }
            }
        
        }
        if ($text_after <> ''){
            $html .= '<tr><td class="td_jd_ldf_after">'.$text_after.'</td></tr>';
        }
    }
    
    echo $html.'</table>';
?>		