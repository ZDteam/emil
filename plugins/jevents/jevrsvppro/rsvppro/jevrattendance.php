<?php
/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */
// TODO - when saving form if not a repeating event reset the "each repeat" options to "all repeats"!!!
// no direct access
defined('_JEXEC') or die('Restricted access');
JLoader::register('JevRsvpInvitees', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrinvitees.php");

class JevRsvpAttendance
{

	private $params;
	private $jomsocial = false;
	private $cbuilder = false;
	private $groupjive = false;

	public function __construct($params)
	{
		$this->params = $params;
		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE . '/components/com_community/community.php'))
		{
			if (JComponentHelper::isEnabled("com_community"))
			{
				$this->jomsocial = true;
			}
		}
		if (JFile::exists(JPATH_SITE . '/components/com_comprofiler/comprofiler.php'))
		{
			if (JComponentHelper::isEnabled("com_comprofiler"))
			{
				if (JFile::exists(JPATH_SITE . "/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.php"))
				{
					$this->groupjive = true;
				}
				$this->cbuilder = true;
			}
		}

	}

	public function editAttendance(&$extraTabs, &$row, &$params)
	{
		$customfields = array();
		// Only setup when editing an event (not a repeat)
		if (JRequest::getString("jevtask", "") != "icalevent.edit" && JRequest::getString("jevtask", "") != "icalevent.editcopy")
			return true;
		JHtml::_('behavior.tooltip');
		/*
		  jimport('joomla.application.component.view');

		  $theme = JEV_CommonFunctions::getJEventsViewName ();
		  if(JVersion::isCompatible("1.6.0")){
		  $this->_basepath = JPATH_SITE . "/plugins/jevents/jevrsvppro/rsvppro/";
		  }
		  else {
		  $this->_basepath = JPATH_SITE . "/plugins/jevents/rsvppro/";
		  }
		  $this->view = new JViewLegacy(array('base_path' => $this->_basepath, "template_path" => $this->_basepath . "tmpl/default", "name" => $theme));

		  $this->view->addTemplatePath($this->_basepath . "tmpl/" . $theme);
		  $this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication ()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . "default");
		  $this->view->addTemplatePath(JPATH_SITE . '/' . 'templates' . '/' . JFactory::getApplication ()->getTemplate() . '/' . 'html' . '/' . "plg_rsvppro" . '/' . $theme);
		  $this->view->setLayout("edit");
		 */

		$db = & JFactory::getDBO();

		$editor = & JFactory::getEditor();

		JHtml::script('plugins/jevents/jevrsvppro/rsvppro/rsvp.js');
		JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

		$eventid = intval($row->ev_id());
		$user = JFactory::getUser();
		if (!($user->id == $row->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row)))
		{
			$label = JText::_('JEV_INVITATIONS');
			$customfield = array("label" => $label, "input" => JText::_('JEV_CREATOR_ONLY'));
			$customfields["rsvp"] = $customfield;
			return true;
		}
		if ($eventid > 0)
		{

			$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $eventid;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			if (!$rsvpdata)
			{
				$rsvpdata = $this->newRSVP();
			}
		}
		else
		{
			$rsvpdata = $this->newRSVP();
		}

		// If this is a copy reset the event id
		if (JRequest::getString("jevtask", "") == "icalevent.editcopy")
		{
			$eventid = 0;
		}

		if ($rsvpdata->message == "")
		{
			$rsvpdata->message = JText::_('JEV_DEFAULT_MESSAGE');
		}
		if ($rsvpdata->subject == "")
		{
			$rsvpdata->subject = JText::_('JEV_DEFAULT_SUBJECT');
		}
		if ($rsvpdata->remindermessage == "")
		{
			$rsvpdata->remindermessage = JText::_('JEV_DEFAULT_REMINDER_MESSAGE');
		}
		if ($rsvpdata->remindersubject == "")
		{
			$rsvpdata->remindersubject = JText::_('JEV_DEFAULT_REMINDER_SUBJECT');
		}
		$script = "JevRsvpLanguage.strings['JEV_HIDE_REGISTRATION_RSVP']='" . JText::_("JEV_HIDE_REGISTRATION_RSVP", true) . "';";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		if (!JVersion::isCompatible("3.0"))
		{
			$style = <<<STYLE
div#jevrsvpattend div , div#jevrsvpinvite div, div#jevrsvpremind div{
	/*clear:left;*/
	line-height:1.6em;
}
div#jevrsvpattend .control-label, div#jevrsvpinvite .control-label, div#jevrsvpremind .control-label{
	width:240px;
	margin-right:10px;
	display:block;
	float:left;
}

