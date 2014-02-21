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
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.tooltip');

$db = & JFactory::getDBO();
$user = & JFactory::getUser();

if (count($this->rows) > 0)
{

	$row = $this->rows[0];

	$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
	$db->setQuery($sql);
	$rsvpdata = $db->loadObject();
}
else
{
	$rsvpdata = false;
}

$eventrepeat = $this->repeat;
$feesAndBalances = false;
if (JVersion::isCompatible("1.6.0"))
{
	$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
}
else
{
	$pluginpath = 'plugins/jevents/rsvppro/';
}
$pathIMG = JURI::root() . $pluginpath . 'assets/';

if (!isset($this->templateInfo))
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) && $xmlfile > 0)
	{
		$db = JFactory::getDbo();
		$db->setQuery("Select * from #__jev_rsvp_templates where id=" . intval($xmlfile));
		$this->templateInfo = $db->loadObject();
		if ($this->templateInfo)
		{
			$this->templateParams = $this->templateInfo->params;
			$this->templateParams = json_decode($this->templateParams);
		}
		else
		{
			$this->templateParams = false;
		}
	}
	else
	{
		$this->templateParams = false;
	}
}

$params = JComponentHelper::getParams('com_rsvppro');
if ($params->get('show_page_title', 1)) :
	?>
	<h1>
	<?php echo JText::_("RSVP_ATTENDEES"); ?>
	</h1>
