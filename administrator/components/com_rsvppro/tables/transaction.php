<?php
/**
 * @version $Id$
 * @package Attend Events
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class rsvpTransaction extends JTable {
	
	public $transaction_id = null;
	public $attendee_id = null;
	public $gateway = null;
	public $amount = null;
	public $transaction_date = null;
	public $logdata = null;
	public $currency = null;
	public $paymentstate = null;
	public $notes = null;

	// Gateway Specific Info Goes in params as JSON data
	public $params = null;

	function __construct() {
		$db = JFactory::getDBO();
		parent::__construct( '#__jev_rsvp_transactions', 'transaction_id', $db );
		$this->params = json_encode(array());
	} 

	function check() {
		if (!is_null($this->transaction_date)){
			$this->transaction_date = strftime('%Y-%m-%d %H:%M:%S', strtotime($this->transaction_date));
		}
		else {
			$this->transaction_date = strftime('%Y-%m-%d %H:%M:%S');
		}

		return true;
	}

	function delete()
	{

		if ( !parent::delete() ) return false;

		// update attendee status too!
		$this->updateAttendee();

		return true;
	}

	function store()
	{
		$this->check();
		if ( !parent::store() ) return false;

		// update attendee status too!
		$this->updateAttendee();
		
		return true;
	}
	

	private function updateAttendee(){
		if (isset($this->attendee_id) && intval($this->attendee_id)>0){
			$db = JFactory::getDBO();
            $sql = "SELECT * FROM #__jev_attendees WHERE id=".intval($this->attendee_id);
			$db->setQuery($sql);
			$attendee = $db->loadObject();

			if (isset($attendee) && isset($attendee->params)){
	            $sql = "SELECT * FROM #__jev_attendance WHERE id=".intval($attendee->at_id);
				$db->setQuery($sql);
				$rsvpdata = $db->loadObject();

				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
				if (is_int($xmlfile) || file_exists($xmlfile)) {
					$params = new JevRsvpParameter($attendee->params,$xmlfile, $rsvpdata, null);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
			}

		}
	}
}

