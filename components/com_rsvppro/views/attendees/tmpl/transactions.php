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
defined('_JEXEC') or die('Restricted Access');

JHtml::_('behavior.tooltip');

$db = & JFactory::getDBO();
$user = & JFactory::getUser();

$pathIMG = JURI::root() . 'administrator/images/';
JPluginHelper::importPlugin("rsvppro");
$dispatcher	=& JDispatcher::getInstance();
?>

<form action="index.php" method="post" name="adminForm" id="adminForm">

	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">
		<tr>
			<th width="20" nowrap="nowrap">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->transactions); ?>);" />
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_NUMBER'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_GATEWAY'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_CURRENCY'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_AMOUNT'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_DATE'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_PAYMENTSTATE'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_TRANSACTION_DETAIL'); ?>
			</th>
			<th class="title" >
				<?php echo  JText::_('RSVP_SEND_TRANSACTION_NOTIFICATION'); ?>
			</th>
		</tr>

		<?php
				$k = 0;
				$nullDate = $db->getNullDate();

				for ($i = 0, $n = count($this->transactions); $i < $n; $i++)
				{
					$transaction = &$this->transactions[$i];
					
				?>
				<tr class="row<?php echo $k; ?>">
					<td width="20"  >
						<input type="checkbox" id="cb<?php echo $i; ?>" name="cid[]" value="<?php echo $transaction->transaction_id; ?>" onclick="isChecked(this.checked);" />
					</td>
					<td   >
						<?php
								$activePlugin = false;
								JRequest::setVar("gateway",$transaction->gateway);
								$dispatcher->trigger( 'activeGatewayClass', array(&$activePlugin));

								if ($activePlugin && class_exists($activePlugin) && method_exists($activePlugin, "editTransaction")){
									?>
									<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.edittransaction')" title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>"><?php echo $transaction->transaction_id; ?></a>
									<?php
								}
								else {
									echo $transaction->transaction_id; 
								}
								?>
					</td>
					<td   >
						<?php echo $transaction->gateway;?>
					</td>
					<td   >
						<?php echo $transaction->currency;?>
					</td>
					<td   >
						<?php echo $transaction->amount;?>
					</td>
					<td   >
						<?php
									$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
									echo strftime($format, strtotime($transaction->transaction_date));
						?>
					</td>
					<td   >
						<?php
								if (!$transaction->paymentstate)
								{
									$cimg = 'Cross.png';
								}
								else
								{
									$cimg = 'Tick.png';
								}
								$walt 	= !$transaction->paymentstate ? JText::_( 'RSVP_MARK_TRANSACTION_INVALID' ) : JText::_( 'RSVP_MARK_TRANSACTION_VALID' );
								$mainframe = JFactory::getApplication();
								if (JVersion::isCompatible("1.6.0")) {
									if ($mainframe->isAdmin()){
										$cimg = JHtml::_('image','admin/'.$cimg, $walt, NULL, true) ;
									}
									else {
										$cimg = '<img src="'.JURI::root().'plugins/jevents/jevrsvppro/rsvppro/assets/'. $cimg .'" border="0" alt="'. $walt .'" style="height:16px;border:none;" /></a>';
									}
								}
								else {
									if ($mainframe->isAdmin()){
										$cimg = '<img src="images/'. $cimg .'"  alt="'. $walt .'" style="height:16px;border:none;" /></a>';
									}
									else {
										$cimg = '<img src="'.JURI::root().'plugins/jevents/jevrsvppro/rsvppro/assets/'. $cimg .'"  alt="'. $walt .'" style="height:16px;border:none;" /></a>';
									}
								}
						
								$task 	= $transaction->paymentstate ? 'invalidtransaction' : 'validtransaction';
								$action 	= !$transaction->paymentstate ? JText::_( 'RSVP_MARK_TRANSACTION_INVALID' ) : JText::_( 'RSVP_MARK_TRANSACTION_VALID' );

								$paymentstate = '
								<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\'attendees.'.$task .'\')" title="'. $action .'">
									'. $cimg. '
								</a>'
								;
								echo $paymentstate;
						?>
						</td>
						<td>
							<?php
							if ($transaction->transaction_id>0){
								$activePlugin = false;
								JRequest::setVar("gateway",$transaction->gateway);
								$dispatcher->trigger( 'activeGatewayClass', array(&$activePlugin));

								if ($activePlugin && class_exists($activePlugin)){

									// create the plugin
									// load plugin parameters
									$pluginname =  strtolower(str_replace("plgRsvppro","",$activePlugin));
									$plugin =& JPluginHelper::getPlugin("rsvppro", $pluginname);
									$gateway = new $activePlugin($dispatcher, (array)($plugin));

									//call_user_func_array(array($gateway,"generatePaymentPage"),array(&$html, $attendee, $rsvpdata, $event, $transaction));
									echo $gateway->transactionDetailLink( $transaction, $this->rsvpdata, $this->attendee, $this->event);
								}
							}
							?>
						</td>
						<td>
							<?php 
							echo '<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\'attendees.resendtransactionnotice\')" title="'. JText::_("RSVP_SEND_TRANSACTION_NOTIFICATION") .'">'.
							JHtml::_('image','system/emailButton.png', JText::_('RSVP_SEND_TRANSACTION_NOTIFICATION'), NULL, true)
							.'</a>';
							?>
						</td>
					</tr>
<?php
					$k = 1 - $k;
				}
?>
			    </table>
			    <input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
			    <input type="hidden" name="atdee_id" value="<?php echo $this->attendee->id; ?>" />
			    <input type="hidden" name="atd_id[]" value="<?php echo $this->rsvpdata->id; ?>|<?php echo $this->rp_id; ?>" />
			    <input type="hidden" name="repeating" value="<?php echo $this->attendee->id; ?>" />
			    <input type="hidden" name="task" value="attendees.transactions" />
			    <input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid",0); ?>" />
<?php echo JHtml::_('form.token'); ?>
</form>

