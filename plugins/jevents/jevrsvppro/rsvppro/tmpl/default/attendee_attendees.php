<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$user = JFactory::getUser();
jimport('joomla.utilities.date');
// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now = $jnow->toUnix();

// list of avatars for Jomsocial (consider CB version later).
$socialhtml = "";
$html = "";
if (JVersion::isCompatible("1.6.0")){
	$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
}
else {
	$pluginpath = 'plugins/jevents/rsvppro/';
}

$hasguest = false;

if (count($this->attendees) > 0)
{
	$rp_id = intval($this->row->rp_id());
	$atd_id = intval($this->rsvpdata->id);
	$Itemid = JRequest::getInt("Itemid",1);
	$link = "index.php?option=com_rsvppro&task=attendees.listaction&at_id=$atd_id&rp_id=$rp_id&Itemid=$Itemid";

	$link = JRoute::_($link);
	
	$html = '<form action="' . $link . '"  method="post"  name="attendeelist"  enctype="multipart/form-data" >
		<input type="hidden" name="jevattendlist_id" id="jevattendlist_id" value="0" />
		<input type="hidden" name="jevattendlist_id_approve" id="jevattendlist_id_approve" value="0" />		';
	
	if ($this->jomsocial)
	{
		$html .= '<div class="cModule jevattendees"><h3><span>' . JText::_( 'JEV_ATTENDEEES' ) . '</span></h3>';
	}
	else
	{
		$html .= " <h3>" . JText::_( 'JEV_ATTENDEEES' ) . "</h3>";
	}
	$html .= "<table cellpadding='5' cellspacing='0' border='0' id='jevattendees'>";
	$showstatus = false;
	if ($this->attendees && count($this->attendees) > 0)
	{
		if ($this->params->get("allowmaybe", 0) || $this->params->get("allowpending", 0) || $this->params->get("shownonattendees", 0) || (isset($attendee->attendstate)  && $attendee->attendstate == 4))
		{
			$showstatus = true;
		}
		if ($user->id == $this->row->created_by() ||  JEVHelper::isAdminUser($user)){
			$showstatus = true;
		}
	}
	if ($this->attendees && count($this->attendees) > 0)
	{
		if ($this->jomsocial)
		{
			$socialhtml = '<div class="cModule jevattendees"><h3><span>' . JText::_( 'JEV_ATTENDEEES' ) . '</span></h3>';
			$socialhtml .= "<ul>";
		}
		$k = 1;
		foreach ($this->attendees as $attendee)
		{
			// if not to show non-attendees then skip them
			//if (!$this->params->get("shownonattendees", 0) && $attendee->attendstate==0) {
			//	continue;
			//}
			if ($attendee->guestcount > 2){
				$hasguest = true;
			}
			
			$k = 1 - $k;

			$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";
			// in this scenario we don't need the rowspan!
			if ($user->id != $this->row->created_by() && !JEVHelper::isAdminUser($user) && !$this->params->get("showcf", 0)) {
				$rowspan = "";
			}
			if (!$attendee->name)
			{
				if ($user->id==0){
					$name = $contactlink = substr($attendee->email_address,0,  strpos($attendee->email_address, "@"))."@email";
				}
				else {
					$name = $contactlink = JHtml::_('email.cloak', $attendee->email_address, 0);				
				}
			}
			else
			{
				switch ($this->params->get("userdatatype", 0)) {
					case 0:
						$name = $attendee->username;
						break;
					case 1:
						$name = $attendee->name;
						break;
					case 2:
						$name = $attendee->name . " (" . $attendee->username . ")";
						break;
				}
			}

			// New parameterised fields
			$params = false;
			if ($this->rsvpdata->template != "")
			{
				$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
				if (is_int($xmlfile)){
					$masterparams = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
				}
				if ( (is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate==0 || $attendee->lockedtemplate==$xmlfile))
				{
					if (isset($attendee->params))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $this->rsvpdata, $this->row);
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else
					{
						$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
					}

					$params = $params->renderToBasicArray('xmlfile', $attendee);

				}
				else if ($attendee->lockedtemplate>0){
					$xmlfile = $attendee->lockedtemplate;
					
					if (isset($attendee->params))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $this->rsvpdata, $this->row);
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else
					{
						$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
					}

					$params = $params->renderToBasicArray('xmlfile', $attendee);					
				}
			}			
			// Do we reset the name from the parameters!
			// This was already done in the jevrattendees library file
			/*
			if ($params){
				foreach ($params as $param)
				{
					// is this a name field?  If so then use this in preference to the user's profile name
					if (isset($param["isname"]) && $param["isname"]){
						$value = $param["value"];
						if (is_array($value) && isset($value[0]) )
						{
							if ($value[0]!=""){
								$name = $value[0];
							}											
						}
						else if (!is_array($value) && $value != "")
							$name = $value;
						}
					}
				}
			}
			 */
			
			if ($this->jomsocial)
			{
				if (!class_exists('CFactory'))
					require_once( JPATH_ROOT . '/' . 'components' . '/' . 'com_community' . '/' . 'libraries' . '/' . 'core.php');
				$jsuser = CFactory::getUser(intval($attendee->user_id));
				$avatarImgPath = $jsuser->getThumbAvatar();
				if (intval($attendee->user_id) > 0)
				{
					$link = CRoute::_('index.php?option=com_community&view=profile&userid=' . intval($attendee->user_id));
					$socialhtml .= "<li style='float:left'><a href='$link' title='" . htmlspecialchars($jsuser->getDisplayName()) . "'><img src='$avatarImgPath' alt='" . htmlspecialchars($jsuser->getDisplayName()) . "'  class='rsvpavatar' /></a></li>";
				}
				else
				{
					$socialhtml .= "<li style='float:left'><img src='$avatarImgPath' alt='" . htmlspecialchars($name) . "'  class='rsvpavatar' /></li>";
				}
			}

			if ($attendee->email_address && !$attendee->confirmed)
				$name .=" (" . JText::_( 'JEV_PENDING' ) . ")";

			if ($attendee->waiting)
			{
				$name = "<em>" . $name . " [" . JText::_( 'JEV_WAITING' ) . "]</em>";
			}

			// must be correct user type to see this list (showcf is set so we can show guests!)
			if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user) || $this->params->get("showcf", 0))
			{
				$html .="<tr class='jevattendeerow row$k'><td $rowspan><div class='jevattendee'>" . $name . "</div></td>";

				// Should we give the option to cancel?
				// not if this is not the first repeat and we have it setup as all repeats together
				// if attendance tracked for the event as a whole then must compare the time of the start of the event
				if ($this->rsvpdata->allrepeats && $now > $this->row->dtstart())
				{
					$html .='';
				}
				// otherwise the start of the repeat
				else if (!$this->rsvpdata->allrepeats && $now > $this->row->getUnixStartTime())
				{
					$html .='';
				}
				else if (JRequest::getCmd("jevtask")!="icalrepeat.detail" && JRequest::getCmd("jevtask")!="icalevent.detail") {
					$html .='';
				}
				else if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user))
				{
					$html .='<td align="center" ' . $rowspan . '><img src="' . JURI::root() . $pluginpath.'assets/Trash.png" onclick="if (confirm(\'' . JText::_("JEV_CANCEL_ATTENDEE_ARE_YOU_SURE", true) . '\')) cancelAttendance(' . $attendee->id . ');" style="height:16px;cursor:pointer" alt="cancel" /></td>';
				}
			}
			else {
				$html .="<tr class='jevattendeerow  row$k' ><td $rowspan><div class='jevattendee'>" . $name . "</div></td>\n";
			}
			// Show the status
			if ($showstatus)
			{
				$images = array("Cross.png", "Tick.png", "Question.png", "Pending.png", "MoneyBag.png");
				$img = $images[$attendee->attendstate];
				$html .='<td align="center"><img src="' . JURI::root() . $pluginpath.'assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
				// pending state allowing for approval
				if (($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user)) && $attendee->attendstate == 3)
				{
					$html .=' (<img src="' . JURI::root() . $pluginpath.'assets/Tick.png" onclick="approveAttendance(' . $attendee->id . ');" style="height:16px;cursor:pointer;" alt="' . JText::_( 'JEV_APPROVE_ATTENDANCE' ) . '" />)';
				}
				$html .='</td>';
			}

			if ($this->params->get("showtimestamp", 1))
			{
				$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
					$html .='<td align="center" ' . $rowspan . '>' . strftime($format, strtotime($attendee->created)) . '</td>';
			}

			// must be correct user type to see this list
			if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user) || $this->params->get("showcf", 0))
			{

				if ($params)
				{
					foreach ($params as $param)
					{
						if ($param['formonly'] || !$param['showinlist'])
						{
							continue;
						}
						if (is_array($param['value']) && $attendee->guestcount > 0)
						{
							if ($param['peruser'] == 2)
							{
								$val = "";
							}
							else {
								$val = $param['value'][0];									
							}
							$val = stripslashes($val);
							$html .='<td >' . $val . '</td>';
						}
						else
						{
							$html .='<td ' . $rowspan . '>' . stripslashes($param['value']) . '</td>';
						}
					}
				}
				$html .="</tr>\n";

				// Now the other param rows
				if ($attendee->guestcount > 0)
				{
					for ($a = 1; $a < $attendee->guestcount; $a++)
					{
						$html .= '<tr class="row' . $k . '" >';

						// Show the status
						if ($showstatus)
						{
							$images = array("Cross.png", "Tick.png", "Question.png", "Pending.png", "MoneyBag.png");
							$img = $images[$attendee->attendstate];
							$html .='<td align="center" ><img src="' . JURI::root() . $pluginpath.'assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
							// pending state allowing for approval
							if (($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user)) && $attendee->attendstate == 3)
							{
								$html .=' (<img src="' . JURI::root() . $pluginpath.'assets/Tick.png" onclick="approveAttendance(' . $attendee->id . ');" style="height:16px;cursor:pointer;" alt="' . JText::_( 'JEV_APPROVE_ATTENDANCE' ) . '" />)';
							}
							$html .='</td>';						
						}
						
						if ($params) 
						{
							foreach ($params as $param)
							{
								if ($param['formonly'] || !$param['showinlist'])
								{
									continue;
								}
								if ($param['label'] != "")
								{
									if (is_array($param['value']))
									{
										$val = $param['accessible'] && isset($param['value'][$a]) ? $param['value'][$a] : "";
										if ($param['peruser'] <= 0)
										{
											$val = "";
										}
										$val = stripslashes($val);
										$html .= '<td >' . $val . '</td>';
									}
								}
							}
						}
						$html .= "</tr>";
					}
				}
			}
			$html .="</tr>\n";
		}
		if ($this->jomsocial)
		{
			$socialhtml .= "</ul><div style='clear:left'></div></div>";
		}
	}
	if (count($this->attendees) > 0)
	{
		$html .= "<thead><tr valign='top'>";
		if ($this->rsvpdata->capacity > 0)
		{
			$html .='<th>' . JText::_( 'JEV_ATTENDEE' ) . ' (' . $this->attendeecount . '/' . $this->rsvpdata->capacity . ($this->rsvpdata->waitingcapacity > 0 ? '+' . $this->rsvpdata->waitingcapacity : '') . ')</th>';
		}
		else
		{
			$html .='<th>' . JText::_( 'JEV_ATTENDEE' ) . '</th>';
		}

		// Should we give the option to cancel?
		// not if this is not the first repeat and we have it setup as all repeats together
		// if attendance tracked for the event as a whole then must compare the time of the start of the event
		if ($this->rsvpdata->allrepeats && $now > $this->row->dtstart())
		{
			$html .='';
		}
		// otherwise the start of the repeat
		else if (!$this->rsvpdata->allrepeats && $now > $this->row->getUnixStartTime())
		{
			$html .='';
		}
		else if (JRequest::getCmd("jevtask")!="icalrepeat.detail" && JRequest::getCmd("jevtask")!="icalevent.detail") {
			$html .='';
		}
		else if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user))
		{
			$html .='<th>' . JText::_( 'JEV_CLICK_TO_REMOVE' ) . '</th>';
		}

		if ($showstatus)
		{
			$html .='<th>' . JText::_( 'JEV_ATTENDANCE_STATUS' ) . '</th>';
		}

		//if ($this->params->get("showtimestamp", 1)  || $this->params->get("shownonattendees", 0))
		if ($this->params->get("showtimestamp", 1) )
		{
			$html .='<th>' . JText::_( 'JEV_REGISTRATION_TIME' ) . '</th>';
		}

		// New parameterised fields
		// must be correct user type to see this list
		if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user) || $this->params->get("showcf", 0))
		{

			if ($this->rsvpdata->template != "")
			{
				$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
				if (is_int($xmlfile) || file_exists($xmlfile) )	{
					$jevparams = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
					$params = $jevparams->renderToBasicArray();
					foreach ($params as $param)
					{
						if ($param['formonly'] || !$param['showinlist'])
							continue;
						if ($param["capacity"] > 0 && isset($param["capacitycount"]))
						{
							// now get the capacity summary - this recalculates the whole thing so we know for certain the numbers
							$atd = false;
							$jevparams->calculateRowContributionsToCapacity($param, $param['type'], $atd);
							$html .='<th>' . JText::_($param['label']) . ' (' . $param["capacitycount"] . '/' . $param["capacity"] . ')</th>';
						}
						else
						{
							$html .='<th>' . JText::_($param['label']) . '</th>';
						}
					}
				}
			}
		}

	}
	if ($this->jomsocial)
	{
		$html .= "</div>";
	}
	$html .= "</tr></thead>\n";

	$html .= "</table>";

	//if ($this->params->get("sortableattendees", 0) && JRequest::getCmd("jevtask")!="icalrepeat.detail" && JRequest::getCmd("jevtask")!="icalevent.detail")
	//if ($this->params->get("sortableattendees", 0) && !$hasguest)
	if ($this->params->get("sortableattendees", 0)  && (JRequest::getCmd("jevtask")=="icalrepeat.detail" || JRequest::getCmd("jevtask")=="icalevent.detail"))
	{
		$registry	=& JRegistry::getInstance("jevents");
		if (!$registry->get("calledthis",0)) {
			JHtml::script($pluginpath. 'mootable/mootable.js');
			JHtml::stylesheet( $pluginpath.'mootable/mootable.css');
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration("window.addEvent('load', function(){if ($('jevattendees')) var mootable = new MooTable($('jevattendees'));});");
			$registry->set("calledthis",1);
		}
	}
	$html .= "</form>";
}

if ($this->jomsocial)
{
	$this->row->rsvp_socialattendees = $socialhtml;
}

echo $html;