<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgRsvpproPaypalipn extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	 public function generatePaymentPage(&$html, $attendee, $rsvpdata, $event, &$transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","paypalipn");
		$params = new JRegistry($plugin->params);

		// TODO transaction based invoiceid
		$invoice = JRequest::getString("invoiceid","").'_'.$transaction->transaction_id;;
		$rand = uniqid("inv_");
		$html .= "Invoice id is $invoice<br/>";
		$amount = ceil(JRequest::getFloat("amount",0)*100)/100;
		$mainframe = JFactory::getApplication();
		$Itemid=JRequest::getInt("Itemid");
		$detaillink = JRoute::_( JUri::root() . $event->viewDetailLink($event->yup(),$event->mup(),$event->dup(),false),false);;

		// TODO pick the payee etc from the session!
		$payee = $params->get("DefaultPayPalAccount");
		$currency = $params->get("Defaultcurrency","GBP");
		$code = "";
		$em = JRequest::getString("em","");
		if ($em != ""){
			$code = "&em=$em";
		}

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

		$html =   $params->get("template","Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to paypal ...<br/><br/>{FORM}");
		if (isset($templateParams->template)){
			//$templateParams->template = nl2br($templateParams->template );
			$html =   $templateParams->template;
		}
		if ($html == ""){
			$html =   "Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to paypal ...<br/><br/>{FORM}";
		}
		if (isset($attendee->outstandingBalances)){
			$html = str_replace("{INVOICEID}",$invoice, $html);
			$html = str_replace("{TOTALFEES}",$this->phpMoneyFormat($attendee->outstandingBalances['totalfee'], $templateParams), $html);
			$html = str_replace("{FEESPAID}",$this->phpMoneyFormat($attendee->outstandingBalances['feepaid'], $templateParams), $html);
			$html = str_replace("{BALANCE}",$this->phpMoneyFormat($attendee->outstandingBalances['feebalance'], $templateParams), $html);
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

		 $currency = isset($templateParams->Currency)? $templateParams->Currency:$currency;
		 $payee = isset($templateParams->DefaultPayPalAccount)? $templateParams->DefaultPayPalAccount:$payee;
		 $mode =  isset($templateParams->PayPalMode)?$templateParams->PayPalMode : $params->get("PayPalMode","www.sandbox.paypal.com");
		 $start_date = JEventsHTML::getDateFormat( $event->yup(), $event->mup(), $event->dup(), 0 );
		 
		 $autoredirect = isset($templateParams->autoredirect)? $templateParams->autoredirect:$params->get("autoredirect",1);;
		ob_start();
		?>
		<form name="redirectToPayPal" id="redirectToPayPal" method="post" action="https://<?php echo $mode;?>/cgi-bin/webscr">
			<input type="hidden" name="task" value="payment" />
			<input type="hidden" name="cmd" value="_xclick" />
			<input type="hidden" name="business" value="<?php echo $payee;?>" />
			<input type="hidden" name="currency_code" value="<?php echo $currency; ?>" />
			<input type="hidden" name="invoice" value="<?php echo $invoice;?>" />
			<input type="hidden" name="item_name" value="<?php echo htmlspecialchars($event->title() . " - ". $start_date);?>" />
			<input type="hidden" name="amount" value="<?php echo $amount;?>" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="rm" value="2" />
			<input type="hidden" name="cbt" value="Return to <?php echo $mainframe->getCfg( 'sitename' ); ?>" />
			<input type="hidden" name="return" value="<?php echo $detaillink.$code; ?>" />
			<input type="hidden" name="notify_url" value="<?php echo JURI::root()."index.php?option=com_rsvppro&task=accounts.notify&gateway=paypalipn"; ?>" />
			<input type="hidden" name="cancel_return" value="<?php $detaillink.$code; ?>" />
			<input type="submit" value="<?php echo JText::_("RSVP_PROCEED_WITH_REDIRECT"); ?>" />
		</form>
		<?php
		$form = ob_get_clean();
		if ($autoredirect){
			$form .= "<script type='text/javascript'>function sendToPaypal(){document.redirectToPayPal.submit()}; setTimeout('sendToPaypal()',3000);</script>";
		}
		// Make sure HTML includes the form (this is often forgotton!)
		if (strpos($html,"{FORM}")===false){
			$html .= "<br/>{FORM}";
		}
		
		$html = str_replace("{FORM}",$form, $html);

		// setup transaction data
		$transaction->amount = $amount;
		$transaction->currency = $currency;
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "paypalipn";

		$transaction->params = new stdClass();
		$transaction->params->payee = $payee;
		$transaction->params = json_encode($transaction->params);
		
		$transaction->store();
		
		$this->notifyPaypalPayment($transaction, $attendee, $rsvpdata);
		
	}

	static public function transactionDetailLink($transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","paypalipn");
		$params = new JRegistry($plugin->params);

		$transactionLogdata = json_decode($transaction->logdata);
		if (!is_object($transactionLogdata) || !isset($transactionLogdata->request)) return "";
		return  '<a href="https://' . $params->get('PayPalMode') .'/vst/id=' . $transactionLogdata->request->txn_id . '" target="_blank">' . $transactionLogdata->request->txn_id . '</a>' ;
	}

	public function activeGatewayClass(&$activeGatewayClass, $action="notify"){
		$gateway = JRequest::getString("gateway");

		if ($gateway == "paypalipn" || $gateway == "1" || strpos($gateway,"paypalipn_")===0){
			$activeGatewayClass = __CLASS__;
		}
	}

	public function activeGateways(&$activeGatewayClasses){
			$activeGatewayClasses[] = __CLASS__;
	}

	static public function notifyRsvpGateway(&$args){

		$gateway = JRequest::getString("gateway");

		if ($gateway == "paypalipn" || $gateway == "1"){

			$plugin = JPluginHelper::getPlugin("rsvppro","paypalipn");
			$dispatcher = & JDispatcher::getInstance();
			$paypalPlugin = new plgRsvpproPaypalipn($dispatcher,  (array)($plugin));

			$paypalPlugin->PayPal_Response();
		}

	}


	protected /**
 *  PayPal_Response
 *  Verifies IPN transaction with PayPal server
 */
	function PayPal_Response()
	{

		// Security check on source IP address
	//	$this->securityCheck();
		
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logText  = "IPN Response - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n";

		// get reveiver email for log messages
		$receiver_email  =   strval( JRequest::getVar( 'receiver_email', '' ) );
		
		// get the invoice ID - will be needed for to get the transaction and rsvpdata
		$invoice = JRequest::getString("invoice","0_0");
		$parts = explode("_",$invoice);
		if (count($parts)!=2){
			$logText .= "Bad invoice id!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText  , $receiver_email);
			return;
		}
		JArrayHelper::toInteger($parts);
		list($attendee_id,$transaction_id) = $parts;
		
		// Load the transaction instance		
		$transaction =new rsvpTransaction( );
		if ( !$transaction->load( $transaction_id ) )
		{
			$logText .= "Error loading rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText  ,$receiver_email );
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
			$logText .= "Error loading attendee instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText  ,$receiver_email );
			return;
		}
		
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
		{
			$logText .= "Error loading rsvpdata instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText  ,$receiver_email );
			return;
		}
		
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
		
		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';

		foreach ( $_POST as $key => $value )
		{
			 if (get_magic_quotes_gpc()){
			         // Fix issue with magic quotes
				$value = stripslashes ($value);
			 }

			$value = urlencode(  $value  );
			$req .= "&$key=$value";
		}

		 $mode =  isset($templateParams->PayPalMode)?$templateParams->PayPalMode : $this->params->get("PayPalMode","www.sandbox.paypal.com");
		 $currency = isset($templateParams->Currency)? $templateParams->Currency:$this->params->get("Defaultcurrency","GBP");
		 $payee = isset($templateParams->DefaultPayPalAccount)? $templateParams->DefaultPayPalAccount:$this->params->get("DefaultPayPalAccount");;
		 $secure = isset($templateParams->securepaypal)? $templateParams->securepaypal:$this->params->get("securepaypal",1);
				
		// post back to PayPal system to validate
		$header = "";
		$logText .= "Authenticating with PayPal...\n";
		if ($secure){
			$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Host: " . $mode . ":443\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
			$fp = fsockopen( 'ssl://' . $mode, 443, $errno, $errstr, 30 );
		}
		else {
			$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Host: " . $mode . "\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
			$fp = fsockopen(  $mode, 80, $errno, $errstr, 30 );
		}		
		if ( !$fp )
		{
			// HTTP ERROR
			$logText .= "Failed to establish post-back connection: $errstr ($errno)!";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";			
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText ,"");
			return;
		}


		// send the POST data back to PayPal, and wait for a response
		fputs ($fp, $header . $req);

		$understoodResponse = false;
		$paypalPostBackResponse = '';
		while ( !feof( $fp ) )
		{
			$res = fgets( $fp, 1024 );
			$paypalPostBackResponse .= $res;
			if ( strcmp( $res, 'VERIFIED' ) == 0 ) $understoodResponse = true;
		}

		if ( !$understoodResponse )
		{
			$logText .= "Invalid PayPal Response!\n";
			$logText .= $paypalPostBackResponse."\n";
			$logText .= "header = $header\n";
			$logText .= "req = $req\n";
			$logText .= '$_POST: ' . print_r( $_POST, true ) . "\n";
			//$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText  , "");
			return;
		}

		// So far so good.  Now, we need to check the specifics of what went on.
		// To do that, safely extract some information for POST to use in the queries.
		$txn_id 		 =   strval( JRequest::getVar(         'txn_id', '' ) );
		$receiver_email  =   strval( JRequest::getVar( 'receiver_email', '' ) );
		$currency        =   strval( JRequest::getVar(    'mc_currency', '' ) );
		$amount          = floatval( JRequest::getVar(    'mc_gross',  0 ) );
		$status          =   strval( JRequest::getVar( 'payment_status', '' ) );
		
		// check the payment_status is Completed
		if ( strcmp( $status, 'Completed' ) != 0  &&  strcmp( $status, 'Refunded' ) != 0)
		{
			$logText .= "Incomplete payment transaction!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText, $receiver_email);
			return;
		}
		
		$refunded = false;
		// if a refund the set the transasction id back to zero
		if (strcmp( $status, 'Refunded' ) == 0) {

			// see https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_RefundTransaction for more ideas
			$logText .= "Payment refunded!\n";
			$this->PayPal_Log( $logText );

			// must ensure the registration is set to cancelled state too!
			$refunded = true;
		}

		// Check #1 - Ensure the payment was received by the proper paypal account
		$business_email  =   strval( JRequest::getVar( 'business',$receiver_email) );
		if ( strcasecmp( $business_email, $transactionData->payee ) != 0 )
		{
			$logText .= "Receiver Email Error!\n";
			$logText .= "receiver = $receiver_email payee from transaction =".$transactionData->payee."\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText, $receiver_email);
			return;
		}

		// Check #2 - Ensure amount is correct
		if ( $amount != $transaction->amount &&  strcmp( $status, 'Refunded' ) != 0) {
			$logText .= "Amount Error!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText, $receiver_email );
			return;
		}

		// Check #3 - Ensure currency is correct
		if ( strcmp( $currency, $transaction->currency ) != 0 )
		{
			$logText .= "Currency Error!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText , $receiver_email);
			return;
		}

		if ( !$transaction->bind( $_POST ) )
		{
			$logText .= "Error binding rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText , $receiver_email);
			return;
		}

		$transaction->logdata = array();
		$transaction->logdata["request"] = JRequest::get();
		$transaction->logdata["paypalPostBackResponse"] = $paypalPostBackResponse;
		$transaction->logdata = json_encode($transaction->logdata);

		if ($refunded){
			$transaction->paymentstate = -1;
		}
		else {
			$transaction->paymentstate = 1;
		}

		if ( !$transaction->check() )
		{
			$logText .= "Error checking rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText , $receiver_email);
			return;
		}

		if ( !$transaction->store() )
		{
			$logText .= "Error saving rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText , $receiver_email);
			return;
		}

		$this->notifyPaypalPayment($transaction, $attendee, $rsvpdata, true);
		
		/*
		if ($refunded){
			// load the registration
			$registration = new comAERegistration( $db );
			$registration->load( $transaction->registration_id );

			$registration->updateStatus();
		}
		*/
		
		$logText .= "Success\n";
		// for debugging only
		//$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";

		$this->PayPal_Message( $logText , $receiver_email);
	}

	protected function PayPal_Message( $text , $email)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'paypal log', $email,'PayPal IPN Message', $text,0 );

	}

	protected function PayPal_Error( $text, $email)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'paypal log', $email,'PayPal IPN Error', $text,0 );
		
	}

	protected function FixEmail(& $email){
		if ($email==""){
			// get Jevents params and send messages to admin if necessary
			$params = JComponentHelper::getParams("com_jevents");
			$jevadmin = $params->get("jevadmin",-1);
			if ($jevadmin==-1) {
				return;
			}
			$jevadmin = JFactory::getUser($jevadmin);
			$email = $jevadmin->email;
		}

	}
	protected function PayPal_Log( $text )
	{

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logfile = JPATH_SITE. str_replace("//","/",$this->params->get('PayPalLogFileLocation','/administrator/components/com_rsvppro/logs/') . '/paypal_log.txt');

		$fp = @fopen($logfile, 'a' );
		if ($fp){
			@fwrite( $fp, $text . "\n\n" );
			@fclose( $fp );
		}
	}


	protected function securityCheck(){

		// - thanks to VM for this.
    	// Get the list of IP addresses for www.paypal.com and notify.paypal.com
        $paypal_iplist = gethostbynamel('www.paypal.com');
	$paypal_iplist2 = gethostbynamel('notify.paypal.com');
        $paypal_iplist = array_merge( $paypal_iplist, $paypal_iplist2 );

        $paypal_sandbox_hostname = 'ipn.sandbox.paypal.com';
        $remote_hostname = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

        $valid_ip = false;

        if( $paypal_sandbox_hostname == $remote_hostname ) {
            $valid_ip = true;
            $hostname = 'www.sandbox.paypal.com';
        }
        else {
            $ips = "";
            // Loop through all allowed IPs and test if the remote IP connected here
            // is a valid IP address
            foreach( $paypal_iplist as $ip ) {
                $ips .= "$ip,\n";
                $parts = explode( ".", $ip );
                $first_three = $parts[0].".".$parts[1].".".$parts[2];
                if( preg_match("/^$first_three/", $_SERVER['REMOTE_ADDR']) ) {
                    $valid_ip = true;
                }
            }
            $hostname = 'www.paypal.com';
        }

        if( !$valid_ip ) {
            $logText =  "Error code 506. Possible fraud. Error with REMOTE IP ADDRESS = ".$_SERVER['REMOTE_ADDR'].".
                        The remote address of the script posting to this notify script does not match a valid PayPal ip address\n" ;

			$this->PayPal_Log( $logText );
			$this->PayPal_Error( $logText );

            exit();
        }

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


	private function notifyPaypalPayment($transaction, $attendee, $rsvpdata, $aftertransation=false){
		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
		// immediate notification
		if (((!$aftertransation && $templateParams->get("notifypplpay", 1)==2) || ($aftertransation && $templateParams->get("notifypplpay", 1)==1))  && $transaction->gateway == "paypalipn")
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
			
			$this->helper->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'pplpay', false, $transaction);
		}		
	}
	
}
