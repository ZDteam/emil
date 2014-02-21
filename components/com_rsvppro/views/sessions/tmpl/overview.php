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
$mainframe = JFactory::getApplication();
$Itemid = JRequest::getInt("Itemid");
$pathIMG = JURI::root() . 'administrator/images/';
?>

<?php
$params = JComponentHelper::getParams('com_rsvppro');
if ($params->get('show_page_title', 1)) :
	?>
	<h1>
	<?php echo $this->escape($params->get('page_title')); ?>
	</h1>
<?php endif; ?>

<form action="<?php echo JRoute::_("index.php?option=com_rsvppro&task=sessions.list&Itemid=$Itemid"); ?>" method="post" name="adminForm"  id="adminForm">
	<table cellpadding="4" cellspacing="0" border="0" >
		<tr>
			<td align="right"><?php echo $this->repeattypelist; ?> </td>
			<!--<td align="right"><?php echo $this->userlist; ?> </td>//-->
			<td align="right"><?php echo JText::_('JEV_HIDE_OLD_EVENTS'); ?> <?php echo $this->hidepast; ?> </td>
			<td><?php echo JText::_('JEV_SEARCH'); ?>&nbsp;</td>
			<td>
				<input type="text" name="search" value="<?php echo $this->search; ?>" class="inputbox" onChange="document.adminForm.submit();" />
			</td>
		</tr>
	</table>

	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">
		<tr>

			<th class="title" width="50%" >
				<?php echo JHtml::_('grid.sort', 'JEV_ICAL_SUMMARY', 'det.summary', $this->orderdir, $this->order, "sessions.list"); ?>
			</th>
			<th width="10%" >
			<?php echo JHtml::_('grid.sort', 'RSVP_STARTDATE', 'startdate', $this->orderdir, $this->order,"sessions.list"); ?>
			</th>
			<!--
			<th width="20%" >
			<?php echo JHtml::_('grid.sort', 'JEV_EVENT_CREATOR', 'ev.created_by', $this->orderdir, $this->order,"sessions.list"); ?>
			</th>
			//-->
				<?php if ($params->get("showcapacitycol", 0))
				{
					if ($params->get("waitinglist", 0))
					{ ?>
					<th width="10%" >
						<?php echo JHtml::_('grid.sort', 'RSVP_CAPACITY', 'capacity', $this->orderdir, $this->order,"sessions.list"); ?>
					</th>
				<?php }
				else
				{ ?>
					<th width="10%" >
					<?php echo JHtml::_('grid.sort', 'RSVP_JUSTCAPACITY', 'capacity', $this->orderdir, $this->order,"sessions.list"); ?>
					</th>
					<?php
					}
				}
				?>
				<?php if ($params->get("showattendeescol", 0))
				{
					if ($params->get("waitinglist", 0))
					{ ?>
					<th width="10%" >
					<?php echo JHtml::_('grid.sort', 'JEV_ATTENDEES_AND_WAITING', 'atdcount', $this->orderdir, $this->order,"sessions.list"); ?>
					</th>
			<?php }
			else
			{ ?>
					<th width="10%" >
				<?php echo JHtml::_('grid.sort', 'RSVP_JUSTATTENDEES', 'atdcount', $this->orderdir, $this->order,"sessions.list"); ?>
					</th>
			<?php
			}
		}
		?>			
					<!--
			<th width="20%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'JEV_INVITEES', 'invcount', $this->orderdir, $this->order ,"sessions.list"); ?>
			</th>
					//-->
		</tr>

<?php
$k = 0;
$nullDate = $db->getNullDate();

