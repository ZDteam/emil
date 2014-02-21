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
class JTableJev_attendee extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * attendance id
	 * 
	 * @var int
	 */
	public $at_id = 0;
	
	/**
	 * user id
	 * 
	 * @var int
	 */
	public $user_id = 0;
	
	/**
	 * repeat id
	 * 
	 * @var int
	 */
	public $rp_id = 0;
	
	/**
	 * email address
	 * 
	 * @var string
	 */
	public $email_address = "";

	/**
	 * confirmed
	 * 
	 * @var 1/0 boolean
	 */
	public $confirmed = null;

	/**
	 * waiting
	 * 
	 * @var 1/0 boolean
	 */
	public $waiting = null;

	/**
	 * params
	 * 
	 * @var string
	 */
	public $params = "";
	
	/**
	 * params
	 * 
	 * @var DATETIME
	 */
	public $created = "";

	/**
	 * params
	 * 
	 * @var DATETIME
	 */
	public $modified = "";
	
	/**
	 * guest count (including the primary registration)
	 *
	 * @var int
	 */
	public $guestcount = 1;

	/**
	 * guest count (including the primary registration)
	 *
	 * @var int
	 */
	public $atdcount = 0;

	/**
	 * guest count (including the primary registration)
	 *
	 * @var int
	 */
	public $attendstate = 1;

	/**
	 * Did attend state of registration
	 *
	 * @var int
	 */
	public $didattend = 1;

	/**
	 * Attendance notes
	 *
	 * @var int
	 */
	public $notes = "";
	
	/**
	 * Locked template
	 *
	 * @var int
	 */
	public $lockedtemplate = 0;
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db =& JFactory::getDBO();
		parent::__construct('#__jev_attendees', 'id', $db);
	}


}
