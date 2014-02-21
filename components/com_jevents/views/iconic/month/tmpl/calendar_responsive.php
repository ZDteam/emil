<?php
defined('_JEXEC') or die('Restricted access');

$cfg = & JEVConfig::getInstance();

// Sort out the events so we only show the max display number
$maxdisplay = $cfg->get('com_calMaxDisplay', 5);
$datacount = count($this->data["dates"]);
for ($dn = 0; $dn < $datacount; $dn++)
{
	if (count($this->data["dates"][$dn]["events"]) > $maxdisplay)
	{
		$this->data["dates"][$dn]["events"] = array_slice($this->data["dates"][$dn]["events"], 0, $maxdisplay);
		$this->data["dates"][$dn]["capped"] = true;
	}
	else
	{
		$this->data["dates"][$dn]["capped"] = false;
	}
}

if ($cfg->get("tooltiptype", 'overlib') == 'overlib')
{
	JEVHelper::loadOverlib();
}

$view = $this->getViewName();
echo $this->loadTemplate('cell');
$eventCellClass = "EventCalendarCell_" . $view;

$datacount = count($this->data["dates"]);

if (!$cfg->get('ictruncpriorpost', 0))
{
	// Find the actual first day of the month
	$firstofmonth = 0;
	for ($d = 0; $d < $datacount; $d++)
	{
		if ($this->data["dates"][$d]["monthType"] == "current")
		{
			$firstofmonth = $d;
			break;
		}
	}
	// Now put copies of the first day events into the prior month
	for ($d = 0; $d < $firstofmonth; $d++)
	{
		for ($i = 0; $i < count($this->data["dates"][$firstofmonth]["events"]); $i++)
		{
			$event = $this->data["dates"][$firstofmonth]["events"][$i];

			if ($this->data["dates"][$d]["cellDate"] >= JevDate::strtotime($event->startDate()))
			{
				$this->data["dates"][$d]["events"][] = clone $event;
			}
		}
	}
}
// setup and allocate slots if necessary NB slots hold the events that occur on that date

if (!isset($this->data["dates"][0]["slots"]))
{
	for ($slot = 0; $slot < count($this->data["dates"]); $slot++)
	{
		//$this->data["dates"][$slot]["slots"]= array_fill(0,5,array(0,0));
		$this->data["dates"][$slot]["slots"] = array();
	}
}
for ($dn = 0; $dn < $datacount; $dn++)
{

	unset($currentDay);
	$currentDay = & $this->data["dates"][$dn];

	$dayOfWeek = JevDate::strftime("%w", $currentDay["cellDate"]);

	$weekstartday = $cfg->get('com_starday');
	if (!$weekstartday)
	{
		$weekstartday = 0;
	}
	// adjust day of week to reflect start day in config
	$dayOfWeek -= $weekstartday;
	if ($dayOfWeek < 0)
	{
		$dayOfWeek+=7;
	}

	// I need to sort the events by start date order (not start time on the day)
	usort($this->data["dates"][$dn]["events"], array($this, "sortjevents"));

	//JEVHelper::onDisplayCustomFieldsMultiRow($this->data["dates"][$dn]["events"]);

	for ($i = 0; $i < count($this->data["dates"][$dn]["events"]); $i++)
	{
		unset($event);
		$event = & $currentDay["events"][$i];

		// find first empty slot for this event
		// If second/third week for event the slot needs to be reset first
		// This clearly doens't apply to multiday evenyts only diusplaying on first day
		if (isset($event->slot_to_use) && $event->slot_to_use > 0 && !isset($event->slotreset) && !$event->multiday())
		{
			$old_slot_to_use = $event->slot_to_use;
			for ($spc = 0; $spc < $event->_length && $dn + $spc < count($this->data["dates"]); $spc++)
			{
				if (isset($this->data["dates"][$dn + $spc]["slots"][$old_slot_to_use]))
				{
					$this->data["dates"][$dn + $spc]["slots"][$old_slot_to_use] = array(0, 0, 0);
				}
			}
			$event->slotreset = 1;
		}

		$slot_to_use = nextEmptySlot($this->data["dates"][$dn]);
		$event->slot_to_use = $slot_to_use;

		// simplest case first - single day events
		// or multiday events set to only show once and it is the first day
		if ($event->endDate() == $event->startDate() || (!$event->multiday() && $currentDay["cellDate"] == $event->_startday))
		{
			// put the event in its slot
			$currentDay["slots"][$slot_to_use] = array($event, 1, $i);
		}

		if ($event->endDate() != $event->startDate() && $event->multiday() && !isset($event->_length))
		{
			// event started last month - if truncate is enabled then truncate these
			if ($cfg->get('ictruncpriorpost', 0) && $currentDay["d"] == 1 && $currentDay["cellDate"] > JevDate::strtotime($event->startDate()))
			{
				$event->_length = JevDate::strtotime($event->endDate()) - $currentDay["cellDate"];
				$event->_length = intval(round($event->_length / 86400, 0)) + 1;
			}
			else
			{
				$event->_length = JevDate::strtotime($event->endDate()) - JevDate::strtotime($event->startDate());
				$event->_length = intval(round($event->_length / 86400, 0)) + 1;
			}
			if ($currentDay["cellDate"] == $event->_startday || ($cfg->get('ictruncpriorpost', 0) && $currentDay["d"] == 1))
			{
				if ($dayOfWeek + $event->_length > 6)
				{
					$blocks = 7 - $dayOfWeek;
				}
				else
				{
					$blocks = $event->_length;
				}
				$this->data["dates"][$dn]["slots"][$slot_to_use] = array($event, $blocks, $i);
				for ($block = 1; $block < $blocks; $block++)
				{
					if ($cfg->get('ictruncpriorpost', 0) && $this->data["dates"][$dn + $block]["month"] != $this->month)
					{
						// RESET THE BLOCK COUNT FOR THE END OF MONTH AND BREAK
						$this->data["dates"][$dn]["slots"][$slot_to_use] = array($event, $block, $i);
						break;
					}
					$this->data["dates"][$dn + $block]["slots"][$slot_to_use] = array($event, 0, $i);
				}
			}
		}
		else
		{
			$event->_length = 1;
		}

		// For events into their second or third week then update blocks accordingly
		if ($dayOfWeek == 0 && $currentDay["cellDate"] != $event->_startday)
		{
			$event->_length = JevDate::strtotime($event->endDate()) - $currentDay["cellDate"];
			$event->_length = intval(round($event->_length / 86400, 0)) + 1;
			if ($dayOfWeek + $event->_length > 6)
			{
				$blocks = 7 - $dayOfWeek;
			}
			else
			{
				$blocks = $event->_length;
			}
			$this->data["dates"][$dn]["slots"][$slot_to_use] = array($event, $blocks, $i);
			for ($block = 1; $block < $blocks; $block++)
			{
				$this->data["dates"][$dn + $block]["slots"][$slot_to_use] = array($event, 0, $i);
			}
		}

		// mark event as shown
		$event->_shown = true;
	}
}

