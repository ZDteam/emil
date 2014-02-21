<?php

defined('_JEXEC') or die('Restricted access');

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: mod.defines.php 1400 2009-03-30 08:45:17Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2012 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 */
if (!class_exists('vmCustomPlugin'))
	require(JPATH_VM_PLUGINS . '/' . 'vmcustomplugin.php');

class plgVmCustomRsvppro extends vmCustomPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);		
		
		$varsToPush = array(	);
		$varsToPush = array(	'transaction_id'=>array(0.0,'int'),
						    		'amount'=>array(0.0,'float')
		);
		

		$this->setConfigParameterable('custom_params',$varsToPush);
		
	}

	// get product param for this plugin on edit
	function plgVmOnProductEdit($field, $product, &$row, &$retValue)
	{
		if ($field->custom_element != $this->_name)
			return '';

		$html = '
			<fieldset>
				<legend>' . JText::_('VMCUSTOM_RSVPPRO') . '</legend>
				<table class="admintable">
					' . JText::_('VMCUSTOM_RSVPPRO_INFO') . '
				</table>
			</fieldset>';
		$retValue .= $html;
		$row++;
		return true;

	}

	function plgVmOnDisplayProductVariantFE($field, &$idx, &$group)
	{
		// default return if it's not this plugin
		if ($field->custom_value != $this->_name)
			return '';
		$html = 'TODO - output price and event into here';
		$group->display .= $html;
		return true;

	}

	function plgVmOnViewCartModule($product, $row, &$html)
	{
		if (!$plgParam = $this->GetPluginInCart($product)) return '' ;

		$html .= '<div>';
		//$html .= 'TODO - output price and event into Module here<br/>';
		$param = current($plgParam);
		$html .= 'Transaction id = '.$param['transaction_id']."<br/>";
		$html .= 'Amount = '.$param['amount'];
		$html .='</div>';
		return true;

	}

	function plgVmOnViewCart($product, $row, &$html)
	{
		if (!$plgParam = $this->GetPluginInCart($product)) return '' ;

		$param = current($plgParam);
		
		if (!defined("JEV_LIBS")){
			include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");
		}
		if (!defined("RSVP_TABLES")){
			include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/rsvppro.defines.php");
		}
		
		$transaction =new rsvpTransaction( );
		
		$extrainfo = "";
		if (false && $transaction->load( $param['transaction_id'] ) ){
			
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
					list($year, $month, $day) = JEVHelper::getYMD();
					$repeatdata = $this->dataModel->getEventData(intval($rpid), "icaldb", $year, $month, $day);
					if ($repeatdata && isset($repeatdata["row"]))
						$repeat = $repeatdata["row"];
				}

				$jevparams =  JComponentHelper::getParams(JEV_COM_COMPONENT);
				$registry = & JRegistry::getInstance("jevents");
				$tz = $jevparams->get("icaltimezonelive", "");
				if ($tz != "" && is_callable("date_default_timezone_set"))
				{
					$timezone = date_default_timezone_get();
					date_default_timezone_set($tz);
					$registry->set("jevents.timezone", $timezone);
				}
			
				$eventstart = new JevDate($repeat->publish_up(), $tz);

				$extrainfo = "Starting : ".$eventstart->toFormat("%Y-%m-%d %H:%M")."<br/>";
				
			}
		}
		
			
		$html .= '<div>';
		//$html .= 'TODO - output price and event into here<br/>';
		$html .= $extrainfo;
		$html .= 'Transaction id = '.$param['transaction_id']."<br/>";
		$html .= 'Amount = '.$param['amount'];
		$html .='</div>';
		return true;
		
	}

	/**
	 *
	 * vendor order display BE
	 */
	function plgVmDisplayInOrderBE($item, $row, &$html)
	{
		$this->plgVmOnViewCart($item, $row, $html); //same render as cart

	}

	/**
	 *
	 * shopper order display FE
	 */
	function plgVmDisplayInOrderFE($item, $row, &$html)
	{
		$this->plgVmOnViewCart($item, $row, $html); //same render as cart

	}

	public function plgVmCalculateCustomVariant(&$product, &$productCustomsPrice, $selected)
	{
		/*
		$customVariant = $this->getCustomVariant($product, $productCustomsPrice, $selected);
		if ($customVariant)
		{
			// TODO - this is where we interact with the database to get the ticket price!
			$productCustomsPrice->custom_price = $customVariant['amount'];
		}
		*/
		// 2.0.8 onwards
		if ($productCustomsPrice->custom_element != $this->_name) return false;

		if (!$customPlugin = JRequest::getVar('customPlugin',0)) {
			$customPlugin = json_decode($product->customPlugin,true);
		}
		$amount  = isset($customPlugin[$productCustomsPrice->virtuemart_customfield_id]['rsvppro']) ? $customPlugin[$productCustomsPrice->virtuemart_customfield_id]['rsvppro']['amount'] :  $customPlugin[$productCustomsPrice->virtuemart_custom_id]['rsvppro']['amount'] ;

		$productCustomsPrice->custom_price= $amount;
		return $amount;

	}
	
}
