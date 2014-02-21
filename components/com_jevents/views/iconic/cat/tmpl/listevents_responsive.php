<?php
defined('_JEXEC') or die('Restricted access');

$cfg = & JEVConfig::getInstance();

$data = $this->datamodel->getCatData($this->catids, $cfg->get('com_showrepeats', 0), $this->limit, $this->limitstart);
$this->data = $data;

$Itemid = JEVHelper::getItemid();
?>
<div id='jev_maincal' class='jev_listview jev_<?php echo $this->colourscheme; ?>'>
	<div class="jev_toprow">
		<div class="jev_header">
			<h2><?php echo JText::_('CATEGORY_VIEW'); ?></h2>
			<div class="today" > <?php $this->viewNavCatText($this->catids, JEV_COM_COMPONENT, 'cat.listevents', $this->Itemid); ?></div>
		</div>
	</div>
	<div  class="jev_table">
		<table class="jev_table">
			<tr>
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
				<td class="jev_cellspacer" />
			</tr>


			<tr class="jev_daysnames jev_daysnames_<?php echo $this->colourscheme; ?> jev_<?php echo $this->colourscheme; ?>">
				<td colspan="7"><?php echo $data['catname']; ?></td>
			</tr>
			<?php
			if (strlen($data['catdesc']) > 0)
			{
				?>
				<tr class='jev_catdesc'><td colspan="7"><?php echo $data['catdesc']; ?></td></tr>
				<?php
			}


			$num_events = count($data['rows']);
			$chdate = "";
			if ($num_events > 0)
			{

				for ($r = 0; $r < $num_events; $r++)
				{
					$row = $data['rows'][$r];

					$Itemid = JRequest::getInt("Itemid");
					$link = JRoute::_('index.php?option=' . JEV_COM_COMPONENT . '&task=day.listevents&year=' . $row->yup() . '&month=' . $row->mup() . '&day=' . $row->dup() . '&Itemid=' . $Itemid);

					$datestp = JevDate::mktime(0, 0, 0, $row->mup(), $row->dup(), $row->yup());
					$day_link = $this->dateicon(explode(":", JEV_CommonFunctions::jev_strftime("%d:%b", $datestp)), JText::_('JEV_CLICK_TOSWITCH_DAY'), $link, "",$row);

					$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
					if ($params->get("colourbar", 0))
						$listyle = 'style="border-color:' . $row->bgcolor() . ';"';
					else
						$listyle = 'style="border:none"';
					?>
					<tr class="jev_listrow">
						<td class="jevleft jevleft_<?php echo $this->colourscheme; ?> jev_<?php echo $this->colourscheme; ?>">
							<?php echo $day_link; ?>
						</td>
						<td  class='jevright'  colspan="6">
							<div class='jevright'  <?php echo $listyle; ?>>
								<?php
								if (!$this->loadedFromTemplate('icalevent.list_row', $row, 0))
								{
									$this->viewEventRowNew($row, 'view_detail', JEV_COM_COMPONENT, $Itemid);
								}
								?>
							</div>
						</td>
					</tr>
					<?php
				}
			}
			else
			{
				?>
				<tr class="jev_listrow  jev_noresults">
					<td  class='jevright'  colspan="7">

						<?php
						if (count($this->catids) == 0 || $data['catname'] == "")
						{
							echo JText::_('JEV_EVENT_CHOOSE_CATEG') . '';
						}
						else
						{
							echo JText::_('JEV_NO_EVENTFOR') . '&nbsp;<b>' . $data['catname'] . '</b>';
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
			<tr class='jevpagination' ><td colspan="7" align="center">
					<?php
					$this->paginationForm($data["total"], $data["limitstart"], $data["limit"]);
					?>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="jev_clear" ></div>

