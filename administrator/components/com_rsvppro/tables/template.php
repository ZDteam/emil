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
class TableTemplate extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	var $id = null;

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
		parent::__construct('#__jev_rsvp_templates', 'id', $db);
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
		// now merge in the jform parts
		if ($success && array_key_exists("jform", $array)){
			//parent::bind($array["jform"], $ignore);
		}
		if (array_key_exists('type',$array)){
			foreach ($array['type'] as $fieldid => $fieldtype) {
				$id = str_replace("field","",$fieldid);
				$tableclass = "Table".ucfirst($fieldtype);
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

	/**
	* Overloaded bind function that creates a copy to be saved
	*
	* @acces public
	* @param array $hash named array
	* @return null|string	null is operation was satisfactory, otherwise returns an error
	* @see JTable:bind
	* @since 1.5
	*/
	function bindcopy($array, $ignore = '')
	{
		$success = parent::bind($array, $ignore);

		$this->id = 0;
		$this->created_by = null;
		$this->created = null;
		if (array_key_exists('type',$array)){
			foreach ($array['type'] as $fieldid => $fieldtype) {
				$id = str_replace("field","",$fieldid);
				$tableclass = "Table".ucfirst($fieldtype);
				if (!class_exists($tableclass) && file_exists(dirname(__FILE__)."/field.".$fieldtype.".php")){
					include_once(dirname(__FILE__)."/field.".$fieldtype.".php");
					$field = new $tableclass();
				}
				else {
					include_once(dirname(__FILE__)."/field.php");
					$field = new TableField();
				}
				$field->bind($array,'', $fieldid);
				$field->_old_field_id = $field->field_id;
				$field->field_id = 0;
				$field->template_id = 0;

				$this->fields[] = $field;
			}
		}
		if (array_key_exists('params',$array) && is_array($array["params"])){
			$this->params = json_encode($array["params"]);
		}
		return $success;
	}

	/*
	 * overloaded load method that converts the params field
	 */
	function load( $oid=null )
	{
		parent::load($oid);

		if ($this->params !="" ){
	//		$this->params = json_decode($this->params);
		}
	}

	/**
	 * Overloaded check method to ensure data integrity
	 *
	 * @access public
	 * @return boolean True on success
	 * @since 1.0
	 */
	function check()
	{

		/** check for valid name */
		if (trim($this->title) == '') {
			$this->setError(JText::_('Your Template must contain a title.'));
			return false;
		}

		/** check for existing name */
		$query = 'SELECT id FROM #__jev_rsvp_templates WHERE title = '.$this->_db->Quote($this->title);
		$user = JFactory::getUser();
		$query .= " AND (created_by = $user->id)";

		$this->_db->setQuery($query);

		$xid = intval($this->_db->loadResult());
		if ($xid && $xid != intval($this->id)) {
			// Change the title to somthing unique
			$query = "SELECT max(id) FROM  #__jev_rsvp_templates";
			$this->_db->setQuery($query);
			$nextId = $this->_db->loadResult();

			// reset the field ids too!
			foreach (array_keys($this->fields) as  $index){
				// keep the old field id for adjusting custom fields afterwards
				$this->fields[$index]->_old_field_id = $this->fields[$index]->field_id;
				$this->fields[$index]->field_id = 0;
				$this->fields[$index]->template_id = intval($this->id);
			}

			$this->title .=" ".($nextId+1);
			if (JRequest::getInt("customise",0)){
				echo "<script type='text/javascript'>alert('".JText::_("JEV_TEMPLATE_TITLE_NOT_UNIQUE",true)."');</script>";
			}
			else {
				JFactory::getApplication()->enqueueMessage(JText::_("JEV_TEMPLATE_TITLE_NOT_UNIQUE"));
			}

			//$this->setError(JText::sprintf('WARNNAMETRYAGAIN', JText::_('Template')));
			//return false;
		}
		// if a new template based on an old one must remember to set the field ids and template ids to 0 in the fields!
		else if ( intval($this->id)==0) {
			// reset the field ids too!
			foreach (array_keys($this->fields) as  $index){
				// keep the old field id for adjusting custom fields afterwards
				if ($this->fields[$index]->field_id>0){
					$this->fields[$index]->_old_field_id = $this->fields[$index]->field_id;				
				}
				$this->fields[$index]->field_id = 0;
				$this->fields[$index]->template_id = 0;
			}

		}

		return true;
	}

	function store( $updateNulls=false ) {
		
		// Create the archive version first
		if ($this->id>0){
			$templatearchive = JTable::getInstance("templatearchive");
			$templatearchive->loadByTemplateId($this->id);
			$templatearchive->store();
		}
		
		$user =& JFactory::getUser();
		if (is_null($this->created_by)){
			$this->created_by = $user->id;
		}
		else {
			$this->modified_by = $user->id;
		}
		if (is_null($this->created)){
			if (class_exists("JevDate")) {
				$datenow = JevDate::getDate();
			}
			else {
				$datenow = JFactory::getDate();
			}
			$this->created	= $datenow->toMySQL();
		}
		$success = parent::store($updateNulls);

		$ids = array(0);
		$oldids = array();
		foreach ($this->fields as &$field) {
			$field->template_id = $this->id;
			$success |= $field->store($updateNulls);
			$ids[] = $field->field_id;
			if (isset($field->_old_field_id) && $field->_old_field_id>0){
				$oldids[$field->_old_field_id] = $field->field_id;
			}
		}
		unset($field);

		// now reset any condition fields etc. in the parameters that rely on old field ids
		// TODO make sure these match FULL strings and not substrings
		foreach ($this->fields as $field) {
			$changed = false;
			foreach ($oldids as $oldid=>$newid){
				if (strpos($field->params, '"field'.$oldid.'"')){
					$field->params = str_replace('"field'.$oldid.'"', '"field'.$newid.'"', $field->params);
				}
			}
			$success |= $field->store($updateNulls);
		}

		// Now update the messages etc.
		if (count($oldids)>0){
			foreach ($oldids as $oldid=>$newid){
				if (strpos($this->params, '"field'.$oldid.'"')){
					$this->params = str_replace('"field'.$oldid.'"', '"field'.$newid.'"', $this->params);
				}
				if (strpos($this->params, '#field'.$oldid.'#')){
					$this->params = str_replace('#field'.$oldid.'#', '#field'.$newid.'#', $this->params);
				}
			}
			$success |= parent::store($updateNulls);
		}
		
		if ($success){
			// remove fields that are no longer  used
			$query = 'DELETE FROM #__jev_rsvp_fields  WHERE template_id = '.(int) $this->id . " AND field_id NOT IN (".implode(",",$ids).")";
			$this->_db->setQuery($query);
			$this->_db->query();
		}

		return $success;
	}
	

}
