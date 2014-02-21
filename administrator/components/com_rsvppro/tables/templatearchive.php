<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Template Table class
*
*/
class JTableTemplatearchive extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	var $id = null;

	/**
	 * Primary Key of live table
	 *
	 * @var int
	 */
	var $template_id = null;
	
	/**
	 * @var datetime
	 */
	var $archived = null;

	/**
	 * @var int 
	 */
	var $archived_by = null;

	
	/**
	 * @var string
	 */
	var $title = null;

	/**
	 * @var string
	 */
	var $description = null;

	/**
	 * @var int
	 * published on by default
	 */
	var $published = 1;

	/**
	 * @var datetime
	 */
	var $created = null;

	/**
	 * @var int 
	 */
	var $created_by = null;

	/**
	 * @var int 
	 */
	var $modified_by = null;

	/**
	 * @var booelan
	 */
	var $withfees = 0;

	/**
	 * @var booelan
	 */
	var $withticket = 0;

	/**
	 * @var booelan
	 */
	var $global = 0;

	/**
	 * @var booelan
	 */
	var $istemplate = 1;

	/**
	 * @var string
	 */
	var $params = "";

	/**
	 * @var string
	 */
	var $ticket = "";

	var $fields = array();
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db =& JFactory::getDBO();
		parent::__construct('#__jev_rsvp_templates_archive', 'id', $db);
	}

	public function loadByTemplateId($key ) {

		$this->reset();

		if (JVersion::isCompatible("1.6")){
			// Initialise the query.
			$query	= $this->_db->getQuery(true);
			$query->select('*');
			$query->from("#__jev_rsvp_templates");

			// Add the search tuple to the query.
			$query->where($this->_db->quoteName('id').' = '.$this->_db->quote($key));
		}
		else {
			$query = "SELECT * FROM #__jev_rsvp_templates WHERE id=".$this->_db->quote($key);
		}

		$this->_db->setQuery($query);
		$row = $this->_db->loadAssoc();

		// Check for a database error.
		if ($this->_db->getErrorNum()) {
			$e = new JException($this->_db->getErrorMsg());
			$this->setError($e);
			return false;
		}

		// Check that we have a result.
		if (empty($row)) {
			$e = new JException(JText::_('JLIB_DATABASE_ERROR_EMPTY_ROW_RETURNED'));
			$this->setError($e);
			return false;
		}

		// Bind the object with the row 
		$this->bind($row);
		
		// now adjust the id and template id fields 
		$this->template_id  = $this->id; 
		$this->id = 0;		
		return true;
	}

	/**
	* Overloaded bind function
	*
	* @acces public
	* @param array $hash named array
	* @return null|string	null is operation was satisfactory, otherwise returns an error
	* @see JTable:bind
	* @since 1.5
	*/
	function bind($array, $ignore = '')
	{
		$success = parent::bind($array, $ignore);
		
		if (array_key_exists('type',$array)){
			foreach ($array['type'] as $fieldid => $fieldtype) {
				$id = str_replace("field","",$fieldid);
				$tableclass = "JTable".ucfirst($fieldtype)."archive";
				if (!class_exists($tableclass) && file_exists(dirname(__FILE__)."/field.".$fieldtype.".php")){
					include_once(dirname(__FILE__)."/field.".$fieldtype.".php");
					$field = new $tableclass();
				}
				else {
					include_once(dirname(__FILE__)."/field.php");
					$field = new TableField();
				}
				$field->bind($array,'', $fieldid);
				
				$this->fields[] = $field;
			}
		}
		if (array_key_exists('params',$array) && is_array($array["params"])){
			$this->params = json_encode($array["params"]);
		}
		return $success;
	}
	
	function store( $updateNulls=false ) {
				
		$user =& JFactory::getUser();
		if (is_null($this->archived_by) || $this->archived_by==0){
			$this->archived_by = $user->id;
		}
		if (is_null($this->archived)){
			if (class_exists("JevDate")) {
				$datenow = JevDate::getDate();
			}
			else {
				$datenow = JFactory::getDate();
			}
			$this->archived	= $datenow->toMySQL();
		}
		$success = parent::store($updateNulls);

		if ($success){
			// load the fields now
			if (JVersion::isCompatible("1.6")){

				$query	= $this->_db->getQuery(true);
				$query->select('field_id');
				$query->from("#__jev_rsvp_fields");

				// Add the search tuple to the query.
				$query->where($this->_db->quoteName('template_id').' = '.$this->_db->quote($this->template_id));
			}
			else {
				$query = "SELECT field_id FROM #__jev_rsvp_fields WHERE template_id=".$this->_db->quote($this->template_id);
			}

			$this->_db->setQuery($query);
			$fields = $this->_db->loadColumn();
			if ($fields){
				foreach ($fields as $fieldid){
					$field = JTable::getInstance("fieldarchive");
					$field->loadByLiveFieldId($fieldid);
					$field->id = 0;
					$field->template_id = $this->template_id;
					$field->archive_template_id = $this->id;
					$success |= $field->store($updateNulls);
				}
			}
			
		}

		// After saving the template archive we now lock any appropriate sessions!
		if ($this->id>0 && JRequest::getInt("lockattendees")){
			$customise =  JRequest::getInt("customise");			
			$evid = JRequest::getInt("evid");
			$oldid= JRequest::getInt("oldid");
			$istemplate = JRequest::getInt("istemplate");
			// event specific customisation of a session template
			if ($customise){
				if ($evid>0){
					// here we lock on an event specific basis based on the old id
					if ($oldid>0){
						$archiveid = $this->id;
						$db = JFactory::getDbo();
						$db->setQuery("SELECT id from #__jev_attendance where template=$oldid and ev_id=$evid");	
						$rsvpdata = $db->loadColumn();
						// set the locked template to match the archived value
						if (count($rsvpdata)>0){
							$sql = "UPDATE #__jev_attendees set lockedtemplate=$archiveid where at_id in (". implode(",",$rsvpdata) .") AND lockedtemplate=0";
							$db->setQuery($sql);
							$db->query();
						}
					}
				}
				else {
					// this scenario should not arise
				}
			}
			else {
				if ($oldid>0){
					$archiveid = $this->id;
					$db = JFactory::getDbo();
					$db->setQuery("SELECT id from #__jev_attendance where template=$oldid ");					
					$rsvpdata = $db->loadColumn();
					// set the locked template to match the archived value
					if (count($rsvpdata)>0){
						$sql = "UPDATE #__jev_attendees set lockedtemplate=$archiveid where at_id in (". implode(",",$rsvpdata) .") AND lockedtemplate=0";
						$db->setQuery($sql);
						$db->query();
					}
				}
			}
		}
		return $success;
	}
	
	

}
