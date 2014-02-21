<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted Access' );

jimport('joomla.application.component.model');

JLoader::register('RsvpForm', JPATH_ADMINISTRATOR. "/components/com_rsvppro/libraries/rsvpform.php");

class TemplatesModelTemplate extends JModelLegacy
{
	/**
	 * Template id
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Template data
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Template data
	 *
	 * @var array
	 */
	var $_dataobject = null;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();

		$array = JRequest::getVar('cid', array(0), '', 'array');
		$edit	= JRequest::getVar('edit',true);
		if($edit)
		$this->setId((int)$array[0]);
	}

	/**
	 * Method to set the template identifier
	 *
	 * @access	public
	 * @param	int Template identifier
	 */
	function setId($id)
	{
		// Set template id and wipe data
		$this->_id		= $id;
		$this->_data	= null;
		$this->_dataobject	= null;
	}

	/**
	 * Method to get a template
	 *
	 * @since 1.5
	 */
	function &getData()
	{
		// Load the template data
		if ($this->_loadData())
		{
			// Initialize some variables
			$user = &JFactory::getUser();

		}
		else  $this->_initData();


		return $this->_dataobject;
	}

	/**
	 * Tests if template is checked out
	 *
	 * @access	public
	 * @param	int	A user id
	 * @return	boolean	True if checked out
	 * @since	1.5
	 */
	function isCheckedOut( $uid=0 )
	{
		if ($this->_loadData())
		{
			if ($uid) {
				return ($this->_data->checked_out && $this->_data->checked_out != $uid);
			} else {
				return $this->_data->checked_out;
			}
		}
	}

	/**
	 * Method to checkin/unlock the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function checkin()
	{
		if ($this->_id)
		{
			$template = & $this->getTable();
			if(! $template->checkin($this->_id)) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}
		return false;
	}

	/**
	 * Method to checkout/lock the template
	 *
	 * @access	public
	 * @param	int	$uid	User ID of the user checking the article out
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function checkout($uid = null)
	{
		if ($this->_id)
		{
			// Make sure we have a user id to checkout the article with
			if (is_null($uid)) {
				$user	=& JFactory::getUser();
				$uid	= $user->get('id');
			}
			// Lets get to it and checkout the thing...
			$template = & $this->getTable();
			if(!$template->checkout($uid, $this->_id)) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}

			return true;
		}
		return false;
	}

	/**
	 * Method to store the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function store($data)
	{
		unset($this->template );
		$row = $this->getTable();
			
		// Bind the form fields to the template table
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Make sure the template table is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		// Store the template table to the database
		if (!$row->store()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// attach a reference to I can refetch the saved row
		$this->template = $row;
		return true;
	}

	/**
	 * Method to store a copy of the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function storecopy($data)
	{
		$row =& $this->getTable();

		// Bind the form fields to the template table
		if (!$row->bindcopy($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Make sure the template table is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		// Store the template table to the database
		if (!$row->store()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		return true;
	}

	/**
	 * Method to remove a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function delete($cid = array())
	{
		$result = false;

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			
			// create archive before deleting
			foreach ($cid as $id){
				$row = $this->getTable("templatearchive", "JTable");
				if ($row->loadByTemplateId($id)){
					$row->store();
				}
			}
			
			$cids = implode( ',', $cid );
			$query = 'DELETE FROM #__jev_rsvp_templates'
			. ' WHERE id IN ( '.$cids.' )';
			$this->_db->setQuery( $query );
			if(!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)publish a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function publish($cid = array(), $publish = 1)
	{
		$user 	=& JFactory::getUser();

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET published = '.(int) $publish
			. ' WHERE id IN ( '.$cids.' )'
			. ' AND ( checked_out = 0 OR ( checked_out = '.(int) $user->get('id').' ) )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)globalise a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function globalise($cid = array(), $global = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET global = '.(int) $global
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)lock a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function lock($cid = array(), $locked = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET locked = '.(int) $locked
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)templatise a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function templatise($cid = array(), $global = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET istemplate = '.(int) $global
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to load content template data
	 *
	 * @access	private
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function _loadData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_dataobject))
		{
			$query = 'SELECT w.* FROM #__jev_rsvp_templates AS w WHERE w.id = '.(int) $this->_id;
			$this->_db->setQuery($query);
			$this->_data = $this->_db->loadObject();

			// now attach the fields
			if ($this->_data){
				$this->_dataobject = & $this->getTable();
				$this->_dataobject->bind((array) $this->_data);

				$query = 'SELECT w.* FROM #__jev_rsvp_fields AS w WHERE w.template_id = '.(int) $this->_dataobject->id. " ORDER BY ordering ASC";
				$this->_db->setQuery($query);
				$fielddata = $this->_db->loadObjectList();
				foreach ($fielddata as $field) {
					$type= $field->type;
					include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/rsvppro.defines.php");
					jimport("joomla.html.parameter.element");
					include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/$type.php");
					// only need to load Scripts when editing templates!
					if (JRequest::getCmd("option")=="com_rsvppro" && (JRequest::getCmd('task', 'sessions.overview')=='templates.edit' || JRequest::getCmd('task', 'sessions.overview')=='templates.editcopy')){
						$field->html=call_user_func(array("JFormField".ucfirst($type),"loadScript"),$field);
					}

					$field->default = $field->defaultvalue;
					$this->_dataobject->fields[]=$field;
					

				}
			}
			return (boolean) $this->_data;
		}
		return true;
	}

	/**
	 * Method to initialise the template data
	 *
	 * @access	private
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function _initData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_dataobject))
		{
			$this->_dataobject =& $this->getTable();

			return (boolean) $this->_data;
		}
		return true;
	}

		/**
	 * Get the params for the currency variables etc.
	 */
	function &getParams($template)
	{
		static $instance;

		if ($instance == null)
		{
			$component	= RSVP_COM_COMPONENT;

			$path	= JPATH_ADMINISTRATOR.'/'.'components/com_rsvppro/templates.xml';

			// Use our own class to add more functionality!
			$instance = RsvpForm::getInstance("com_rsvppro.template", $path, array('control' => 'params', 'load_data' => true), true, "/form");
			
			$templateparams = $template->params;
			if (is_string($templateparams) && strlen(trim($templateparams))>2){
				$templateparams = new JRegistry($templateparams);
			}
			else if (is_string($templateparams) && trim($templateparams)==""){
				$templateparams = JComponentHelper::getParams($component)->toArray();
			}
			
			// Now load the payment engines
			$plugins = JPluginHelper::getPlugin("rsvppro");
			foreach ($plugins as $plugin){
				if (!$plugin->params) continue;
				$pluginpath =JPATH_SITE."/plugins/rsvppro/".$plugin->name."/".$plugin->name.".xml" ;
								
				$instance->loadfile($pluginpath,false,"/extension/template/form");							
				$lang = JFactory::getLanguage();
				$lang->load("plg_rsvppro_".$plugin->name, JPATH_ADMINISTRATOR);
				$pluginparams = $plugin->params;
				if (is_string($pluginparams) && strlen(trim($pluginparams))>2){
					$pluginparams = new JRegistry($pluginparams);
				}					
				$instance->bind($pluginparams);
			}
			$instance->bind($templateparams);
			
		}
		return $instance;
	}

	/**
	 * Either returns 0 if we don't need to lock any existing 
	 * @param type $template
	 * @return type int
	 */
	function hasAttendeesToLock($template) {
		return 0;
		// TODO locked attendees - test properly and limit scope e.g. can't handle capacity changes etc.
		
		if ($template->istemplate ){
			// this is a customisation of a template - this can only happen on an event specific basis
			if (JRequest::getInt("customise")){
				// in this case we ONLY lock existing attendees if this is an existing event
				if (JRequest::getInt("evid")>0){
					return true;
				}
				return false;
			}
			// this a template that is being changed
			else {
				// we offer to lock if its an existing template
				if ($template->id>0){
					return true;
				}
				return false;				
			}			
		}
		else {
			// this is a customisation of a non-template or a new template being edited as a raw template
			// in this case we ONLY lock existing attendees if this is an existing event
			// we offer to lock if its an existing template
			if ($template->id>0){
				return true;
			}
			return false;
		}
	}
}