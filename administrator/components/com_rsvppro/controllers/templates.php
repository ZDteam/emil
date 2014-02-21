<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: cpanel.php 1429 2009-04-28 16:45:57Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

jimport('joomla.application.component.controller');

class AdminTemplatesController extends JControllerLegacy {
	/**
	 * Controler for the Control Panel
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'list',  'overview' );
		$this->registerTask( 'new',  'edit' );
		$this->registerTask( 'editcopy',  'edit' );
		$this->registerTask( 'apply',  'save' );
		$this->registerDefaultTask("overview");

		if (!JevTemplateHelper::canCreateOwn()){
			JError::raiseError( 403, JText::_('ALERTNOTAUTH') );
		}

	}

	/**
	 * List Ical Events
	 *
	 */
	function overview( )
	{
		// get the view
		$this->view = & $this->getView("templates","html");

		// Set the layout
		$this->view->setLayout('overview');

		// Get/Create the model
		if ($model = & $this->getModel("templates", "templatesModel")) {
			// Push the model into the view (as template)
			$this->view->setModel($model, true);
		}

		$this->view->overview();
	}

	function edit(){
		// get the view
		$this->view = & $this->getView("templates","html");

		// Set the layout
		$this->view->setLayout('edit');

		// Get/Create the model
		if ($model = & $this->getModel("template", "templatesModel")) {
			// Push the model into the view (as template)
			$this->view->setModel($model, true);
		}

		$this->view->edit();

	} // edittemplates()

	function cancel(){
		if (JRequest::getString("tmpl")=="component"){
			$this->close();
			return;
		}
		else {
			$this->setRedirect(JRoute::_("index.php?option=".RSVP_COM_COMPONENT."&task=templates.overview",false) );
		}
	}


	function save() {

		$cid = JRequest::getVar('cid', array(0), '', 'array');
		JArrayHelper::toInteger($cid);
		if (count($cid)>0){

			// Get/Create the model
			if ($model = & $this->getModel("template", "templatesModel")) {
				if ($model->store(JRequest::get("post",JREQUEST_ALLOWHTML))){
					$template = $model->template;
					// A New one edited directly from the event
					if ($this->getTask()=="apply"){
						$action = "";
						$link = "index.php?option=".RSVP_COM_COMPONENT."&task=templates.edit&cid[0]=".$template->id;
						if (JRequest::getInt("customise",0)){
							$link .= "&customise=1";
							$link .= "&tmpl=component";
						}
						$link .= "&evid=".JRequest::getInt("evid");
					}
					else {
						$action = "window.parent.SqueezeBox.close();";
						$link = "index.php?option=".RSVP_COM_COMPONENT."&task=templates.overview";
					}
					if (intval($cid[0])<=0 && JRequest::getInt("customise",0)==1){
						echo "<script type='text/javascript'>window.parent.setTemplate(".$template->id.",'".addslashes($template->title)."');alert('".JText::_("JEV_TEMPLATE_SAVED", true)."');$action</script>";
						if ($this->getTask()=="apply"){						
							$this->setRedirect($link);
						}
						else {
							exit();
						}
					}
					else if ( JRequest::getInt("customise",0)==1){
						echo "<script type='text/javascript'>window.parent.setTemplateTitle(".$template->id.",'".addslashes($template->title)."');alert('".JText::_("JEV_TEMPLATE_SAVED", true)."');$action</script>";
						if ($this->getTask()=="apply"){						
							$this->setRedirect($link);
						}
						else {
							exit();
						}
					}
					else {
						if ($this->getTask()=="apply"){
							$this->setRedirect($link,JText::_("RSVP_TEMPLATE_SAVED"));
							echo "<script> alert('".addslashes(JText::_("RSVP_TEMPLATE_SAVED"))."'); window.history.go(-1); </script>\n";
						}
						else {
							$this->setRedirect($link,JText::_("RSVP_TEMPLATE_SAVED"));
						}
					}
					return;
				}
				else {
					echo "<script> alert('".addslashes($model->getError())."'); window.history.go(-1); </script>\n";
					exit();
				}
			}
		}

	}

	function savecopy() {

		$cid = JRequest::getVar('cid', array(0), '', 'array');
		JArrayHelper::toInteger($cid);
		if (count($cid)>0){

			// Get/Create the model
			if ($model = & $this->getModel("template", "templatesModel")) {
				if ($model->storecopy(JRequest::get("post",JREQUEST_ALLOWHTML))){
					$this->setRedirect("index.php?option=".RSVP_COM_COMPONENT."&task=templates.overview",JText::_("RSVP_TEMPLATE_SAVED"));
					return;
				}
				else {
					echo "<script> alert('".addslashes($model->getError())."'); window.history.go(-1); </script>\n";
					exit();
				}
			}
		}

	}

	function globalise()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to publish' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->globalise($cid, 1)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if ( JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_( 'index.php?option=com_rsvppro&task=templates.list' . $tmpl));
	}

	function lock()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to lock' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->lock($cid, 1)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if ( JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_( 'index.php?option=com_rsvppro&task=templates.list' . $tmpl));
	}

	function unlock()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to unlock' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->lock($cid, 0)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if ( JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_( 'index.php?option=com_rsvppro&task=templates.list' . $tmpl));
	}
	
	function privatise()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){

			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to unpublish' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->globalise($cid, 0)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if (JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		
		//$this->list();
		$this->setRedirect( JRoute::_('index.php?option=com_rsvppro&task=templates.list'.$tmpl) );
	}

	function maketemplate()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to publish' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->templatise($cid, 1)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if ( JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_( 'index.php?option=com_rsvppro&task=templates.list' . $tmpl));
	}


	function unmaketemplate()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );

		if (JevTemplateHelper::canCreateGlobal()){
			$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
			JArrayHelper::toInteger($cid);

			if (count( $cid ) < 1) {
				JError::raiseError(500, JText::_( 'Select an item to publish' ) );
			}

			$this->_authoriseAccess($cid);

			$model = & $this->getModel("template", "TemplatesModel");
			if(!$model->templatise($cid, 0)) {
				echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
			}

			$returntask	= JRequest::getVar( 'returntask', "templates.overview");
			if ($returntask!="templates.list" && $returntask!="templates.overview" && $returntask!="templates.select"){
				$returntask="templates.overview";
			}
			if (method_exists($this,str_replace("templates.","",$returntask))){
				$returntask = str_replace("templates.","",$returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if ( JRequest::getString("tmpl","")=="component"){
				$tmpl ="&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_( 'index.php?option=com_rsvppro&task=templates.list' . $tmpl));
	}

	function delete ()
	{
		// get the view
		$this->view = & $this->getView("templates","html");

		// Set the layout
		$this->view->setLayout('edit');

		// Get/Create the model
		if ($model = & $this->getModel("template", "templatesModel")) {
			$cid = JRequest::getVar("cid",array());
			JArrayHelper::toInteger($cid);
			$model->delete($cid);

			$this->setRedirect( JRoute::_('index.php?option=com_rsvppro&task=templates.list'.$tmpl) );
			
		}


	}
	function _authoriseAccess($locid=0)
	{
		// TODO 
		return 1;
	}

	function close(){
		ob_end_clean();
		?>
		<script type="text/javascript">
		window.parent.SqueezeBox.close();
		try {
			window.parent.closedialog();
		}
		catch (e){}
		</script>
		<?php
		exit();
	}
	
}
