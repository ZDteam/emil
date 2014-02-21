<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$user = JFactory::getUser();

$html = "";
if (isset($this->attendee->attendstate))
{
	$c = !$this->rsvpdata->allowcancellation && $this->attending && $this->attendee->attendstate == 1;
	if (!$this->rsvpdata->allowcancellation && $this->attending && $this->attendee->attendstate == 1)
	{
		$html .= "<div class='jevattendstate'>" . JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}
	else if ($this->attendee->attendstate == 1)
	{
		$html .= "<div class='jevattendstate'>" . ($this->attendee->waiting ? JText::_('JEV_YOU_ARE_ON_WAITINGLIST') : JText::_('JEV_YOU_ARE_ATTENDING')) . "<br/>";
		$html .=JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}
	else if ($this->attendee->attendstate == 0)
	{
		$html .="<div class='jevattendstate'>" . JText::_('JEV_ARE_NOT_ATTENDING') . "<br/>";
		$html .=JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}
	else if ($this->attendee->attendstate == 2)
	{
		$html .="<div class='jevattendstate'>" ;
		$html .=JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}
	else if ($this->attendee->attendstate == 4)
	{
		$html .="<div class='jevattendstate'>" . JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}

}
if (!isset($this->attendee) || !$this->attendee ){
	if (JText::_('JEV_TO_MAKE_A_RESERVATION_USE_THE_FORM_BELOW') != 'JEV_TO_MAKE_A_RESERVATION_USE_THE_FORM_BELOW'){
		$html .="<div class='jevattendstate'>" . JText::_('JEV_TO_MAKE_A_RESERVATION_USE_THE_FORM_BELOW') . "</div>";		
	}	
}


$initialstate = $this->rsvpdata->initialstate ? 1 : 3;
$attendstate = $this->params->get("defaultattendstate", -1);
if (isset($this->attendee->attendstate))
	$attendstate = $this->attendee->attendstate;
// if subject to payment then must be a yes!
if ($attendstate == 4)
{
	$attendstate = 1;
}
$this->initialstate = $initialstate;
$this->attendstate = $attendstate;

$confirm = "";
if (isset($this->attendee->attendstate) && ($this->attendee->attendstate == 1 || $this->attendee->attendstate == 4)){
	$submitmessage = JText::_("RSVP_CANCEL_WILL_REMOVE_ALL_GUESTS_TOO", true);
	if ($submitmessage!="RSVP_CANCEL_WILL_REMOVE_ALL_GUESTS_TOO"){
		$confirm = 'onsubmit="if ($(\'jevattend_no\') && $(\'jevattend_no\').checked && document.updateattendance.guestcount.value>1) {return confirm(\''.$submitmessage.'\');} else return true; "';		
	}
}
$html .= '<form action="' . $this->link . '"  method="post"  name="updateattendance"  enctype="multipart/form-data"  '.$confirm.'>';

// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) || file_exists($xmlfile))
	{
		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->row);
			$feesAndBalances = $params->outstandingBalance($this->attendee);
		}
		else
		{
			$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $this->rsvpdata);
		$registry->set("event", $this->row);

		// set the potential attendee in the params - needed for rendering
		$params->potentialAttendee = $user;

		JHtml::_('behavior.tooltip');
		if ($params->getNumParams() > 0)
		{

			if ($params->isMultiAttendee())
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<ul class="mootabs_title">';
				$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active">' . JText::_('JEV_PRIMARY_ATTENDEE') . '</li>';
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

				$html .= $this->loadTemplate("byemail");

				$html .= $params->render('params', "xmlfile");

				$capacity = $this->rsvpdata->capacity+$this->rsvpdata->waitingcapacity-$this->attendeeCount;
				
				if ($params->isMultiAttendee())
				{
					// Add new guest button
					$html .= '
			<div style="margin-top:5px;clear:left;min-height:20px;">
				<div class="button2-left"  id="addguest" >
					<div class="blank">
						<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("JEV_ADD_GUEST", true) . '" onclick="addGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_('JEV_ADD_GUEST') . '</a>
					</div>
				</div>
				<div id="killguest" >
					<div class="button2-left" >
						<div class="blank">
							<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("RSVP_REMOVE_GUEST", true) . '" onclick="removeGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_('RSVP_REMOVE_GUEST') . '</a>
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
				$html .= '<div style="clear:left;min-height:20px;">';
				$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
				$html .= $this->loadTemplate("attendanceform_updateattendbutton");
				$html .='<noscript><input type="submit" value="' . JText::_('JEV_CONFIRM') . '" /></noscript>';
				$html .= '</div>';

				$html .= '</div>';
				$html .= '</div>';
				$html .= '<script type="text/javascript">var regTabs = new mootabs("registration-tab-pane",{mouseOverClass:"active",	activateOnLoad:"tab0"	});</script>';
			}
			else
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				//$html .= '<ul class="mootabs_title">';
				//$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active">' . JText::_( 'JEV_PRIMARY_ATTENDEE' ) . '</li>';
				//$html .= '</ul>';
				$html .= '<div class="mootabs_panel active">';

				$html .= $this->loadTemplate("byemail");

				$html .= $params->render('params', "xmlfile", array());
				$html .= '<div style="clear:left;min-height:20px;">';
				$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
				$html .= $this->loadTemplate("attendanceform_updateattendbutton");
				$html .='<noscript><input type="submit" value="' . JText::_('JEV_CONFIRM') . '" /></noscript>';
				$html .= '</div>';

				$html .= '</div>';
				$html .= '</div>';
			}
		}
		else
		{
			$html .= '<div id="registration-tab-pane" class="tab-page">';
			//$html .= '<ul class="mootabs_title">';
			//$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active">' . JText::_( 'JEV_PRIMARY_ATTENDEE' ) . '</li>';
			//$html .= '</ul>';
			$html .= '<div class="mootabs_panel active">';

			$html .= $this->loadTemplate("byemail");

			$html .= '<div style="clear:left;min-height:20px;">';
			$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
			$html .= $this->loadTemplate("attendanceform_updateattendbutton");
			$html .='<noscript><input type="submit" value="' . JText::_('JEV_CONFIRM') . '" /></noscript>';
			$html .= '</div>';

			$html .= '</div>';
			$html .= '</div>';
		}
		$hasparams = true;
	}
}
else
{
	$html .= '<div id="registration-tab-pane" class="tab-page">'; //1 
	$html .= '<div class="mootabs_panel active">'; //2 

	$html .= $this->loadTemplate("byemail");

	if (isset($this->attendee) && isset($this->attendee->params))
	{
		$params = new JevRsvpParameter($this->attendee->params, null, $this->rsvpdata, $this->row);
		$feesAndBalances = $params->outstandingBalance($this->attendee);
	}
	else
	{
		$params = new JevRsvpParameter("", null, $this->rsvpdata, $this->row);
	}


	$html .= '<div style="clear:left;min-height:20px;">'; //5
	$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
	$html .= $this->loadTemplate("attendanceform_updateattendbutton");
	$html .='<noscript><input type="submit" value="' . JText::_('JEV_CONFIRM') . '" /></noscript>';
	$html .= '</div>'; // x5


	$html .= '</div>'; // x2
	$html .= '</div>'; // x1
}

// guest count
$html .='<input type="hidden" name="guestcount" id="guestcount" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';
$html .='<input type="hidden" name="lastguest" id="lastguest" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';

$html .='<input type="hidden" name="Itemid"  value="' . JRequest::getInt("Itemid", 1) . '" />';
$html .='</form>';



echo $html;

