<?php defined('_JEXEC') or die('Restricted access'); ?>
<div id="jevents">
   <form action="index.php" method="post" name="adminForm"   id="adminForm">
	<div id="cpanel">
		<?php
		$option=JRequest::getCmd("option");
		$link = "index.php?option=$option&task=people.overview";
		$this->_quickiconButton( $link, "people.png", JText::_( 'JEV_PEOPLE' ) );

		$compparams = JComponentHelper::getParams("com_jevpeople");
		$link = "index.php?option=$option&task=types.list";
		$this->_quickiconButton( $link, "categories.png", JText::_( 'PEOPLE_TYPES' ));

		$compparams = JComponentHelper::getParams("com_jevpeople");		
		$link = "index.php?option=com_categories&extension=".JEVEX_COM_COMPONENT;
		
		$this->_quickiconButton( $link, "categories.png", JText::_( 'CATEGORIES' ));
		?>
	</div>
  <input type="hidden" name="task" value="cpanel" />
  <input type="hidden" name="act" value="" />
  <input type="hidden" name="option" value="<?php echo JEVEX_COM_COMPONENT; ?>" />
</form>
</div>
