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
EasyBlog(function($){

	$.Joomla("submitbutton", function(action) {

		if( action == 'saveNew' )
		{
			$( '#savenew' ).val( '1' );
			action	= 'save';
		}

		$.Joomla("submitform", [action]);
    });

	window.insertUser = function( id , username )
	{
		$( '#author-name' ).html( username ).show();
		$('#created_by').val( id );
		$.Joomla("squeezebox").close();
	}
});
</script>
<form action="index.php" method="post" name="adminForm" id="adminForm">

<?php echo $this->loadTemplate( $this->getTheme() ); ?>

<?php echo JHTML::_( 'form.token' ); ?>
<input type="hidden" name="savenew" value="0" id="savenew" />
<input type="hidden" name="option" value="com_easyblog" />
<input type="hidden" name="c" value="tag" />
<input type="hidden" name="task" value="save" />
<input type="hidden" name="tagid" value="<?php echo $this->tag->id;?>" />
</form>
