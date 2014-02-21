<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1703 2010-02-16 12:23:46Z geraint $
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
class AdminAttendeesViewAttendees extends RSVPAbstractView
{
	protected $xmlparams = array();
	
	function __construct($config = array()){
		parent::__construct($config);
		JHtml::stylesheet('administrator/components/'.JEV_COM_COMPONENT.'/assets/css/eventsadmin16.css');
		JHtml::_('behavior.framework', true);

	}

	function overview($tpl = null)
	{
		if (JVersion::isCompatible("3.0")) 	{
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		} else {
			JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin16.css' );	
		}
		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('JEV_ATTENDEES_AND_WAITING'));

		// Set toolbar items for the page
		if (count($this->rows) > 0)
		{
			$attendee = $this->rows[0];
		}
		JToolBarHelper::title( JText::_( 'JEV_ATTENDEES_AND_WAITING' )." : ".$this->event->title(), 'jevents' );
		if (JEVHelper::canEditEvent($this->repeat)){
			JToolBarHelper::addNew( 'attendees.edit');
			JToolBarHelper::deleteList("RSVP_DELETE_ATTENDEE","attendees.delete");
			JToolBarHelper::custom( 'attendees.export', 'archive.png', 'archive.png', 'RSVP_EXPORT', false );
		}

		$user = JFactory::getUser();
		 if ($user->id == $this->repeat ->created_by() || JEVHelper::isAdminUser($user) || $user->authorise('core.sendmessage', 'com_rsvppro')){
			//JToolBarHelper::custom( 'attendees.messageall', 'send.png', 'send.png', 'JEV_RSVP_SEND_MESSAGE_TO_ALL', false );
			JToolBarHelper::custom( 'attendees.message', 'send.png', 'send.png', 'JEV_RSVP_SEND_MESSAGE_TO_SELECTED',true);
		 }		
		
		if (JFactory::getApplication()->isAdmin()){
			JToolBarHelper::custom( 'cpanel.cpanel', 'default.png', 'default.png', 'JEV_ADMIN_CPANEL', false );
		}
		else {
		}	
				
		JSubMenuHelper::addEntry(
			JText::_('RSVP_SESSIONS'), 
			"index.php?option=com_rsvppro&task=sessions.list", 
			false
		);
		
		$this->showToolBar();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$this->assignRef("params",$params);
		JHtml::_('behavior.tooltip');
	}

	function transactions($tpl = null)
	{

		JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_TRANSACTIONS'));

		$attendeeName = "";
		if ($this->attendee->user_id>0){
			$user =JFactory::getUser($this->attendee->user_id);
			$attendeeName = $user->name." (".$user->username.")";
		}
		else {
			$attendeeName = $this->attendee->email_address;
		}

		JSubMenuHelper::addEntry(
			$this->event->title(), 
			"index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" .  $this->rsvpdata->id ."|". $this->attendee->rp_id ."&repeating=" . $this->rsvpdata->allrepeats, 
			false
		);

		JToolBarHelper::title( JText::_( 'RSVP_TRANSACTIONS' )." : ".$this->event->title()." :: ".$attendeeName  , 'jevents' );
		JToolBarHelper::addNew( 'attendees.newtransaction');
		JToolBarHelper::deleteList(JText::_("RSVP_DELETE_TRANSACTION", true),"attendees.deletetransaction");

		if (JFactory::getApplication()->isAdmin()){
			JToolBarHelper::custom( 'cpanel.cpanel', 'default.png', 'default.png', 'JEV_ADMIN_CPANEL', false );
		}
		else {
			JToolBarHelper::cancel( 'attendees.overview');
		}

		$this->showToolBar();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$this->assignRef("params",$params);
		JHtml::_('behavior.tooltip');
	}

	function export($tpl = null)
	{


	}

	function message($tpl = null)
	{
		$user = JFactory::getUser();
		 if ($user->id != $this->repeat ->created_by() && !JEVHelper::isAdminUser($user) && ! $user->authorise('core.sendmessage', 'com_rsvppro')){
			 return;
		 }
	
		JHtml::_('behavior.modal');
		// sequence is important since rsvpadmin overwrites the find user method
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css' );
		JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );
		JHtml::script( 'administrator/components/com_rsvppro/assets/js/rsvpadmin.js' );
		JHtml::script(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/js/forms.js' );
		
		$script = "var urlroot = '".JURI::root()."';\n";
		$script .= "var jsontoken = '".JSession::getFormToken()."';\n";
		$script .= "var jsonclient = '".JFactory::getApplication()->getClientId()."';\n";

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		$document->setTitle(JText::_('RSVP_EDIT_ATTENDEE_MESSAGE'));


		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_EDIT_ATTENDEE_MESSAGE' ), 'jevents' );
		JToolBarHelper::custom( 'attendees.message', 'send.png', 'send.png', 'JEV_RSVP_SEND_MESSAGE',false);

		// Don't show this button - we use the update button beneath!
		JToolBarHelper::cancel('attendees.overview');

		RsvpproHelper::addSubmenu();
		
		$this->showToolBar();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');
			
	}
	
	function edit($tpl = null)
	{

		JHtml::_('behavior.modal');
		// sequence is important since rsvpadmin overwrites the find user method
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css' );
		JHtml::script( 'administrator/components/com_rsvppro/assets/js/rsvpadmin.js' );

		$script = "var urlroot = '".JURI::root()."';\n";
		$script .= "var jsontoken = '".JSession::getFormToken()."';\n";
		$script .= "var jsonclient = '".JFactory::getApplication()->getClientId()."';\n";

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		$document->setTitle(JText::_('RSVP_EDIT_ATTENDEE'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_EDIT_ATTENDEE' ), 'jevents' );

		// Don't show this button - we use the update button beneath!
		//JToolBarHelper::save( 'attendees.save');
		JToolBarHelper::cancel('attendees.overview');

		RsvpproHelper::addSubmenu();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');
	}

	function edittransaction($tpl = null)
	{

		JHtml::_('behavior.modal');
		// sequence is important since rsvpadmin overwrites the find user method
		JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
		JHtml::script('administrator/components/com_rsvppro/assets/js/rsvpadmin.js' );
		JHtml::stylesheet(  'administrator/components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		$script = "var urlroot = '".JURI::root()."';\n";
		$script .= "var jsontoken = '".JSession::getFormToken()."';\n";
		$script .= "var jsonclient = '".JFactory::getApplication()->getClientId()."';\n";

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		$document->setTitle(JText::_('RSVP_EDIT_TRANSACTION'));
		
		$attendeeName = "";
		if ($this->attendee->user_id>0){
			$user =JFactory::getUser($this->attendee->user_id);
			$attendeeName = $user->name." (".$user->username.")";
		}
		else {
			$attendeeName = $attendee->email_address;
		}
		JToolBarHelper::title( JText::_( 'RSVP_TRANSACTIONS' )." : ".$this->event->title()." :: ".$attendeeName  , 'jevents' );
		JToolBarHelper::save( 'attendees.savetransaction');
		JToolBarHelper::cancel('attendees.transactions');

		RsvpproHelper::addSubmenu();
		
		$this->showToolBar();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');
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
	
}