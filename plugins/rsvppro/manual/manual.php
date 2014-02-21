<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgRsvpproManual extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	public function generatePaymentPage(&$html, $attendee, $rsvpdata, $event, &$transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","manual");
		$params = new JRegistry($plugin->params);
		$comparams  = JComponentHelper::getParams("com_rsvppro");

		$currency = $comparams->get("Defaultcurrency","GBP");
		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;

		if (isset($rsvpdata->template) &&  is_numeric($rsvpdata->template)){
			$db = JFactory::getDBO();
			$db->setQuery("Select params from #__jev_rsvp_templates where id=".intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams){
				$templateParams =  json_decode($templateParams->params);
			}
			else {
				$templateParams = $params;
			}
		}
		else {
			$templateParams = $params;
		}

		$html =   isset($templateParams->templatebody)? $templateParams->templatebody: $params->get("templatebody","Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please send your payment to ...");
		if (isset($attendee->outstandingBalances)){
			$html = str_replace("{TOTALFEES}",$this->phpMoneyFormat($attendee->outstandingBalances['totalfee'], $templateParams), $html);
			$html = str_replace("{FEESPAID}",$this->phpMoneyFormat($attendee->outstandingBalances['feepaid'], $templateParams), $html);
			$html = str_replace("{BALANCE}",$this->phpMoneyFormat($attendee->outstandingBalances['feebalance'], $templateParams), $html);
			$html = str_replace("{DEPOSIT}",$this->phpMoneyFormat($attendee->outstandingBalances['deposit'], $templateParams), $html);
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
		
		$comparams = JComponentHelper::getParams("com_rsvppro");
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($comparams);
		
		$html = $this->helper->parseMessage($html, $rsvpdata, $event, $name, $username, $attendee, false, false, $transaction);

		$currency = isset($templateParams->Currency)? $templateParams->Currency:$currency;

		// setup transaction data
		$transaction->amount = $amount;
		$transaction->currency = $currency;
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "manual";

		$transaction->params = new stdClass();
		$transaction->params = json_encode($transaction->params);
		
		$transaction->store();
		
		$this->notifyManualPayment($transaction, $attendee, $rsvpdata);
		
	}

	public function editTransaction ($attendee, $rsvpdata, $event, &$transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","manual");
		$params = new JRegistry($plugin->params);
		$comparams  = JComponentHelper::getParams("com_rsvppro");

		$lang =& JFactory::getLanguage();
		$lang->load("plg_rsvppro_manual", JPATH_ADMINISTRATOR);
		
		$db = JFactory::getDBO();

		if (isset($rsvpdata->template) &&  is_numeric($rsvpdata->template)){
			$template = $rsvpdata->template;
			$db->setQuery("Select params from #__jev_rsvp_templates where id=".intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams){
				$templateParams =  json_decode($templateParams->params);
			}
			else {
				$templateParams = $params;
			}
		}
		else {
			$template = false;
			$templateParams = $params;
		}
		$html ="<h2>".($transaction->transaction_id ? JText::_("RSVP_FORM_TRANSACTION_EDIT") :  JText::_("RSVP_FORM_TRANSACTION_ADD"))."</h2>";

		$html .=   isset($templateParams->templatebody)? $templateParams->templatebody: $params->get("templatebody","Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please send your payment to ...");
		if (isset($attendee->outstandingBalances)){
			$html = str_replace("{TOTALFEES}",$this->phpMoneyFormat($attendee->outstandingBalances['totalfee'], $template), $html);
			$html = str_replace("{FEESPAID}",$this->phpMoneyFormat($attendee->outstandingBalances['feepaid'], $template), $html);
			$html = str_replace("{BALANCE}",$this->phpMoneyFormat($attendee->outstandingBalances['feebalance'], $template), $html);

			$transaction->amount = $transaction->amount ==0?$attendee->outstandingBalances['feebalance']:$transaction->amount ;
		}

		// setup transaction data
		$transaction->currency = isset($templateParams->Currency) ? $templateParams->Currency : $params->get("Currency");
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "manual";

		$transaction->params = new stdClass();
		$transaction->params = json_encode($transaction->params);

		JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
		JHtml::script( 'components/com_rsvppro/assets/js/tabs.js' );
		jimport('joomla.utilities.date');
		// Must use strtotime format for force JDate to not just parse the date itself!!!
		if (is_null($transaction->transaction_date) || $transaction->transaction_date=="0000-00-00 00:00:00" || $transaction->transaction_date=="1970-01-01 01:00:00"){
			jimport("joomla.utilities.date");
			$tempdate = new JDate("+0days");
			$transaction->transaction_date=$tempdate->toMySql();
		}
		ob_start();
?>
<script  type="text/javascript">
<!--
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		submitform( pressbutton );
	}
// -->
</script>

<form action="index.php" name="adminForm" method="post">
			<input type="hidden" name="transaction_id" value="<?php echo $transaction->transaction_id; ?>" />
			<input type="hidden" name="attendee_id" value="<?php echo $attendee->id; ?>" />
			<input type="hidden" name="cid[]" value="<?php echo $attendee->id; ?>" />
			<input type="hidden" name="paymentstate" value="1" />
	<table cellpadding="4" cellspacing="1" border="0" class="adminform">
		<tr>
			<td><?php echo  JText::_("RSVP_TRANSACTION_DATE");?>:</td>
			<td>
				<input type="text" name="transaction_date" size="40" value="<?php echo $transaction->transaction_date; ?>" />
			</td>
		</tr>
		<tr>
			<td><?php echo  JText::_("RSVP_TRANSACTION_GATEWAY");?>:</td>
			<td>
				<?php echo  JText::_("ENGINE_MANUAL");?>
				<input type="hidden" name="gateway" value="manual" />
			</td>
		</tr>
		<tr>
			<td><?php echo  JText::_("RSVP_TRANSACTION_CURRENCY");?>:</td>
			<td>
				<?php echo $transaction->currency;?>
				<input type="hidden" name="currency" value="<?php echo $transaction->currency;?>" />
			</td>
		</tr>
		<tr>
			<td><?php echo  JText::_("RSVP_TRANSACTION_AMOUNT");?>:</td>
			<td>
				<input type="text" name="amount" size="40" value="<?php echo $transaction->amount; ?>" />
			</td>
		</tr>
		<tr>
			<td valign="top"><?php echo  JText::_("RSVP_TRANSACTION_NOTES");?>:</td>
			<td>
				<textarea rows="5" cols="50" name="notes"><?php echo $transaction->notes; ?></textarea>
			</td>
		</tr>
	</table>

	<input type="hidden" name="option" value="com_rsvppro" />
	<input type="hidden" name="task" value="attendees.edittransaction" />
	<?php echo JHtml::_( 'form.token' ); ?>
</form>
<?php
		$html .= ob_get_clean();
		return $html;
	}

	static public function transactionDetailLink($transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","manual");
		$params = new JRegistry($plugin->params);

		return  'Manual Payment' ;
	}

	public function activeGatewayClass(&$activeGatewayClass, $action="notify"){
		$gateway = JRequest::getString("gateway");

		if ($gateway == "manual" || $gateway == "0" || strpos($gateway,"manual_")===0){
			$activeGatewayClass = __CLASS__;
		}
	}

	public function activeGateways(&$activeGatewayClasses){
			$activeGatewayClasses[] = __CLASS__;
	}

	function phpMoneyFormat($amount, $template) {
		if (isset($template->Currency)){
			$digits 	= $template->CurrencyDigits;
			$symbol 	= $template->CurrencySymbol;
			$onLeft 	= (strcmp($template->CurrencyPlacement,'left') == 0);
			$separator 	= $template->CurrencySeparator;
			$decimal	= $template->CurrencyDecimal;
		}
		else {
			$eSessParams = JComponentHelper::getParams('com_rsvppro');

			$digits 	= $eSessParams->get("CurrencyDigits");
			$symbol 	= $eSessParams->get("CurrencySymbol");
			$onLeft 	= (strcmp($eSessParams->get("CurrencyPlacement"),'left') == 0);
			$separator 	= $eSessParams->get("CurrencySeparator");
			$decimal	= $eSessParams->get("CurrencyDecimal");
		}
		$formattedText  = '';

		// negative amount
		if ( $amount < 0 ) $formattedText .= '-';

		// currency symbol on the left
		if ( $onLeft )     $formattedText .= $symbol;

		// format with correct number of digits and separator
		if ( $digits > 0 ) {
			$formattedText .= number_format( abs($amount), $digits, $decimal, $separator );
		} else {
			$formattedText = abs( $amount );
		}

		// currency symbols on the right
		if ( !$onLeft )    $formattedText .= $symbol;


		return $formattedText;
	}

	private function notifyManualPayment($transaction, $attendee, $rsvpdata){
		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
		// immediate notification
		if ($templateParams->get("notifymanpay", 1)==2  && $transaction->gateway == "manual")
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
			
			$this->helper->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'manpay', false, $transaction);
		}		
	}
		
	
}