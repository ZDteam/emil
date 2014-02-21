<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view');

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminCPanelViewCPanel extends JViewLegacy  
{
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function cpanel($tpl = null)
	{
		jimport('joomla.html.pane');

		$option=JRequest::getCmd("option");
		JHTML::stylesheet(  'administrator/components/'.$option.'/assets/css/jevpeople.css' );	 	
		if (!JVersion::isCompatible("3.0.0")){
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin16.css');
		}
		else {
			JHTML::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin.css' );
		}

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_( 'MANAGED_PEOPLE' ) . ' :: ' .JText::_( 'MANAGED_PEOPLE' ));
		
		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'MANAGED_PEOPLE' ) .' :: '. JText::_( 'MANAGED_PEOPLE' ), 'jevents' );
		JToolBarHelper::preferences('com_jevpeople', '580', '750');

		$this->_hideSubmenu();
		
		$mainframe=JFactory::getApplication();
		if (JFactory::getApplication()->isAdmin()){
			//JToolBarHelper::preferences(JEVEX_COM_COMPONENT, '580', '750');
		}

		JSubMenuHelper::addEntry(JText::_( 'CONTROL_PANEL' ), 'index.php?option='.$option, true);
		
		$params = JComponentHelper::getParams($option);
		
		parent::display($tpl);
	}	

	 
	/**
	 * This method creates a standard cpanel button
	 *
	 * @param unknown_type $link
	 * @param unknown_type $image
	 * @param unknown_type $text
	 */
	function _quickiconButton( $link, $image, $text, $path=null, $target='', $onclick='' ) {
	 	if( $target != '' ) {
	 		$target = 'target="' .$target. '"';
	 	}
	 	if( $onclick != '' ) {
	 		$onclick = 'onclick="' .$onclick. '"';
	 	}
	 	if( $path === null || $path === '' ) {
	 		$option=JRequest::getCmd("option");
	 		$path = 'administrator/components/'.$option.'/assets/images/';
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
	 * Routine to hide submenu suing CSS since there are no paramaters for doing so without hiding the main menu
	 *
	 */
	function _hideSubmenu(){
		$option=JRequest::getCmd("option");
		JHTML::stylesheet( 'hidesubmenu.css', 'administrator/components/'.$option.'/assets/css/' );	 	
	}
	 
}