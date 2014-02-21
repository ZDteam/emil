<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

if ($this->attendstate <= 0)
	return "";

$html = '
	<input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	<input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	<input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
	<input type="hidden" name="jevattend" value="0" />
	<br/>';

$confirm = "if (!confirm('" . JText::_("RSVP_ARE_YOU_SURE_YOU_WANT_TO_CANCEL", true) . "')) return false;";
$params = JComponentHelper::getParams("com_rsvppro");


if ($params->get("cancellation_unpaid_only") == 1)
{
	if (!isset($this->attendee) || !isset($this->attendee->outstandingBalances) || $this->attendee->outstandingBalances["feebalance"] <= 0)
	{
		return '';
	}
}
if (isset($this->attendee) && $this->attendee !== false)
{
	$html .='
		<input type="submit" value="' . JText::_('RSVP_CANCEL_ATTENDANCE') . '" onclick="' . $this->checkemail . $confirm . '" id="jevattendsubmit" />';
}
else
{
	$style = "";
	if ($this->attendstate == -1)
	{
		$style = 'style="display:none"';
	}
	$html .='
		<input type="submit" value="' . JText::_('RSVP_CANCEL_ATTENDANCE') . '" onclick="' . $this->checkemail . $confirm . '" ' . $style . ' id="jevattendsubmit" />';
}
echo $html;
