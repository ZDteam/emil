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
class JTableJev_invitees extends JTable
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
	 * sent message?
	 * 
	 * @var 0/1 int
	 */
	public $sentmessage = 0;

	/**
	 * viewed event
	 * 
	 * @var 0/1 int
	 */
	public $viewedevent = 0;

	/**
	 * invitedate
	 * 
	 * @var datetime 
	 */
	public $invitedate = '0000-00-00 00:00:00';

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
		parent::__construct('#__jev_invitees', 'id', $db);
	}


}
