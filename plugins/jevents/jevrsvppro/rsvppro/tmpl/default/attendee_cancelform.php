<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );

$html = "";
$user = JFactory::getUser();

$Itemid = JRequest::getInt("Itemid");
list($year, $month, $day) = JEVHelper::getYMD();
//$link = $this->row->viewDetailLink($year,$month,$day,false, $Itemid);
$rp_id = intval($this->row->rp_id());
$atd_id = intval($this->rsvpdata->id);
$link = "index.php?option=com_rsvppro&task=attendees.record&at_id=$atd_id&rp_id=$rp_id&Itemid=$Itemid";
if (JRequest::getCmd("tmpl","")=="component"){
	$link .= "&tmpl=component";
}

// Do we need the email address security code?
if ($this->emailaddress != "")
{
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress));
	$link = $link . "&em=" . $code;
}
$link = JRoute::_($link);

$db = JFactory::getDBO();

// Until we incorporate registration deadline we stop registrations from the time the event starts
jimport('joomla.utilities.date');

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now = $jnow->toUnix();

$feesAndBalances = isset($this->attendee->outstandingBalances) ? $this->attendee->outstandingBalances : false;

if (!isset($this->templateInfo )) {
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) &&  $xmlfile>0){
		$db = JFactory::getDbo();
		$db->setQuery("Select * from #__jev_rsvp_templates where id=" . intval($xmlfile));
		$this->templateInfo = $db->loadObject();
		if ($this->templateInfo){
			$this->templateParams = $this->templateInfo->params;
			$this->templateParams = json_decode($this->templateParams);
		}
		else {
			$this->templateParams= false;
		}		
	}	
	else {
		$this->templateParams= false;
	}
}

// Tell the user they are attending only if cancellation is not allows and they are attending
if (!$this->rsvpdata->allowcancellation && $this->attending && $this->attendee->attendstate == 1)
{
	$html .= $this->loadTemplate("youareattending");
	if ($feesAndBalances)
	{
		$html = $this->loadTemplate("awaitingpayment");
	}
}
else if (isset($this->attendee->attendstate) && $this->attendee->attendstate == 2)
{
	$html = $this->loadTemplate("youmaybeattending");
}
else if (isset($this->attendee->attendstate) && $this->attendee->attendstate == 3)
{
	$html = $this->loadTemplate("awaitingconfirmation");
	if ($feesAndBalances)
	{
		$html = $this->loadTemplate("awaitingpayment");
	}
}
else if (isset($this->attendee->attendstate) && $this->attendee->attendstate == 4)
{
	$html = $this->loadTemplate("awaitingpayment");
}
else if (($this->rsvpdata->allowcancellation || $this->rsvpdata->allowchanges ) && $this->attending && ($this->attendee->attendstate == 1 || $this->attendee->attendstate == 4))
{
	if ($this->templateParams)
	{
		if (isset($this->templateParams->whentickets) && count($this->templateParams->whentickets) > 0)
		{
			$html = $this->loadTemplate("ticket");
		}
	}
}

// We see if regisrations are open
// if attendance tracked for the event as a whole then must compare the time of the start of the event
if ($this->rsvpdata->allrepeats)
{
	$regclose = $this->rsvpdata->regclose == "0000-00-00 00:00:00" ? $this->row->dtstart() : strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen == "0000-00-00 00:00:00" ? strtotime("-1 year") : strtotime($this->rsvpdata->regopen);
	if ($now > $regclose)
	{
		echo $html . $this->loadTemplate("registrationsclosed") . $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $regopen)
	{
		echo $html . $this->loadTemplate("registrationsnotopen") . $this->loadTemplate("emptyattendanceform");
		return;
	}
}
// otherwise the start of the repeat
else
{
	$regclose = $this->rsvpdata->regclose == "0000-00-00 00:00:00" ? $this->row->dtstart() : strtotime($this->rsvpdata->regclose);
	$regopen = $this->rsvpdata->regopen == "0000-00-00 00:00:00" ? strtotime("-1 year") : strtotime($this->rsvpdata->regopen);
	$eventstart = $this->row->dtstart();
	$repeatstart = $this->row->getUnixStartTime();
	$adjustedregclose = $regclose + ($repeatstart - $eventstart);
	$adjustedregopen = $regopen + ($repeatstart - $eventstart);
	if ($now > $adjustedregclose)
	{
		echo $html . $this->loadTemplate("registrationsclosed") . $this->loadTemplate("emptyattendanceform");
		return;
	}
	else if ($now < $adjustedregopen)
	{
		echo $html . $this->loadTemplate("registrationsnotopen") . $this->loadTemplate("emptyattendanceform");
		return;
	}
}

