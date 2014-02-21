<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
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
class JTableJev_attendance extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id = null;

	public $ev_id = null;
	public $allowregistration = null;
	public $allowrcancellation =null;
	public $allowrepeats = null;
	public $allowchanges = null;
	public $initialstate = null;
	public $attendintro = "";
	public $regopen = null;
	public $cancelclose = null;
	public $regclose = null;
	public $capacity = 0;
	public $waitingcapacity = 0;
	public $template = 0;
	public $hidenoninvitees = 0;
	public $allinvites = 0;
	public $showattendees = 0;
	public $invites = null;
	public $subject = '';
	public $message = '';
	public $allowreminders = null;
	public $remindersubject = '';
	public $remindermessage = '';
	public $remindernotice = null;
	public $remindallrepeats = null;
	public $sessionaccess = null;
	public $sessionaccessmessage = null;
	public $params = "";

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db =& JFactory::getDBO();
		parent::__construct('#__jev_attendance', 'id', $db);
	}


}
