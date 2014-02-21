<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

JHtml::_('behavior.tooltip');

$db = JFactory::getDBO();
$user = JFactory::getUser();
$params = false;
$feesAndBalances = false;
if (count($this->rows) > 0)
{

	$attendee = $this->rows[0];

	$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
	$db->setQuery($sql);
	$rsvpdata = $db->loadObject();
}
else
{
	$rsvpdata = false;
}

if (JVersion::isCompatible("1.6.0"))
{
	$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
}
else
{
	$pluginpath = 'plugins/jevents/rsvppro/';
}

$pathIMG = JURI::root() . 'administrator/images/';
?>

<form action="index.php" method="post" name="adminForm"  id="adminForm"  >
	<table cellpadding="4" cellspacing="0" border="0" >
		<tr>
			<td><?php echo JText::_('JEV_SEARCH'); ?>&nbsp;<input type="text" name="search" value="<?php echo $this->search; ?>" class="inputbox" onchange="document.adminForm.task.value='attendees.list';document.adminForm.submit();" /></td>
			<td><?php echo $this->confirmed; ?></td>
			<td><?php echo $this->waiting; ?></td>
			<td><?php echo $this->attendstate; ?></td>
		</tr>
	</table>

	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
		<tr>
			<th width="20" nowrap="nowrap">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows); ?>);" />
			</th>
			<th class="title" >
				<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDEE_NUMBER', 'atdees.id', $this->orderdir, $this->order, "attendees.list"); ?>
			</th>
			<th class="title" >
				<?php echo JHtml::_('grid.sort', 'JEV_ATTENDEE', 'attendee', $this->orderdir, $this->order, "attendees.list"); ?>
			</th>
			<th class="title" >
				<?php echo JText::_("RSVP_DELETE"); ?>
			</th>
			<th class="title" >
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
			<th class="title" >
				<?php echo JHtml::_('grid.sort', 'JEV_MODIFICATION_TIME', 'atdees.modified', $this->orderdir, $this->order, "attendees.list"); ?>
			</th>
			<th class="title" >
			<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDED', 'atdees.didattend', $this->orderdir, $this->order, "attendees.list"); ?>
			</th>
			<th class="title" >
			<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDANCENOTES', 'atdees.notes', $this->orderdir, $this->order, "attendees.list"); ?>
			</th>
			<?php
			$html = "";
			$colcount = 11;
			if (count($this->rows) > 0)
			{

				$attendee = $this->rows[0];

				$template = $rsvpdata->template;

				// Store details in registry - will need them for waiting lists!
				$registry = & JRegistry::getInstance("jevents");
				$registry->set("rsvpdata", $rsvpdata);
				$registry->set("event", $attendee->eventrepeat);

				// New parameterised fields
				$params = false;
				if ($template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
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
						$html = "";
						if ($feesAndBalances && $feesAndBalances["hasfees"])
						{
							$html .='<th class="title" >'.JText::_("RSVP_TRANSACTIONS").'</th>';
							$colcount++;
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
			echo $html;
		?>
		</tr>

		<?php
		$k = 0;
		$nullDate = $db->getNullDate();

		for ($i = 0, $n = count($this->rows); $i < $n; $i++)
		{
			$attendee = &$this->rows[$i];

			$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";

			// New parameterised fields
			$params = false;
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

			if (!$attendee->confirmed)
			{
				$cimg = 'Cross.png';
			}
			else
			{
				$cimg = 'Tick.png';
			}
			$calt = "";
			if (!$attendee->waiting)
			{
				$wimg = 'Cross.png';
			}
			else
			{
				$wimg = 'Tick.png';
			}
			$walt = "";

			$trashimg = "Trash.png";
			$talt = "";

			$wimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $wimg . '" border="0" alt="' . $walt . '" style="height:16px;border:none;" /></a>';
			$cimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $cimg . '" border="0" alt="' . $walt . '" style="height:16px;border:none;" /></a>';
			$trashimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $trashimg . '" border="0" alt="' . $talt . '" style="height:16px;border:none;" /></a>';
			$mainframe = JFactory::getApplication();
			?>
			<tr class="row<?php echo $k; ?>">
				<td width="20"  <?php echo $rowspan; ?>>
					<input type="checkbox" id="cb<?php echo $i; ?>" name="cid[]" value="<?php echo $attendee->atdee_id; ?>" onclick="isChecked(this.checked);" />
				</td>
				<td   <?php echo $rowspan; ?>>
					<?php echo $attendee->atdee_id; ?>
				</td>
				<td   <?php echo $rowspan; ?>>
					<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.edit')" title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>"><?php echo $attendee->attendee; ?></a>
				</td>
				<td   <?php echo $rowspan; ?>>
					<a href="#delete" onclick="if (confirm('<?php echo addslashes(JText::sprintf("RSVP_DELETE_ATTENDEE_NAMED", $attendee->attendee)); ?>')) return listItemTask('cb<?php echo $i; ?>','attendees.delete');else return false;" title="<?php echo JText::_('JEV_CLICK_TO_DELETE_ATTENDEE'); ?>">
					<?php echo $trashimg; ?>
					</a>
				</td>
				<td   <?php echo $rowspan; ?>>
					<?php
					if (!$attendee->confirmed)
					{
						?>
						<a href="javascript: void(0);" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.confirm')" title="<?php echo JText::_('JEV_CLICK_TO_CONFIRM', true); ?>"><?php echo $cimg; ?></a>
					<?php
					}
					else
					{
						?>
						<?php echo $cimg; ?>
					<?php } ?>
				</td>
				<td  <?php echo $rowspan; ?>>
	<?php
	$images = array("Cross.png", "Tick.png", "Question.png", "Pending.png", "MoneyBag.png", "RedMoneyBag.png");
	$img = $images[$attendee->attendstate];
	if ($attendee->attendstate == 1 && $feesAndBalances)
	{
		if ($feesAndBalances["feebalance"] < -0.000001)
		{
			$img = $images[5];
		}
	}
	// pending state allowing for approval
	if ($attendee->attendstate == 3)
	{
		?>
						<img src="<?php echo JURI::root() . $pluginpath . '/assets/Pending.png'; ?>"  alt="<?php echo JText::_("JEV_PENDING"); ?>" />
						<a href="#changestate" onclick="if (confirm('<?php echo JText::_("JEV_APPROVE_ATTENDANCE") . "?"; ?>')) return listItemTask('cb<?php echo $i; ?>','attendees.approve');else return false;" title="<?php echo JText::_('JEV_APPROVE_ATTENDANCE'); ?>">
							(<img src="<?php echo JURI::root() . $pluginpath . '/assets/Tick.png'; ?>"  alt="<?php echo JText::_("JEV_APPROVE_ATTENDANCE"); ?>" />)
						</a>
		<?php
	}
	else
	{
		?>
						<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.changestate')" title="<?php echo JText::_('RSVP_CLICK_TO_CHANGE_STATE'); ?>">
		<?php
		echo '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
		?>
						</a>
						<?php
					}
					?>
				</td>
				<td   <?php echo $rowspan; ?>>
					<?php echo $wimg; ?>
				</td>
				<td   <?php echo $rowspan; ?>>
					<?php
					$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
					echo strftime($format, strtotime($attendee->created));
					?>
				</td>
				<td   <?php echo $rowspan; ?>>
					<?php
					$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
					if ($attendee->modified != "0000-00-00 00:00:00")
					{
						echo strftime($format, strtotime($attendee->modified));
					}
					?>
				</td>
				<td   <?php echo $rowspan; ?>>
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
				<td><textarea rows="1" cols="5" name="notes[<?php echo $attendee->atdee_id; ?>]" id="attendeenotes"><?php echo $attendee->notes;?></textarea></td>
				<?php
				if ($feesAndBalances && $feesAndBalances["hasfees"])
				{
					?>
					<td   <?php echo $rowspan; ?>>
						<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','attendees.transactions')" title="<?php echo JText::_('RSVP_TRANSACTIONS'); ?>">
					<?php
					echo count($feesAndBalances["transactions"]);
					$img = "MoneyBag.png";
					echo ' <img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
					?>					
						</a>
					</td>
					<?php
				}

				$html = "";

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