// if there is an intro to the form display it here:
if ($this->rsvpdata->attendintro != "")
{
	$html .= $this->loadTemplate("intro");
}

// if tracking capacity find how many spaces are used up/left
if ($this->params->get("capacity", 0) && $this->rsvpdata->capacity > 0)
{

	$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $this->rsvpdata->id;
	if (!$this->rsvpdata->allrepeats)
	{
		$sql .= " and a.rp_id=" . $this->row->rp_id();
	}
	$db->setQuery($sql);
	$attendeeCount = $db->loadResult();

	if ($attendeeCount >= $this->rsvpdata->capacity)
	{

		// I need the attendance form if I'm administering and attending the event otherwise I can't cancel attendees!
		if ($user->id == $this->row->created_by() || JEVHelper::isAdminUser($user) || $this->attending)
		{
			$html .= $this->loadTemplate("eventfull");
		}
		else
		{
			$html .= $this->loadTemplate("eventfull");
			if ($attendeeCount < $this->rsvpdata->capacity + $this->rsvpdata->waitingcapacity)
			{
				$html .= $this->loadTemplate("waitinglist");
			}
			else
			{
				if ($this->jomsocial && $html != "")
				{
					$html = '<div class="cModule jevattendform"><h3><span>' . JText::_('JEV_ATTEND_THIS_EVENT') . '</span></h3>' . $html . "</div>";
				}
				echo $html . $this->loadTemplate("emptyattendanceform");
				return;
			}
		}
	}
	else
	{
		$this->assign("attendeeCount", $attendeeCount);
		$html .= $this->loadTemplate("capacityremaing");
	}
}


