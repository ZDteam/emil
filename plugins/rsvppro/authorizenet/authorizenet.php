<?php
/**
 * RSVP Pro component for Joomla 2.5+
 *
 * @version     $Id: jevboolean.php 1331 2010-10-19 12:35:49Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 * 
 * Partly based on Authorize.net payment gateway for Virtuemart
 */
if (!defined('_JEXEC'))
	die('Direct Access  is not allowed.');

jimport('joomla.plugin.plugin');

class plgRsvpproAuthorizenet extends JPlugin
{

	function __construct(& $subject, $config)
	{
		//see https://developer.authorize.net/integration/fifteenminutes/#hosted etc.
		require_once 'anet_php_sdk/AuthorizeNet.php'; // Include the SDK you downloaded in Step 2

		parent::__construct($subject, $config);

	}

	public function generatePaymentPage(&$html, $attendee, $rsvpdata, $event, &$transaction)
	{

		$plugin = JPluginHelper::getPlugin("rsvppro", "authorizenet");
		$params = new JRegistry($plugin->params);

		// TODO transaction based invoiceid
		$invoice = JRequest::getString("invoiceid", "") . '_' . $transaction->transaction_id;
		$rand = uniqid("inv_");

		$api_login_id = $params->get("login_id");
		$transaction_key = $params->get("transaction_key");
		
		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;
		$fp_timestamp = time();
		$fp_sequence = $transaction->transaction_id; // Enter an invoice or other unique number.
		$fingerprint = AuthorizeNetSIM_Form::getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp);
		
		$mainframe = JFactory::getApplication();
		$Itemid = JRequest::getInt("Itemid");
		$detaillink = JRoute::_(JUri::root() . $event->viewDetailLink($event->yup(), $event->mup(), $event->dup(), false), false);

		// TODO pick the payee etc from the session!
		$currency = $params->get("Defaultcurrency", "GBP");
		$code = "";
		$em = JRequest::getString("em", "");
		if ($em != "")
		{
			$code = "&em=$em";
		}

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
								
		$html = $params->get("aztemplate", "Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to Authorize.net ...<br/><br/>{FORM}");
		if (isset($templateParams->aztemplate))
		{
			$html = $templateParams->aztemplate;
		}
		if ($html == "")
		{
			$html = "Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to Authorize.net ...<br/><br/>{FORM}";
		}
		
		
		if (isset($attendee->outstandingBalances))
		{
			$html = str_replace("{INVOICEID}", $invoice, $html);
			$html = str_replace("{TOTALFEES}", $this->phpMoneyFormat($attendee->outstandingBalances['totalfee'], $templateParams), $html);
			$html = str_replace("{FEESPAID}", $this->phpMoneyFormat($attendee->outstandingBalances['feepaid'], $templateParams), $html);
			$html = str_replace("{BALANCE}", $this->phpMoneyFormat($attendee->outstandingBalances['feebalance'], $templateParams), $html);
			$html = str_replace("{DEPOSIT}",$this->phpMoneyFormat($attendee->outstandingBalances['deposit'], $templateParams), $html);
		}


		$comparams = JComponentHelper::getParams("com_rsvppro");
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

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($comparams);

		$html = $this->helper->parseMessage($html, $rsvpdata, $event, $name, $username, $attendee, false, false, $transaction);

