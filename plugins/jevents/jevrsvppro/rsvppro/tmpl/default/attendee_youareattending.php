<?php

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

if ((isset($this->canCancel) && $this->canCancel) || $this->rsvpdata->allowchanges)
	return;

echo $this->attendee->waiting ? JText::_('JEV_YOU_ARE_ON_WAITINGLIST') :  JText::_('JEV_YOU_ARE_ATTENDING') . "<br/>";

$html = "";
// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	if ($this->templateParams && !$this->attendee->waiting){
		$html .= $this->loadTemplate("ticket");
	}
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) || file_exists($xmlfile))
	{
		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->row);
		}
		else
		{
			// if we don't have an attendee we should not be here!
			return;
		}

		// This MUST be called before renderToBasicArray to populate the balance fields
		$feesAndBalances = isset($this->attendee->outstandingBalances) ? $this->attendee->outstandingBalances : false;

		$paramsarray = $params->renderToBasicArray('xmlfile', $this->attendee);
		if (count($paramsarray) > 0)
		{
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
					// Make sure balance info output is displayed correctly
					if ($param["type"]=="jevrbalance" && $feesAndBalances){
						$param["value"]=RsvpHelper::phpMoneyFormat($feesAndBalances[$param["name"]]);
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
					$html .='<td class="rsvpoptionlabel">' . stripslashes(JText::_($param['label'])) . ' : </td>';
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
										if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']) )
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


			if ($feesAndBalances)
			{
				$outstandingBalance = $feesAndBalances["feebalance"];
				if ($outstandingBalance > 0)
				{
					$Itemid = JRequest::getInt("Itemid");
					$html .= $params->paymentForm($this->attendee);
				}
				else if ($outstandingBalance < -0.01)
				{
					$html .= $params->repaymentForm($this->attendee);
				}
			}
		}
		if (isset($params->ticket) && $params->ticket != "")
		{
			$html .= $this->loadTemplate("ticket");
		}
	}
}

echo $html;

