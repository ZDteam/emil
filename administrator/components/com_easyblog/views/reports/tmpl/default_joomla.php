<?php
/**
* @package		EasyBlog
* @copyright	Copyright (C) 2010 Stack Ideas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasyBlog is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Restricted access');
?>
<script type="text/javascript">
EasyBlog(function($) {

	window.deletePost = function( id )
	{
		if( confirm('<?php echo JText::_( 'COM_EASYBLOG_CONFIRM_DELETE_POST' , true );?>' ) )
		{
			// Change the tasks
			$( '#deletePost-' + id ).submit();
			return;
		}
	}

	window.unpublishPost = function( id )
	{
		if( confirm('<?php echo JText::_( 'COM_EASYBLOG_CONFIRM_UNPUBLISH_POST' , true );?>' ) )
		{
			// Change the tasks
			$( '#unpublishPost-' + id ).submit();
			return;
		}
	}

	$.Joomla("submitbutton", function(action){
		$.Joomla("submitform", [action]);
	});
});
</script>
<form action="index.php" method="post" name="adminForm">
<div class="adminform-body">
<table class="adminlist" cellspacing="1">
<thead>
	<tr>
		<th width="1%" align="center" style="text-align: center !important;">
			<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->reports ); ?>);" />
		</th>
		<th class="title" style="text-align: left;" width="30%"><?php echo JText::_('COM_EASYBLOG_LINK'); ?></th>
		<th class="title" style="text-align: left;" width="20%"><?php echo JText::_('COM_EASYBLOG_ACTIONS'); ?></th>
		<th class="title" style="text-align: center;" width="5%"><?php echo JText::_('COM_EASYBLOG_TYPE'); ?></th>
		<th class="title" style="text-align: center;" width="10%"><?php echo JText::_('COM_EASYBLOG_IP_ADDRESS'); ?></th>
		<th class="title" style="text-align: center;" width="10%"><?php echo JText::_('COM_EASYBLOG_REPORTED_BY'); ?></th>
		<th class="title" style="text-align: center;" width="1%"><?php echo JText::_('ID'); ?></th>
	</tr>
</thead>
<tbody>
<?php if( $this->reports ){ ?>
	<?php $i = 0; ?>
	<?php foreach( $this->reports as $report ){ ?>
	<tr>
		<td style="text-align:center;"><?php echo JHTML::_('grid.id', $i++, $report->id); ?></td>
		<td>
			<div>
				<?php echo $this->getReportLink( $report->obj_id , $report->obj_type ); ?>
			</div>
			<div>
				<?php echo $this->escape( $report->reason ); ?>
			</div>
		</td>
		<td>
			<a href="javascript:void(0);" onclick="deletePost('<?php echo $report->obj_id;?>');"><?php echo JText::_( 'COM_EASYBLOG_DELETE_POST' ); ?></a> |
			<a href="javascript:void(0);" onclick="unpublishPost('<?php echo $report->obj_id;?>');"><?php echo JText::_( 'COM_EASYBLOG_UNPUBLISH_POST' ); ?></a>
		</td>
		<td style="text-align:center;">
			<?php echo $this->getType( $report->obj_type ); ?>
		</td>
		<td style="text-align:center;">
			<?php if( $report->ip ){ ?>
				<?php echo $report->ip; ?>
			<?php } else { ?>
				<?php echo JText::_( 'COM_EASYBLOG_UNAVAILABLE' ); ?>
			<?php } ?>
		</td>
		<td style="text-align:center;">
			<?php if( $report->created_by == 0 ){ ?>
			<?php echo JText::_( 'COM_EASYBLOG_GUEST' ); ?>
			<?php } else { ?>
			<a href="<?php echo JURI::root();?>administrator/index.php?option=com_easyblog&c=user&id=<?php echo $this->escape( $report->created_by );?>&task=edit" target="_blank"><?php echo $report->getAuthor()->getName();?>
			<?php } ?>
		</td>
		<td style="text-align:center;">
			<?php echo $report->id;?>
		</td>
	</tr>
	<?php } ?>
<?php } else { ?>
	<tr>
		<td colspan="7" align="center">
			<?php echo JText::_('COM_EASYBLOG_NO_REPORTS_YET');?>
		</td>
	</tr>
<?php } ?>
</tbody>
<tfoot>
	<tr>
		<td colspan="7">
			<?php echo $this->pagination->getListFooter(); ?>
		</td>
	</tr>
</tfoot>
</table>
</div>
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_easyblog" />
<input type="hidden" name="c" value="reports" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' );?>
</form>

<?php foreach( $this->reports as $report ){ ?>
<form id="deletePost-<?php echo $report->obj_id;?>" method="post">
	<input type="hidden" name="cid[]" value="<?php echo $report->obj_id;?>" />
	<input type="hidden" name="c" value="blogs" />
	<input type="hidden" name="option" value="com_easyblog" />
	<input type="hidden" name="task" value="remove" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
<form id="unpublishPost-<?php echo $report->obj_id;?>" method="post">
	<input type="hidden" name="cid[]" value="<?php echo $report->obj_id;?>" />
	<input type="hidden" name="c" value="blogs" />
	<input type="hidden" name="option" value="com_easyblog" />
	<input type="hidden" name="task" value="unpublish" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
<?php } ?>
