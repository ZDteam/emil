<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrfile extends JevrField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrfile';

	function isEnabled() {
		return false;
	}

	function loadScript($field=false) {}

	function fetchElement($name, $value, &$node, $control_name)
	{
		$plugin = JPluginHelper::getPlugin('jevents', 'jevfiles' );
		if (!$plugin) return "<strong>".JText::_("Please_install_the_JEV_Files_Addon")."</strong>";

		// intercept this to save the file
		if (isset($_FILES) && count($_FILES)>0){

			$hasfile = false;
			$uploadfile = "";
			foreach ($_FILES as $key=>$val){
				if ((strpos($key,"upload_image")===0 || strpos($key,"upload_file")===0) && $val["size"]>0){
					$hasfile =true;
					$uploadfile = $key;
					break;
				}
			}

			if ($hasfile)	return $this->processUpload($uploadfile);
		}

		JPlugin::loadLanguage( 'plg_jevents_jevfiles',JPATH_ADMINISTRATOR );

		$this->params = new JRegistry($plugin->params);

		// Get the media component configuration settings
		$params =& JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE')) {
			define('JEVP_MEDIA_BASE',    JPATH_ROOT.'/'.$params->get('image_path', 'images'.'/'.'stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root().$params->get('image_path', 'images/stories'));
		}

		// folder relative to media folder
		$folder = $this->params->get("folder","");
		if ($folder=="") {
			echo JText::_("JEV_SAVE_PLUGIN_PARAMETERS");
			return;
		}
		// ensure folder exists
		 jimport ("joomla.filesystem.folder");
		if (!JFolder::exists(JEVP_MEDIA_BASE.'/'.$folder)) {
			JFolder::create(JEVP_MEDIA_BASE.'/'.$folder);
		}

		static $setscripts;
		static $result;
		if (!isset($setscripts)){
			$setscripts = 1;
			// need session id to ensure login is maintained
			$session = JFactory::getSession();
			$targetURL = JURI::root().'index.php?task=day.listevents&option=com_jevents&tmpl=component&folder='.$folder.'&'.$session->getName().'='.$session->getId().'&'.JSession::getFormToken().'=1';

			$uploaderInit = "
			var oldAction = '';
			var oldTarget = '';
			var oldTask = '';
			var oldOption = '';
			function uploadFileType(field){
				form = document.updateattendance;
				oldAction = form.action;
				oldTarget = form.target;
				form.action = '".$targetURL."&field='+field;
				
				form.target = 'uploadtarget';
				form.submit();
				form.action = oldAction ;
				form.target = oldTarget ;
				
				var loading = document.getElementById(field+'_loading');
				loading.style.display='block';
				var loaded = document.getElementById(field+'_loaded');
				loaded.style.display='none';
			}
			
			function clearFile(elemname){
				img = document.getElementById(elemname+'_link');
				img.href = ''
				img.innerHTML='';
				img.style.display='none';
				elem = document.getElementById('custom_'+elemname);
				if (elem) elem.value = '';
			}
			
			function setLinkFileHref(){			
				iframe = frames.uploadtarget;
				if(!iframe.fname) return;
				
				elemname = iframe.fname.substr(0,iframe.fname.length-5);
				elem = document.getElementById('custom_'+elemname);
				if (elem) elem.value = iframe.filename+'|'+iframe.oname;
				elem = document.getElementById(iframe.fname);
				if (elem) elem.value = '';
				mylink = document.getElementById(elemname+'_link');
				mylink.href = '".JEVP_MEDIA_BASEURL."/$folder/'+iframe.filename;
				mylink.innerHTML = iframe.oname;
				
				var loading = document.getElementById(elemname+'_loading');
				loading.style.display='none';
				var loaded = document.getElementById(elemname+'_loaded');
				loaded.style.display='block';			
			}		
			
			";
			$document =& JFactory::getDocument();
			$document->addScriptDeclaration($uploaderInit);

			$result = '<iframe src="about:blank" style="display:none" name="uploadtarget" id="uploadtarget"></iframe>';
		}
		else {
			$result = "";
		}

		if (strpos($value,"|")>0){
			list($filename,$filetitle) = explode("|",$value);
		}
		else {
			$filename = "";
			$filetitle="";
		}

		if ($filename){
			$href = JURI::root().JEVP_MEDIA_BASEURL."/$folder/$filename";
		}
		else {
			$href = "about:blank";
		}

		$fieldname = "upload_file_".$name;
		$result .= '<a id="'.$fieldname.'_link" href="'.$href.'" style="float:left;margin-right:10px;" target="_blank">'.($filename?$filetitle:"")."</a>";
		$result .= JHtml::_( 'form.token' );
		$result .= '<input type="hidden" name="params['.$name.']" id="custom_'.$fieldname.'" value="'.$value.'" size="50"/>';
		$result .= '<label for="'.$fieldname.'_file">'.JText::sprintf("JEV_MAX_FILE_UPLOAD",number_format($this->params->get("maxupload",1000000)/1000000,2)).'MB</label><br/>';
		$result .= '<input type="file" name="'.$fieldname.'_file" id="'.$fieldname.'_file" size="50"/>';
		$result .= ' <input type="button" onclick="uploadFileType(\''.$fieldname.'\')" value="'.JText::_("jev_upload").'"/> ';
		$result .= '<input type="button" onclick="clearFile(\''.$fieldname.'\')" value="'.JText::_("jev_Delete").'"/>';
		$result .= '<div id="'.$fieldname.'_loading" class="loading" style="display:none">'.JText::_("JEV_UPLOADING_FILE_WAIT") .'</div>';
		$result .= '<div id="'.$fieldname.'_loaded" class="loaded" style="display:none">'.JText::_("JEV_UPLOAD_COMPLETE").'</div>';
		$result .= '<br style="clear:both"/>';


		return $result;

		$size = ( $node->attributes('size') ? 'size="'.$node->attributes('size').'"' : '' );
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').'"' : 'class="text_area"' );
		/*
		* Required to avoid a cycle of encoding &
		* html_entity_decode was used in place of htmlspecialchars_decode because
		* htmlspecialchars_decode is not compatible with PHP 4
		*/
		$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);

		return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$class.' '.$size.' />';
	}

	public function convertValue($value){
		return $value;
		static $values;
		if (!isset($values)){
			$values =  array();
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option["value"];
				$text = (string)$option;
				$values[$val] = $text;
			}
		}
		return $values[$value];
	}
}