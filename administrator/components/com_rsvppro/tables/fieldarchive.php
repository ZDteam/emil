<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Template Field class
*
*/
class JTableFieldarchive extends JTable
{
	/**
	 * Primary Key
	 *
	 * @public int
	 */
	public $id = null;

	/**
	 * Primary Key of live data
	 *
	 * @public int
	 */
	public $field_id = null;

	/**
	 * @public int
	 * actual template id
	 */
	public $template_id = null;

	/**
	 * @public int
	 * id of the archive of the template
	 */
	public $archive_template_id = null;
	
	/**
	 * @public string
	 */
	public $name = null;

	/**
	 * @public string
	 */
	public $type = null;

	/**
	 * @public string
	 */
	public $label = null;

	/**
	 * @public string
	 */
	public $tooltip = null;

	/**
	 * @public string
	 */
	public $defaultvalue = null;

	/**
	 * @public int 
	 */
	public $required = 0;

	/**
	 * @public access 
	 */
	public $access = 0;

	/**
	 * @public access flag
	 */
	public $accessflag = 1;

	/**
	 * @public string 
	 */
	public $requiredmessage = 0;

	/**
	 * @public int 
	 */
	public $ordering = 0;

	/**
	 * @public int
	 */
	public $size = 0;

	/**
	 * @public int
	 */
	public $cols = 0;

	/**
	 * @public int
	 */
	public $rows = 0;

	/**
	 * @public int
	 */
	public $maxlength = 0;

	/**
	 * @public int
	 */
	public $peruser = 0;

	/**
	 * @public int
	 */
	public $showinlist= 0;

	/**
	 * @public int
	 */
	public $showinform = 0;

	/**
	 * @public int
	 */
	public $showindetail = 0;

	/**
	 * @public int
	 */
	public $formonly = 0;

	/**
	 * @public boolean
	 */
	public $allowoverride = 0;

	/**
	 * @public string
	 */
	public $applicablecategories = "";

	/**
	 * @public string
	 */
	public $options  = "";

	/**
	 * @public string
	 */
	public $params = "";

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct() {
		$db =& JFactory::getDBO();
		parent::__construct('#__jev_rsvp_fields_archive', 'id', $db);
	}


	function loadByLiveFieldId($id){
		$this->reset();

		if (JVersion::isCompatible("1.6")){
			// Initialise the query.
			$query	= $this->_db->getQuery(true);
			$query->select('*');
			$query->from("#__jev_rsvp_fields");

			// Add the search tuple to the query.
			$query->where($this->_db->quoteName('field_id').' = '.$this->_db->quote($id));
		}
		else {
			$query = "SELECT * FROM #__jev_rsvp_fields WHERE field_id=".$this->_db->quote($id);
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
		$this->id = 0;		
		
	}
}
