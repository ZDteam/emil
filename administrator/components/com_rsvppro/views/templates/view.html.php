<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminTemplatesViewTemplates extends RSVPAbstractView
{
	
	function __construct($config = null)
	{
		parent::__construct($config);
		// always load mootools!
		JHtml::_('behavior.framework', true);
		JHtml::stylesheet( 'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/eventsadmin16.css');

	}
	
	/**
	 * Templates display function
	 *
	 * @param template $tpl
	 */
	function overview($tpl = null)
	{
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_RSVP') . ' :: ' .JText::_('RSVP_SESSION_TEMPLATES'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_SESSION_TEMPLATES' ), 'jevents' );

		//JToolBarHelper::publishList('templates.publish');
		//JToolBarHelper::unpublishList('templates.unpublish');
		JToolBarHelper::addNew('templates.edit');
		JToolBarHelper::editList('templates.edit');
		JToolBarHelper::custom('templates.editcopy','copy.png','copy.png','RSVP_COPYEDIT');
		JToolBarHelper::deleteList('RSVP_DELETE_ALL','templates.delete');
		JToolBarHelper::spacer();
		if (JFactory::getApplication()->isAdmin()){
			JToolBarHelper::custom( 'cpanel.cpanel', 'cancel.png', 'cancel.png', 'Control_Panel', false );
		}

		$this->showToolBar();

		RsvpproHelper::addSubmenu();

		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");
		$filter_order		= $mainframe->getUserStateFromRequest( $option.'rsvp_filter_order',		'filter_order',		'tmpl.title',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'rsvp_filter_order_Dir',	'filter_order_Dir',	'',				'word' );
		$search				= $mainframe->getUserStateFromRequest( $option.'rsvp_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );
		$customised			= $mainframe->getUserStateFromRequest( $option.'rsvp_customised',			'customised',			0,				'int' );
		$limit		= intval( $mainframe->getUserStateFromRequest( "templatelistlimit", 'limit', 10 ));
		$limitstart = JRequest::getInt('limitstart', 0 );

		$lists = array();
		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// search & customised filter
		$lists['search']= $search;
		$lists['customised']= $customised;

		// state filter
		$lists['state']	= "";//JHtml::_('grid.state',  $filter_state );

		JHtml::_('behavior.tooltip');

		$mainframe = JFactory::getApplication();

		$db		=& JFactory::getDBO();
		$uri	=& JFactory::getURI();

		// Get data from the model
		$model	=& $this->getModel();
		$items		= & $this->get( 'Data');
		$total		= & $this->get( 'Total');

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );
		
		$this->assignRef('user',		JFactory::getUser());
		$this->assignRef('items',		$items);
		$this->assignRef('lists',		$lists);
		$this->assignRef('pageNav',		$pageNav);

		parent::displaytemplate($tpl);


	}

	function edit($tpl = null){

		include_once(JPATH_ADMINISTRATOR.'/'."includes".'/'."toolbar.php");
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}

		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/assets/js/forms.js' );

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_EDIT_TEMPLATE'));

		// Set toolbar items for the page

		if (JRequest::getString("task")=="templates.editcopy"){
		JToolBarHelper::title(JText::_( 'RSVP_EDIT_COPY_TEMPLATE' ), 'jevents' );
			JToolBarHelper::save('templates.savecopy');
		}
		else {
		JToolBarHelper::title(JText::_( 'RSVP_EDIT_TEMPLATE' ), 'jevents' );
			JToolBarHelper::save('templates.save');
			JToolBarHelper::apply('templates.apply', "RSVP_APPLY");
		}
		if (JFactory::getApplication()->isAdmin()){
			JToolBarHelper::cancel('templates.cancel');
		}

		$this->showToolBar();

		RsvpproHelper::addSubmenu();
		
		JHtml::_('behavior.tooltip');

		$mainframe = JFactory::getApplication();

		$db		=& JFactory::getDBO();
		$uri	=& JFactory::getURI();
		
		// Get data from the model
		$model	=& $this->getModel();
		$item	= & $this->get( 'Data');
		$this->params	= &$model->getParams($item);
		$templateparams = $item->params;
		if (is_string($templateparams) && strlen(trim($templateparams))>2){
			$templateparams = new JRegistry($templateparams);
		}
		else if (is_string($templateparams) && trim($templateparams)==""){
			$templateparams = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		}
		$this->templateparams =$templateparams ;
		
		$hasAttendeesToLock = $model->hasAttendeesToLock($item);

		if (JVersion::isCompatible("3.0")){
			$this->setLayout("edit");
		}
		else {
			$this->setLayout("edit16");
		}
		$this->assignRef('item',		$item);
		
		$this->assign('hasAttendeesToLock', $hasAttendeesToLock);
		
		parent::displaytemplate($tpl);

	}


	function showToolBar(){
		$mainframe = JFactory::getApplication();
		if (JRequest::getVar("tmpl","")=="component" || !$mainframe->isAdmin()){
			?>
		<div class='jevrsvppro'>
				<div id="toolbar-box" >
					<div class="t">
					<div class="t">
						<div class="t"></div>
					</div>
				</div>
				<div class="m">
				<?php
				$bar = & JToolBar::getInstance('toolbar');
				$barhtml = $bar->render();
				$barhtml = preg_replace('/onclick="(.*)" /','onclick="$1;return false;" ',$barhtml);
				echo $barhtml;
				if (JVersion::isCompatible("3.0")){
					$title = JFactory::getApplication()->JComponentTitle;
				}
				else {
					$title = JFactory::getApplication()->get('JComponentTitle');
				}
				
				echo $title;
				?>
				<div class="clr"></div>
				</div>
				<div class="b">
					<div class="b">
						<div class="b"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
		}
		// Kepri doesn't load icons etc. when using tmpl=component - but we want them!
		if (JRequest::getVar("tmpl","")=="component" && $mainframe->isAdmin()){
			JHtml::stylesheet( 'administrator/templates/'.$mainframe->getTemplate().'/css/template.css' );

		}
	}

	function _globalHTML(&$row, $i){
		$img 	= $row->global ? 'tick.png':  'publish_x.png';
		$alt 	= $row->global ? JText::_( 'Global' ) : JText::_( 'User' );

		$mainframe = JFactory::getApplication();
		if (JVersion::isCompatible("1.6.0")) {
			if ($mainframe->isAdmin()){
				$img = JHtml::_('image','admin/'.$img, $alt, NULL, true) ;
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/templates/bluestork/images/admin/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}
		else {
			if ($mainframe->isAdmin()){
				$img = '<img src="images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}

		$action = $row->global ? JText::_( 'RSVP_MAKE_TEMPLATE_PRIVATE' ) : JText::_( 'RSVP_MAKE_TEMPLATE_COMMON' );
		$task = $row->global ? "templates.privatise":"templates.globalise";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}

	function _lockedHTML(&$row, $i){
		$img 	= $row->locked ? 'tick.png':  'publish_x.png';
		$alt 	= $row->locked ? JText::_( 'RSVP_LOCKED' ) : JText::_( 'RSVP_UNLOCKED' );

		$mainframe = JFactory::getApplication();
		if (JVersion::isCompatible("1.6.0")) {
			if ($mainframe->isAdmin()){
				$img = JHtml::_('image','admin/'.$img, $alt, NULL, true) ;
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/templates/bluestork/images/admin/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}
		else {
			if ($mainframe->isAdmin()){
				$img = '<img src="images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}

		$action = $row->locked ? JText::_( 'RSVP_UNLOCK_TEMPLATE' ) : JText::_( 'RSVP_LOCK_TEMPLATE' );
		$task = $row->locked ? "templates.unlock":"templates.lock";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}
	
	function isTemplateHTML(&$row, $i){
		$img 	= $row->istemplate ? 'tick.png':  'publish_x.png';
		$alt 	= $row->istemplate ? JText::_( 'Global' ) : JText::_( 'User' );

		$mainframe = JFactory::getApplication();
		if (JVersion::isCompatible("1.6.0")) {
			if ($mainframe->isAdmin()){
				$img = JHtml::_('image','admin/'.$img, $alt, NULL, true) ;
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/templates/bluestork/images/admin/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}
		else {
			if ($mainframe->isAdmin()){
				$img = '<img src="images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
			else {
				$img = '<img src="'.JURI::root().'administrator/images/'. $img .'" border="0" alt="'. $alt .'" /></a>';
			}
		}

		$action = $row->istemplate ? JText::_( 'RSVP_UNMAKE_TEMPLATE' ) : JText::_( 'RSVP_MAKE_TEMPLATE' );
		$task = $row->istemplate ? "templates.unmaketemplate":"templates.maketemplate";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}

}