<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php
JHTML::_('behavior.tooltip');
$compparams = JComponentHelper::getParams("com_jevpeople");
$Itemid = JRequest::getInt("Itemid");

$params = & JComponentHelper::getParams('com_media');
$mediabase = JURI::root() . $params->get('image_path', 'images/stories');
// folder relative to media folder
$folder = "jevents/jevpeople";
?>

<?php if ($compparams->get('show_page_heading', 1))
{
	?>
	<h1>
	<?php echo $this->escape($compparams->get('page_heading')); ?>
	</h1>
<?php }; ?>

<div class='jevpeople'>
	<form action="<?php echo JRoute::_("index.php?option=com_jevpeople&task=people.people&layout=people_blog&Itemid=$Itemid"); ?>" method="post" name="adminForm"  id="adminForm">
		<table>
			<tr>
				<td align="left" width="100%">
<?php echo JText::_('FILTER'); ?>:
					<input type="text" name="search" id="search" value="<?php echo $this->lists['search']; ?>" class="text_area" onchange="document.adminForm.submit();" />
					<button onclick="this.form.submit();"><?php echo JText::_('GO'); ?></button>
					<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_catid').value='0';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JText::_('RESET'); ?></button>
				</td>
				<td nowrap="nowrap">
					<?php
					if ($compparams->get("type", "") == "")
						echo $this->lists['typefilter'];
					if ($compparams->get("jevpcat", "") == "")
						echo $this->lists['catid'];
					?>
				</td>
			</tr>
		</table>
		<div id="editcell">
			<?php
			$params = JComponentHelper::getParams("com_jevpeople");
			$targetid = intval($params->get("targetmenu", 0));
			if ($targetid > 0)
			{
				$menu = & JSite::getMenu();
				$targetmenu = $menu->getItem($targetid);
				if ($targetmenu->component != "com_jevents")
				{
					$targetid = JEVHelper::getItemid();
				}
				else
				{
					$targetid = $targetmenu->id;
				}
			}
			else
			{
				$targetid = JEVHelper::getItemid();
			}
			$task = $params->get("jevview", "month.calendar");


			$k = 0;
			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row = &$this->items[$i];

				$tmpl = "";
				if (JRequest::getString("tmpl", "") == "component")
				{
					$tmpl = "&tmpl=component";
				}

				$link = JRoute::_('index.php?option=com_jevpeople&task=people.detail&pers_id=' . $row->pers_id . $tmpl . "&se=1" . "&title=" . JFilterOutput::stringURLSafe($row->title));

				$eventslink = JRoute::_("index.php?option=com_jevents&task=$task&peoplelkup_fv=" . $row->pers_id . "&Itemid=" . $targetid);

				// global list
				$global = $this->_globalHTML($row, $i);

				$ordering = ($this->lists['order'] == 'pers.ordering');
				?>
				<div class="jevresource-container">
					<?php
					if (!$this->loadedFromTemplate('com_jevpeople.people.' . $row->type_id . '.bloglist', $row, false, "bloglist"))
					{
						?>
						<h3 class="editlinktip hasTip" title="<?php echo JText::_('JEV_VIEW_PERSON'); ?>::<?php echo $this->escape($row->title); ?>">
							<a href="<?php echo $link; ?>"><?php echo $this->escape($row->title); ?></a>
						</h3>
						<?php if ($compparams->get('showimage', 1))
						{
							?>
							<?php
							if ($row->image != "")
							{
								$thimg = '<img class="jevresource-bloglayout-image" src="' . $mediabase . '/' . $folder . '/thumbnails/thumb_' . $row->image . '" />';
								?>
								<span class="editlinktip hasTip" title="<?php echo JText::_('JEV_VIEW_ASSOCIATED_EVENTS'); ?>::<?php echo $this->escape($row->title); ?>">
									<a href="<?php echo $link; ?>"><?php echo $thimg; ?></a>
								</span>
								<?php
							}
							?>
							<?php } ?>
						<div><?php echo JText::_('CATEGORIES') ?>:
						<?php echo $this->escape($row->catname0); ?>
						<?php if (isset($row->catname1)) echo ", " . $this->escape($row->catname1); ?>
						<?php if (isset($row->catname2)) echo ", " . $this->escape($row->catname2); ?>
						</div>
		<?php if ($row->hasEvents)
		{
			?>
							<span class="editlinktip hasTip" title="<?php echo JText::_('JEV_VIEW_ASSOCIATED_EVENTS'); ?>::<?php echo $this->escape($row->title); ?>">
								<a href="<?php echo $eventslink; ?>">
									<img src="<?php echo JURI::base(); ?>components/com_jevpeople/assets/images/jevents_event_sml.png" alt="Calendar" style="height:24px;margin:0px;"/>
								</a>
							</span>
					<?php } ?>
				<?php } ?>
				</div>
	<?php
	$k = 1 - $k;
}
?>

		</div>

		<?php
//We set the layout to people to use the same people_map template
		$this->setLayout("people");
		?>
		<?php if ($compparams->get("showmap", 0)) echo $this->loadTemplate("map"); ?>
<?php
//We restore the layout to people_blog
$this->setLayout("people_blog");
?>
		<input type="hidden" name="option" value="com_jevpeople" />
		<input type="hidden" name="Itemid" value="<?php echo $Itemid; ?>" />
		<input type="hidden" name="task" value="people.people" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
		<?php if (JRequest::getString("tmpl", "") == "component")
		{
			?>
			<input type="hidden" name="tmpl" value="component" />	
<?php } ?>
<?php echo JHTML::_('form.token'); ?>
	</form>
</div>