		// sandbox test
		if (isset($templateParams->login_id) && $templateParams->login_id!= $api_login_id){
			$api_login_id = $templateParams->login_id;
			$transaction_key = $templateParams->transaction_key;
			$fingerprint = AuthorizeNetSIM_Form::getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp);
		}
				
		$currency = isset($templateParams->Currency) ? $templateParams->Currency : $currency;
		$mode = isset($templateParams->AZNMode) ? $templateParams->AZNMode : $params->get("AZNMode", "test.authorize.net");
		$mode = str_replace("www.authorize", "secure.authorize", $mode);
		$modepath = strpos($mode, "authorize.net")!==false ? "/gateway/transact.dll" : "/cgi-bin/an/order.pl";
		$start_date = JEventsHTML::getDateFormat($event->yup(), $event->mup(), $event->dup(), 0);

		$autoredirect = isset($templateParams->azautoredirect) ? $templateParams->azautoredirect : $params->get("azautoredirect", 1);
		
		ob_start();
		?>
		<form name="redirectToAuthorizeNet" id="redirectToAuthorizeNet" method="post" action="https://<?php echo $mode . $modepath; ?>">
			<input type='hidden' name="x_login" value="<?php echo $api_login_id ?>" />
			<input type='hidden' name="x_type" value="AUTH_CAPTURE" />
			<input type='hidden' name="x_fp_hash" value="<?php echo $fingerprint ?>" />
			<input type='hidden' name="x_amount" value="<?php echo $amount ?>" />
			<input type='hidden' name="x_fp_timestamp" value="<?php echo $fp_timestamp ?>" />
			<input type='hidden' name="x_fp_sequence" value="<?php echo $fp_sequence ?>" />
			<input type='hidden' name="x_version" value="3.1">
			<input type='hidden' name="x_show_form" value="payment_form">
			<input type='hidden' name="x_test_request" value="false" />
			<input type='hidden' name="x_method" value="cc">	
			<input type="hidden" name="x_invoice_num" value="<?php echo $invoice; ?>" />
			<input type="hidden" name="x_description" value="<?php echo htmlspecialchars($event->title() . " - " . $start_date); ?>" />

			<!--
			// must not use this an relay together!
			<INPUT TYPE=HIDDEN NAME="x_receipt_link_method" VALUE="LINK">
			<INPUT TYPE=HIDDEN NAME="x_receipt_link_text" VALUE="Return to <?php echo $mainframe->getCfg('sitename'); ?>" >
			<INPUT TYPE=HIDDEN NAME="x_receipt_link_URL" VALUE="<?php echo $detaillink . $code; ?>">
			//-->
			
			<INPUT TYPE=HIDDEN NAME="x_cancel_url _text" VALUE="Cancel and return to <?php echo $mainframe->getCfg('sitename'); ?>" >
			<INPUT TYPE=HIDDEN NAME="x_cancel_url" VALUE="<?php echo $detaillink . $code; ?>">

			<INPUT TYPE=HIDDEN NAME="x_relay_response" VALUE="TRUE">
			<INPUT TYPE=HIDDEN NAME="x_relay_always" VALUE="FALSE">
			<INPUT TYPE=HIDDEN NAME="x_relay_url" VALUE="<?php echo JURI::root() . "index.php?option=com_rsvppro&task=accounts.notify&gateway=authorizenet&Itemid=$Itemid&return=".base64_encode($detaillink . $code); ?>">

			<!--	<input type="hidden" name="x_currency_code" value="<?php echo $currency; ?>" />//-->

			<input type="submit" value="<?php echo JText::_("RSVP_PROCEED_WITH_REDIRECT"); ?>" />
		</form>
		<?php
		$form = ob_get_clean();
		if ($autoredirect)
		{
			$form .= "<script type='text/javascript'>function sendToAuthorizeNet(){document.redirectToAuthorizeNet.submit()}; setTimeout('sendToAuthorizeNet()',3000);</script>";
		}
		// Make sure HTML includes the form (this is often forgotton!)
		if (strpos($html, "{FORM}") === false)
		{
			$html .= "<br/>{FORM}";
		}

		$html = str_replace("{FORM}", $form, $html);

		// setup transaction data
		$transaction->amount = $amount;
		$transaction->currency = $currency;
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "authorizenet";

		$transaction->params = new stdClass();
		$transaction->params->fp_sequence = $fp_sequence;
		$transaction->params->fingerprint = $fingerprint;
		$transaction->params = json_encode($transaction->params);

		$transaction->store();

		//$this->notifyRsvpGateway($transaction, $attendee, $rsvpdata);

	}

	static public function transactionDetailLink($transaction)
	{

		$plugin = JPluginHelper::getPlugin("rsvppro", "authorizenet");
		$params = new JRegistry($plugin->params);

		$transactionLogdata = json_decode($transaction->logdata);
		if (!is_object($transactionLogdata) || !isset($transactionLogdata->request))
			return "";
		
		//$host = $params->get('AZNMode')=="test.authorize.net" ?  "sandbox.authorize.net" : "www.authorize.net";
		//return '<a href="https://' . $host  . '/UI/themes/sandbox/transaction/transactiondetail.aspx?transID=' . $transactionLogdata->request->x_trans_id . '" target="_blank">' . $transactionLogdata->request->x_trans_id . '</a>';
		
		$url = $params->get('AZNMode')=="test.authorize.net" ?  
				"sandbox.authorize.net/UI/themes/sandbox/transaction/transactiondetail.aspx?transID=" :
				"account.authorize.net/UI/themes/anet/transaction/transactiondetail.aspx?transID=";
			
		return '<a href="https://' . $url  . $transactionLogdata->request->x_trans_id . '" target="_blank">' . $transactionLogdata->request->x_trans_id . '</a>';			

	}

	public function activeGatewayClass(&$activeGatewayClass, $action="notify")
	{
		$gateway = JRequest::getString("gateway");
		 
		if ($gateway == "authorizenet" || strpos($gateway,"authorizenet_")===0)
		{
			$activeGatewayClass = __CLASS__;
		}

	}

	public function activeGateways(&$activeGatewayClasses)
	{
		$activeGatewayClasses[] = __CLASS__;

	}

	static public function notifyRsvpGateway(&$args)
	{

		$gateway = JRequest::getString("gateway");

		if ($gateway == "authorizenet" || $gateway == "1")
		{

			$plugin = JPluginHelper::getPlugin("rsvppro", "authorizenet");
			$dispatcher = & JDispatcher::getInstance();
			$anPlugin = new plgRsvpproAuthorizenet($dispatcher, (array) ($plugin));

			$anPlugin->Authorize_Response();
		}

	}

	function phpMoneyFormat($amount, $template)
	{
		if (isset($template->Currency))
		{
			$digits = $template->CurrencyDigits;
			$symbol = $template->CurrencySymbol;
			$onLeft = (strcmp($template->CurrencyPlacement, 'left') == 0);
			$separator = $template->CurrencySeparator;
			$decimal = $template->CurrencyDecimal;
		}
		else
		{
			$eSessParams = JComponentHelper::getParams('com_rsvppro');

			$digits = $eSessParams->get("CurrencyDigits");
			$symbol = $eSessParams->get("CurrencySymbol");
			$onLeft = (strcmp($eSessParams->get("CurrencyPlacement"), 'left') == 0);
			$separator = $eSessParams->get("CurrencySeparator");
			$decimal = $eSessParams->get("CurrencyDecimal");
		}
		$formattedText = '';

		// negative amount
		if ($amount < 0)
			$formattedText .= '-';

		// currency symbol on the left
		if ($onLeft)
			$formattedText .= $symbol;

		// format with correct number of digits and separator
		if ($digits > 0)
		{
			$formattedText .= number_format(abs($amount), $digits, $decimal, $separator);
		}
		else
		{
			$formattedText = abs($amount);
		}

		// currency symbols on the right
		if (!$onLeft)
			$formattedText .= $symbol;


		return $formattedText;

	}

	protected /**
	 *  Verifies transaction with Authorize,net  server
	 */
	function Authorize_Response()
	{

		//JRequest::setVar("tmpl","component");

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logText = "SIM Response - " . gmstrftime("%b %d %Y %H:%M:%S", time()) . "\n";

		// get reveiver email for log messages
		$receiver_email = strval(JRequest::getVar('receiver_email', 'FIX.ME@copyn.plus.com'));

		// get the invoice ID - will be needed for to get the transaction and rsvpdata
		$invoice = JRequest::getString("x_invoice_num", "0_0");
		$parts = explode("_", $invoice);
		if (count($parts) != 2 || $parts[1] == 0)
		{
			$logText .= "Bad invoice id $invoice!\n";
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			return;
		}
		JArrayHelper::toInteger($parts);
		list($attendee_id, $transaction_id) = $parts;

		// Load the transaction instance		
		$transaction = new rsvpTransaction( );
		if (!$transaction->load($transaction_id))
		{
			$logText .= "Error loading rsvpTransaction instance $transaction_id\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			echo "Invalid transaction record specified<Br/>";
			return;
		}
		$transactionData = json_decode($transaction->params);

		// fetch the session and the attendee
		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee_id;
		$db->setQuery($sql);
		$attendee = $db->loadObject();
		if (!$attendee)
		{
			$logText .= "Error loading attendee instance $attendee_id\n";
			$logText .= "DB Error is " . $db->getErrorMsg() . "\n";
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			echo "Invalid attendee record specified<Br/>";
			return;
		}

		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
		{
			$logText .= "Error loading rsvpdata instance" . $attendee->at_id . "\n";
			$logText .= "DB Error is " . $db->getErrorMsg() . "\n";
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			echo "Invalid attendance record specified<Br/>";
			return;
		}

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

		// Security check on source IP address etc.
		$this->securityCheck($transaction, $templateParams);

		if (!$transaction->bind($_POST))
		{
			$logText .= "Error binding rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			return;
		}

		$transaction->logdata = array();
		$transaction->logdata["request"] = JRequest::get();
		$transaction->logdata = json_encode($transaction->logdata);

		if (JRequest::getInt("x_response_code") == 1)
		{
			$transaction->paymentstate = 1;

		}
		else
		{
			$transaction->paymentstate = -1;
			echo "There was a problem with your payment<br/>";
			if (JRequest::getString("x_response_reason_text")){
				echo JRequest::getString("x_response_reason_text")."<br/>";
			} 

			if (JRequest::getString("x_response_reason_code") < 5) {
				$msgs = array("1"=>"This transaction has been approved. <br/>",
					"2"=>"This transaction has been declined.<br/> ",
					"3"=>"There has been an error processing this transaction. <br/>",
					"4"=>"This transaction is being held for review.<br/>");
			}
			echo  $msgs[JRequest::getString("x_response_reason_code")]. "<br/>";
			$logText .= "Error processing payment\n";
			$logText .= JRequest::getString("x_response_reason_text")."\n";
			$logText .= "Reason code is ".JRequest::getString("x_response_reason_code")." See http://www.authorize.net/support/merchant/Transaction_Response/Response_Reason_Codes_and_Response_Reason_Text.htm";
					
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email, false);
			$this->redirect();
			return;
		}

		if (!$transaction->check())
		{
			echo "There was a problem with your payment<br/>";

			$logText .= "Error checking rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			$this->redirect();
			return;
		}

		if (!$transaction->store())
		{
			echo "There was a problem with your payment<br/>";
			
			$logText .= "Error saving rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText, $receiver_email);
			$this->redirect();
			return;
		}

		echo "Your payment succeeded<br/>";
		$this->redirect();
	
			
		// for debugging only
		/*
		$logText .= "Authorize,net Payment Success\n";
		$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
		$this->Authorize_Message($logText, $receiver_email);
		*/
	}

	protected function redirect(){
		// do not show modules etc.
		JRequest::setVar("tmpl","component");
		
		$return = JRequest::getVar("return","");
		
		if ($return) {
			$return = base64_decode($return);
			echo "<a href='return'>Return to ".JFactory::getApplication()->getCfg('sitename')."</a>"; 
			?>
<script type="text/javascript">
setTimeout(function () {
   window.location.href= '<?php echo $return;?>';
},1000); 
</script>
<?php
		} 
		
	}
		
	protected function Authorize_Message($text, $email)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'authorize.net log', $email, 'Authorize SIM Message', $text, 0);

	}

	protected function Authorize_Log($text)
	{

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logfile = JPATH_SITE. str_replace("//","/",$this->params->get('AZLogFileLocation','/administrator/components/com_rsvppro/logs/') . '/authorize_log.txt');

		$fp = @fopen($logfile, 'a' );
		if ($fp){
			@fwrite( $fp, $text . "\n\n" );
			@fclose( $fp );
		}
	}

	protected function Authorize_Error($text, $email, $die=true)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'authorize.net log', $email, 'Authorize.net Error', $text, 0);
		if ($die){
			echo "There was an error";
			exit();
		}
	}

	protected function FixEmail(& $email)
	{
		if ($email == "" || $email == 'FIX.ME@copyn.plus.com')
		{
			// get Jevents params and send messages to admin if necessary
			$params = JComponentHelper::getParams("com_jevents");
			$jevadmin = $params->get("jevadmin", -1);
			if ($jevadmin==-1) {
				return;
			}
			$jevadmin = JFactory::getUser($jevadmin);
			$email = $jevadmin->email;
		}

	}

	protected function securityCheck($transaction, $templateParams)
	{

		$plugin = JPluginHelper::getPlugin("rsvppro", "authorizenet");
		$params = new JRegistry($plugin->params);

		// TODO transaction based invoiceid
		$invoice = JRequest::getString("invoiceid", "") . '_' . $transaction->transaction_id;
		$rand = uniqid("inv_");

		$api_login_id = $params->get("login_id");
		$api_hash = $params->get("hash");

		// sandbox test
		if (isset($templateParams->login_id) && isset($templateParams->hash) && $templateParams->login_id!= $api_login_id){
			$api_login_id = $templateParams->login_id;
			$api_hash = $templateParams->hash;
		}
		
		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;

		$authorizeNetSIM = new AuthorizeNetSIM($api_login_id, $api_hash);
		// fix for eProcessingNetwork using lower case return key for x_md5_hash where Authorize.net uses x_MD5_Hash
		$authorizeNetSIM->md5_hash = JRequest::getString("x_md5_hash", $authorizeNetSIM->md5_hash);
		
		if (!$authorizeNetSIM->isAuthorizeNet())
		{
			$logText = "Error code 506. Possible fraud.";
			$logText .= "SIM Response - " . gmstrftime("%b %d %Y %H:%M:%S", time()) . "\n";
			//$logText .= "return md5 = ".$authorizeNetSIM->md5_hash." calculated md5 = ".$authorizeNetSIM->generateHash()."\n";
			$logText .= "return md5 = ".$authorizeNetSIM->md5_hash." calculated md5 = ".$authorizeNetSIM->generateHash()."\n";
			$logText .= "amount ".$authorizeNetSIM->amount." transaction_id ".$authorizeNetSIM->transaction_id."\n";
			
			$logText .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n";
			$logText .= '$_SERVER: ' . print_r($_SERVER, true) . "\n";

			$this->Authorize_Log($logText);
			$this->Authorize_Error($logText,"");

			echo "Failed security check<br/>";
			exit();
		}

	}

	private function notifyAuthorizePayment($transaction, $attendee, $rsvpdata){
		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);

		// immediate notification
		if ($templateParams->get("aznotifyppay", 1)>0  && $transaction->gateway == "authorizenet")
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

			$this->helper->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'azpay', false, $transaction);

			return $repeat;
		}		
	}
	
}