STYLE;
			$document->addStyleDeclaration($style);
		}
		if ($this->params->get("attendance", 1))
		{

			ob_start();
			?>
			<input type="hidden" name="custom_rsvp_evid" value="<?php echo $eventid; ?>" />
			<div id="jevrsvpattend">
				<fieldset  class="form-horizontal" >
					<legend><?php echo JText::_('JEV_ATTENDANCE'); ?></legend>
					<div class="control-group">
						<label class='control-label'><?php echo JText::_('JEV_ALLOW_REGISTRATION'); ?></label>
						<div class="rsvp_allowregistration controls radio btn-group">
							<label for="custom_rsvp_allowregistration1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
								<input type="radio" name="custom_rsvp_allowregistration" id="custom_rsvp_allowregistration1" value="1" <?php echo $rsvpdata->allowregistration == 1 ? "checked='checked'" : ""; ?> onclick="enableattendance();" />
							</label>
							<label for="custom_rsvp_allowregistration0"  class="btn radio"><?php echo JText::_('JEV_NO'); ?>
								<input type="radio" name="custom_rsvp_allowregistration" id="custom_rsvp_allowregistration0" value="0" <?php echo $rsvpdata->allowregistration ? "" : "checked='checked'"; ?> onclick="disableattendance();"/>
							</label>
							<?php
							if ($this->params->get("invites", 0))
							{
								?>
								<label for="custom_rsvp_allowregistration2" class="btn radio"><?php echo JText::_('JEV_BY_INVITATION'); ?>
									<input type="radio" name="custom_rsvp_allowregistration" id="custom_rsvp_allowregistration2" value="2" <?php echo $rsvpdata->allowregistration == 2 ? "checked='checked'" : ""; ?> onclick="enableattendance();"/>
								</label>
							<?php } ?>
						</div>
					</div>
					<input type="hidden" name="custom_rsvp_params" id="custom_rsvp_params" value="<?php echo $rsvpdata->params; ?>"  />

					<div id="jevattendance" <?php
				if (!$rsvpdata->allowregistration)
					echo "style='display:none'";
							?> >
						<div class="rsvp_sessionaccess control-group">
							<label for="custom_rsvp_sessionaccess"  class='control-label'><?php echo JText::_('JEV_SESSION_ACCESSLEVEL'); ?></label>
							<div class="controls">
								<?php
								$accesslist = JEventsHTML::buildAccessSelect(intval($rsvpdata->sessionaccess), 'class="inputbox" size="1" style="width:200px" onchange="toggleSessionAccessMessage()" ', JText::_("JEV_ACCESSLEVEL_MATCHES_EVENT"), "custom_rsvp_sessionaccess");
								if ($rsvpdata->sessionaccess < 0)
								{
									$accesslist = str_replace('<option value="">', '<option value="-1" selected="selected">', $accesslist);
								}
								else
								{
									$accesslist = str_replace('<option value="">', '<option value="-1" >', $accesslist);
								}
								echo $accesslist;
								?>
							</div>
						</div>
						<div id="rsvp_sessionaccessmessage" class="control-group" <?php
					if ($rsvpdata->sessionaccess < 0)
						echo "style='display:none'";
								?>>
							<div class='control-label'><?php echo JText::_('JEV_SESSION_NOACCESS_MESSAGE'); ?></div>		
							<div class="controls">
								<input type="text" size="60" maxlength="250" value="<?php echo htmlspecialchars($rsvpdata->sessionaccessmessage); ?>" id="custom_rsvp_sessionaccessmessage" name="custom_rsvp_sessionaccessmessage">
							</div>
						</div>
						<?php
						if ($this->params->get("capacity", 0))
						{
							?>

							<div   class="rsvp_capacity  control-group">
								<label for="custom_rsvp_capacity"  class='control-label'><?php echo JText::_('JEV_CAPACITY'); ?></label>
								<div class="controls">
									<input type="text" name="custom_rsvp_capacity" id="custom_rsvp_capacity" value="<?php echo $rsvpdata->capacity; ?>" size="6" />
								</div>
							</div>
							<?php
							if ($this->params->get("waitinglist", 0))
							{
								?>
								<div  class="rsvp_waitingcapacity  control-group">
									<label for="custom_rsvp_waitingcapacity"  class='control-label'><?php echo JText::_('JEV_WAITING_CAPACITY'); ?></label>
									<div class="controls">
										<input type="text" name="custom_rsvp_waitingcapacity" id="custom_rsvp_waitingcapacity" value="<?php echo $rsvpdata->waitingcapacity; ?>" size="6" />
									</div>
								</div>
								<?php
							}
						}
						if ($this->params->get("allowpending", 0))
						{
							?>
							<div  class="rsvp_initialstate  control-group">
								<div class='control-label'><?php echo JText::_('JEV_INITIAL_REGISTRATION_STATE'); ?></div>
								<div class="controls radio btn-group">
									<label for="custom_rsvp_initialstate0" class="btn radio"><?php echo JText::_('JEV_INITIAL_STATE_PENDING'); ?>
										<input type="radio" name="custom_rsvp_initialstate" id="custom_rsvp_initialstate0" value="0" <?php echo $rsvpdata->initialstate == 0 ? "checked='checked'" : ""; ?> />
									</label>
									<label for="custom_rsvp_initialstate1" class="btn radio"><?php echo JText::_('JEV_INITIAL_STATE_APPROVED'); ?>
										<input type="radio" name="custom_rsvp_initialstate" id="custom_rsvp_initialstate1" value="1" <?php echo $rsvpdata->initialstate == 1 ? "checked='checked'" : ""; ?> />
									</label>
								</div>
							</div>
							<?php
						}
						jimport("joomla.filesystem.file");
						$templates = JFolder::files(dirname(__FILE__) . "/params/", ".xml");
						// only offer extra fields templates if there is more than one available
						if ($rsvpdata->template == "")
						{
							$rsvpdata->template = $this->params->get("defaultcf");
						}
						// And the new db versions
						$db = JFactory::getDBO();
						$user = JFactory::getUser();
						$db->setQuery("SELECT * FROM #__jev_rsvp_templates where (global=1 OR created_by=" . $user->id . " OR id=" . intval($rsvpdata->template) . ") AND ((istemplate=1  AND published =1  ) OR id=" . intval($rsvpdata->template) . ")");
						$dbtemplates = $db->loadObjectList();
						echo $db->getErrorMsg();

						if (count($templates) > 1 || (count($templates) == 1 && $templates[0] == "fieldssample.xml"))
						{
							?>
							<div class="rsvp_extrafields">
								<strong><?php echo JText::_('JEV_EXTRA_FIELDS'); ?></strong>
								<div  class="control-group">
									<label for="custom_rsvp_template" class='control-label'><?php echo JText::_('JEV_EXTRA_FIELDS_TEMPLATE'); ?></label>
									<div class="controls">
										<?php
										$options = array();
										$options[] = JHtml::_('select.option', "", JText::_('JEV_SELECT_TEMPLATE'), 'var', 'text');
										$options[] = JHtml::_('select.option', -1, JText::_('JEV_BLANK_TEMPLATE'), 'var', 'text');
										foreach ($dbtemplates as $template)
										{
											// we will use the locked field to specify disabled then we will replace later
											$option = JHtml::_('select.option', $template->id, $template->title, 'var', 'text', $template->locked);
											$options[] = $option;
										}

										foreach ($templates as $template)
										{
											if ($template == "fieldssample.xml")
											{
												continue;
											}
											$options[] = JHtml::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'var', 'text');
										}
										// if only one choice then no need to ask - this is not good since it forces you to use the custom fields
										if (count($options) == 2)
										{
											//    array_shift($options);
										}
										$html = JHtml::_('select.genericlist', $options, "custom_rsvp_template", 'onchange="changeTemplateSelection()" style="width:300px" ', 'var', 'text', $rsvpdata->template);
										// we uses the locked field to specify disabled so we replace now
										echo str_replace('disabled="disabled"', 'locked=1', $html);
										?>
									</div>
									<?php
									JHtml::_('behavior.modal');
									$link = JRoute::_("index.php?option=com_rsvppro&task=templates.edit&tmpl=component&cid[0]=xxGGxx&customise=1");
									if (intval($rsvpdata->template) > 0)
									{
										$style = "";
									}
									else
									{
										$style = "style='display:none' ";
									}
									if (JevTemplateHelper::canCreateOwn() || JevTemplateHelper::canCreateGlobal())
									{
										?>
										<div class="controls">
											<a <?php echo $style; ?> href='#<?php echo str_replace(" ", "_", JText::_('JEV_CUSTOMISE_EXTRA_FIELDS_TEMPLATE')); ?>' onclick="customiseTemplate('<?php echo $link; ?>');return false;"  id="custom_rsvp_template_link" ><?php echo JText::_('JEV_CUSTOMISE_EXTRA_FIELDS_TEMPLATE'); ?></a>
										</div>
										<?php
									}
									else
									{
										?>
										<div class="controls">
											<span id="custom_rsvp_template_link"></span>
										</div>
										<?php
									}
									?>
								</div>
							</div>
						<?php }
						?>
						<div class="rsvp_allowcancellation control-group">
							<div class='control-label'><?php echo JText::_('JEV_ALLOW_CANCELLATION'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_allowcancellation1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
									<input type="radio" name="custom_rsvp_allowcancellation" id="custom_rsvp_allowcancellation1" value="1"  <?php echo $rsvpdata->allowcancellation ? "checked='checked'" : ""; ?> onclick="updateCancelClose(1);"/>
								</label>
								<label for="custom_rsvp_allowcancellation0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
									<input type="radio" name="custom_rsvp_allowcancellation" id="custom_rsvp_allowcancellation0" value="0" <?php echo $rsvpdata->allowcancellation ? "" : "checked='checked'"; ?>  onclick="updateCancelClose(0);"/>
								</label>
							</div>
						</div>

						<?php
						// handle legacy events before allowchanges was introduced
						if ($rsvpdata->allowchanges == -1)
						{
							$rsvpdata->allowchanges = $rsvpdata->allowcancellation;
						}
						?>
						<div class="rsvp_allowchanges control-group">
							<div class='control-label''><?php echo JText::_('JEV_ALLOW_CHANGES'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_allowchanges1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
									<input type="radio"  name="custom_rsvp_allowchanges" id="custom_rsvp_allowchanges1" value="1"  <?php echo $rsvpdata->allowchanges ? "checked='checked'" : ""; ?> onclick="updateCancelClose(1);"/>
								</label>
								<label for="custom_rsvp_allowchanges0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
									<input type="radio" name="custom_rsvp_allowchanges" id="custom_rsvp_allowchanges0" value="0" <?php echo $rsvpdata->allowchanges ? "" : "checked='checked'"; ?>  onclick="updateCancelClose(0);"/>
								</label>
							</div>
						</div>

						<div class="rsvp_allrepeats control-group">
							<div  class='control-label'><?php echo JText::_('JEV_REGISTER_TRACK_ATTENDANCE'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_allrepeats1" class="btn radio"><?php echo JText::_('JEV_ALL_REPEATS'); ?>
									<input type="radio"  name="custom_rsvp_allrepeats" id="custom_rsvp_allrepeats1" value="1" <?php echo $rsvpdata->allrepeats ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_allrepeats0" class="btn radio"><?php echo JText::_('JEV_SPECIFIC_REPEATS'); ?>
									<input type="radio"  name="custom_rsvp_allrepeats" id="custom_rsvp_allrepeats0" value="0" <?php echo $rsvpdata->allrepeats ? "" : "checked='checked'"; ?>/>
								</label>
							</div>
						</div>
						<div class="rsvp_showattendees control-group">
							<div  class='control-label'><?php echo JText::_('JEV_SHOW_ATTENDEES'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_showattendees1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
									<input type="radio"  name="custom_rsvp_showattendees" id="custom_rsvp_showattendees1" value="1" <?php echo $rsvpdata->showattendees == 1 ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_showattendees0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
									<input type="radio"  name="custom_rsvp_showattendees" id="custom_rsvp_showattendees0" value="0" <?php echo $rsvpdata->showattendees ? "" : "checked='checked'"; ?>/>
								</label>
								<?php
								if ($this->params->get("invites", 0))
								{
									?>
									<label for="custom_rsvp_showattendees2" class="btn radio"><?php echo JText::_('JEV_BY_INVITATION'); ?>
										<input type="radio"  name="custom_rsvp_showattendees" id="custom_rsvp_showattendees2" value="2" <?php echo $rsvpdata->showattendees == 2 ? "checked='checked'" : ""; ?>/>
									</label>
								<?php } ?>
							</div>
						</div>
						<div class="rsvp_attendintro control-group">
							<div style="font-weight:bold"><?php echo JText::_('JEV_ATTEND_INTRO'); ?></div>
							<?php
							// parameters : areaname, content, hidden field, width, height, rows, cols
							echo $editor->display('custom_rsvp_attendintro', $rsvpdata->attendintro, "100%", 250, '50', '10', false);
							?>
						</div>
						<?php
						echo $this->openCloseDates($rsvpdata, $row);
						?>
					</div>
				</fieldset>
			</div>
			<?php
			$input = ob_get_clean();

			$label = JText::_('JEV_ATTENDANCE');
			$extraTabs[] = array("title" => $label, "paneid" => 'jev_attend_pane', "content" => $input);
		}

		if ($this->params->get("invites", 0))
		{
			$rsvpdata->invites = ($rsvpdata->invites || $this->params->get("autoinvite", "") != "" || $this->params->get("defaultallowinvites", 0));
			ob_start();
			?>
			<div id="jevrsvpinvite">
				<fieldset class="form-horizontal" >
					<legend><?php echo JText::_('JEV_INVITATION_OPTIONS'); ?></legend>
					<div class="control-group">
						<div class='control-label'><?php echo JText::_('JEV_CREATE_INVITES'); ?></div>
						<div class="rsvp_allowinvitations controls radio btn-group">
							<label for="custom_rsvp_invites1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
								<input type="radio" name="custom_rsvp_invites" id="custom_rsvp_invites1" value="1" onclick="enableinvites()" <?php echo $rsvpdata->invites ? "checked='checked'" : ""; ?>/>
							</label>
							<label for="custom_rsvp_invites0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
								<input type="radio" name="custom_rsvp_invites" id="custom_rsvp_invites0" value="0" onclick="disableinvites()"  <?php echo $rsvpdata->invites ? "" : "checked='checked'"; ?>/>
							</label>
						</div>
					</div>
					<div id="jev_allinvites" <?php echo $rsvpdata->invites ? "" : "style='display:none;'"; ?> >
						<div class="rsvp_allinvites control-group">
							<div class='control-label'><?php echo JText::_('JEV_ALL_INVITES'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_allinvites1" class="btn radio"><?php echo JText::_('JEV_ALL_REPEATS'); ?>
									<input type="radio" name="custom_rsvp_allinvites" id="custom_rsvp_allinvites1" value="1" <?php echo $rsvpdata->allinvites ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_allinvites0" class="btn radio"><?php echo JText::_('JEV_SPECIFIC_REPEATS'); ?>
									<input type="radio" name="custom_rsvp_allinvites" id="custom_rsvp_allinvites0" value="0" <?php echo $rsvpdata->allinvites ? "" : "checked='checked'"; ?>/>
								</label>
							</div>
						</div>
						<div class="control-group">
							<div class='control-label'><?php echo JText::_('JEV_HIDE_NONE_INVITEES'); ?></div>
							<div class="controls radio btn-group">
								<label for="custom_rsvp_hidenoninvitees1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
									<input type="radio" name="custom_rsvp_hidenoninvitees" id="custom_rsvp_hidenoninvitees1" value="1" <?php echo $rsvpdata->hidenoninvitees ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_hidenoninvitees0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
									<input type="radio" name="custom_rsvp_hidenoninvitees" id="custom_rsvp_hidenoninvitees0" value="0" <?php echo $rsvpdata->hidenoninvitees ? "" : "checked='checked'"; ?>/>
								</label>
							</div>
						</div>
					</div>
					<div id="jev_invites" <?php echo $rsvpdata->invites ? "" : "style='display:none;'"; ?>>
						<em><?php echo JText::_('JEV_ADD_INVITES_MESSAGE'); ?></em>
					</div>

					<div id="jevmessage"  class="control-group" <?php
			if (!$rsvpdata->invites)
			{
				echo "style='display:none'";
			}
			?>>
						<div style="font-weight:bold">
							<?php
							echo JText::_('JEV_EMAIL_MESSAGE');
							echo " " . JHtml::_('tooltip', JText::_('JEV_DEFAULT_MESSAGE_DESC'), null, 'tooltip.png', null, null, 0);
							?>
						</div>
						<div  class='control-label'>
							<?php echo JText::_('JEV_EMAIL_SUBJECT'); ?>
						</div>
						<div class="controls">
							<input type="text" name="custom_rsvp_subject" value="<?php echo $rsvpdata->subject; ?>" size="50" maxlength="255" />
						</div>
						<?php
						// parameters : areaname, content, hidden field, width, height, rows, cols
						if ($rsvpdata->message == strip_tags($rsvpdata->message))
						{
							$rsvpdata->message = htmlspecialchars(nl2br($rsvpdata->message));
						}
						?>
						<div class="controls">
							<?php echo $editor->display('custom_rsvp_message', $rsvpdata->message, "100%", 250, '50', '10', false); ?>
						</div>
					</div>
				</fieldset>
			</div>
			<?php
			$input = ob_get_clean();
			$label = JText::_('JEV_INVITATION_OPTIONS');
			$extraTabs[] = array("title" => $label, "paneid" => 'jev_invite_pane', "content" => $input);
		}
		if ($this->params->get("reminders", 0))
		{
			JPluginHelper::importPlugin("rsvppro");
			$dispatcher = & JDispatcher::getInstance();
			$extrareminders = array();
			$results = $dispatcher->trigger('onEditReminders', array(&$extrareminders));

			ob_start();
			?>
			<div id="jevrsvpremind">
				<fieldset class="form-horizontal" >
					<legend><?php echo JText::_('JEV_REMINDER_OPTIONS'); ?></legend>
					<div   class="control-group">
						<div class='control-label'><?php echo JText::_('JEV_ALLOW_REMINDERS'); ?></div>
						<div class="rsvp_allowreminders controls radio btn-group">
							<label for="custom_rsvp_allowreminders1" class="btn radio"><?php echo JText::_('JEV_YES'); ?>
								<input type="radio" name="custom_rsvp_allowreminders" id="custom_rsvp_allowreminders1" value="1" onclick="enablereminders()" <?php echo $rsvpdata->allowreminders == 1 ? "checked='checked'" : ""; ?>/>
							</label>
							<label for="custom_rsvp_allowreminders0" class="btn radio"><?php echo JText::_('JEV_NO'); ?>
								<input type="radio" name="custom_rsvp_allowreminders" id="custom_rsvp_allowreminders0" value="0" onclick="disablereminders()"  <?php echo $rsvpdata->allowreminders ? "" : "checked='checked'"; ?>/>
							</label>
							<?php
							foreach ($extrareminders as $k => $v)
							{
								?>
								<label for="custom_rsvp_allowreminders<?php echo $k; ?>" class="btn radio"><?php echo $v; ?>
									<input type="radio" name="custom_rsvp_allowreminders" id="custom_rsvp_allowreminders<?php echo $k; ?>" value="<?php echo $k; ?>" onclick="enablereminders()"  <?php echo $rsvpdata->allowreminders == $k ? "checked='checked'" : ""; ?>/>
								</label>
								<?php
							}
							?>
						</div>
					</div>
					<div id="jevreminder" <?php
				if (!$rsvpdata->allowreminders)
				{
					echo "style='display:none'";
				}
							?>  >
						<div class="rsvp_allreminders control-group">
							<div class='control-label'><?php echo JText::_('JEV_REMIND_ALL_REPEATS'); ?></div>
							<div class="rsvp_allowreminders controls radio btn-group">
								<label for="custom_rsvp_remindallrepeats1" class="btn radio"><?php echo JText::_('JEV_FIRST_REPEAT'); ?>
									<input type="radio" name="custom_rsvp_remindallrepeats" id="custom_rsvp_remindallrepeats1" value="1" <?php echo $rsvpdata->remindallrepeats == 1 ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_remindallrepeats2" class="btn radio"><?php echo JText::_('JEV_ALL_INDIVIDUAL_REPEATS'); ?>
									<input type="radio" name="custom_rsvp_remindallrepeats" id="custom_rsvp_remindallrepeats2" value="2" <?php echo $rsvpdata->remindallrepeats == 2 ? "checked='checked'" : ""; ?>/>
								</label>
								<label for="custom_rsvp_remindallrepeats0" class="btn radio"><?php echo JText::_('JEV_SPECIFIC_REPEATS'); ?>
									<input type="radio" name="custom_rsvp_remindallrepeats" id="custom_rsvp_remindallrepeats0" value="0" <?php echo $rsvpdata->remindallrepeats == 0 ? "checked='checked'" : ""; ?>/>
								</label>
							</div>
						</div>

						<div class="rsvp_remindernotice control-group">
							<div class='control-label'>
								<label for="custom_rsvp_remindernotice" ><?php echo JText::_('JEV_REMINDER_INTERVAL'); ?></label>
							</div>
							<div class="controls">
								<input type="text" name="custom_rsvp_remindernotice" id="custom_rsvp_remindernotice" value="<?php echo intval($rsvpdata->remindernotice / 3600) ?>" size="12" />
							</div>
						</div>
						<div class="rsvp_remindermessage control-group">
							<div style="font-weight:bold" ><?php
			 echo JText::_('JEV_REMINDER_EMAIL_MESSAGE');
			 JHtml::_('behavior.tooltip');
			 echo " " . JHtml::_('tooltip', JText::_('JEV_DEFAULT_MESSAGE_DESC'), null, 'tooltip.png', null, null, 0);
							?></div>
							<div  class='control-label'><?php echo JText::_('JEV_EMAIL_SUBJECT'); ?></div>
							<div class="controls">
								<input type="text" name="custom_rsvp_remindersubject" value="<?php echo $rsvpdata->remindersubject; ?>" size="50" maxlength="255" />
							</div>
							<?php
							// parameters : areaname, content, hidden field, width, height, rows, cols
							if ($rsvpdata->remindermessage == strip_tags($rsvpdata->remindermessage))
							{
								$rsvpdata->remindermessage = htmlspecialchars(nl2br($rsvpdata->remindermessage));
							}
							?>
							<div class="controls">
								<?php echo $editor->display('custom_rsvp_remindermessage', $rsvpdata->remindermessage, "100%", 250, '50', '10', false); ?>
							</div>
						</div>
					</div>
				</fieldset>
			</div>
			<?php
			$input = ob_get_clean();

			JHtml::_('behavior.modal');

			$label = JText::_('JEV_REMINDER_OPTIONS');
			$customfield = array("label" => $label, "input" => $input);
			$customfields["rsvp"] = $customfield;

			$extraTabs[] = array("title" => $label, "paneid" => 'jev_remind_pane', "content" => $input);
		}

		return true;

	}

	private function openCloseDates($rsvpdata, $row)
	{
		ob_start();
		?>
		<div>
			<fieldset><legend><?php echo JText::_('JEV_STARTREGISTRATION'); ?></legend>
				<strong><?php echo JText::_("JEV_REGISTRATION_TIME_INTRO"); ?> </strong><br/>
				<div style="float:left" class="regopendate">
					<?php
					echo JText::_('JEV_STARTREGISTRATION_DATE') . "&nbsp;";
					$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
					$minyear = $params->get("com_earliestyear", 1970);
					$maxyear = $params->get("com_latestyear", 2150);
					if ($rsvpdata->regopen == "0000-00-00 00:00:00")
						$rsvpdata->regopen = strftime("%Y-%m-%d %H:00:00");
					?>
					<input type="text" id="custom_rsvp_regopen" name="custom_rsvp_regopen"  value="<?php echo $rsvpdata->regopen; ?>" style="display:none" />
					<?php
					if (method_exists("JEVHelper", "loadCalendar11"))
					{
						JEVHelper::loadCalendar11("regopen", "regopen", substr($rsvpdata->regopen, 0, 10), $minyear, $maxyear, 'checkRegDates(\'regopentime\');', 'checkRegDates(\'regopentime\');', 'Y-m-d');
					}
					else
					{
						JevRsvpAttendance::loadCalendar11("regopen", "regopen", substr($rsvpdata->regopen, 0, 10), $minyear, $maxyear, 'checkRegDates(\'regopentime\');', 'checkRegDates(\'regopentime\');', 'Y-m-d');
					}

					if (strlen($rsvpdata->regopen) > 10)
					{
						$regopentime = strtotime($rsvpdata->regopen);
						$hiddenregopentime = strftime("%H:%M", $regopentime);
						list($h, $m) = explode(":", $hiddenregopentime);
						if (!$params->get("com_calUseStdTime"))
						{
							$format = "%H:%M";
							$regopentime = strftime($format, $regopentime);
						}
						else
						{
							$format = JApplication::isWinOS() ? "%I:%M" : "%l:%M";
							if ($h > 11)
							{
								$regopentime = strftime($format, $regopentime) . " " . JText::_("JEV_PM");
							}
							else
							{
								$regopentime = strftime($format, $regopentime) . " " . JText::_("JEV_AM");
							}
						}
					}
					else
					{
						$regopentime = "";
						$hiddenregopentime = "00:00";
					}
					?>
				</div>
				<div style="position:relative;float:left;" class="regopentime">
					<?php echo JText::_('JEV_STARTREGISTRATION_TIME') . "&nbsp;"; ?>
					<input class="inputbox" type="text"  id="regopentime" size="8" value="<?php echo $regopentime; ?>" />
					<input class="inputbox" type="hidden" id="hiddenregopentime" size="8" value="<?php echo $hiddenregopentime; ?>" />
				</div>
			</fieldset>
		</div>
		<div>
			<fieldset><legend><?php echo JText::_('JEV_ENDREGISTRATION'); ?></legend>
				<strong><?php echo JText::_("JEV_REGISTRATION_TIME_INTRO"); ?> </strong><br/>
				<div style="float:left" class="regclosedate">
					<?php
					echo JText::_('JEV_ENDREGISTRATION_DATE') . "&nbsp;";
					$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
					$minyear = $params->get("com_earliestyear", 1970);
					$maxyear = $params->get("com_latestyear", 2150);
					if ($rsvpdata->regclose == "0000-00-00 00:00:00")
						$rsvpdata->regclose = $row->publish_up();
					?>
					<input type="text" id="custom_rsvp_regclose"  name="custom_rsvp_regclose" value="<?php echo $rsvpdata->regclose; ?>" style="display:none" />
					<?php
					if (method_exists("JEVHelper", "loadCalendar11"))
					{
						JEVHelper::loadCalendar11("regclose", "regclose", substr($rsvpdata->regclose, 0, 10), $minyear, $maxyear, 'checkRegDates(\'regclosetime\');', 'checkRegDates(\'regclosetime\');', 'Y-m-d');
					}
					else
					{
						JevRsvpAttendance::loadCalendar11("regclose", "regclose", substr($rsvpdata->regclose, 0, 10), $minyear, $maxyear, 'checkRegDates(\'regclosetime\');', 'checkRegDates(\'regclosetime\');', 'Y-m-d');
					}


					if (strlen($rsvpdata->regclose) > 10)
					{
						$regclosetime = strtotime($rsvpdata->regclose);
						$hiddenregclosetime = strftime("%H:%M", $regclosetime);
						list($h, $m) = explode(":", $hiddenregclosetime);
						if (!$params->get("com_calUseStdTime"))
						{
							$format = "%H:%M";
							$regclosetime = strftime($format, $regclosetime);
						}
						else
						{
							$format = JApplication::isWinOS() ? "%I:%M" : "%l:%M";
							if ($h > 11)
							{
								$regclosetime = strftime($format, $regclosetime) . " " . JText::_("JEV_PM");
							}
							else
							{
								$regclosetime = strftime($format, $regclosetime) . " " . JText::_("JEV_AM");
							}
						}
					}
					else
					{
						$regclosetime = "";
						$hiddenregclosetime = "00:00";
					}
					?>
				</div>
				<div style="position:relative;float:left;" class="regclosetime"> 
					<?php echo JText::_('JEV_ENDREGISTRATION_TIME') . "&nbsp;"; ?>
					<input class="inputbox" type="text" id="regclosetime" size="8" value="<?php echo $regclosetime; ?>" />
					<input class="inputbox" type="hidden" id="hiddenregclosetime" size="8" value="<?php echo $hiddenregclosetime; ?>" />
				</div>
			</fieldset>
		</div>
		<div id='jevendcancel' style="display:<?php echo $rsvpdata->allowcancellation ? 'block' : 'none'; ?>">
			<fieldset><legend><?php echo JText::_('JEV_ENDCANCELLATIONS'); ?></legend>
				<strong><?php echo JText::_("JEV_ENDCANCELLATIONS_INTRO"); ?> </strong><br/>
				<div style="float:left">
					<?php
					echo JText::_('JEV_ENDCANCELLATION_DATE') . "&nbsp;";
					$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
					$minyear = $params->get("com_earliestyear", 1970);
					$maxyear = $params->get("com_latestyear", 2150);
					if ($rsvpdata->cancelclose == "0000-00-00 00:00:00")
						$rsvpdata->cancelclose = $row->publish_up();
					?>
					<input type="hidden" id="custom_rsvp_cancelclose"  name="custom_rsvp_cancelclose" value="<?php echo $rsvpdata->cancelclose; ?>" />
					<?php
					if (method_exists("JEVHelper", "loadCalendar11"))
					{
						JEVHelper::loadCalendar11("cancelclose", "cancelclose", substr($rsvpdata->cancelclose, 0, 10), $minyear, $maxyear, 'checkRegDates(\'cancelclosetime\');', 'checkRegDates(\'cancelclosetime\');', 'Y-m-d');
					}
					else
					{
						JevRsvpAttendance::loadCalendar11("cancelclose", "cancelclose", substr($rsvpdata->cancelclose, 0, 10), $minyear, $maxyear, 'checkRegDates(\'cancelclosetime\');', 'checkRegDates(\'cancelclosetime\');', 'Y-m-d');
					}

					if (strlen($rsvpdata->cancelclose) > 10)
					{
						$cancelclosetime = strtotime($rsvpdata->cancelclose);
						$hiddencancelclosetime = strftime("%H:%M", $cancelclosetime);
						list($h, $m) = explode(":", $hiddencancelclosetime);
						if (!$params->get("com_calUseStdTime"))
						{
							$format = "%H:%M";
							$cancelclosetime = strftime($format, $cancelclosetime);
						}
						else
						{
							$format = JApplication::isWinOS() ? "%I:%M" : "%l:%M";
							if ($h > 11)
							{
								$cancelclosetime = strftime($format, $cancelclosetime) . " " . JText::_("JEV_PM");
							}
							else
							{
								$cancelclosetime = strftime($format, $cancelclosetime) . " " . JText::_("JEV_AM");
							}
						}
					}
					else
					{
						$cancelclosetime = "";
						$hiddencancelclosetime = "00:00";
					}
					if (JVersion::isCompatible("1.6.0"))
					{
						$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
					}
					else
					{
						$pluginpath = 'plugins/jevents/rsvppro/';
					}
					?>
				</div>
				<div style="position:relative">
					<?php echo JText::_('JEV_ENDCANCELLATION_TIME') . "&nbsp;"; ?>
					<input class="inputbox" type="text" id="cancelclosetime" size="8" value="<?php echo $cancelclosetime; ?>" />
					<input class="inputbox" type="hidden" id="hiddencancelclosetime" size="8" value="<?php echo $hiddencancelclosetime; ?>" />
				</div>
			</fieldset>
		</div>
		<div id="rsvpspacer"></div>
		<script  type="text/javascript" >
			window.addEvent("domready",function(){
		<?php
		if (!$params->get("com_calUseStdTime"))
		{
			$timeoptions = ", hours : [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24], amPm :[]";
		}
		else
		{
			$timeoptions = ", amPm :['" . JText::_("JEV_AM") . "','" . JText::_("JEV_PM") . "']";
		}
		?>
																																											
				myStartPick = new HoverPickTime('regopentime', {'img':	'<?php echo JURI::root(); ?><?php echo $pluginpath; ?>clock_red.png' <?php echo $timeoptions; ?>});

				myEndPick = new HoverPickTime('regclosetime', {'img':	'<?php echo JURI::root(); ?><?php echo $pluginpath; ?>clock_red.png' <?php echo $timeoptions; ?>});

				myCancelPick = new HoverPickTime('cancelclosetime', {'img':	'<?php echo JURI::root(); ?><?php echo $pluginpath; ?>clock_red.png' <?php echo $timeoptions; ?>});
			});
		</script>
		<?php
		$html = ob_get_clean();
		return $html;

	}

	public function storeAttendance($event)
	{
		$evdetail = $event->_detail;
		if (!isset($evdetail->_customFields) || !is_array($evdetail->_customFields) || !array_key_exists("rsvp_allowregistration", $evdetail->_customFields))
			return;

		$db = & JFactory::getDBO();

		$eventid = intval($evdetail->_customFields["rsvp_evid"]);
		if ($eventid == 0)
		{
			$eventid = $event->ev_id;
		}
		if ($eventid > 0)
		{

			$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $eventid;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();

			// Store details in registry - will need them for waiting lists!
			$registry = & JRegistry::getInstance("jevents");
			$registry->set("rsvpdata", $rsvpdata);
			$registry->set("event", $event);

			JTable::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_rsvppro/tables/");
			$rsvpitem = & JTable::getInstance('jev_attendance');
			//$rsvpitem = new JTable("#__jev_attendance", "id", $db);
			// ensure picks up default values
			foreach ($this->newRSVP() as $k => $v)
			{
				$rsvpitem->$k = $v;
			}
			$rsvpitem->id = 0;
			foreach ($evdetail->_customFields as $key => $value)
			{
				if (strpos($key, "rsvp_") === 0)
				{
					$key = str_replace("rsvp_", "", $key);
					$rsvpitem->$key = $value;

					// update to seconds already done by newRSVP unless set from custom fields
					if ($key == "remindernotice")
					{
						$rsvpitem->remindernotice *= 3600;
					}

					if ($key == "regopen" && ($value == "" || $value == "0000-00-00 00:00:00"))
					{
						$rsvpitem->$key = strftime("%Y-%m-%d %H:00:00");
					}

					if ($key == "regclose" && ($value == "" || $value == "0000-00-00 00:00:00"))
					{
						$start = JevDate::strftime('%Y-%m-%d %H:00:00', $evdetail->dtstart);
						$rsvpitem->$key = $start;
					}

					if ($key == "cancelclose" && ($value == "" || $value == "0000-00-00 00:00:00"))
					{
						$start = JevDate::strftime('%Y-%m-%d %H:00:00', $evdetail->dtstart);
						$rsvpitem->$key = $start;
					}
				}
			}
			unset($rsvpitem->evid);
			$rsvpitem->ev_id = intval($eventid);

			if (JRequest::getString("freq") == "none")
			{
				$rsvpitem->allrepeats = 1;
				$rsvpitem->allinvites = 1;
				$rsvpitem->remindallrepeats = 1;
			}
			if ($rsvpdata && $rsvpdata->id > 0)
			{
				$rsvpitem->id = intval($rsvpdata->id);
				$success = $rsvpitem->store();

				// Also clear out defunct attendance and invitation records
				// if !registration allows then remove all attendance records
				if (!$rsvpitem->allowregistration)
				{
					/*
					 * Keep the attendees in case someone wants to disable registrations temporarily and then reinstate them
					  $sql = "DELETE FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id;
					  $db->setQuery($sql);
					  $db->query();
					 */
				}

				// if attendance is recorded once for all repeats then remove repeat specific attendance records
				if ($rsvpitem->allrepeats)
				{
					$sql = "DELETE FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " AND rp_id>0";
					$db->setQuery($sql);
					$db->query();
					//$sql = "DELETE FROM #__jev_attendance WHERE ev_id=" . $rsvpdata->ev_id . " AND allrepeats=0";
					//$db->setQuery($sql);
					//$db->query();
				}
				else
				{
					// if attendance is recorded separately for each repeats then remove general attendance records
					$sql = "DELETE FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " AND rp_id=0";
					$db->setQuery($sql);
					$db->query();
					//$sql = "DELETE FROM #__jev_attendance WHERE ev_id=" . $rsvpdata->ev_id . " AND allrepeats=1";
					//$db->setQuery($sql);
					//$db->query();
				}

				// if no invites for this event then remove all invites
				if (!$rsvpitem->invites)
				{
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=" . $rsvpdata->id;
					$db->setQuery($sql);
					$db->query();
				}
				// if invites cover all repeats then remove repeat specific attendance records
				if ($rsvpitem->allinvites)
				{
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=" . $rsvpdata->id . " AND rp_id>0";
					$db->setQuery($sql);
					$db->query();
				}
				else
				{
					// if attendance is recorded separately for each repeats then remove general attendance records
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=" . $rsvpdata->id . " AND rp_id=0";
					$db->setQuery($sql);
					$db->query();
				}


				// TODO clean up reminders too
			}
			else
			{
				$success = $rsvpitem->store();
			}

			if ($success)
			{
				// Make sure the waiting list reflects any change in capacity
				if (isset($rsvpdata) && $rsvpdata->id)
				{
					JLoader::register('JevRsvpAttendees', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrattendees.php");
					$jevrDisplayAttendees = new JevRsvpAttendees($this->params, $this->jomsocial, $rsvpdata);
					$jevrDisplayAttendees->updateWaitingList($rsvpdata, $rsvpdata->id);
					//$jevrDisplayAttendance = new JevRsvpDisplayAttendance($this->params);
					//$jevrDisplayAttendance->updateWaitingList($rsvpdata->id);
				}
			}

			return $success;
		}
		return false;

	}

	public function deleteAttendance($idlist)
	{
		$ids = explode(",", $idlist);
		JArrayHelper::toInteger($ids);
		$idlist = implode(",", $ids);

		// fetch the attendance records
		$db = JFactory::getDBO();
		$sql = "SELECT id FROM #__jev_attendance WHERE ev_id IN (" . $idlist . ")";
		$db->setQuery($sql);
		$atids = $db->loadColumn();

		$sql = "DELETE FROM #__jev_attendance WHERE ev_id IN (" . $idlist . ")";
		$db->setQuery($sql);
		$db->query();

		if ($atids && count($atids) > 0)
		{
			$atids = implode(",", $atids);

			$sql = "DELETE FROM #__jev_attendees WHERE at_id IN (" . $atids . ")";
			$db->setQuery($sql);
			$db->query();

			$sql = "DELETE FROM #__jev_invitees WHERE at_id IN (" . $atids . ")";
			$db->setQuery($sql);
			$db->query();

			$sql = "DELETE FROM #__jev_reminders WHERE at_id IN (" . $atids . ")";
			$db->setQuery($sql);
			$db->query();
		}

		return true;

	}

	private function newRSVP()
	{
		$rsvpdata = new stdClass();
		$rsvpdata->id = 0;
		$rsvpdata->allowregistration = $this->params->get("defaultallow", 0);
		// Add these as defaults from the params!!!
		if ($this->params->get("allowpending", 0))
		{
			$rsvpdata->initialstate = $this->params->get("defaultinitialstate", 1);
		}
		else
		{
			$rsvpdata->initialstate = 1;
		}
		$rsvpdata->allowcancellation = $this->params->get("defaultcancellation", 0);
		$rsvpdata->allowchanges = $this->params->get("defaultchanges", 0);
		$rsvpdata->allinvites = 1;
		$rsvpdata->allrepeats = 1;
		$rsvpdata->showattendees = $this->params->get("defaultshowattendees", 0);
		$rsvpdata->hidenoninvitees = 0;
		$rsvpdata->capacity = 0;
		$rsvpdata->waitingcapacity = 0;
		$rsvpdata->invites = intval($this->params->get("defaultallowinvites", 0));
		$rsvpdata->template = "";
		$rsvpdata->attendintro = $this->params->get("defintro", "");

		$rsvpdata->message = $this->params->get("message", JText::_('JEV_DEFAULT_MESSAGE'));
		$rsvpdata->subject = $this->params->get("subject", JText::_('JEV_DEFAULT_SUBJECT'));
		$rsvpdata->allowreminders = $this->params->get("defaultallowreminders", 0);
		$rsvpdata->remindermessage = $this->params->get("remindermessage", JText::_('JEV_DEFAULT_REMINDER_MESSAGE'));
		$rsvpdata->remindersubject = $this->params->get("remindersubject", JText::_('JEV_DEFAULT_REMINDER_SUBJECT'));
		$rsvpdata->remindernotice = intval($this->params->get("reminderinterval", 24)) * 3600;
		$rsvpdata->remindallrepeats = 1;
		$rsvpdata->sessionaccess = -1;
		$rsvpdata->sessionaccessmessage = "";
		$rsvpdata->params = "";
		$rsvpdata->regclose = "";
		$rsvpdata->cancelclose = "";
		$rsvpdata->regopen = "";
		$rsvpdata->params = "";

		return $rsvpdata;

	}

	public function autoInvite($event)
	{
		if ($this->params->get("autoinvite", "") == "")
			return;

		$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id;
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$rsvpitem = $db->loadObject();

		if (!$rsvpitem)
			return;

		// check if this has already been processed.
		$db = JFactory::getDBO();
		$db->setQuery("SELECT count(at_id) FROM #__jev_invitees WHERE at_id=" . intval($rsvpitem->id));
		if (intval($db->loadResult()) != 0)
		{
			return;
		}

		$datamodel = new JEventsDataModel();
		$row = $datamodel->queryModel->getEventById($event->ev_id, 0, "icaldb");
		if (!$row)
		{
			return;
		}
		JRequest::setVar("jevattend_hiddeninitees", 1);
		JRequest::setVar("jevinvitee", explode(",", $this->params->get("autoinvite", "")));
		JRequest::setVar("rsvp_email", "email");

		$this->jevrinvitees = new JevRsvpInvitees($this->params, $this->jomsocial, $this->cbuilder, $this->groupjive);
		$this->jevrinvitees->updateInvitees($rsvpitem, $row, false);

		/*
		  if (is_callable("curl_exec")){
		  // I need the repeat id
		  $query = "SELECT  rpt.* FROM #__jevents_vevent as ev "
		  . "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid = ev.ev_id"
		  . "\n LEFT JOIN #__jevents_vevdetail as det ON det.evdet_id = rpt.eventdetail_id"
		  . "\n LEFT JOIN #__jevents_rrule as rr ON rr.eventid = ev.ev_id"
		  . "\n WHERE ev.ev_id = '".intval($rsvpitem->ev_id)."' ORDER BY rpt.startrepeat asc LIMIT 1" ;

		  $db->setQuery( $query );
		  $repeat = $db->loadObject();

		  if (is_null($repeat)) return;

		  $ch = curl_init();
		  $Itemid=JRequest::getInt("Itemid");
		  $url = JURI::root()."index.php?option=com_jevents&task=icalrepeat.detail&Itemid=$Itemid&tmpl=component&evid=".intval($repeat->rp_id);

		  $url .= '&start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';

		  curl_setopt($ch, CURLOPT_URL,$url);
		  curl_setopt($ch, CURLOPT_VERBOSE, 1);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS, 	"jevattend_hiddeninvitees=1&jevinvitee=".$this->params->get("autoinvite","") ."&rsvp_email=email");
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		  $this->rawData = curl_exec($ch);
		  curl_close ($ch);
		  }
		 */

	}

	public function autoRemind($event)
	{
		if ($this->params->get("autoremind", 0) < 3)
			return;

		JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
		$this->jevrreminders = new JevRsvpReminders($this->params, 0);

		$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id;
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$rsvpitem = $db->loadObject();

		if (!$rsvpitem)
			return;

		$this->jevrreminders->remindUsers($rsvpitem, $event, $this->params->get("autoremind", 0));

	}

	public function deleteReminders($event)
	{
		$sql = "SELECT * FROM #__jev_attendance WHERE ev_id=" . $event->ev_id;
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$sql = "DELETE FROM #__jev_reminders WHERE at_id=" . $rsvpdata->id;
		if ($rsvpdata->remindallrepeats == 0)
		{
			$sql .= " AND rp_id=" . $event->rp_id();
		}

		$db->setQuery($sql);
		$db->query();

	}

	/**
	 * Loads all necessary files for and creats popup calendar link
	 *
	 * @static
	 */
	static function loadCalendar11($fieldname, $fieldid, $value, $minyear, $maxyear, $onhidestart = "", $onchange = "", $format = 'Y-m-d')
	{
		$document = & JFactory::getDocument();
		$component = "com_jevents";
		$params = & JComponentHelper::getParams($component);
		$forcepopupcalendar = $params->get("forcepopupcalendar", 1);
		$offset = $params->get("com_starday", 1);
		$calendar = (JVersion::isCompatible("3.0")) ? 'calendar14.js' : 'calendar12.js';
		JEVHelper::script($calendar, "components/" . $component . "/assets/js/", true);
		JHtml::stylesheet("components/" . $component . "/assets/css/dashboard.css", true);
		$script = '
			var field' . $fieldid . '=false;
			window.addEvent(\'domready\', function() {
			if (field' . $fieldid . ') return;
			field' . $fieldid . '=true;
			new NewCalendar(
				{ ' . $fieldid . ' :  "' . $format . '"},
				{
				direction:0,
				classes: ["dashboard"],
				draggable:true,
				navigation:2,
				tweak:{x:0,y:-75},
				offset:' . $offset . ',
				range:{min:' . $minyear . ',max:' . $maxyear . '},
				readonly:' . $forcepopupcalendar . ',
				months:["' . JText::_("JEV_JANUARY") . '",
				"' . JText::_("JEV_FEBRUARY") . '",
				"' . JText::_("JEV_MARCH") . '",
				"' . JText::_("JEV_APRIL") . '",
				"' . JText::_("JEV_MAY") . '",
				"' . JText::_("JEV_JUNE") . '",
				"' . JText::_("JEV_JULY") . '",
				"' . JText::_("JEV_AUGUST") . '",
				"' . JText::_("JEV_SEPTEMBER") . '",
				"' . JText::_("JEV_OCTOBER") . '",
				"' . JText::_("JEV_NOVEMBER") . '",
				"' . JText::_("JEV_DECEMBER") . '"
				],
				days :["' . JText::_("JEV_SUNDAY") . '",
				"' . JText::_("JEV_MONDAY") . '",
				"' . JText::_("JEV_TUESDAY") . '",
				"' . JText::_("JEV_WEDNESDAY") . '",
				"' . JText::_("JEV_THURSDAY") . '",
				"' . JText::_("JEV_FRIDAY") . '",
				"' . JText::_("JEV_SATURDAY") . '"
				]
				';
		if ($onhidestart != "")
		{
			$script.=',
				onHideStart : function () { ' . $onhidestart . '; },
				onHideComplete :function () { ' . $onchange . '; }';
		}
		$script.='}
			);
		});';
		$document->addScriptDeclaration($script);
		if ($onchange != "")
		{
			$onchange = 'onchange="' . $onchange . '"';
		}
		echo '<input type="text" name="' . $fieldname . '" id="' . $fieldid . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" maxlength="10" ' . $onchange . ' size="12"  />';

	}

}