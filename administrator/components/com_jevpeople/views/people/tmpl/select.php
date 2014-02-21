<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php JHTML::_('behavior.tooltip'); ?>

<div class='jevpeople'>
	<form action="index.php" method="post" name="adminForm"  id="adminForm">
<table>
<tr>
	<td valign="bottom">
		<?php echo JText::_( 'FILTER' ); ?>:
		<input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
	</td>
	<td valign="bottom" >
		<?php
		echo $this->lists['typefilter'];
		?>
	</td>	
	<td  valign="bottom">
		<?php
		echo $this->lists['catid'];
		?>
	</td>
	<td  valign="bottom">
		<?php
		echo $this->lists['perstype'];
		?>
	</td>
</tr>
<tr>
	<td  colspan="4">
		<button onclick="this.form.submit();"><?php echo JText::_( 'GO' ); ?></button>
		<button onclick="document.getElementById('search').value='';if (this.form.getElementById('filter_catid')) {this.form.getElementById('filter_catid').value='0';};this.form.getElementById('filter_loctype').value='0';this.form.submit();"><?php echo JText::_( 'RESET' ); ?></button>
	</td>
</tr>

</table>
	<input type="hidden" name="option" value="com_jevpeople" />
	<input type="hidden" name="task" value="people.select" />
	<input type="hidden" name="returntask" value="people.select" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
<div id="editcell">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
	<thead>
		<tr>
			<th width="5">
				<?php echo JText::_( 'NUM' ); ?>
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  JText::_( 'TITLE_CLICK_TO_SELECT' ), 'pers.title', $this->lists['order_Dir'], $this->lists['order'] , "people.select"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort', JText::_( 'COUNTRY' ), 'pers.catid', $this->lists['order_Dir'], $this->lists['order'] , "people.select"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',   JText::_( 'STATE' ), 'pers.catid1', $this->lists['order_Dir'], $this->lists['order'] , "people.select"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',   JText::_( 'CITY' ), 'pers.catid2', $this->lists['order_Dir'], $this->lists['order'] , "people.select"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',  JText::_( 'POSTCODE' ), 'pers.postcode', $this->lists['order_Dir'], $this->lists['order'] , "people.select"); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="9">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = &$this->items[$i];

		$link 	= JRoute::_( 'index.php?option=com_jevpeople&task=people.setperson&cid[]='. $row->pers_id );

		$checked 	= JHTML::_('grid.checkedout',   $row, $i ,"pers_id");
		$checked = str_replace('"images/' ,'"../administrator/images/',$checked);
		$published 	= $row->published?'/administrator/images/tick.png': '/administrator/images/publish_x.png';

		// global list
		$global	= $this->_globalHTML($row,$i);

		$ordering = ($this->lists['order'] == 'pers.ordering');

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $this->pagination->getRowOffset( $i ); ?>
			</td>
			<td>
				<?php
				if (  JTable::isCheckedOut($this->user->get ('id'), $row->checked_out ) ) {
					echo $this->escape($row->title);
				} else {
				?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'SELECT_PERSON' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="javascript:void()" onclick="return window.parent.sortablePeople.selectThisPerson(<?php echo $row->pers_id;?>,this,'<?php echo $this->escape($row->typename);?>');"><?php echo $this->escape($row->title); ?></a></span>
				<?php
				}
				?>
			</td>
			<td>
				<?php echo $this->escape($row->country); ?>
			</td>
			<td>
				<?php echo $this->escape($row->state); ?>
			</td>
			<td>
				<?php echo $this->escape($row->city); ?>
			</td>
			<td>
				<?php echo $this->escape($row->postcode); ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	if (count($this->items)==0){
		?>
		<tr>
			<td colspan="9"></td>
		</tr>
		<?php
	}
	?>
	</tbody>
	</table>
</div>

</form>
</div>
