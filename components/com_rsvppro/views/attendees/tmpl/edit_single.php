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

$html = "";

// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);

	if (is_int($xmlfile) || file_exists($xmlfile))
	{
		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->repeat);
			$feesAndBalances = $params->outstandingBalance($this->attendee);
		}
		else
		{
			$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->repeat);
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $this->rsvpdata);
		$registry->set("event", $this->repeat);

		JHtml::_('behavior.tooltip');
		if ($params->getNumParams() > 0)
		{

			$attendstate = -1;
			if (isset($this->attendee->attendstate))
				$attendstate = $this->attendee->attendstate;
			$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

			if ($params->isMultiAttendee())
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<ul class="mootabs_title">';
				$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active">' . JText::_("JEV_PRIMARY_ATTENDEE") . '</li>';
				$currentattenddees = $params->curentAttendeeCount();
				if ($currentattenddees > 0)
				{
					for ($ca = 1; $ca < $currentattenddees; $ca++)
					{
						$html .= '<li title="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1)) . '" class="inactive">' . JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1) . '</li>';
					}
				}
				$html .= '</ul>';
				$html .= '<div class="mootabs_panel active">';

				$html .= $params->render('params', "xmlfile");

				$capacity = $this->rsvpdata->capacity+$this->rsvpdata->waitingcapacity-$this->attendeeCount;
	
				if ($params->isMultiAttendee())
				{
					// Add new guest button
					$html .= '
			<div style="margin-top:10px;clear:left;height:20px;">
				<div class="button2-left"  id="addguest">
					<div class="blank">
						<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("JEV_ADD_GUEST", true) . '" onclick="addGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_("JEV_ADD_GUEST") . '</a>
					</div>
				</div>
				<div id="killguest" onclick="removeGuest('.$capacity.');">
					<div class="button2-left" >
						<div class="blank">
						' . JText::_("RSVP_REMOVE_GUEST") . '
						</div>
					</div>
			    </div>
		    </div>
			<br/>
					';
					// labels for new guest tab
					$html .= '<input type="hidden" id="jevnexttabtitle" value="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", 'xxx')) . '" />';
				}

				// Attend this event
				$html .= '<div style="clear:left;height:20px;">';
				$html .= '<strong><label>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</label></strong>';
				$html .= '<label for="jevattend_yes"><input type="radio" name="jevattend" id="jevattend_yes" value="' . $initialstate . '"  ' . (($attendstate == 1 || $attendstate == 4) ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
						. '<label for="jevattend_no"><input type="radio" name="jevattend" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
						. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" id="jevattend_maybe" value="2"  ' . ($attendstate == 2 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '');
				$html .= '</div>';

				$html .= '</div>';
				$html .= '</div>';
				$html .= '<script type="text/javascript">var regTabs = new mootabs("registration-tab-pane",{mouseOverClass:"active",	activateOnLoad:"tab0"	});</script>';
			}
			else
			{
				$html .= $params->render('params', "xmlfile",
								array('<label>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</label><br/>',
									'<label for="jevattend_yes"><input type="radio" name="jevattend" id="jevattend_yes" value="' . $initialstate . '"  ' . (($attendstate == 1 || $attendstate == 4) ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
									. '<label for="jevattend_no"><input type="radio" name="jevattend" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
									. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" id="jevattend_maybe" value="2"  ' . ($attendstate == 2 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '')
								)
				);
			}
			$hasparams = true;
		}
	}
}
else
{
	if (isset($this->attendee) && isset($this->attendee->params))
	{
		$params = new JevRsvpParameter($this->attendee->params, null, $this->rsvpdata, $this->repeat);
		$feesAndBalances = $params->outstandingBalance($this->attendee);
	}
	else
	{
		$params = new JevRsvpParameter("", null, $this->rsvpdata, $this->repeat);
	}
}

// guest count
$html .='<input type="hidden" name="guestcount" id="guestcount" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';
$html .='<input type="hidden" name="lastguest" id="lastguest" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';

if ($hasparams)
{
	$html .='
	    <input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	    <input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
		<br/>';
	if (isset($this->attendee) && $this->attendee !== false && ($this->attendee->user_id!=0 || $this->attendee->email_address!=""))
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_UPDATE") . '" onclick="submitbutton(\'attendees.save\');return false;" id="jevattendsubmit" />';
	}
	else
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_ATTEND") . '" onclick="submitbutton(\'attendees.save\');return false;"  id="jevattendsubmit" />';
	}
	$html .='
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>
';
}
else
{
	$attendstate = -1;
	if (isset($this->attendee->attendstate))
		$attendstate = $this->attendee->attendstate;
	$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

	$html .=
			'<strong>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</strong><br/>' .
			'<label for="jevattend_yes"><input type="radio" name="jevattend" id="jevattend_yes" value="' . $initialstate . '"  ' . ($attendstate == 1 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
			. '<label for="jevattend_no"><input type="radio" name="jevattend" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
			. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" id="jevattend_maybe" value="2" ' . ($attendstate == 2 ? "checked='checked'" : "") . '  onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '')
			. '<br/>';

	if (isset($this->attendee) && $this->attendee !== false && ($this->attendee->user_id!=0 || $this->attendee->email_address!=""))
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_UPDATE") . '" onclick="submitbutton(\'attendees.save\');return false;" id="jevattendsubmit" />';
	}
	else
	{
		$style = "";//'style="display:none" ';
		if ($attendstate>=0){
			$style='';
		}
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_SUBMIT") . '" onclick="submitbutton(\'attendees.save\');return false;" '.$style.' id="jevattendsubmit" />';
	}

	$html .='
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>'
			. '<input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	    <input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
	    <!--<input type="hidden" name="tmpl" value="component" />//-->
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>
	';
}

echo $html;