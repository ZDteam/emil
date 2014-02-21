<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php JHTML::_('behavior.tooltip'); ?>

<form action="index.php" method="post" name="adminForm" id="adminForm">

<div id="editcell">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
	<thead>
		<tr>
			<th width="5">
				<?php echo JText::_( 'NUM' ); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
			</th>
			<th class="title">
				<?php echo JText::_( 'TITLE' ); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JText::_( 'ID' ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = &$this->items[$i];

		$tmpl = "";
		if (JRequest::getString("tmpl","")=="component"){
			$tmpl = "&tmpl=component";
		}

		$link 	= JRoute::_( 'index.php?option=com_jevpeople&task=types.edit&cid[]='. $row->type_id . $tmpl);

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo  $i ; ?>
			</td>
			<td>
			   <?php echo JHtml::_('grid.id', $i, $row->type_id); ?>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'EDIT_PERSON_TYPE' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $link; ?>">
						<?php echo $this->escape($row->title); ?></a>
				</span>
			</td>
			<td align="center">
				<?php echo $row->type_id; ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
				}
	?>
	</tbody>
	</table>
</div>

	<input type="hidden" name="option" value="com_jevpeople" />
	<input type="hidden" name="task" value="types.overview" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php if (JRequest::getString("tmpl","")=="component"){ ?>
	<input type="hidden" name="tmpl" value="component" />	
	<?php } ?>
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
