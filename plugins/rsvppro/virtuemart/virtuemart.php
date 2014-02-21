<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgRsvpproVirtuemart extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		require_once(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_virtuemart/tables/");

		$this->catid = $this->params->get("catid", "0");

		$this->setupPlugin();

	}

	public function generatePaymentPage(&$html, $attendee, $rsvpdata, $event, &$transaction)
	{

		// TODO transaction based invoiceid
		$invoice = JRequest::getString("invoiceid", "") . '_' . $transaction->transaction_id;

		$html = "Invoice id is $invoice<br/>";
		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;
		$mainframe = JFactory::getApplication();
		$Itemid = JRequest::getInt("Itemid");
		$detaillink = JRoute::_(JUri::root() . $event->viewDetailLink($event->yup(), $event->mup(), $event->dup(), false), false);

		$currency = $this->params->get("currency", "GBP");
		if (isset($rsvpdata->template) && is_numeric($rsvpdata->template))
		{
			$db = JFactory::getDBO();
			$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams)
			{
				$templateParams = json_decode($templateParams->params);
			}
			else
			{
				$templateParams = $params;
			}
		}
		else
		{
			$templateParams = $params;
		}
		$currency = isset($templateParams->Currency) ? $templateParams->Currency : $currency;

		$html = $this->params->get("template", "Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please send your payment to ...");
		if (isset($attendee->outstandingBalances))
		{
			$html = str_replace("{TOTALFEES}", $currency . " " . $attendee->outstandingBalances['totalfee'], $html);
			$html = str_replace("{FEESPAID}", $currency . " " . $attendee->outstandingBalances['feepaid'], $html);
			$html = str_replace("{BALANCE}", $currency . " " . $attendee->outstandingBalances['feebalance'], $html);
		}

		// setup transaction data
		$transaction->amount = $amount;
		$transaction->currency = $currency;
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "virtuemart";

		$transaction->params = new stdClass();
		$transaction->params = json_encode($transaction->params);

		$transaction->store();

		// This redirects the visitor to the Virtuemart checkout.
		$row = $event;
		$rp_id = $row->rp_id();

		$eventid = $event->ev_id();

		// attach anonymous creator etc.
		$dispatcher = & JDispatcher::getInstance();
		$dispatcher->trigger('onDisplayCustomFields', array(&$row));

		// Make sure the product hasn't been created yet
		$product = $this->getProduct($rsvpdata, $rp_id, $eventid);

		if (!$product)
		{
			$this->setupProduct($event, $rsvpdata);
		}

		$product = $this->getProduct($rsvpdata, $rp_id, $eventid);

		$tableproduct = new JTableVMProducts($db);
		$tableproduct->load($product->virtuemart_product_id);

		$this->addToCart($tableproduct, $amount, $transaction);

	}

	static public function transactionDetailLink($transaction, $rsvpdata, $attendee, $event)
	{

		$plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
		$params = new JRegistry($plugin->params);
                $rsvp_sku = $params->get('skuprefix', 'RSVP');
		$rp_id = $event->rp_id();
		$eventid = $event->ev_id();

		$db = & JFactory::getDBO();
		if ($rsvpdata->allrepeats)
		{
			$sku = $db->Quote( $rsvp_sku . '_' . $eventid . '_0');
		}
		else
		{
			$sku = $db->Quote( $rsvp_sku . '_' . $eventid . '_' . $rp_id);
		}

		// Get the order info
		$sql = "SELECT ord.*, orit.product_attribute , orit.virtuemart_order_id  as order_id FROM #__virtuemart_orders as ord"
				. " \n LEFT JOIN #__virtuemart_order_items as orit ON orit.virtuemart_order_id = ord.virtuemart_order_id"
				//. " \n WHERE (orit.order_status='C' OR orit.order_status='S' OR orit.order_status='I') "
				// Always show the link so that you can get easy access
				. "\n WHERE 1 "
				. " \n AND orit.order_item_sku=" . $sku 
				. " \n AND ( orit.product_attribute LIKE ('%\"transaction_id\":". $transaction->transaction_id.",\"amount\":" .  $transaction->amount . "}%')" 
				// VM 2.0.18 change this !!!!
				. " \n OR orit.product_attribute LIKE ('%\"transaction_id\":\"". $transaction->transaction_id."\",\"amount\":\"" .  $transaction->amount . "\"%') )" 
				. "\n ORDER BY created_on desc";
		$db->setQuery($sql);
		// Make sure Joomfish doesn't translate this
		$order = $db->loadObject('stdClass', false);

		if (!$order)
			return "";

		$mainframe = JFactory::getApplication();
		if ($mainframe->isAdmin())
		{
			return '<a href="' . JRoute::_("index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=" . $order->order_id) . '"  target="_blank">' . $order->order_id . '</a>';
		}
		else
		{
			return '<a href="' . JRoute::_("index.php?option=com_virtuemart&view=orders&layout=details&order_number=" . $order->order_number) . '"  target="_blank">' . $order->order_id . '</a>';
		}

	}

	public function activeGatewayClass(&$activeGatewayClass, $action="notify")
	{
		$gateway = JRequest::getString("gateway");

		if ($gateway == "virtuemart" || $gateway == "2" || strpos($gateway,"virtuemart_")===0)
		{
			$activeGatewayClass = __CLASS__;
		}

	}

	public function activeGateways(&$activeGatewayClasses)
	{
		$activeGatewayClasses[] = __CLASS__;

	}

	public function updatePaymentStatus($rsvpdata, $attendee, $event)
	{
		if (!$event)
		{
			return;
		}

		// Important to avoid repeats AND RECURSION
		static $updated = array();
		if (isset($updated[$event->rp_id()]))
		{
			return;
		}
		$updated[$event->rp_id()] = 1;
                
                $plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
		$params = new JRegistry($plugin->params);
		$rsvp_sku = $params->get('skuprefix', 'RSVP');
		$rp_id = $event->rp_id();
		$eventid = $event->ev_id();

		$db = & JFactory::getDBO();
		if ($rsvpdata->allrepeats)
		{
			$sku = $db->Quote($rsvp_sku . '_' . $eventid . '_0');
		}
		else
		{
			$sku = $db->Quote($rsvp_sku . '_' . $eventid . '_' . $rp_id);
		}

		// Update payments completed
		$sql = "SELECT ord.*, orit.product_attribute, orit.order_status FROM #__virtuemart_orders as ord"
				. " \n LEFT JOIN #__virtuemart_order_items as orit ON orit.virtuemart_order_id = ord.virtuemart_order_id"
				// new version to handle irritating VM2 bug with discount coupons !
				. " \n WHERE (orit.order_status='C' OR orit.order_status='S' OR orit.order_status='I' OR orit.order_status='P' ) "
				. " \n AND orit.order_item_sku=" . $sku
				. "\n ORDER BY created_on desc";
		$db->setQuery($sql);
		// Make sure Joomfish doesn't translate this
		$orders = $db->loadObjectList('', 'stdClass', false);

		// coupon code bug workaruond
		if ($orders) {
			foreach ($orders as $o => $val){ 
				if ($orders[$o]->order_status=="P" && $orders[$o]->order_total>0){  
					unset($orders[$o]);
				}
				else if ($orders[$o]->order_status=="P" && $orders[$o]->order_total==0 && ($orders[$o]->coupon_code==""  || $orders[$o]->coupon_discount==0)){
					unset($orders[$o]);
				}				
			}
			$orders = array_values($orders);
		}
		
		$transactionids = array();
		if ($orders) {
			foreach ($orders as $order)
			{
				$product_attribute = $order->product_attribute;
				$product_attribute = json_decode($product_attribute);
				foreach ($product_attribute as $key => $val)
				{
					if (isset($val->rsvppro->transaction_id))
					{
						$transactionid = $val->rsvppro->transaction_id;
						$transactionids[] = intval($transactionid);
					}
				}
			}
		}
		if (count($transactionids) > 0)
		{
			// which ones need updating 
			
			$sql = "select transaction_id from #__jev_rsvp_transactions WHERE transaction_id IN (" . implode(",", $transactionids) . ") AND paymentstate<>1 ";

			$db->setQuery($sql);
			$updatedtransactions = $db->loadColumn(); 
			if ($updatedtransactions  && count($updatedtransactions )>0){
				foreach ($updatedtransactions  as $transid){
					$transaction =new rsvpTransaction( );
					if ( $transaction->load( $transid ) )
					{
						$namefields = array("ju.username", "ju.name", "ju.name, ju.username");
						$params = JComponentHelper::getParams("com_rsvppro");
						$namefield = $namefields [$params->get("userdatatype", 0)];

						$where = array();
						$join = array();

						$where[] = "ev.ev_id IS NOT NULL";
						$where[] = "atdees.id = $transaction->attendee_id";
						
						$query = "SELECT det.*, atd.* , atd.id as atd_id, atdc.atdcount, atdees.*,atdees.id as atdee_id, ju.username, ju.email, "
						. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE CONCAT_WS(' - ',$namefield,ju.email) END as attendee, "
						. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE ju.name END as name "
						. "\n FROM #__jevents_vevent as ev "
						. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
						. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
						. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id"
						. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id"
						. "\n LEFT JOIN #__users AS ju ON ju.id = atdees.user_id"
						. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
						. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' )
						;
						$db->setQuery($query);

						$atdee = $db->loadObject();

						// Must load each attendee to notify them otherwise we notify the first one about all the payments
						$this->notifyVMPayment($transaction, $atdee, $rsvpdata);	
					}
				}
			}

			$sql = "UPDATE #__jev_rsvp_transactions SET paymentstate=1 WHERE transaction_id IN (" . implode(",", $transactionids) . ")";
			$db->setQuery($sql);
			$db->query();
			
		}

		
		$validtransactionids = $transactionids;
		
		// Update payments not-completed
		$sql = "SELECT ord.*, orit.product_attribute FROM #__virtuemart_orders as ord"
				. " \n LEFT JOIN #__virtuemart_order_items as orit ON orit.virtuemart_order_id = ord.virtuemart_order_id"
				. " \n WHERE (orit.order_status!='C' AND orit.order_status!='S'  AND orit.order_status!='I') "
				. " \n AND orit.order_item_sku=" . $sku
				. "\n ORDER BY created_on desc";
		$db->setQuery($sql);
		// Make sure Joomfish doesn't translate this
		$orders = $db->loadObjectList('', 'stdClass', false);

		// coupon code bug workaruond
		if ($orders) {
			foreach ($orders as $o => $val){ 
				if ($orders[$o]->order_status=="P" && ($orders[$o]->coupon_code<>""  AND  $orders[$o]->coupon_discount<>0)){
					unset($orders[$o]);
				}				
			}
			$orders = array_values($orders);
		}
		
		$transactionids = array();
		if ($orders) {
			foreach ($orders as $order)
			{
				$product_attribute = $order->product_attribute;
				$product_attribute = json_decode($product_attribute);
				foreach ($product_attribute as $key => $val)
				{
					if (isset($val->rsvppro->transaction_id))
					{
						$transactionid = $val->rsvppro->transaction_id;
						$transactionids[] = intval($transactionid);
					}
				}
			}
		}
		if (count($validtransactionids)==0){
			$validtransactionids = array(-1);
		}

		if (count($transactionids) > 0 && count($validtransactionids)>0)
		{
			$sql = "UPDATE #__jev_rsvp_transactions SET paymentstate=0  WHERE transaction_id IN (" . implode(",", $transactionids) . ") AND transaction_id NOT IN (" . implode(",", $validtransactionids) . ")";
			$db->setQuery($sql);
			$db->query();
		}
		
		$comparams = JComponentHelper::getParams("com_rsvppro");
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$attendeehelper = new RsvpAttendeeHelper($comparams);
		
		// update attendee count etc.
		$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

		if (is_int($xmlfile) || file_exists($xmlfile))
		{
			// update attendee state
			$rsvpparams = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $event);
			$feesAndBalances = $rsvpparams->outstandingBalance($attendee);
		}

		$attendeehelper->countAttendees($rsvpdata->id, true);
	}

	private function setupPlugin()
	{

		$db = & JFactory::getDBO();

		// get the VM configuration
		$query = ' SELECT `config` FROM `#__virtuemart_configs` WHERE `virtuemart_config_id` = "1";';
		$db->setQuery($query);
		$configraw = $db->loadResult();
		$pair = array();
		$config = explode('|', $configraw);
		foreach ($config as $item)
		{
			$item = explode('=', $item);
			if (!empty($item[1]))
			{
				if ($item[0] !== 'offline_message' && $item[0] !== 'dateformat' )
				{
					$pair[$item[0]] = unserialize($item[1]);
				}
				else
				{
					if (strpos($item[1], ":")===false){
						$pair[$item[0]] = unserialize(base64_decode($item[1]));
					}
					else {
						$pair[$item[0]] = unserialize($item[1]);
					}
				}
			}
			else
			{
				$pair[$item[0]] = '';
			}
		}
		$this->vmconfig = $pair;

		// get the VM language info
		$this->vmlang = $this->vmconfig["vmlang"];
		if (isset($this->vmconfig["active_languages"]))
		{
			$this->vmlangs = $this->vmconfig["active_languages"];
		}
		else
		{
			$this->vmlangs = array($this->vmlang);
		}

		$this->vmlang = str_replace("-", "_", strtolower($this->vmlang));

		// No need to Create 'no-shipping' category

	}

	public function getProduct($rsvpdata, $rpid, $eventid)
	{
                $plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
		$params = new JRegistry($plugin->params);
                $rsvp_sku = $params->get('skuprefix', 'RSVP');
                        
		$db = & JFactory::getDBO();
		if ($rsvpdata->allrepeats)
		{
			$sku = $db->Quote($rsvp_sku . '_' . $eventid . '_0');
		}
		else
		{
			$sku = $db->Quote($rsvp_sku . '_' . $eventid . '_' . $rp_id);
		}
		$this->sku = $sku;

		$db = & JFactory::getDBO();

		//$vmptype = $this->ptypeid;

		$sql = "SELECT p.* FROM #__virtuemart_products as p"
				// . " \n LEFT JOIN #__virtuemart_product_product_type_relations as xr ON xr.virtuemart_product_id = p.virtuemart_product_id"
				. " \n WHERE p.product_sku=" . $sku;

		$db->setQuery($sql);

		// Make sure Joomfish doesn't translate this
		$product = $db->loadObject('stdClass', false);

		return $product;

	}

	private function setupProduct($row, $rsvpdata)
	{

		// Create the product 
		$db = JFactory::getDBO();

		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;
		$currency = $this->params->get("currency", "GBP");
		//$taxid = $this->params->get("vmtaxrate", 0);

		$title = $row->title();
		$rp_id = $row->rp_id();
		$eventid = $row->ev_id();
                $plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
		$params = new JRegistry($plugin->params);
                $rsvp_sku = $params->get('skuprefix', 'RSVP');

		$sdescription = strip_tags($row->content());
		$description = $row->content() . "<br/>{jevent=$rp_id|_self|1}";

		//$ptypeid = $this->ptypeid;
		$currency = $currency;
		if (isset($row->_imageurl1))
		{
			$image = str_replace(JURI::root(), "", $row->_imageurl1);
			$thumb = str_replace(JURI::root(), "", $row->_thumburl1);
		}
		else
		{
			$image = $db->Quote("");
			$thumb = $db->Quote("");
		}

		if ($rsvpdata->allrepeats)
		{
			$sku = $rsvp_sku . '_' . $eventid . '_0';
		}
		else
		{
			$sku = $rsvp_sku . '_' . $eventid . '_' . $rp_id;
		}

		$timestamp = new JDate();
		$timestamp = $timestamp->toMySQL();

		$pname = new JTableVMProducts($db);
		$pname->virtuemart_product_id = 0;
		$pname->virtuemart_vendor_id = 0;
		$pname->product_parent_id = 0;
		$pname->product_sku = $sku;
		$pname->product_weight = 0.0000;
		$pname->product_weight_uom = 'KG';
		$pname->product_length = 0.0000;
		$pname->product_width = 0.0000;
		$pname->product_height = 0.0000;
		$pname->product_lwh_uom = 'M';
		$pname->product_in_stock = 99999999;
		$pname->product_ordered = 0;
		$pname->product_available_date = $timestamp;
		$pname->product_special = 0;
		$pname->product_params = 'min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"0";|max_order_level=s:1:"0";|min_order_level=s:1:"1";|max_order_level=s:3:"100";|min_order_level=s:1:"1";|max_order_level=s:3:"100";|min_order_level=s:1:"1";|max_order_level=s:3:"100";|min_order_level=s:1:"1";|max_order_level=s:3:"100";|';
		$pname->layout = 0;
		$pname->published = 1;
		$pname->created_on = $timestamp;
		$pname->created_by = 0;
		$success = $pname->store();
		echo $db->getErrorMsg();

		if (!$success)
			return;

		$this->pslug = $sku;

		$this->insertVmLanguageValues("#__virtuemart_products", array("virtuemart_product_id", "product_name", "product_s_desc", "product_desc", "slug"), array($pname->virtuemart_product_id, $title, $sdescription, $description, $this->pslug));

		$pid = $pname->virtuemart_product_id;

		$db->setQuery("SELECT * FROM #__virtuemart_shoppergroups as sg WHERE sg.default=1 and sg.published=1");
		$defgroup = $db->loadObject();

		// Custom field for plugin
		$db->setQuery("SELECT * FROM #__virtuemart_customs WHERE custom_element = 'rsvppro'");
		$customfield = $db->loadObject();
		if (!$customfield)
		{
			$customfield = new JTableVMCustoms($db);
			$customfield->virtuemart_custom_id = 0;
			$customfield->custom_parent_id = 0;
			$customfield->custom_element = 'rsvppro';
			$customfield->virtuemart_vendor_id = 1;
			if (JVersion::isCompatible("1.6"))
			{
				$db->setQuery("SELECT extension_id as id from #__extensions where type='plugin' and folder='vmcustom' and element='rsvppro' ");
			}
			else
			{
				$db->setQuery("SELECT id from #__plugins where  element='rsvppro' and folder='vmcustom' ");
			}
			$pluginid = $db->loadResult();
			$customfield->custom_jplugin_id = $pluginid;
			//$customfield->custom_title = "RSVPPRO_TICKETS";
			$customfield->custom_title = "RSVP Pro Ticket Information";
			$customfield->custom_tip = "";
			$customfield->custom_field_desc = "";
			$customfield->field_type = "E";
			$customfield->is_cart_attribute = 1;
			$customfield->published = 1;
			$customfield->store();
			echo $db->getErrorMsg();
		}

		$db->setQuery("SELECT * FROM #__virtuemart_product_customfields WHERE virtuemart_product_id=" . $pid . " AND virtuemart_custom_id=" . $customfield->virtuemart_custom_id);
		$productcustom = $db->loadObject();

		if (!$productcustom)
		{
			$prodcust = new JTableVMProductCustom($db);
			$prodcust->virtuemart_customfield_id;
			$prodcust->virtuemart_product_id = $pid;
			$prodcust->virtuemart_custom_id = $customfield->virtuemart_custom_id;
			$prodcust->custom_value = 'rsvppro';
			$prodcust->store();
			echo $db->getErrorMsg();
		}


		$db->setQuery("SELECT * FROM #__virtuemart_product_prices WHERE virtuemart_product_id = '$pid'");
		$pprice = $db->loadObject();
		if (!$pprice)
		{
			$pprice = new JTableVMProductPrices($db);
			$pprice->virtuemart_product_price_id = 0;
			$pprice->virtuemart_product_id = $pid;
			$pprice->virtuemart_shoppergroup_id = $defgroup->virtuemart_shoppergroup_id;
			$pprice->product_price = 0;
			$pprice->override = 0;
			$pprice->product_override_price = 0;
			// find the currency
			$db->setQuery("SELECT virtuemart_currency_id FROM #__virtuemart_currencies WHERE currency_code_3 = '$currency'");
			$ccy = $db->loadResult();

			$pprice->product_currency = intval($ccy);
			$pprice->created_on = $timestamp;
			$pprice->price_quantity_start = 0;
			$pprice->price_quantity_end = 0;
			$pprice->store();
			echo $db->getErrorMsg();
		}

		$db->setQuery("SELECT * FROM #__virtuemart_product_categories WHERE virtuemart_product_id = '$pid'");
		$pcat = $db->loadObject();
		if (!$pcat)
		{
			//Insert the product category field
			foreach (explode($this->catid) as $catid)
			{
				$sql = <<<SQL
            INSERT INTO #__virtuemart_product_categories (id, virtuemart_product_id, virtuemart_category_id, ordering) 
            VALUES (0, $pid, $catid, 0);
SQL;
				$db->setQuery($sql);
				$db->query();
			}
		}

	}

	private function addToCart($product, $amount, $transaction)
	{
		if (!class_exists('VmConfig'))
			require(JPATH_ADMINISTRATOR . '/' . 'components' . '/' . 'com_virtuemart' . '/' . 'helpers' . '/' . 'config.php');
		VmConfig::loadConfig();

		if (!class_exists('VirtueMartCart'))
			require(JPATH_VM_SITE . '/' . 'helpers' . '/' . 'cart.php');
		if (!class_exists('calculationHelper'))
			require(JPATH_VM_ADMINISTRATOR . '/' . 'helpers' . '/' . 'calculationh.php');

		$vmcart = VirtueMartCart::getCart();

		JModelLegacy::addIncludePath(JPATH_VM_ADMINISTRATOR . '/' . 'models');
		$model = JModelLegacy::getInstance('Product', 'VirtueMartModel');
		$vmproduct = $model->getProduct($product->virtuemart_product_id, true, false);

		$success = true;

		// must set 
		// $post['quantity'][0] = 1;
		// $post['customPrice'] - array(virtumart_custom_id=> NUMERIC ??? e.g. array[0][19]=89
		//  the 89 is virtuenmart_customfield_id from virtuemart_product_customfields table
		// This should take care of the cart overwriting/updating
		// $post['customPlugin'] (that will be json encoded e.g. array[19]['textinput']['comment']="{"transaction_id"=1289023}= "georef";

		JRequest::setVar("quantity", array(1));
		$db = JFactory::getDbo();
		$db->setQuery("SELECT * FROM #__virtuemart_product_customfields where virtuemart_product_id=" . $product->virtuemart_product_id);
		$customfield = $db->loadObject();
		JRequest::setVar("customPrice", array(array($customfield->virtuemart_custom_id => $customfield->virtuemart_customfield_id)));
		// changed for VM 2.0.8e ARGH!!!
		JRequest::setVar("customPlugin", array($customfield->virtuemart_customfield_id => array('rsvppro' => array('transaction_id' => $transaction->transaction_id, 'amount' => $amount))));
		//JRequest::setVar("customPlugin", array($customfield->virtuemart_custom_id => array('rsvppro' => array('transaction_id' => $transaction->transaction_id, 'amount' => $amount))));

		$vmcart->add(array(intval($product->virtuemart_product_id)), $success);

		$mainframe = JFactory::getApplication();
		$Itemid = JRequest::getInt("Itemid", 0);
		$mainframe->redirect(JRoute::_("index.php?view=cart&ssl_redirect=1&option=com_virtuemart&Itemid=$Itemid", false));

	}

	private function insertVmLanguageValues($table, $fields, $values)
	{
		$db = JFactory::getDbo();
		$values = array_map(array($db, "quote"), $values);
		foreach ($this->vmlangs as $vmlang)
		{
			$vmlang = str_replace("-", "_", strtolower($vmlang));
			$db->setQuery("replace into " . $table . "_" . $vmlang . " (" . implode(",", $fields) . ") values (" . implode(",", $values) . ")");
			$db->query();
			echo $db->getErrorMsg();
		}

	}

	private function notifyVMPayment($transaction, $attendee, $rsvpdata){
		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
		// notification after payment is confirmed
		if ($templateParams->get("notifyvmpay", 1)==1  && $transaction->gateway == "virtuemart")
		{

			$comparams = JComponentHelper::getParams("com_rsvppro");
			include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
			$this->helper = new RsvpAttendeeHelper($comparams);

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

			$user = JFactory::getUser($attendee->user_id);
			if ($user->id == 0 && $comparams->get("attendemails", 0))
			{
				$name = $attendee->email_address;
				$username = $attendee->email_address;
			}
			else
			{
				$name = $user->name;
				$username = $user->username;
			}

			$class = "plgRsvppro" . ucfirst($transaction->gateway);
			$pluginpath = JVersion::isCompatible("1.6.0")?JPATH_SITE."/plugins/rsvppro/$transaction->gateway/" : JPATH_SITE."/plugins/rsvppro/";
			JLoader::register($class, $pluginpath . $transaction->gateway . ".php");
			
			$this->helper->event = $repeat;
			$this->helper->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'vmpay', false, $transaction);
		}		
	}
	
}

class JTableVMCategory extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_categories', 'virtuemart_category_id', $db);

	}

}

class JTableVMCategoryCategories extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_category_categories', 'id', $db);

	}

}

class JTableVMProducts extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_products', 'virtuemart_product_id', $db);

	}

}

class JTableVMProductPrices extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_product_prices', 'virtuemart_product_price_id', $db);

	}

}

class JTableVMCustoms extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_customs', 'virtuemart_custom_id', $db);

	}

}

class JTableVMProductCustom extends JTable
{

	public function __construct(&$db)
	{
		parent::__construct('#__virtuemart_product_customfields', 'virtuemart_customfield_id', $db);

	}

}