// determine rowspan in advance
$weekslots = array_fill(0, 6, 0);
$dn = 0;
for ($w = 0; $w < 6 && $dn < $datacount; $w++)
{
	for ($d = 0; $d < 7 && $dn < $datacount; $d++)
	{
		unset($currentDay);
		$currentDay = $this->data["dates"][$dn];
		if (count($currentDay["slots"]) > 0)
		{
			$weekslots[$w] = $weekslots[$w] < max(array_keys($currentDay["slots"])) + 1 ? max(array_keys($currentDay["slots"])) + 1 : $weekslots[$w];
		}
		$dn++;
	}
}

// previous and following month names and links
$followingMonth = $this->datamodel->getFollowingMonth($this->data);
$precedingMonth = $this->datamodel->getPrecedingMonth($this->data);

$compname = JEV_COM_COMPONENT;
$viewname = $this->getViewName();
$viewpath = JURI::root() . "components/$compname/views/" . $viewname . "/assets";
$viewimages = $viewpath . "/images";

$hiddendatearray=array();
?>
<div id='jev_maincal' class='jev_<?php echo $this->colourscheme; ?>'>
	<div class="jev_toprow">
		<div class="jev_header">
			<h2><span><?php echo JText::_('MONTHLY_VIEW'); ?></span></h2>
			<div class="today" ><span><?php echo $this->data['fieldsetText']; ?></span></div>
		</div>
		<div class="jev_header2">

			<div class="previousmonth" >
				<span>
					<?php if ($precedingMonth) echo "<a href='" . $precedingMonth["link"] . "' title='" . $precedingMonth["link"] . "' >" . $precedingMonth['name'] . "</a>"; ?>
				</span>
			</div>
			<div class="currentmonth">
				<span>
					<?php echo $this->data['fieldsetText']; ?>
				</span>
			</div>
			<div class="nextmonth">
				<span>
					<?php if ($followingMonth) echo "<a href='" . $followingMonth["link"] . "' title='" . $followingMonth["link"] . "' >" . $followingMonth['name'] . "</a>"; ?>
				</span>
			</div>

			<?php
			$dc = 0;
			foreach ($this->data["daynames"] as $dayname)
			{
				?>
				<div class="jev_daysnames jev_daysnames_<?php echo $this->colourscheme; ?> jev_<?php echo $this->colourscheme; ?> jev_daysnames<?php echo $dc; ?> ">
					<span>
						<?php echo $dayname; ?>
					</span>
				</div>
			<?php 			
			$dc++;
			}
			?>
		</div>
	</div>
	<?php
	$dn = 0;
	$dn2 = 0;
	$dn3 = 0;
	for ($w = 0; $w < 6 && $dn < $datacount; $w++)
	{
		if (!array_key_exists($w, $weekslots))
			continue;
		?>
		<div class="jev_clear jev_week jev_week<?php echo $w; ?>" >
			<div class="jev_row weekslots<?php echo $weekslots[$w]; ?>" >
				<div class="interim">			

					<div class="jevdaydata">
						<?php
						$dn2 = $dn;
						for ($d = 0; $d < 7 && $dn < $datacount; $d++)
						{
							unset($currentDay);
							$currentDay = $this->data["dates"][$dn];
							switch ($currentDay["monthType"]) {
								case "prior":
									?>
									<div  class="jev_daynum_outofmonth jev_daynum">
										<span>
											<?php echo $currentDay["d"]; ?>
										</span>
									</div>
									<?php
									break;
								case "following":
									?>
									<div  class="jev_daynum_outofmonth jev_daynum <?php echo $d == 6 ? 'jev_lastdaynum' : ''; ?>">
										<span>
											<?php echo $currentDay["d"]; ?>
										</span>
									</div>
									<?php
									break;
								case "current":
									$cellclass = $currentDay["today"] ? 'jev_daynum_today jev_daynum' : 'jev_daynum_noevents jev_daynum';
									$cellclass.= $d == 6 ? ' jev_lastdaynum' : '';
									?>
									<div <?php echo 'class="' . $cellclass . '"'; ?>  >
										<span>
											<?php $this->_datecellAddEvent($currentDay["year"], $currentDay["month"], $currentDay["d"]); ?>
											<a class="cal_daylink" href="<?php echo $currentDay["link"]; ?>" title="<?php echo JText::_('JEV_CLICK_TOSWITCH_DAY'); ?>"><?php echo $currentDay['d']; ?></a>
										</span>
									</div>
									<?php
									break;
							}
							$dn++;
						}
						?>
					</div>
					<?php
					if ($weekslots[$w]==0){
						// I need the weekslots to create the underlay so always have one at least
						$weekslots[$w]=1;
					}
					for ($slot = 0; $slot < $weekslots[$w]; $slot++)
					{
						//continue;
						?>
						<div class="jeveventrow slots<?php echo $weekslots[$w]; ?>">
							<?php
							$dn3 = $dn2;
							for ($d = 0; $d < 7 && $dn3 < $datacount; $d++)
							{
								unset($currentDay);
								$currentDay = $this->data["dates"][$dn3];
								if ($currentDay["monthType"] == "current")
								{
									$cellclass = $currentDay["today"] ? 'jev_today jevblocks1' : 'jev_daynoevents jevblocks1';
								}
								else
								{
									$cellclass = 'jev_dayoutofmonth' . ($d == 6 ? ' jev_lastday' : '');
								}
								if (array_key_exists($slot, $currentDay["slots"]))
								{
									$event = $currentDay["slots"][$slot][0];
									$blocks = $currentDay["slots"][$slot][1];
									$key = $currentDay["slots"][$slot][2];
									// reset class to include block count
									$withorwithout = $blocks > 0 ? "jev_daywithevents" : "jev_daynoevents";
									if ($currentDay["monthType"] == "current")
									{
										$cellclass = $currentDay["today"] ? 'jev_today jevblocks' . $blocks : $withorwithout . ' jevblocks' . $blocks;
									}
									else
									{
										$cellclass = 'jev_dayoutofmonth jevblocks' . $blocks . ($d == 6 ? ' jev_lastday' : '');
									}

									if ($blocks > 0)
									{
										// include custom class name to capture start date order!
										echo '<div class="' . $cellclass . ' jevstart_'.$event->getUnixStartTime().'" >';
										$datestp = JevDate::mktime(0, 0, 0,$event->mup(), $event->dup(), $event->yup());
										$day_link = $this->dateicon(explode(":", JEV_CommonFunctions::jev_strftime("%d:%b", $datestp)), JText::_('JEV_CLICK_TOSWITCH_DAY'), $currentDay['link'], "",$event);
										echo "<span class='hiddenicon'>$day_link</span>";
										
										if ($cfg->get("scalabledayname",0) &&  !in_array($datestp, $hiddendatearray) && (!isset($lastdatestp) || $datestp!=$lastdatestp)){
											$lastdatestp = $datestp;
											$hiddendatearray[]=$datestp;
										?>
										<div class="hiddendayname jev_daysnames jev_daysnames_<?php echo $this->colourscheme; ?> jev_<?php echo $this->colourscheme; ?>" style="display:none">
											<span>
											<?php 
											$weekday = JevDate::strftime("%w", $currentDay["cellDate"]);

											// adjust day of week to reflect start day in config
											$weekday -= $weekstartday;
											if ($weekday < 0)
											{
												$weekday+=7;
											}

											echo $this->data["daynames"][$weekday];
											?>
											</span>
										</div>
										<?php							
										}
										
										$ecc = new $eventCellClass($event, $this->datamodel, $this);
										echo $ecc->calendarCell($currentDay, $this->year, $this->month, $key, $slot);
										//echo $event->_summary. " ".$currentDay["slots"][$slot][1]." ".JevDate::strftime("%d",$event->_startday);
										echo '</div>';
										$currentDay['countDisplay']++;
									}
									else if (!$event)
									{
										echo "<div class='$cellclass' >&nbsp;</div>";
									}
								}
								else
								{
									echo "<div class='$cellclass' >&nbsp;</div>";
								}
								$dn3++;
							}
							?>
						</div>
						<?php
					}

					// Are any of these days capped
					$dn3 = $dn2;
					$capped = false;
					for ($d = 0; $d < 7 && $dn3 < $datacount; $d++)
					{
						unset($currentDay);
						$currentDay = $this->data["dates"][$dn3];
						if ($currentDay["capped"])
							$capped = true;
						$dn3++;
					}
					// if capped then offer the link to more events
					if ($capped)
					{
						?>
						<div class="jeveventrow slots<?php echo $weekslots[$w] + 1; ?>">
							<?php
							$dn3 = $dn2;
							for ($d = 0; $d < 7 && $dn3 < $datacount; $d++)
							{
								unset($currentDay);
								$currentDay = $this->data["dates"][$dn3];
								$withorwithout = $currentDay["capped"] ? "jev_daywithevents" : "jev_daynoevents";
								if ($currentDay["monthType"] == "current")
								{
									$cellclass = $currentDay["today"] ? 'jev_today jevblocks1' : $withorwithout . ' jevblocks1';
								}
								else
								{
									$cellclass = 'jev_dayoutofmonth' . ($d == 6 ? ' jev_lastday' : '');
								}
								if ($currentDay["capped"])
								{
									echo "<div class='$cellclass' style='text-align:right'><span style='margin-right:5px'>";
									echo '<a class="cal_daylink" href="' . $currentDay["link"] . '" title="' . JText::_('JEV_CLICK_TOSWITCH_DAY') . '">' . JText::_('MORE') . ' ...</a></span>';
									echo '</div>';
								}
								else
								{
									echo "<div class='$cellclass' >&nbsp;</div>";
								}
								$dn3++;
							}
							?>
						</div>
						<?php
					}
					
					// userlay
					?>
					<div class="jev_underlay">
						<?php
						// Are any of these days capped
						$dn4 = $dn2;
						$capped = false;
						for ($d = 0; $d < 7 && $dn4 < $datacount; $d++)
						{
							unset($currentDay);
							$currentDay = $this->data["dates"][$dn4];
							$class = "jev_underlay_daynum";
							if ($d == 0)
							{
								$class .= " jev_underlay_firstdaynum";
							}
							else if ($d == 6)
							{
								$class .= " jev_underlay_lastdaynum";
							}
							if ($currentDay["today"])
							{
								$class .= " jev_underlay_daynum_today";
							}
							switch ($currentDay["monthType"]) {
								case "prior":
									$class .= " jev_underlay_outofmonth_start";
									break;
								case "following":
									$class .= " jev_underlay_outofmonth_end";
									break;
								case "current":
									break;
							}
							?>
							<div class="<?php echo $class; ?>">
								<div>&nbsp;</div>
							</div>
							<?php
							$dn4++;
						}
						?>

					</div>			

					<div class="jev_clear"></div>
				</div>
			</div>
		</div>
		<?php
	}
	?>

</div>

<div class="jev_clear"></div>

<?php
$this->eventsLegend();

function nextEmptySlot($currentDay)
{
	if (!array_key_exists("slots", $currentDay) || count($currentDay["slots"]) == 0)
		return 0;
	$maxpossible = max(array_keys($currentDay["slots"])) + 1;
	for ($key = 0; $key <= $maxpossible; $key++)
	{

		if (!array_key_exists($key, $currentDay["slots"]) || !$currentDay["slots"][$key])
		{
			return $key;
		}
	}
	return $maxpossible;

}