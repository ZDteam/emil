<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$helper = new modJeventsNotifyHelper($params);
?>
<strong><?php echo JText::_("JEV_NOTIFY_ME_BY_CATEGORY"); ?></strong><br/>
<?php
echo $helper->categoriesTree($params->get("limit",10));
?>
<!--
<div class="button2-left"   style="margin-right:10px;">
	<div class="blank">
		<a href="#<?php echo JText::_("JEV_UPDATE_NOTIFICATIONS"); ?>" onclick="updateNotifications('<?php echo JURI::root(); ?>modules/mod_jevents_notify/updateNotifications.php');return false;"  title="<?php echo JText::_("JEV_UPDATE_NOTIFICATIONS"); ?>"  style="padding:0px 5px;text-decoration:none"><?php echo JText::_("JEV_UPDATE_NOTIFICATIONS"); ?></a>
	</div>
</div>
//-->
<input type="button"  onclick="updateNotifications('<?php echo JURI::root(); ?>modules/mod_jevents_notify/updateNotifications.php');return false;"  value="<?php echo JText::_("JEV_UPDATE_NOTIFICATIONS"); ?>"  style="padding:0px 5px;text-decoration:none;margin-top:5px;" name="updateNotifications" />