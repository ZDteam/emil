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
class TableField extends JTable
{
	/**
	 * Primary Key
	 *
	 * @public int
	 */
	public $field_id = null;

	/**
	 * @public int
	 */
	public $template_id = null;

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
		parent::__construct('#__jev_rsvp_fields', 'field_id', $db);
	}

	/**
	* Overloaded bind function
	*
	*/
	function bind($array, $ignore='', $fieldid)
	{
		$array2 =  array();
		foreach ($array as $key=>$data){
			switch ($key){
				case 'dv':
					if (array_key_exists($fieldid,$data)){
						$this->defaultvalue = $data[$fieldid];
						if (is_array($this->defaultvalue)){
							$this->defaultvalue = json_encode($this->defaultvalue);
						}
					}
					break;	
				case 'fa':
					if (array_key_exists($fieldid,$data)){
						$this->access = $data[$fieldid];
						// Joomla 1.6 allows multiple select here!
						if (is_array($this->access)){
							$this->access = implode(",",$this->access);
						}
					}
					break;	
				case 'faf':
					if (array_key_exists($fieldid,$data)){
						$this->accessflag = $data[$fieldid];
					}
					break;	
				case 'ao':
					if (array_key_exists($fieldid,$data)){
						$this->allowoverride = $data[$fieldid];
					}
					break;
				case 'facc':
					if (array_key_exists($fieldid,$data)){
						$fielddata = (array_key_exists("facs",$array) && array_key_exists($fieldid, $array["facs"]) ) ? $array["facs"][$fieldid] : array();
					
						// fix the categories fields
						if ($data[$fieldid]!='select') $fielddata=array();

						$this->applicablecategories= implode("|",$fielddata);;
					}
					break;
				case 'fl':
					if (array_key_exists($fieldid,$data)){
						$this->label = $data[$fieldid];
						$this->name =  htmlspecialchars($data[$fieldid]);
					}
					break;	
				case 'fid':
					if (array_key_exists($fieldid,$data)){
						$this->field_id = str_replace("field","",$data[$fieldid]);					
					}
					break;	
				case 'ft':
					if (array_key_exists($fieldid,$data)){
						$this->tooltip = $data[$fieldid];
					}
					break;	
				case 'rm':
					if (array_key_exists($fieldid,$data)){
						$this->requiredmessage = $data[$fieldid];
					}
					break;	
				case 'rr':
					if (array_key_exists($fieldid,$data)){
						$this->required = $data[$fieldid];
					}
					break;	
				case 'ordering':
					if (array_key_exists($fieldid,$data)){
						$this->ordering = $data[$fieldid];
					}
					break;	
				case 'type':
					if (array_key_exists($fieldid,$data)){
						$this->type = $data[$fieldid];
					}
					break;
				case 'options':
					if (array_key_exists($fieldid,$data)){
						$this->options = json_encode($data[$fieldid]);
					}
					break;
				case 'params':
					if (array_key_exists($fieldid,$data)){
						$this->params = json_encode($data[$fieldid]);
					}
					break;
				case 'defaultvalue':
					break;
				default:
					if (is_array($data) && array_key_exists($fieldid,$data) && in_array($key,  array_keys(get_object_vars($this))))
					{
						$this->$key = $data[$fieldid];
					}
					break;

			}
		}
		
		if (isset($array['id'])) $this->template_id=$array['id'];

		return true;
	}

}