if ($this->rsvpdata->allrepeats)
{
	$html .='
<form action="' . $link . '"  method="post"  name="updateattendance"  enctype="multipart/form-data" >';
	// if not logged in and allowing email based attendence then put in the input box
	$html .= $this->loadTemplate("byemail");


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

			$paramsarray = $params->renderToBasicArray('xmlfile', $this->attendee);
			if (count($paramsarray) > 0)
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<div class="mootabs_panel active">';

				// we have guests
				if ($this->attendee->guestcount > 0)
				{
					$html .= '<table class="attendeesummary" style="border:none;">';
					$html .= '<tr>';
					$html .= '<th />';
					$html .= '<th>' . JText::_('JEV_PRIMARY_ATTENDEE') . '</th>';
					for ($guest = 1; $guest < $this->attendee->guestcount; $guest++)
					{
						$html .= '<th>' . JText::sprintf("JEV_ATTENDEE_NUMBER", $guest + 1) . '</th>';
					}
					$html .= '</tr>';
					foreach ($paramsarray as $param)
					{
						if ($param['formonly'] || !$param['showindetail'] || !$param["accessible"])
						{
							continue;
						}
						if ($param['peruser'] == 2 && $this->attendee->guestcount <= 1)
							continue;

						// if group conditional then skip altogether
						if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
							{
								continue;
							}
						}
						// if not group conditional but no matches then skip too
						if ($param['peruser'] > 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]))
							{
								if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']))
								{
									$showany = false;
									foreach ($paramsarray[$param['conditionalfield']]['rawvalue'] as $rawvalue)
									{
										if ($rawvalue == $param['conditionalfieldvalue'])
										{
											$showany = true;
										}
									}
									if (!$showany)
									{
										continue;
									}
								}
								else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
								{
									continue;
								}
							}
						}

						$html .= "<tr>";
						$html .='<td class="rsvpoptionlabel">' . JText::_($param['label']) . ' : </td>';
						if (!isset($param['peruser']) || $param['peruser'] <= 0)
						{
							$val = $param['value'];
							if (is_array($val))
							{
								$val = implode(",", $val);
							}
							$val = stripslashes($val);
							$html .='<td class="rsvpgroupoptionvalue" colspan="' . $this->attendee->guestcount . '">' . $val . '</td>';
						}
						else
						{
							// fix non-array values
							// TODO find why this is happening on pistol site
							if (!is_array($param['value']) && $this->attendee->guestcount==1){
								$param['value'] = array($param['value']);
							}

							if (is_array($param['value']) && count($param['value']) == $this->attendee->guestcount)
							{
								for ($guest = 0; $guest < $this->attendee->guestcount; $guest++)
								{
									if ($param['peruser'] == 2 && $guest == 0)
									{
										$html .='<td class="rsvpnaoption"></td>';
									}
									else
									{
										$val = $param['value'][$guest];
										if (is_array($val))
										{
											$val = implode(",", $val);
										}
										$val = stripslashes($val);

										// should we skip this output because its a conditional field
										if (isset($param['conditionalfield']) && $param['conditionalfield'] != "" && isset($paramsarray[$param['conditionalfield']]))
										{
											if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']))
											{
												if (isset($paramsarray[$param['conditionalfield']]['rawvalue'][$guest]) && $paramsarray[$param['conditionalfield']]['rawvalue'][$guest] != $param['conditionalfieldvalue'])
												{
													$val = "";
												}
											}
											else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
											{
												$val = "";
											}
										}

										$html .='<td class="rsvpuseroptionvalue">' . $val . '</td>';
									}
								}
							}
							else
							{
								// WE HAVE A PROBLEM
								$html .='<td class="rsvpproblemvalue" colspan="' . $this->attendee->guestcount . '">' . JText::_('JEV_PROBLEM_CONTACT_ORGANISER') . '</td>';
							}
						}
						$html .= "</tr>";
					}

					$html .= '</table>';
				}
				// its just the one!
				else
				{
					foreach ($paramsarray as $param)
					{
						if ($param['formonly'])
						{
							continue;
						}
						// if group conditional then skip altogether
						if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
							{
								continue;
							}
						}

						$html .='<span class="rsvpoptionlabel">' . stripslashes(JText::_($param['label'])) . ' : </span>';
						if (is_array($param['value']))
						{
							$values = $param['value'];
							$html .='<span class="rsvpoptionvalue">' . stripslashes(implode(",", $values)) . '</span>';
						}
						else
						{
							$html .='<span class="rsvpoptionvalue">' . stripslashes($param['value']) . '</span>';
						}
						$html .= "<br/>";
					}
				}
				$html .= '</div>';
				$html .= '</div>';
			}
		}
	}


	if ($hasparams)
	{
		$this->attendstate = $attendstate;
		$cancelbutton = $this->loadTemplate("attendanceform_cancelbutton");
		if ($cancelbutton == "")
		{
			return "";
		}
		else
		{
			$html .= $cancelbutton;
		}
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
		if ($attendstate == 4)
		{
			$attendstate = 1;
		}

		$this->attendstate = $attendstate;
		$cancelbutton = $this->loadTemplate("attendanceform_cancelbutton");
		if ($cancelbutton == "")
		{
			return "";
		}
		else
		{
			$html .= $cancelbutton;
		}
		$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
		$html .='</form>';
	}
}
// or just this repeat
else if ($this->row->hasrepetition())
{
	$html.='
<form action="' . $link . '"  method="post"  name="updateattendance"  enctype="multipart/form-data" >';
	// if not logged in and allowing email based attendence then put in the input box
	$html .= $this->loadTemplate("byemail");

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
			}
			else
			{
				$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
			}

			$paramsarray = $params->renderToBasicArray('xmlfile', $this->attendee);
			if (count($paramsarray) > 0)
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<div class="mootabs_panel active">';

				// we have guests
				if ($this->attendee->guestcount > 0)
				{
					$html .= '<table class="attendeesummary" style="border:none;">';
					$html .= '<tr>';
					$html .= '<th />';
					$html .= '<th>' . JText::_('JEV_PRIMARY_ATTENDEE') . '</th>';
					for ($guest = 1; $guest < $this->attendee->guestcount; $guest++)
					{
						$html .= '<th>' . JText::sprintf("JEV_ATTENDEE_NUMBER", $guest + 1) . '</th>';
					}
					$html .= '</tr>';
					foreach ($paramsarray as $param)
					{
						if ($param['formonly'] || !$param['showindetail'] || !$param["accessible"])
						{
							continue;
						}
						if ($param['peruser'] == 2 && $this->attendee->guestcount <= 1)
							continue;

						// if group conditional then skip altogether
						if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
							{
								continue;
							}
						}
						// if not group conditional but no matches then skip too
						if ($param['peruser'] > 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]))
							{
								if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']))
								{
									$showany = false;
									foreach ($paramsarray[$param['conditionalfield']]['rawvalue'] as $rawvalue)
									{
										if ($rawvalue == $param['conditionalfieldvalue'])
										{
											$showany = true;
										}
									}
									if (!$showany)
									{
										continue;
									}
								}
								else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
								{
									continue;
								}
							}
						}

						$html .= "<tr>";
						$html .='<td class="rsvpoptionlabel">' . JText::_($param['label']) . ' : </td>';
						if (!isset($param['peruser']) || $param['peruser'] <= 0)
						{
							$html .='<td class="rsvpgroupoptionvalue" colspan="' . $this->attendee->guestcount . '">' . $param['value'] . '</td>';
						}
						else
						{
							// fix non-array values
							// TODO find why this is happening on pistol site
							if (!is_array($param['value']) && $this->attendee->guestcount==1){
								$param['value'] = array($param['value']);
							}
							if (is_array($param['value']) && count($param['value']) == $this->attendee->guestcount)
							{
								for ($guest = 0; $guest < $this->attendee->guestcount; $guest++)
								{
									if ($param['peruser'] == 2 && $guest == 0)
									{
										$html .='<td class="rsvpnaoption"></td>';
									}
									else
									{
										$val = $param['value'][$guest];
										if (is_array($val))
										{
											$val = implode(",", $val);
										}
										$val = stripslashes($val);

										// should we skip this output because its a conditional field
										if (isset($param['conditionalfield']) && $param['conditionalfield'] != "" && isset($paramsarray[$param['conditionalfield']]))
										{
											if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']))
											{
												if (isset($paramsarray[$param['conditionalfield']]['rawvalue'][$guest]) && $paramsarray[$param['conditionalfield']]['rawvalue'][$guest] != $param['conditionalfieldvalue'])
												{
													$val = "";
												}
											}
											else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
											{
												$val = "";
											}
										}

										$html .='<td class="rsvpuseroptionvalue">' . $val . '</td>';
									}
								}
							}
							else
							{
								// WE HAVE A PROBLEM
								$html .='<td class="rsvpproblemvalue" colspan="' . $this->attendee->guestcount . '">' . JText::_('JEV_PROBLEM_CONTACT_ORGANISER') . '</td>';
							}
						}
						$html .= "</tr>";
					}

					$html .= '</table>';
				}
				// its just the one!
				else
				{
					foreach ($paramsarray as $param)
					{
						if ($param['formonly'])
						{
							continue;
						}
						
						// if group conditional then skip altogether
						if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
						{
							if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
							{
								continue;
							}
						}

						
						$html .='<span class="rsvpoptionlabel">' . JText::_($param['label']) . ' : </span>';
						if (is_array($param['value']))
						{
							$values = $param['value'];
							$html .='<span class="rsvpoptionvalue">' . implode(",", $values) . '</span>';
						}
						else
						{
							$html .='<span class="rsvpoptionvalue">' . $param['value'] . '</span>';
						}
						$html .= "<br/>";
					}
				}
				$html .= '</div>';
				$html .= '</div>';
			}
		}
	}

	if ($hasparams)
	{
		$this->attendstate = $attendstate;
		$cancelbutton = $this->loadTemplate("attendanceform_cancelbutton");
		if ($cancelbutton == "")
		{
			return "";
		}
		else
		{
			$html .= $cancelbutton;
		}
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
		if ($attendstate == 4)
		{
			$attendstate = 1;
		}
		
		$this->attendstate = $attendstate;
		$cancelbutton = $this->loadTemplate("attendanceform_cancelbutton");
		if ($cancelbutton == "")
		{
			return "";
		}
		else
		{
			$html .= $cancelbutton;
		}
		$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
		$html .='</form>';
	}
}

if ($this->jomsocial && $html != "")
{
	$html = '<div class="cModule jevattendform"><h3><span>' . JText::_('JEV_ATTEND_THIS_EVENT') . '</span></h3>' . $html . "</div>";
}

echo $html;
