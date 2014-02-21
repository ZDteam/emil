<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevuser.php 1659 2010-01-06 03:13:31Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// no direct access
defined('_JEXEC') or die('Restricted Access');

/**
* User Table class
*
* @subpackage	Users
* @since 1.0
*/
class JTableJev_invitelist_member extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * list id
	 * 
	 * @var int
	 */
	public $list_id = 0;
	
	/**
	 * user id
	 * 
	 * @var int
	 */
	public $user_id = 0;
	
	/**
	 * email_name
	 * 
	 * @var string
	 */
	public $email_name = "";

	/**
	 * email_address
	 * 
	 * @var string
	 */
	public $email_address = "";
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db =& JFactory::getDBO();
		parent::__construct('#__jev_invitelist_member', 'id', $db);
	}


}
