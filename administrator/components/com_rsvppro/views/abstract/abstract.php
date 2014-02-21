<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: abstract.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view');

class RSVPAbstractView extends JViewLegacy {
	
	function __construct($config = null)
	{
		parent::__construct($config);
		$this->_addPath('template', $this->_basePath.'/'.'views'.'/'.'abstract'.'/'.'tmpl');
	}

	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function display($tpl = null)
	{
		$layout = $this->getLayout();
		if (method_exists($this,$layout)){
			$this->$layout($tpl);
		} 			

		parent::display($tpl);
	}
	
	function displaytemplate($tpl = null)
	{
		return parent::display($tpl);
	}
	
	/**
	 * Routine to hide submenu suing CSS since there are no paramaters for doing so without hiding the main menu
	 *
	 */
	function _hideSubmenu(){
		JHtml::stylesheet( 'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/hidesubmenu.css' );	 	
	}

	 /**
	 * This method creates a standard cpanel button
	 *
	 * @param string $link
	 * @param string $image
	 * @param string $text
	 * @param string $path
	 * @param string $target
	 * @param string $onclick
	 * @access protected
	 */
	function _quickiconButton( $link, $image, $text, $path='/administrator/images/', $target='', $onclick='' ) {
	 	if( $target != '' ) {
	 		$target = 'target="' .$target. '"';
	 	}
	 	if( $onclick != '' ) {
	 		$onclick = 'onclick="' .$onclick. '"';
	 	}
	 	if( $path === null || $path === '' ) {
	 		$path = '/administrator/images/';
	 	}
		$alttext = str_replace("<br/>", " ", $text);
		?>
		<div style="float:left;">
			<div class="icon">
				<a href="<?php echo $link; ?>" <?php echo $target;?>  <?php echo $onclick;?> title="<?php echo $alttext;?>">
					<?php 
					//echo JHTML::_('image.administrator', $image, $path, NULL, NULL, $text ); 
					if (strpos($path, '/')===0){
						$path = substr($path,1);
					}
					echo JHTML::_('image', $path.$image, $alttext , array('title'=>$alttext), false);
					//JHtml::_('image', 'mod_languages/'.$menuType->image.'.gif', $alt, array('title'=>$menuType->title_native), true)
					?>
					<span><?php echo $text; ?></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	* Creates label and tool tip window as onmouseover event
	* if label is empty, a (i) icon is used
	*
	* @static
	* @param $tip	string	tool tip text declaring label
	* @param $label	string	label text
	* @return		string	html string
	*/
	function tip ( $tip='', $label='') {

		JHtml::_('behavior.tooltip');
		if (!$tip) {
			$str = $label;
		}
		//$tip = htmlspecialchars($tip, ENT_QUOTES);
		//$tip = str_replace('&quot;', '\&quot;', $tip);
		$tip = str_replace("'", "&#039;", $tip);
		$tip = str_replace('"', "&quot;", $tip);
		$tip = str_replace("\n", " ", $tip);
		if (!$label) {
			$str = JHtml::_('tooltip',$tip, null, 'tooltip.png', null, null, 0);
		} else {
			$str = '<span class="editlinktip">'
			. JHtml::_('tooltip',$tip, $label, null,  $label, '', 0)
			. '</span>';
		}
		return $str;
	}
	

	function toolbarButton($task = '', $icon = '', $iconOver = '', $alt = '', $listSelect = true){
		include_once(JPATH_ADMINISTRATOR."/components/com_jevents/libraries/jevbuttons.php");
		$bar = & JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton( 'Jev', $icon, $alt, $task, $listSelect );

	}

	function toolbarConfirmButton($task = '',  $msg='',  $icon = '', $iconOver = '', $alt = '', $listSelect = true){
		include_once(JPATH_ADMINISTRATOR."/components/com_jevents/libraries/jevbuttons.php");
		$bar = & JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton( 'Jevconfirm', $msg, $icon, $alt, $task, $listSelect );

	}
	
}
