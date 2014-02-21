<?php defined('_JEXEC') or die('Restricted Access'); ?>

<?php JHtml::_('behavior.tooltip'); ?>
<div class='jevrsvppro'>

<form action="index.php" method="post" name="adminForm" id="adminForm">
<table>
<tr>
	<td align="left" width="100%">
		<?php echo JText::_( 'Filter' ); ?>:
		<input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
		<button onclick="this.form.submit();"><?php echo JText::_( 'Go' ); ?></button>
		<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_catid').value='0';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JText::_( 'Reset' ); ?></button>
	</td>
	<td nowrap="nowrap">
		<?php echo JText::_( 'JEV_SHOW_MY_CUSTOMISED_TEMPLATES' ); ?>:
		<span class="radio btn-group">
			<label for="customised_1" class="btn <?php echo $this->lists['customised']?"active":""; ?>"><?php echo JText::_("RSVP_YES");?>
			<input type="radio" name="customised" id="customised_1" value="1" <?php echo $this->lists['customised']?"checked='checked'":"";?> onclick="document.adminForm.submit();" />
			</label>
			<label for="customised_0" class="btn <?php echo $this->lists['customised']?"":"active"; ?>"><?php echo JText::_("RSVP_NO");?>
			<input type="radio" name="customised" id="customised_0" value="0" <?php echo $this->lists['customised']?"":"checked='checked'";?> onclick="document.adminForm.submit();" />
			</label>
		</span>
		<?php
		echo $this->lists['state'];
		?>
	</td>
</tr>
</table>
<div id="editcell">
	<table class="adminlist  table table-striped">
	<thead>
		<tr>
			<th width="5">
				<?php echo JText::_('RSVP_NUM'); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="<?php echo JVersion::isCompatible("3.0") ? "Joomla.checkAll(this)" : "checkAll(" . count($this->items) . ")"; ?>" />
			</th>
			<th class="title">
				<?php echo JHtml::_('grid.sort',  'RSVP_TEMPLATE_TITLE', 'tmpl.title', $this->lists['order_Dir'], $this->lists['order'] ,"templates.list"); ?>
			</th>
			<th width="5%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'RSVP_GLOBAL_TEMPLATE', 'tmpl.global', $this->lists['order_Dir'], $this->lists['order'] ,"templates.list"); ?>
			</th>
			<th width="5%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'RSVP_IS_TEMPLATE', 'tmpl.istemplate', $this->lists['order_Dir'], $this->lists['order'] ,"templates.list"); ?>
			</th>
			<th width="5%" nowrap="nowrap">
				<?php echo JHtml::_('grid.sort',  'RSVP_IS_LOCKED', 'tmpl.locked', $this->lists['order_Dir'], $this->lists['order'] ,"templates.list"); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JText::_('ID'); ?>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = &$this->items[$i];

		$link 	= JRoute::_( 'index.php?option='.RSVP_COM_COMPONENT.'&task=templates.edit&cid[]='. $row->id );

		// global list
		$global	= $this->_globalHTML($row,$i);

		// locked template
		$locked	= $this->_lockedHTML($row,$i);
		
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $i+1; ?>
			</td>
			<td>
				<?php echo JHtml::_('grid.id', $i, $row->id); ?>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'Edit Session' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $link; ?>">
					<?php echo $this->escape($row->title); ?></a>
				</span>
			</td>
			<td align="center">
				<?php echo $global;?>
			</td>
			<td align="center">
				<?php echo $this->isTemplateHTML($row,$i);;?>
			</td>					
			<td align="center">
				<?php echo $locked;?>
			</td>			
			<td align="center">
				<?php echo $row->id; ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
    	<tr>
    		<th align="center" colspan="9"><?php echo $this->pageNav->getListFooter(); ?></th>
    	</tr>
	</tbody>
	</table>
</div>

	<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT;?>" />
	<input type="hidden" name="task" value="templates.list" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid",0); ?>" />
	<?php echo JHtml::_( 'form.token' ); ?>
</form>
</div>

<script type="text/javascript" >
	window.setTimeout("setupRSVPTemplateBootstrap()", 500);

	function setupRSVPTemplateBootstrap(){
		(function($){
			// Turn radios into btn-group
			$('.radio.btn-group label').addClass('btn');
			var el = $(".radio.btn-group label:not(.active)");
			
			// Isis template and others may already have done this so remove these!
			$(".radio.btn-group label:not(.active)").unbind('click');
			
			$(".radio.btn-group label:not(.active)").click(function() {
				var label = $(this);
				var input = $('#' + label.attr('for'));
				if (!input.prop('checked') && !input.prop('disabled')) {
					label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
					label.addClass('active btn-success');
					input.prop('checked', true);
				}
			});

			// Turn checkboxes into btn-group
			$('.checkbox.btn-group label').addClass('btn');
			
			// Isis template and others may already have done this so remove these!
			$(".checkbox.btn-group label").unbind('click');
			
			$(".checkbox.btn-group label").click(function(event) {
				event || (event = window.event);
				var label = $(this);
				var input = $('#' + label.attr('for'));
				//alert(label.val()+ " checked? "+input.prop('checked')+ " disabled? "+input.prop('disabled')+ " label disabled? "+label.hasClass('disabled'));
				if (input.prop('disabled')) {
					label.removeClass('active btn-success btn-danger btn-primary');
					input.prop('checked', false);
					event.stopImmediatePropagation();
					return false;
				}
				if (!input.prop('checked')) {
					label.addClass('active btn-success');
				}
				else {
					label.removeClass('active btn-success btn-danger btn-primary');
				}
				// bootstrap takes care of the checkboxes themselves!
			});
		
			$(".btn-group input[type=checkbox]").each(function() {
				var input = $(this);
				input.css('display','none');
			});		
		})(jQuery);
		
		initialiseRSVPTemplateBootstrapButtons();
	}
	
	function initialiseRSVPTemplateBootstrapButtons(){
		(function($){	
			// this doesn't seem to find just the checked ones!'
			//$(".btn-group input[checked=checked]").each(function() {
			$(".btn-group input").each(function() {
				var label = $("label[for=" + $(this).attr('id') + "]");
				var elem = $(this);
				if (elem.prop('disabled')) {
					label.addClass('disabled');
					label.removeClass('active btn-success btn-danger btn-primary');
					return;
				}
				label.removeClass('disabled');
				if (!elem.prop('checked')) {
					label.removeClass('active btn-success btn-danger btn-primary');
					return;
				}
				label.addClass('active btn-success');
			});
			
		})(jQuery);
	}
	
</script>
