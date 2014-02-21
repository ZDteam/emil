<?php

defined ('_JEXEC') or die('Restricted access');

/**
 *
 * a special type of payment plugin - just to update RSVP Pro status':
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 
 */
if (!class_exists ('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . '/' . 'vmpsplugin.php');
}

class plgVmCouponRsvppro extends vmPSPlugin {

	// instance of class
	public static $_this = FALSE;
	
	/**
	 * Save updated order data to the method specific table
	 *
	 * @param array $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 * @author Oscar van Eijk
	*/
	public function plgVmCouponUpdateOrderStatus(  $_formData) {
		$orderid =$_formData->virtuemart_order_id;
		$db = JFactory::getDbo();
		$db->setQuery("select * from #__virtuemart_order_items as oit WHERE oit.virtuemart_order_id=$orderid");
		$orderdata = $db->loadObject();
		if (!$orderdata || strpos($orderdata->product_attribute, "transaction_id")===false  || strpos($orderdata->product_attribute, "rsvppro")===false){
			return;
		}
		$orderdata->rsvp = json_decode($orderdata->product_attribute);
		$data = current(get_object_vars($orderdata->rsvp));		
		$transaction_id =  $data->rsvppro->transaction_id;
		
		JLoader::register('jevFilterProcessing', JPATH_SITE . "/components/com_jevents/libraries/filters.php");
		JLoader::register('JevRsvpParameter', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrsvpparameter.php");
		JLoader::register('JevRsvpAttendance', JPATH_SITE. "/plugins/jevents/jevrsvppro/rsvppro/jevrattendance.php");
		JLoader::register('JevRsvpDisplayAttendance', JPATH_SITE. "/plugins/jevents/jevrsvppro/rsvppro/jevrdisplayattendance.php");
		JLoader::register('RsvpHelper', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/rsvphelper.php");
		JLoader::register('JevTemplateHelper', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/templatehelper.php");

		JLoader::register('JevDate', JPATH_SITE . "/components/com_jevents/libraries/jevdate.php");
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/rsvppro.defines.php");
		
		$transaction =new rsvpTransaction( );
		
		$extrainfo = "";
		if ($transaction->load( $transaction_id ) ){
			
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $transaction->attendee_id;
			$db->setQuery($sql);
			$attendee = $db->loadObject();
			if ($attendee)
			{
				$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
				$db->setQuery($sql);
				$rsvpdata = $db->loadObject();
				
				$rpid = $attendee->rp_id;
				$this->dataModel = new JEventsDataModel();
				$this->queryModel = new JEventsDBModel($this->dataModel);

				// Find the first repeat
				$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
				if ($rpid == 0)
				{
					$repeat = $vevent->getFirstRepeat();
				}
				else
				{
					$repeat = false;
					list($year, $month, $day) = JEVHelper::getYMD();
					$repeatdata = $this->dataModel->getEventData(intval($rpid), "icaldb", $year, $month, $day);
					if ($repeatdata && isset($repeatdata["row"]))
						$repeat = $repeatdata["row"];
				}

				if ($repeat){
					// update the payment status within RSVP Pro
					JPluginHelper::importPlugin("rsvppro");
					$dispatcher = & JDispatcher::getInstance();
					$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $attendee, $repeat));				
				}
			}
		}
		
		
		return null;
	}

}
