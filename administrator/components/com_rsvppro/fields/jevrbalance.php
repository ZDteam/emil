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

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/rsvppro.defines.php");
include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrbalance extends JevrField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrbalance';
	const name = 'jevrbalance';

	function loadScript($field=false){
		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevrbalance.js' );

		if ($field){
			$id = 'field'.$field->field_id;
		}
		else {
			$id = '###';
		}
		ob_start();
?>
<div class='rsvpfieldinput'>

	<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE");?></div>
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRBALANCE");?><?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field);
	RsvpHelper::tooltip($id,  $field);

	$balancetype= "";
	if( $field) {
		try {
			$params = json_decode($field->params);
			$balancetype  =  isset($params->balancetype)?$params->balancetype:"";
		}
		catch (Exception $e){
		}
	}

	?>
	
	<div class="rsvplabel"><?php echo JText::_("RSVP_BALANCE_TYPE");?></div>
	<input type="hidden" name="dv[<?php echo $id; ?>]" value="0" />
	<select name="params[<?php echo $id; ?>][balancetype]" id="balancetype<?php echo $id; ?>" onclick="jevrbalance.setvalue('<?php echo $id; ?>');" >
			<option value="total" <?php if ($balancetype=='total')                       echo 'selected="selected"';?> ><?php echo JText::_("RSVP_BALANCE_TOTAL"); ?></option>
			<option value="paid" <?php if ($balancetype=='paid')                         echo 'selected="selected"';?> ><?php echo JText::_("RSVP_BALANCE_PAID"); ?></option>
			<option value="outstanding" <?php if ($balancetype=='outstanding') echo 'selected="selected"';?> ><?php echo JText::_("RSVP_BALANCE_OUTSTANDING"); ?></option>
	</select>
	<div class="rsvpclear"></div>

	<input type="hidden" name="peruser[<?php echo $id; ?>]" value="-1" />
	<?php
	RsvpHelper::formonly($id,  $field);
	RsvpHelper::showinform($id,  $field);
	RsvpHelper::showindetail($id,  $field);
	RsvpHelper::showinlist($id,  $field);
	RsvpHelper::allowoverride($id,  $field);
	RsvpHelper::accessOptions($id,  $field);
	RsvpHelper::applicableCategories("facc[$id]","facs[$id]", $id,  $field?$field->applicablecategories:"all");
	?>

	<div class="rsvpclear"></div>
	
</div>
<div class='rsvpfieldpreview'  id='<?php echo $id;?>preview'>
	<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW");?></div>
	<div class="rsvplabel rsvppl" id='pl<?php echo $id;?>' ><?php echo $field?$field->label:JText::_("RSVP_FIELD_LABEL");?></div>
	<div id="pdv<?php echo $id;?>">
		<?php echo $balancetype;?>
	</div>
</div>
<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id,  $field, $html, self::name	);

	}

	function paidOption(){
		return 1;
	}

	function getInput()
	{
		//$name, $value, &$node
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;
		
		$this->declareScript();

		// NB the value depends on the Attendee
		if (isset($this->attendee) && isset($this->attendee->outstandingBalances)){
			$this->outstandingBalances = $this->attendee->outstandingBalances;
			
			$x = $this->outstandingBalances;
			if ($this->attribute("balancetype")=="total"){
				$value = $this->outstandingBalances["totalfee"];
			}
			else if ($this->attribute("balancetype")=="outstanding"){
				$value = $this->outstandingBalances["feebalance"];
			}
			else if ($this->attribute("balancetype")=="paid"){
				$value = $this->outstandingBalances["feepaid"];
			}
		}

		$html =  "<span id='jevr$fieldname'>".RsvpHelper::phpMoneyFormat( $value)."</span><input type='hidden' name='".$name."' id='".$id ."' value='".$value."'/>";
		return $html;
	}

	public function convertValue($value){
		$this->declareScript();
		// NB the value depends on the Attendee
		if (isset($this->attendee) && isset($this->attendee->outstandingBalances)){
			if ($this->attribute("balancetype")=="total"){
				$value = $this->attendee->outstandingBalances["totalfee"];
			}
			else if ($this->attribute("balancetype")=="outstanding"){
				$value = $this->attendee->outstandingBalances["feebalance"];
			}
			else if ($this->attribute("balancetype")=="paid"){
				$value = $this->attendee->outstandingBalances["feepaid"];
			}

		}
		$html = RsvpHelper::phpMoneyFormat( $value);
		return $html;
	}

	private function declareScript(){
		RsvpHelper::jsMoneyFormat();

		static $loaded;
		if (!isset($loaded)){

			$script = $this->jsMoneyFormat();

			$document = JFactory::getDocument();
			$document->addScriptDeclaration($script);
			$loaded = true;
		}
	}

	private function jsMoneyFormat() {
		$params = JComponentHelper::getParams("com_rsvppro");

		$digits 	= $params->get("CurrencyDigits",2);
		$symbol 	= $params->get("CurrencySymbol","$");
		$onLeft 	= (strcmp($params->get("CurrencyPlacement","left"),'left') == 0);
		$separator 	= $params->get("CurrencySeparator",",");
		$decimal	= $params->get("CurrencyDecimal",".");

		$jsCode = "
			function rsvpMoneyFormat(amount) {
				// ensure numerical input
				amount = parseFloat(amount);

				// format to the correct number of digits
				// @todo prototype needs to implement toFixed() for browsers that don't support this.
				amount = amount.toFixed(".$digits.");

				// split into whole/partial for thousands separator
				var dollars = amount.split('.')[0];
				var cents	= amount.split('.')[1];

				// apply separator between every three digits
				var rgx = /(\d+)(\d{3})/;
				while (rgx.test(dollars)) {
					dollars = dollars.replace(rgx, '\$1' + '$separator' + '\$2');
				}

				";
		if ($onLeft) {
			if ( $digits > 0 ) {
				$jsCode .= "return '$symbol' + dollars + '$decimal' + cents;\n";
			} else {
				$jsCode .= "return '$symbol' + dollars;\n";
			}
		} else {
			if ( $digits > 0 ) {
				$jsCode .= "return dollars + '$decimal' + cents + '$symbol';\n";
			} else {
				$jsCode .= "return dollars + '$symbol';\n";
			}
		}
		$jsCode .= "
			}";

		return $jsCode;
	}

	function toXML($field){
		$result = array();
		if (is_string($field->params) && strpos($field->params,"{")===0){
			$field->params = json_decode($field->params);
		}
		$balancefields = array("total"=>"totalfee", "paid"=>"feepaid", "outstanding"=>"feebalance");
		$result[] = "<field ";		
		foreach (get_object_vars($field) as $k => $v){
			if ( $k=="options" || $k=="html"  || $k=="defaultvalue" || $k=="name") continue;
			if ($k=="field_id") {
				$k="name";
				$v = $balancefields[$field->params->balancetype];
			}
			if ($k=="params") {
				$k="balancetype";
				$v = $field->params->balancetype;
			}
			if ($k=="field_id") {

			}
			$result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
		}
		$result[] =  'fieldname="field'.$field->field_id.'" ';
		$result[] = " />";
		$xml =  implode(" " , $result);
		return $xml;
	}
}