for ($i = 0, $n = count($this->rows); $i < $n; $i++)
{
	$row = &$this->rows[$i];
	$mainframe = JFactory::getApplication();
	$Itemid = JRequest::getInt("Itemid");
	// Joomla 1.5 doesn't always keep the Itemid in the URL if its not a JEvents menu item
	if (!JVersion::isCompatible("1.6") ){
		$Itemid = JEVHelper::getItemid($row->repeat);
	}
	$link = $row->repeat->viewDetailLink($row->repeat->yup(), $row->repeat->mup(), $row->repeat->dup(), 1, $Itemid);
	?>
			<tr class="row<?php echo $k; ?>">
				<td >
					<span class="editlinktip hasTip" title="<?php echo JText::_('JEV_view_Session'); ?>::<?php echo $this->escape($row->summary); ?>">
						<a href="<?php echo $link; ?>">
					<?php echo $this->escape($row->summary); ?></a>
					</span>
				</td>
				<td align="center">
					<?php echo strftime($params->get("listdateformat", "%Y-%m-%d"),$row->starttime); ?>
				</td>
				<!--
				<td align="center"><?php echo $row->repeat->creatorName(); ?></td>
				//-->
					<?php if ($params->get("showcapacitycol", 0))
					{ ?>
					<td align="center"><?php
						if ($row->allowregistration)
						{
							// only show attendance data if repeating type matches the attendance record
							if (($this->repeating == 1 && $row->allrepeats == 0) || ($this->repeating == 0 && $row->allrepeats == 1))
							{
								echo JText::_("RSVP_NA");
							}
							else
							{
								if ($row->capacity > 0)
								{
									echo $row->capacity;
									if ($row->waitingcapacity > 0)
										echo " ( $row->waitingcapacity )";
								}
							}
						}
						?>
					</td>
					<?php } ?>
						<?php if ($params->get("showattendeescol", 0) > 0)
						{ ?>
					<td align="center"><?php
					if ($row->allowregistration)
					{
						// only show attendance data if repeating type matches the attendance record
						if (($this->repeating == 1 && $row->allrepeats == 0) || ($this->repeating == 0 && $row->allrepeats == 1))
						{
							echo JText::_("RSVP_NA");
						}
						// anon user and not show attendees to them
						else if ($user->id == 0 && !$params->get("showtoanon",0)) {
							if ($row->atdcount>0) {
								$row->atdcount -= $row->waitingcount;
								echo $row->atdcount;
								if ($row->waitingcount>0){
									echo " + $row->waitingcount";
								}
								else if ($row->waitingcapacity>0) {
									echo " + 0";
								}
							}
							else echo " -- ";
							?>
							<img src="<?php echo JUri::root();?>/components/com_rsvppro/assets/images/Leads.png" alt='Leads' />
							<?php
						}
						else
						{
							if (!$row->showattendees && $row->repeat->created_by() != intval($user->id)  && !JEVHelper::canPublishEvent($row->repeat)) 
							{
								if ($row->atdcount>0) {
									$row->atdcount -= $row->waitingcount;
									echo $row->atdcount;
									if ($row->waitingcount>0){
										echo " + $row->waitingcount";
									}
									else if ($row->waitingcapacity>0) {
										echo " + 0";
									}
								}
								else echo " -- ";
								?>
								<img src="<?php echo JUri::root();?>/components/com_rsvppro/assets/images/Leads.png" alt='Leads' />
								<?php
							}
							else if ($row->atdcount > 0 || $user->id == $row->repeat->created_by() || JEVHelper::canPublishEvent($row->repeat))
							{
								$row->atdcount -= $row->waitingcount;
								$text = $row->atdcount;
								if ($row->waitingcount > 0)
								{
									$text .= " + $row->waitingcount";
								}
								else if ($row->waitingcapacity > 0)
								{
									$text .= " + 0";
								}

								// full list of attendees
								if (($params->get("showattendeescol", 0) == 1 && $row->showattendees) || $user->id == $row->repeat->created_by() || JEVHelper::canPublishEvent($row->repeat))
								{
									?>
										<a href="<?php echo JUri::root(); ?>index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=<?php echo $row->atd_id . "|" . $row->rp_id; ?>&repeating=<?php echo $this->repeating; ?>&Itemid=<?php echo JRequest::getInt("Itemid", 0); ?>" title="<?php echo JText::_('JEV_CLICK_FOR_LIST'); ?>">
						<?php
						echo $text;
						?>
											<img src="<?php echo JUri::root(); ?>components/com_rsvppro/assets/images/Leads.png" alt='Leads' />
										</a>
							<?php
						}
						else if ($params->get("showattendeescol", 0) == 2)
						{
							echo $text;
						}
					}
					else
						echo " -- ";
					?>
				<?php
			}
		}
		?></td>
	<?php } ?>
		<?php /* ?>
              	<td align="center"><?php if ($row->invites) {
              		// only show invites data if repeating type matches the attendance record
              		if 	(($this->repeating==1 && $row->allinvites==0) || ($this->repeating==0 && $row->allinvites==1)){
              			echo JText::_("RSVP_NA");
              		}
              		else {
              		?>
              		 <a href="index.php?option=com_rsvppro&task=invitees.overview&atd_id[]=<?php echo $row->atd_id."|".$row->rp_id; ?>&repeating=<?php echo $this->repeating;?>" title="<?php echo JText::_('JEV_CLICK_FOR_LIST'); ?>">
              		 <?php 	
						if ($row->invcount>0) echo $row->invcount; else echo " -- ";              		 ?>
              		 <img src="<?php echo JUri::root();?>/components/com_rsvppro/assets/images/Invitees.png" alt='Leads' />
              		 </a>
        			<?php
              		}

              	}?></td>
		<?php */ ?>
			</tr>
	<?php
	$k = 1 - $k;
}
?>
		<tr>
			<th align="center" colspan="9"><?php echo $this->pageNav->getListFooter(); ?></th>
		</tr>
    </table>
    <input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
    <input type="hidden" name="task" value="sessions.list" />
    <input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->order; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->orderdir; ?>" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid", 0); ?>" />
	<!-- used to make sure attendee list is unfiltered when first visited //-->
	<input type="hidden" name="filter_waiting" value="-1" />
	<input type="hidden" name="filter_confirmed" value="-1" />
<?php echo JHtml::_('form.token'); ?>
</form>

