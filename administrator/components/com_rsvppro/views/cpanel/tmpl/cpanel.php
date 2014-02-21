<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: cpanel.php 1432 2009-04-29 15:24:53Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');
$option = JRequest::getCmd("option");
?>
<div id="jevents">
	<form action="index.php" method="post" name="adminForm" >
		<table width="90%" border="0" cellpadding="2" cellspacing="2" class="adminform">

			<tr>
				<td width="100%" valign="top">
					<div id="cpanel">
						<?php
						$link = "index.php?option=" . RSVP_COM_COMPONENT . "&task=sessions.list";
						$this->_quickiconButton($link, "jevents_event_sml.png", JText::_('RSVP_SESSIONS'), "/administrator/components/" . RSVP_COM_COMPONENT . "/assets/images/");

						if (RSVPPRO_DEV)
						{
							/*
							  $link = "index.php?option=".RSVP_COM_COMPONENT."&task=accounts.list";
							  $this->_quickiconButton( $link, "icon-48-extension.png", JText::_('RSVP_ACCOUNTS') ,"/administrator/templates/khepri/images/header/");

							  $link = "index.php?option=".RSVP_COM_COMPONENT."&task=attendees.list";
							  $this->_quickiconButton( $link, "icon-48-user.png", JText::_('RSVP_ATTENDEES')  ,"/administrator/templates/khepri/images/header/");

							  $link = "index.php?option=".RSVP_COM_COMPONENT."&task=invitees.list";
							  $this->_quickiconButton( $link, "icon-48-massmail.png", JText::_('RSVP_INVITEES')  ,"/administrator/templates/khepri/images/header/");

							  $link = "index.php?option=".RSVP_COM_COMPONENT."&task=transactions.list";
							  $this->_quickiconButton( $link, "icon-48-extension.png", JText::_('RSVP_TRANSACTIONS') ,"/administrator/templates/khepri/images/header/");
							 */

							// new version
							$juser =  JFactory::getUser();
							$authorised = false;
							if ($juser->authorise('core.admin', 'com_rsvppro'))
							{
								$authorised = true;
							}
							if ($authorised){
								$link = "index.php?option=" . RSVP_COM_COMPONENT . "&task=params.edit";
								$this->_quickiconButton($link, "jevents_config_sml.png", JText::_('RSVP_CONFIGURATION'), "/administrator/components/" . RSVP_COM_COMPONENT . "/assets/images/");
							}

							$link = "index.php?option=$option&task=templates.list";
							$this->_quickiconButton($link, "jevents_layouts_sml.png", JText::_('RSVP_TEMPLATES'), "/administrator/components/" . RSVP_COM_COMPONENT . "/assets/images/");
						}
						?>
					</div>
				</td>
			</tr>
		</table>
		<input type="hidden" name="task" value="cpanel" />
		<input type="hidden" name="act" value="" />
		<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
	</form>
</div>