<?php endif; ?>
<div class="jev_eventattendees_adminlist">
	<form action="index.php" method="post" name="adminForm" id="adminForm">
		<table cellpadding="4" cellspacing="0" border="0" >
			<tr>
				<td><?php echo JText::_('JEV_SEARCH'); ?>&nbsp;<input type="text" name="search" value="<?php echo $this->search; ?>" class="inputbox" onChange="document.adminForm.submit();" /></td>
				<td><?php echo $this->confirmed; ?></td>
				<td><?php echo $this->waiting; ?></td>
				<td><?php echo $this->attendstate; ?></td>
			</tr>
		</table>

		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">
			<tr>
					<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
					{ ?>
					<th width="20" nowrap="nowrap">
						<input type="checkbox" name="toggle" value="" onclick="<?php echo JVersion::isCompatible("3.0") ? "Joomla.checkAll(this)" : "checkAll(" . count($this->rows) . ")"; ?>" />				
					</th>
<?php } ?>
				<th class="title" width="100%" >
					<?php echo JHtml::_('grid.sort', 'JEV_ATTENDEE', 'attendee', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
					<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
					{ ?>
					<th class="title" >
						<?php echo JText::_("JTOOLBAR_DELETE"); ?>
					</th>
				<?php } ?>
				<th class="title jevconfirmed">
					<?php echo JHtml::_('grid.sort', 'RSVP_CONFIRMED', 'atdees.confirmed', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
				<?php echo JHtml::_('grid.sort', 'JEV_ATTENDANCE_STATUS', 'atdees.attendstate', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
					<?php echo JHtml::_('grid.sort', 'JEV_WAITING', 'atdees.waiting', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
				<?php echo JHtml::_('grid.sort', 'JEV_REGISTRATION_TIME', 'atdees.created', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
				{ ?>
					<th class="title jevdidattend">
					<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDED', 'atdees.didattend', $this->orderdir, $this->order, "attendees.list"); ?>
					</th>
					<?php
					if ($this->templateParams && $this->templateInfo->withticket)
					{
						?>
						<th class="title jevticket">
						<?php echo JText::_("JEV_PRINT_TICKET"); ?>
						</th>
						<?php
					}
				}
				?>

				<?php
				$html = "";
				$colcount = 7;
				if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
				{
					$colcount += 2;
				}

				if (count($this->rows) > 0)
				{

					$row = $this->rows[0];
					$attendee = $this->rows[0];
					$template = $rsvpdata->template;

					// Store details in registry - will need them for waiting lists!
					$registry = & JRegistry::getInstance("jevents");
					$registry->set("rsvpdata", $rsvpdata);
					$registry->set("event", $eventrepeat);

					// must be correct user type to see custom fields
					if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat) || $this->params->get("showcf", 0))
					{
						// New parameterised fields
						$params = false;
						if ($template != "")
						{
							$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
							// New parameterised fields
							if (is_int($xmlfile))
							{
								$eventrow = clone $this->repeat;
								$masterparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							}

							if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
							{
								// transfer attendee specific information into the event row
								$eventrow = clone $this->repeat;
								if (isset($this->xmlparams[$xmlfile]))
								{
									$params = clone ($this->xmlparams[$xmlfile]);
								}
								else
								{
									$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
									$this->xmlparams[$xmlfile] = $params;
								}

								if (isset($attendee->params))
								{
									// ensure plugins like virtuemart that don't have a universal notify mechanism are up to date!
									JPluginHelper::importPlugin("rsvppro");
									$dispatcher = & JDispatcher::getInstance();
									$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $attendee, $eventrow));

									// building from scratch each time is slow! so use a cloned object!
									//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
									$params->loadData($attendee->params, $rsvpdata, $eventrow);
									$feesAndBalances = $params->outstandingBalance($attendee);
								}
								else
								{
									//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
									$feesAndBalances = false;
								}
								$params = $params->renderToBasicArray();
								foreach ($params as $param)
								{
									if ($param["capacity"] > 0 && isset($param["capacitycount"]))
									{
										$html .='<th>' . JText::_($param['label']) . ' (' . $param["capacitycount"] . '/' . $param["capacity"] . ')</th>';
										$colcount++;
									}
									else
									{
										if ($param['label'] != "" && $param["showinlist"])
										{
											$html .='<th>' . stripslashes(JText::_($param['label'])) . '</th>';
											$colcount++;
										}
									}
								}
							}
						}
					}
				}
				echo $html;
				if ($feesAndBalances && $feesAndBalances["hasfees"] && ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat)))
				{
					?>
					<th class="title jevtransactions">
				<?php echo JText::_("RSVP_TRANSACTIONS"); ?>
					</th>
				<?php
			}
			?>
			</tr>

			<?php
			$k = 0;
			$nullDate = $db->getNullDate();

			for ($i = 0, $n = count($this->rows); $i < $n; $i++)
			{
				$row = &$this->rows[$i];

				$attendee = & $row;

				if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat) || $this->params->get("showcf", 0))
				{
					// must be correct user type to see custom fields and that is where the rowspan appears from 
					$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";
				}
				else
				{
					$rowspan = 1;
				}

				// New parameterised fields
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
					{
						// transfer attendee specific information into the event row
						$eventrow = clone $this->repeat;
						if (isset($this->xmlparams[$xmlfile]))
						{
							$params = clone ($this->xmlparams[$xmlfile]);
						}
						else
						{
							$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$this->xmlparams[$xmlfile] = $params;
						}
						foreach (get_object_vars($attendee) as $key => $val)
						{
							$eventrow->$key = $val;
						}
						if (isset($attendee->params))
						{
							// building from scratch each time is slow! so use a cloned object!
							//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
							$params->loadData($attendee->params, $rsvpdata, $eventrow);
							$feesAndBalances = $params->outstandingBalance($attendee);
						}
						else
						{
							//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$feesAndBalances = false;
						}
						$params = $params->renderToBasicArray('xmlfile', $attendee);
					}
					else if ($attendee->lockedtemplate > 0)
					{
						$xmlfile = $attendee->lockedtemplate;

						// transfer attendee specific information into the event row
						$eventrow = clone $this->repeat;
						if (isset($this->xmlparams[$xmlfile]))
						{
							$params = clone ($this->xmlparams[$xmlfile]);
						}
						else
						{
							$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$this->xmlparams[$xmlfile] = $params;
						}
						foreach (get_object_vars($attendee) as $key => $val)
						{
							$eventrow->$key = $val;
						}
						if (isset($attendee->params))
						{
							// building from scratch each time is slow! so use a cloned object!
							//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
							$params->loadData($attendee->params, $rsvpdata, $eventrow);
							$feesAndBalances = $params->outstandingBalance($attendee);
						}
						else
						{
							//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$feesAndBalances = false;
						}
						$params = $params->renderToBasicArray('xmlfile', $attendee);
					}
					else
					{
						$params = false;
					}
				}

				if (!$attendee->name)
				{
					$name = $attendee->email_address;
				}
				else
				{
					switch ($this->params->get("userdatatype", 0)) {
						case 0:
							if (isset($attendee->username))
								$name = $attendee->username;
							else
								$name = $attendee->name;
							break;
						case 1:
							$name = $attendee->name;
							break;
						case 2:
							if ($attendee->username != "")
							{
								$name = $attendee->name . " (" . $attendee->username . ")";
							}
							else
							{
								$name = $attendee->name;
							}
							break;
					}
				}
				if (strpos($name, "@") > 0)
				{
					$name = JHtml::_('email.cloak', $name, 0);
				}

				if ($attendee->email_address && !$attendee->confirmed)
					$name .=" (" . JText::_("JEV_PENDING") . ")";

				if ($attendee->waiting)
				{
					$name = "<em>" . $name . " [" . JText::_("JEV_WAITING") . "]</em>";
				}

				if (!$row->confirmed)
				{
					$cimg = 'Cross.png';
					$ceimg = 'Email.png';
				}
				else
				{
					$cimg = 'Tick.png';
				}
				if (!$row->waiting)
				{
					$wimg = 'Cross.png';
				}
				else
				{
					$wimg = 'Tick.png';
				}
				?>
	            <tr class="row<?php echo $k; ?>">
						<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
						{ ?>
						<td width="20"  <?php echo $rowspan; ?>>
							<?php echo JHtml::_('grid.id', $i, $attendee->atdee_id); ?>
						</td>
						<?php } ?>
					<td  <?php echo $rowspan; ?>>
						<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
						{ ?>
							<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.edit')" title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>"><?php echo $name; ?></a>			
						<?php
						}
						else
						{
							echo $name;
						}
						?>
					</td>
	<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
	{ ?>
						<td   <?php echo $rowspan; ?>>
							<a href="#delete" onclick="if (confirm('<?php echo JText::_("RSVP_DELETE_ATTENDEE"); ?>')) return listItemTask('cb<?php echo $i; ?>','attendees.delete');else return false;" title="<?php echo JText::_('JEV_CLICK_TO_DELETE_ATTENDEE'); ?>"><img src="<?php echo $pathIMG; ?>Trash.png" width="16" height="16" border="0" alt="" /></a>
						</td>
						<?php } ?>
					<td <?php echo $rowspan; ?>>
						<?php if (($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat)) && !$row->confirmed)
						{ ?>
							<a href="#confirm" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.confirm')" title="<?php echo JText::_('JEV_CLICK_TO_CONFIRM'); ?>">
								<img src="<?php echo $pathIMG . $cimg; ?>" width="16" height="16" border="0" alt="" />
							</a>
							<a href="#redmind" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.remindconfirm')" title="<?php echo JText::_('JEV_CLICK_TO_REMIND'); ?>">
								<img src="<?php echo $pathIMG . $ceimg; ?>" width="16" height="16" border="0" alt="" />
							</a>
						<?php }
						else
						{
							?>
							<img src="<?php echo $pathIMG . $cimg; ?>" width="16" height="16" border="0" alt="" />
						<?php } ?>
					</td>
					<td <?php echo $rowspan; ?> class="jevconfirmed">
						<?php
						if (JVersion::isCompatible("1.6.0"))
						{
							$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
						}
						else
						{
							$pluginpath = 'plugins/jevents/rsvppro/';
						}
						$images = array("Cross.png", "Tick.png", "Question.png", "Pending.png", "MoneyBag.png", "RedMoneyBag.png");
						$img = $images[$row->attendstate];
						echo '<img src="' . JURI::root() . $pluginpath . 'assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
						// pending state allowing for approval
						if (($user->id == $eventrepeat->created_by() || JEVHelper::isAdminUser($user)) && $attendee->attendstate == 3)
						{
							?>
							<a href="#changestate" onclick="if (confirm('<?php echo JText::_("JEV_APPROVE_ATTENDANCE") . "?"; ?>')) return listItemTask('cb<?php echo $i; ?>','attendees.approve');else return false;" title="<?php echo JText::_('JEV_APPROVE_ATTENDANCE'); ?>">
								(<img src="<?php echo JURI::root() . $pluginpath . 'assets/Tick.png'; ?>"  alt="<?php echo JText::_("JEV_APPROVE_ATTENDANCE"); ?>" />)
							</a>
		<?php
	}
	?>
					</td>
					<td <?php echo $rowspan; ?>>
	                    <img src="<?php echo $pathIMG . $wimg; ?>" width="16" height="16" border="0" alt="" />
					</td>
					<td <?php echo $rowspan; ?>>
					<?php
					$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
					echo strftime($format, strtotime($row->created));
					?>
					</td>
					<?php if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat))
					{ ?>
						<td   <?php echo $rowspan; ?> class="jevdidattend">
						<?php
						$img = $attendee->didattend ? 'tick.png' : 'publish_x.png';
						$task = $attendee->didattend ? 'notattend' : 'attend';
						$alt = !$attendee->didattend ? JText::_('RSVP_MARK_ATTENDANCE') : JText::_('RSVP_MARK_NONATTENDANCE');
						$action = !$attendee->didattend ? JText::_('RSVP_MARK_ATTENDANCE') : JText::_('RSVP_MARK_NONATTENDANCE');

						$mainframe = JFactory::getApplication();
						if (JVersion::isCompatible("1.6.0"))
						{
							if ($mainframe->isAdmin())
							{
								$img = JHtml::_('image', 'admin/' . $img, $alt, NULL, true);
							}
							else
							{
								$img = '<img src="' . JURI::root() . 'administrator/templates/bluestork/images/admin/' . $img . '" border="0" alt="' . $alt . '" /></a>';
							}
						}
						else
						{
							if ($mainframe->isAdmin())
							{
								$img = '<img src="images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
							}
							else
							{
								$img = '<img src="' . JURI::root() . 'administrator/images/' . $img . '" border="0" alt="' . $alt . '" /></a>';
							}
						}

						$didattend = '
				<a href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'attendees.' . $task . '\')" title="' . $action . '">	' . $img . '</a>';
						echo $didattend;
						?>
						</td>
						<?php
						if ($this->templateParams && $this->templateInfo->withticket)
						{
							?>
							<td   <?php echo $rowspan; ?> class="jevtickets">
								<div class="jevtickets">
									<a href="<?php echo JRoute::_("index.php?option=com_rsvppro&tmpl=component&task=attendees.ticket&attendee=" . $attendee->id); ?>"  title="<?php echo JText::_("JEV_PRINT_TICKET"); ?>"
									class="modal" rel="{handler: 'iframe', size: {x:600, y:500}}"  >
										<img src="<?php echo JURI::root() . "/components/com_rsvppro/assets/images/ticketicon.jpg"; ?>" alt="<?php echo JText::_("JEV_PRINT_TICKET"); ?>" style='vertical-align:middle' />
									</a>
								</div>
							</td>
							<?php
						}
					}
					$html = "";

					// must be correct user type to see custom fields
					if ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat) || $this->params->get("showcf", 0))
					{

						if ($params)
						{
							foreach ($params as $param)
							{
								if ($param['label'] != "" && $param["showinlist"])
								{
									if (is_array($param['value']) && $attendee->guestcount > 0)
									{
										$val = $param['value'][0];
										$html .='<td >' . stripslashes($val) . '</td>';
									}
									else
									{
										$html .='<td ' . $rowspan . '>' . stripslashes($param['value']) . '</td>';
									}
								}
							}
						}

						echo $html;
						if ($feesAndBalances && $feesAndBalances["hasfees"] && ($user->id == $eventrepeat->created_by() || JEVHelper::canPublishEvent($eventrepeat)))
						{
							?>
							<td   <?php echo $rowspan; ?> class="jevtransactions">
								<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.transactions')" title="<?php echo JText::_('RSVP_TRANSACTIONS'); ?>">
			<?php
			echo count($feesAndBalances["transactions"]);
			$img = "MoneyBag.png";
			echo ' <img src="' . JURI::root() . $pluginpath . 'assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
			?>					
								</a>
							</td>
					<?php
				}
				?>
					</tr>
		<?php
		// Now the other param rows
		if ($attendee->guestcount > 0 && $params)
		{
			for ($a = 1; $a < $attendee->guestcount; $a++)
			{
				?>
							<tr class="row<?php echo $k; ?>">
				<?php
				foreach ($params as $param)
				{
					if ($param['label'] != "" && $param["showinlist"])
					{
						if (is_array($param['value']))
						{
							$val = $param['accessible'] && isset($param['value'][$a]) ? $param['value'][$a] : "";
							if ($param['peruser'] <= 0)
							{
								$val = "";
							}

							echo '<td >' . stripslashes($val) . '</td>';
						}
					}
				}
				?>
							</tr>
				<?php
			}
		}
		$k = 1 - $k;
	}
	else
	{
		?>
					</tr>
		<?php
	}
}
?>
			<tr>
				<th align="center" colspan="<?php echo $colcount; ?>"><?php echo $this->pageNav->getListFooter(); ?></th>
			</tr>
		</table>
		<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
		<input type="hidden" name="task" value="attendees.list" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="atd_id[]" value="<?php echo $this->atd_id . "|" . $this->rp_id; ?>" />
		<input type="hidden" name="repeating" value="<?php echo $this->repeating; ?>" />
		<input type="hidden" name="filter_order" value="<?php echo $this->order; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->orderdir; ?>" />
		<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid", 0); ?>" />
<?php echo JHtml::_('form.token'); ?>
	</form>

</div>

