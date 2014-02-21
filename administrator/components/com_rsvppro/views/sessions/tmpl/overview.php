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

$db	=& JFactory::getDBO();
$user =& JFactory::getUser();
$params = JComponentHelper::getParams('com_rsvppro');
?>
<form action="index.php" method="post" name="adminForm"  id="adminForm">
	<table cellpadding="4" cellspacing="0" border="0" >
		<tr>
			<td align="right"><br/><?php echo $this->repeattypelist;?> </td>
			<td align="right"><br/><?php echo $this->userlist;?> </td>
			<td align="right"><?php echo JText::_('JEV_HIDE_OLD_EVENTS');?><br/><?php echo $this->hidepast;?> </td>
			<td><?php echo JText::_('JEV_SEARCH'); ?><br/>
				<input type="text" name="search" value="<?php echo $this->search; ?>" class="inputbox" onChange="document.adminForm.submit();" />
			</td>
			<td><?php echo JText::_('RSVP_SEARCH_BY_ATTENDEE'); ?><br/>
				<input type="text" name="searchattendees" value="<?php echo $this->searchattendees; ?>" class="inputbox" onChange="document.adminForm.submit();" />
			</td>
			<td><?php echo JText::_('RSVP_SEARCH_BY_INVITEE'); ?><br/>
				<input type="text" name="searchinvitees" value="<?php echo $this->searchinvitees; ?>" class="inputbox" onChange="document.adminForm.submit();" />
			</td>
			<td valign="bottom">
				<?php echo $this->catlist;?>
			</td>
		</tr>
	</table>

	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist   table table-striped">
		<tr>
			<th width="20" nowrap="nowrap">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->rows ); ?>);" />
			</th>
			
			<th class="title" width="50%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'JEV_ICAL_SUMMARY', 'det.summary', $this->orderdir, $this->order, "sessions.list" ); ?>
			</th>
			<th width="10%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'RSVP_STARTDATE', 'startdate', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<th width="10%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'JEV_EVENT_CATEGORY', 'ev.catid', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<th width="10%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'JEV_EVENT_CREATOR', 'ev.created_by', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<?php if ($params->get("showcapacitycol",0) && $params->get("waitinglist",0)){ ?>
			<th width="10%" >
				<?php echo JHtml::_('grid.sort',  'RSVP_CAPACITY', 'capacity', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<?php } 
			else { ?>
			<th width="10%" >
				<?php echo JHtml::_('grid.sort',  'RSVP_JUSTCAPACITY', 'capacity', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<?php }
			?>
			<?php if ($params->get("showattendeescol",0)&& $params->get("waitinglist",0)){ ?>
			<th width="10%" >
				<?php echo JHtml::_('grid.sort',  'JEV_ATTENDEES_AND_WAITING', 'atdcount', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<?php } 
			else { ?>
			<th width="10%" >
				<?php echo JHtml::_('grid.sort',  'RSVP_JUSTATTENDEES', 'atdcount', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
			<?php }
			?>
			<th width="20%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'JEV_INVITEES', 'invcount', $this->orderdir, $this->order , "sessions.list"); ?>
			</th>
		</tr>

        <?php
        $k 			= 0;
        $nullDate 	= $db->getNullDate();

        for( $i=0, $n=count( $this->rows ); $i < $n; $i++ ){
        	$row = &$this->rows[$i];
        	//$editlink = $this->repeating?$this->editLink($row->repeat):$this->editRepeatLink($row->repeat);
	// always edit event for session details!
	$editlink = $this->editLink($row->repeat);
        	?>
            <tr class="row<?php echo $k; ?>">
            	<td width="20" >
                    <input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->ev_id; ?>" onclick="isChecked(this.checked);" />
                    <!--<input type="checkbox" id="at<?php echo $i;?>" name="atd_id[]" value="<?php echo $row->atd_id."|".$row->rp_id; ?>" style="display:none" />//-->
            	</td>
              	<td >
              		<a href="<?php echo $editlink;?>"  title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>" target="_blank"><?php echo $row->summary; ?></a>
              	</td>
              	<td align="center">
              	<?php echo strftime($params->get("listdateformat", "%Y-%m-%d"),$row->starttime); ?>
              	</td>
              	<td align="center"><?php echo $row->repeat->getCategoryName();?></td>
              	<td align="center"><?php echo $row->repeat->creatorName();?></td>
              	<td align="center"><?php if ($row->allowregistration) {
              		// only show attendance data if repeating type matches the attendance record
              		if 	(($this->repeating==1 && $row->allrepeats==0) || ($this->repeating==0 && $row->allrepeats==1)){
              			echo JText::_("RSVP_NA");
              		}
              		else {
              			if ($row->capacity>0) {
              				echo $row->capacity;
              				if ($row->waitingcapacity>0) echo " ( $row->waitingcapacity )";
              			}
              		}
              	}
              	?>
              	</td>
              	<td align="center"><?php if ($row->allowregistration) {
              		// only show attendance data if repeating type matches the attendance record
              		if 	(($this->repeating==1 && $row->allrepeats==0) || ($this->repeating==0 && $row->allrepeats==1)){
              			echo JText::_("RSVP_NA");
              		}
              		else {
              		?>
              		 <a href="index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=<?php echo $row->atd_id."|".$row->rp_id; ?>&repeating=<?php echo $this->repeating;?>" title="<?php echo JText::_('JEV_CLICK_FOR_LIST'); ?>">
              		 <?php 
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
              		 /*
              		 if ($row->capacity>0) {
              		 echo " (".$row->capacity;
              		 if ($row->waitingcapacity>0) echo " + $row->waitingcapacity";
              		 echo ")";
              		 }
              		 */
              		    ?>
              		 <img src="<?php echo JUri::root();?>/components/com_rsvppro/assets/images/Leads.png" alt='Leads' />
              		 </a>
        			<?php
              		}
              	}?></td>
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
            </tr>
            <?php
            $k = 1 - $k;
        } ?>
    	<tr>
    		<th align="center" colspan="9"><?php echo $this->pageNav->getListFooter(); ?></th>
    	</tr>
    </table>
    <input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT;?>" />
    <input type="hidden" name="task" value="sessions.list" />
    <input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->order; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->orderdir; ?>" />
	<!-- used to make sure attendee list is unfiltered when first visited //-->
	<input type="hidden" name="filter_waiting" value="-1" />
	<input type="hidden" name="filter_confirmed" value="-1" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid",0); ?>" />
	<?php echo JHtml::_( 'form.token' ); ?>
</form>
