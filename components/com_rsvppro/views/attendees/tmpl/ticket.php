<?php
defined('_JEXEC') or die('Restricted access');
?>

<div style="margin:20px;">
	<a href="javascript:void(0);" onclick="javascript:window.print(); return false;" title="<?php echo JText::_('JEV_CMN_PRINT'); ?>">
      	<?php 
		if ( JVersion::isCompatible("1.6")){
			echo JHtml::_('image.site', 'printButton.png','/media/system/images/', NULL, NULL, JText::_('JEV_CMN_PRINT'));
		}
		else {
			echo JHtml::_('image.site', 'printButton.png','/images/M_images/', NULL, NULL, JText::_('JEV_CMN_PRINT'));
		}
	?>
	</a>
	<?php
echo "<br/><br/>";

echo $this->attendeeParams->getTicket($this->attendee, $this->rsvpdata, $this->event);
?>
</div>
