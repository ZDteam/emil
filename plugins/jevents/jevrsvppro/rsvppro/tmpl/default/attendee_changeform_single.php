<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
JHtml::script( 'components/com_rsvppro/assets/js/tabs.js');

$user = JFactory::getUser();

$html = "";
if (isset($this->attendee->attendstate)){
	if (!$this->rsvpdata->allowcancellation && $this->attending  && $this->attendee->attendstate==1){
		$html .= "<div class='jevattendstate'>".JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";		
	}
	else if ($this->attendee->attendstate==1){
		$html .= "<div class='jevattendstate'>". ($this->attendee->waiting ? JText::_('JEV_YOU_ARE_ON_WAITINGLIST') :  JText::_('JEV_YOU_ARE_ATTENDING')) ."<br/>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
	}
	else if ($this->attendee->attendstate==0){
		$html .="<div class='jevattendstate'>".JText::_( 'JEV_ARE_NOT_ATTENDING' )."<br/>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
	}
	else if ($this->attendee->attendstate==2){
		$html .="<div class='jevattendstate'>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
	}
	else if ($this->attendee->attendstate==4){
		$html .="<div class='jevattendstate'>" . JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
	}
}

$html .= '<form action="' . $this->link . '"  method="post"  name="updateattendance"  enctype="multipart/form-data" >';

// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) || file_exists($xmlfile) )	{
		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->row);
			$feesAndBalances = $params->outstandingBalance($this->attendee);
		}
		else
		{
			$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
		}

		// set the potential attendee in the params - needed for rendering
		$params->potentialAttendee = $user;

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = & JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $this->rsvpdata);
		$registry->set("event", $this->row);

		JHtml::_('behavior.tooltip');
		if ($params->getNumParams() > 0)
		{

			$attendstate = $this->params->get("defaultattendstate", -1);
			if (isset($this->attendee->attendstate))
				$attendstate = $this->attendee->attendstate;
			$initialstate = $this->rsvpdata->initialstate ? 1 : 3;
			
			// if subject to payment or approval then must be a yes!
			if ($attendstate==4 || $attendstate==3) {
				$attendstate = 1;
			}
			
			if ($params->isMultiAttendee())
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<ul class="mootabs_title">';
				$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active">' . JText::_( 'JEV_PRIMARY_ATTENDEE' ) . '</li>';
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

				$html .=  $this->loadTemplate("byemail");
				
				$html .= $params->render('params', "xmlfile");

				$capacity = $this->rsvpdata->capacity+$this->rsvpdata->waitingcapacity-$this->attendeeCount;

				if ($params->isMultiAttendee())
				{
					// Add new guest button
					$html .= '
			<div style="margin-top:5px;clear:left;min-height:20px;">
				<div class="button2-left" id="addguest" >
					<div class="blank">
						<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("JEV_ADD_GUEST", true) . '" onclick="addGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_( 'JEV_ADD_GUEST' ) . '</a>
					</div>
				</div>
				<div id="killguest" >
					<div class="button2-left" >
						<div class="blank">
							<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("RSVP_REMOVE_GUEST", true) . '" onclick="removeGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_( 'RSVP_REMOVE_GUEST' ) . '</a>
						</div>
					</div>
			    </div>
		    </div>
			<br/>
					';
					// labels for new guest tab
					$html .= '<input type="hidden" id="jevnexttabtitle" value="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", 'xxx')) . '" />';

				}

				$this->initialstate = $initialstate;
				$this->attendstate = $attendstate;
				$attendyes =  '<input type="hidden" name="jevattend" id="jevattend_yes" value="'.$attendstate.'" />';

				// Attend this event
				$html .= $attendyes ;

				$html .= '</div>';
				$html .= '</div>';
				$html .= '<script type="text/javascript">var regTabs = new mootabs("registration-tab-pane",{mouseOverClass:"active",	activateOnLoad:"tab0"	});</script>';
			}
			else
			{
				$this->initialstate = $initialstate;
				$this->attendstate = $attendstate;
				$attendyes =  '<input type="hidden" name="jevattend" id="jevattend_yes" value="'.$attendstate.'" />';

				$html .=  $this->loadTemplate("byemail");
				
				$html .= $params->render('params', "xmlfile",array('',$attendyes));
			}
			$hasparams = true;
		}
	}
}
else
{
	$html .=  $this->loadTemplate("byemail");

	if (isset($this->attendee) && isset($this->attendee->params))
	{
		$params = new JevRsvpParameter($this->attendee->params, null, $this->rsvpdata, $this->row);
		$feesAndBalances = $params->outstandingBalance($this->attendee);
	}
	else
	{
		$params = new JevRsvpParameter("", null, $this->rsvpdata, $this->row);
	}
}

// guest count
$html .='<input type="hidden" name="guestcount" id="guestcount" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';
$html .='<input type="hidden" name="lastguest" id="lastguest" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';


if ($hasparams)
{
	$this->attendstate = $attendstate;
	$html .=  $this->loadTemplate("attendanceform_updateattendbutton");
	$html .='<noscript><input type="submit" value="' . JText::_( 'JEV_CONFIRM' ) . '" /></noscript>';
	$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
	$html .='</form>';
}
else
{
	$attendstate = $this->params->get("defaultattendstate", -1);
	if (isset($this->attendee->attendstate))
		$attendstate = $this->attendee->attendstate;
	$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

	// if subject to payment then must be a yes!
	if ($attendstate==4) {
		$attendstate = 1;
	}
	
	$this->initialstate = $initialstate;
	$this->attendstate = $attendstate;

	$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
	$this->attendstate = $attendstate;
	$html .=  $this->loadTemplate("attendanceform_updateattendbutton");
	$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
	$html .='
		<noscript><input type="submit" value="' . JText::_( 'JEV_CONFIRM' ) . '" /></noscript>
</form>';
}

echo $html;
