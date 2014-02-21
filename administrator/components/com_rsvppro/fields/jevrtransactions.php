<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrtransactions extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrtransactions';
	const name = 'jevrtransactions';

	function loadScript($field=false)
	{

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		ob_start();
?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
		<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRTRANSACTIONS"); ?><?php RsvpHelper::fieldId($id);?></div>
		<div class="rsvpclear"></div>

	<?php
		RsvpHelper::hidden($id, $field, self::name);
		RsvpHelper::label($id, $field);
		RsvpHelper::tooltip($id, $field);
	?>
		<input type="hidden" name="peruser[<?php echo $id; ?>]" value="-1" />
	<?php
		RsvpHelper::formonly($id, $field);
		RsvpHelper::showinform($id, $field);
		RsvpHelper::showindetail($id, $field);
		RsvpHelper::showinlist($id, $field);
		RsvpHelper::allowoverride($id, $field);
		RsvpHelper::accessOptions($id, $field);
		RsvpHelper::applicableCategories("facc[$id]", "facs[$id]", $id, $field ? $field->applicablecategories : "all");
	?>

		<div class="rsvpclear"></div>

	</div>
	<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
		<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
		<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>'><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
		<div id="pdv<?php echo $id; ?>">
			</div>
		</div>
		<div class="rsvpclear"></div>
<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function paidOption()
	{
		return 1;

	}

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		if (!$this->attribute("showinform"))
		{
			return "";
		}
		// NB the value depends on the Attendee
		if (isset($this->attendee) && isset($this->attendee->outstandingBalances))
		{
			JPluginHelper::importPlugin("rsvppro");

			$dispatcher = & JDispatcher::getInstance();
			$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "paymentpage"));

			$html = array();

			foreach ($this->attendee->outstandingBalances["transactions"] as $transaction)
			{
				$link = "";
				$class = "plgRsvppro" . ucfirst($transaction->gateway);
				$pluginpath = JVersion::isCompatible("1.6.0")?JPATH_SITE."/plugins/rsvppro/$transaction->gateway/" : JPATH_SITE."/plugins/rsvppro/";
				JLoader::register($class, $pluginpath . $transaction->gateway . ".php");
				$link = call_user_func_array(array($class, "transactionDetailLink"), array($transaction, $this->rsvpdata, $this->attendee, $this->event));

				$html[] = RsvpHelper::phpMoneyFormat($transaction->amount) . "  (" . $transaction->transaction_date . ") " . $link;
			}

			return implode("<br/>", $html);
		}
		return;

	}

	public function convertValue($value)
	{
		// NB the value depends on the Attendee
		if (isset($this->attendee) && isset($this->attendee->outstandingBalances))
		{
			$html = array();

			foreach ($this->attendee->outstandingBalances["transactions"] as $transaction)
			{
				$link = "";
				$class = "plgRsvppro" . ucfirst($transaction->gateway);
				$pluginpath = JVersion::isCompatible("1.6.0")?JPATH_SITE."/plugins/rsvppro/$transaction->gateway/" : JPATH_SITE."/plugins/rsvppro/";
				JLoader::register($class, $pluginpath . $transaction->gateway . ".php");
				$link = call_user_func_array(array($class, "transactionDetailLink"), array($transaction, $this->rsvpdata, $this->attendee, $this->event));

				$html[] = RsvpHelper::phpMoneyFormat($transaction->amount) . "  (" . $transaction->transaction_date . ")" . $link;
			}

			return implode("<br/>", $html);
		}
		return "";

	}

}