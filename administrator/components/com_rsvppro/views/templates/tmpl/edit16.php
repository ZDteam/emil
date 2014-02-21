<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: edit.php 1438 2009-05-02 09:25:42Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');


$editor = & JFactory::getEditor();
if ($this->item->id === 0)
	exit();

$editorfields = array();
?>		
<div class='jevrsvppro'>
	<div id="jevents">
		<form action="index.php" method="post" name="adminForm" id="adminForm">
			<table width="90%" border="0" cellpadding="2" cellspacing="2" class="adminform" >
				<tr>
					<td>
						<input type="hidden" name="customise" value="<?php echo JRequest::getInt("customise"); ?>" />
						<input type="hidden" name="cid[]" value="<?php echo (JRequest::getInt("customise") && $this->item->istemplate) ? 0 : $this->item->id; ?>" />
						<input type="hidden" name="id" id="id" value="<?php echo (JRequest::getInt("customise") && $this->item->istemplate) ? 0 : $this->item->id; ?>" />
						<input type="hidden" name="istemplate" id="istemplate" value="<?php echo JRequest::getInt("customise") ? 0 : 1; ?>" />
						<input type="hidden" name="evid" id="evid" value="<?php echo JRequest::getInt("evid"); ?>" />
						<input type="hidden" name="oldid" id="oldid" value="<?php echo $this->item->id; ?>" />

						<div class="adminform" align="left">
							<div style="margin-bottom:20px;">
								<table cellpadding="5" cellspacing="0" border="0" >
									<tr>
										<td align="left"><?php echo JText::_('RSVP_TEMPLATE_TITLE'); ?>:</td>
										<td >
											<input class="inputbox" type="text" name="title" size="50" maxlength="100" value="<?php echo htmlspecialchars($this->item->title, ENT_QUOTES, 'UTF-8'); ?>" />
										</td>
									</tr>

									<?php
									// if we already have attendees then offer to lock the template
									if ($this->hasAttendeesToLock)
									{
										?>
										<td align="left"><span class="editlinktip hasTip" style="font-weight:bold" title="<?php echo JText::_('RSVP_LOCK_ATTENDEE_SPECIFIC_TEMPLATES'); ?>::<?php echo JText::_('RSVP_LOCK_ATTENDEE_SPECIFIC_TEMPLATES_DESC');
									;
										?>"><?php echo JText::_('RSVP_LOCK_ATTENDEE_SPECIFIC_TEMPLATES'); ?></span></td>
										<td>
											<label><?php echo JText::_("JEV_YES"); ?><input class="inputbox" type="radio" name="lockattendees"  value="1"  /></label>
											<label><?php echo JText::_("JEV_NO"); ?><input class="inputbox" type="radio" name="lockattendees"  value="0"   checked='checked'  /></label>
										</td>
										</tr>
									<?php } ?>

									<?php if (!JRequest::getInt("customise"))
									{
										?>
										<tr>
											<td valign="top" align="left">
												<?php echo JText::_('RSVP_TEMPLATE_DESCRIPTION'); ?>
											</td>
											<td style="width:600px;">
												<?php
												$editorfields[]="description";
												// parameters : areaname, content, hidden field, width, height, rows, cols
												echo $editor->display('description', htmlspecialchars($this->item->description, ENT_QUOTES, 'UTF-8'), "100%", 150, '70', '10', false);
												?>                    
											</td>
										</tr>
<?php } ?>
									<tr>
										<td align="left"><?php echo JText::_('RSVP_HAS_FEES'); ?>:</td>
										<td >
											<label><?php echo JText::_("JEV_YES"); ?><input class="inputbox" type="radio" name="withfees"  value="1"  <?php echo $this->item->withfees ? "checked='checked'" : ""; ?> onclick="rsvppro.hasFees(this)" /></label>
											<label><?php echo JText::_("JEV_NO"); ?><input class="inputbox" type="radio" name="withfees"  value="0"   <?php echo!$this->item->withfees ? "checked='checked'" : ""; ?> onclick="rsvppro.hasFees(this)" /></label>
										</td>
									</tr>
									<tr>
										<td align="left"><?php echo JText::_('RSVP_HAS_TICKETS'); ?>:</td>
										<td >
											<label><?php echo JText::_("JEV_YES"); ?><input class="inputbox" type="radio" name="withticket"  value="1"  <?php echo $this->item->withticket ? "checked='checked'" : ""; ?> onclick="rsvppro.hasTicket(this)" /></label>
											<label><?php echo JText::_("JEV_NO"); ?><input class="inputbox" type="radio" name="withticket"  value="0"   <?php echo!$this->item->withticket ? "checked='checked'" : ""; ?> onclick="rsvppro.hasTicket(this)" /></label>
										</td>
									</tr>
<?php if (!JRequest::getInt("customise"))
{
	?>
										<tr>
											<td valign="top" align="right" class="key">
	<?php echo JText::_('RSVP_GLOBAL_TEMPLATE'); ?>:
											</td>
											<td>
												<label><?php echo JText::_("JEV_YES"); ?><input class="inputbox" type="radio" name="global"  value="1"  <?php echo $this->item->global ? "checked='checked'" : ""; ?> /></label>
												<label><?php echo JText::_("JEV_NO"); ?><input class="inputbox" type="radio" name="global"  value="0"   <?php echo!$this->item->global ? "checked='checked'" : ""; ?> /></label>
											</td>
										</tr>
							<?php } ?>
								</table>
							</div>
							<?php
							// sesssion options
							ob_start();
							$groups = $this->params->getFieldsets();
							foreach ($groups as $group => $element)
							{
								$count = $this->params->getFieldCountByFieldSet($group);
								if ($group == "RSVP_SESSION_OPTIONS" && $count > 0)
								{
									?>
									<ul class="adminformlist">								
									<?php
									unset($customfields);
									$customfields = array();
									$this->params->render('params', $group, $customfields);
									foreach ($customfields as $cf)
									{
										echo "<li>". $cf["label"] .  "<div class='forminput'>".$cf["input"] . "</div></li>";
									}
									?>
									</ul>
									<?php
								}
							}
							$sessionoptions = ob_get_clean();
							if ($sessionoptions != "")
							{
								?>
								<fieldset class='jevmessages'>
									<legend class="toggleEnlarge">
	<?php echo JText::_('RSVP_SESSION_OPTIONS'); ?><span class="spanclosed"> [+]</span><span class="spanopen"> [-]</span>
									</legend>
									<div class="largeDetail">				
										<div class="largeDetailContent">
										</div>
									</div>
								</fieldset>
									<?php } ?>

							<fieldset class='jevconfig <?php echo $this->item->withfees ? "" : "jevconfighidden"; ?>'>
								<legend class="toggleEnlarge">
										<?php echo JText::_('RSVP_CONFIG_PAYMENTS'); ?><span class="spanclosed"> [+]</span><span class="spanopen"> [-]</span>
								</legend>
								<div class="largeDetail">				
									<div class="largeDetailContent">
										<?php
										$groups = $this->params->getFieldsets();
										if (count($groups) > 0)
										{
											jimport('joomla.html.pane');
											$tabs = & JPane::getInstance('tabs');
											echo $tabs->startPane('configs');
											foreach ($groups as $group => $element)
											{
												$count = $this->params->getFieldCountByFieldSet($group);
												if ($group != "RSVP_ATTENDANCE_MESSAGES" && $group != "RSVP_SESSION_OPTIONS" && $count > 0)
												{
													echo $tabs->startPanel(JText::_($group), 'config_' . str_replace(" ", "_", $group));
													?>
													<ul class="adminformlist">								
													<?php													
													unset($customfields);
													$customfields = array();
													$this->params->render('params', $group, $customfields);
													foreach ($customfields as $cf)
													{
														echo "<li>". $cf["label"] .  "<div class='forminput'>".$cf["input"] . "</div></li>";
														if ($cf["type"]=="Editor"){
															$editorfields[] = $cf["name"];
														}
													}
													?>
													</ul >
													<?php													
													echo $tabs->endPanel();
												}
											}

											echo $tabs->endPane();
										}
										else
										{
											echo $this->params->render();
										}
										?>
									</div>
								</div>
							</fieldset>

							<fieldset class='jevticket <?php echo $this->item->withticket ? "" : "jevtickethidden"; ?>'>
								<legend class="toggleEnlarge">
										<?php echo JText::_('RSVP_TICKET'); ?><span class="spanclosed"> [+]</span><span class="spanopen"> [-]</span>
								</legend>
								<div class="largeDetail">
									<div class="largeDetailContent">
<?php
$editorfields[]="ticket";
echo $editor->display('ticket', htmlspecialchars($this->item->ticket, ENT_QUOTES, 'UTF-8'), 500, 150, '70', '10', false);
?>
										<h4><?php echo JText::_("RSVP_SELECT_FIELD_TO_INSERT"); ?> : </h4>
										<select onchange="ticketsEditorPlugin.insert('ticket','ticketfields' )" id="ticketfields">
											<option value="Select ...:">Select ...</option>
											<optgroup label="<?php echo JText::_("RSVP_EVENT_FIELDS", true); ?>" >
												<option value="EVENT"><?php echo JText::_("RSVP_EVENT_TITLE"); ?></option>
												<option value="DATE}%Y %m %d{/DATE"><?php echo JText::_("RSVP_EVENT_DATE"); ?></option>
												<option value="LOCATION"><?php echo JText::_("RSVP_EVENT_LOCATION"); ?></option>						
												<option value="LINK"><?php echo JText::_("RSVP_EVENT_LINK"); ?></option>						
												<option value="TOTALFEES"><?php echo JText::_("RSVP_TOTALFEES"); ?></option>						
												<option value="FEESPAID"><?php echo JText::_("RSVP_FEESPAID"); ?></option>						
												<option value="BALANCE"><?php echo JText::_("RSVP_BALANCE"); ?></option>						
												<option value="CREATOR"><?php echo JText::_("RSVP_EVENT_CREATOR"); ?></option>
												<option value="REGDATE}%Y-%m-%d{/REGDATE"><?php echo JText::_("RSVP_EVENT_REGISTRATION_DATE"); ?></option>						
												<option value="REGID}Ticket Number : %s{/REGID"><?php echo JText::_("RSVP_EVENT_REGISTRATION_ID"); ?></option>						
												<option value="GUESTNUM}Guest Number : %s{/GUESTNUM"><?php echo JText::_("RSVP_EVENT_GUEST_NUMBER"); ?></option>
												<option value="REGEMAIL}%s{/REGEMAIL"><?php echo JText::_("RSVP_EVENT_REGISTRATION_EMAIL"); ?></option>
												<!--<option value="BARCODE"><?php echo JText::_("RSVP_EVENT_BARCODE"); ?></option>/-->
												<option value="CUSTOM"><?php echo JText::_("RSVP_EVENT_CUSTOMFIELD_SUMMARY"); ?></option>						
												<option value="REPEATSUMMARY"><?php echo JText::_("RSVP_EVENT_REPEATSUMMARY"); ?></option>						
											</optgroup>
											<optgroup label="<?php echo JText::_("RSVP_TEMPLATE_FIELDS", true); ?>" class="templatefields">
											</optgroup>
										</select>
										<table cellpadding="5" cellspacing="0" border="0" >
											<?php
											$ticketparams = $this->templateparams->getValue("whentickets");
											if ($ticketparams == "")
											{
												echo "<tr><td><h3 class='error'>" . JText::_('You have not selected when to offer the tickets!') ."</h3></td></tr>";
												$ticketparams = array("paidnochangecancel");
											}
											?>
											<tr>
												<td align="left"><h4><?php echo JText::_('RSVP_WHEN_OFFER_TICKETS'); ?>:</h4></td>
											</tr>
											<tr>
												<td >							
													<label><input class="inputbox" type="checkbox" name="params[whentickets][]"  value="cancancel"  <?php echo in_array("cancancel", $ticketparams) ? "checked='checked'" : ""; ?> /><?php echo JText::_("RSVP_WHEN_CAN_STILL_BE_CANCELLED"); ?></label><br/>
													<label><input class="inputbox" type="checkbox" name="params[whentickets][]"  value="canchange"  <?php echo in_array("canchange", $ticketparams) ? "checked='checked'" : ""; ?> /> <?php echo JText::_("RSVP_WHEN_CAN_STILL_BE_CHANGED"); ?></label><br/>
													<label><input class="inputbox" type="checkbox" name="params[whentickets][]"  value="outstandingbalance"   <?php echo in_array("outstandingbalance", $ticketparams) ? "checked='checked'" : ""; ?> /> <?php echo JText::_("RSVP_WITH_OUTSTANDING_BALANCE"); ?></label>
												</td>
											</tr>
										</table>

									</div>
								</div>
							</fieldset>

							<fieldset class="jevtemplatefields">
								<legend class="toggleEnlarge">
<?php echo JText::_('RSVP_TEMPLATE_FIELDS'); ?><span class="spanclosed"> [+]</span><span class="spanopen"> [-]</span>
								</legend>
								<div class="largeDetail">
									<div class="largeDetailContent">				
										<h3><?php echo JText::_('RSVP_Add_field'); ?></h3>
										<div id="jevtemplate_fields">
											<?php
											jimport("joomla.filesystem.folder");
											$templates = JFolder::files(RSVP_ADMINPATH . "/fields/", ".php");
											$options = array();
											$freeoptions = array();
											$value = false;
											foreach ($templates as $template)
											{
												$type = str_replace(".php", "", $template);
												if (strpos($type, "JevrElement") === 0 || strpos($type, "jevr") !== 0)
													continue;
												if (!$value)
													$value = $type;
												include_once(RSVP_ADMINPATH . "/fields/" . $template);
												if (method_exists("JFormField" . ucfirst($type), "isEnabled"))
												{
													if (!call_user_func(array("JFormField" . ucfirst($type), "isEnabled")))
													{
														continue;
													}
												}
												call_user_func(array("JFormField" . ucfirst($type), "loadScript"));
												$label = JText::_('RSVP_TEMPLATE_TYPE_' . $type);
												if ($label == 'RSVP_TEMPLATE_TYPE_' . $type && method_exists("JFormField" . ucfirst($type), "fieldName"))
												{
													$label = call_user_func(array("JFormField" . ucfirst($type), "fieldName"));
												}
												$options[] = JHtml::_('select.option', $type, $label);
												if (!method_exists("JFormField" . ucfirst($type), "paidOption") || !call_user_func(array("JFormField" . ucfirst($type), "paidOption")))
												{
													$freeoptions[] = JHtml::_('select.option', $type, $label);
												}
											}
											RsvpHelper::getFieldScript();

											$freelist = JHtml::_('select.genericlist', $freeoptions, 'templatetype', 'class="rsvptemplatetype" size="1" ', 'value', 'text', $value, "templatetype");
											$fulllist = JHtml::_('select.genericlist', $options, 'templatetype', 'class="rsvptemplatetype" size="1" ', 'value', 'text', $value, "templatetype");
											echo $this->item->withfees ? $fulllist : $freelist;
											?>
											<script type="text/javascript" >
												var freelist = <?php echo json_encode($freelist); ?>;
												var fulllist = <?php echo json_encode($fulllist); ?>;
											</script>
										</div>
										<input id="newFieldButton" type="button" value="<?php echo JText::_("RSVP_CREATE_FIELD"); ?>"/>
										<input id="deleteFieldButton" type="button" value="<?php echo JText::_("RSVP_DELETE_FIELD"); ?>" style='display:none' class="deleteFieldButton"/>
										<input id="closeFieldButton" type="button" value="<?php echo JText::_("RSVP_CLOSE_FIELD"); ?>" style='display:none' class="closeFieldButton"/>
										<div id="rsvpfields">
											<?php
											foreach ($this->item->fields as $field)
											{
												$fieldhtml = $field->html;
												?>
												<div class='rsvpfield' id='field<?php echo $field->field_id; ?>'>
													<input id="deleteFieldButtonfield<?php echo $field->field_id; ?>" type="button" value="<?php echo JText::_("RSVP_DELETE_FIELD"); ?>" class="deleteFieldButton"/>
													<input id="closeFieldButtonfield<?php echo $field->field_id; ?>" type="button" value="<?php echo JText::_("RSVP_CLOSE_FIELD"); ?>" class="closeFieldButton"/>
												<?php
												echo $fieldhtml;
												?>
												</div>
	<?php
}
?>
										</div>
									</div>
								</div>
							</fieldset>

							<fieldset class='jevmessages'>
								<legend class="toggleEnlarge">
										<?php echo JText::_('RSVP_CUSTOM_MESSAGES'); ?><span class="spanclosed"> [+]</span><span class="spanopen"> [-]</span>
								</legend>
								<div class="largeDetail">				
									<div class="largeDetailContent">
										<?php
										$groups = $this->params->getFieldsets();
										foreach ($groups as $group => $element)
										{
											$count = $this->params->getFieldCountByFieldSet($group);
											if ($group == "RSVP_ATTENDANCE_MESSAGES" && $count > 0)
											{
												?>
												<ul class="adminformlist">
												<?php										
												unset($customfields);
												$customfields = array();
												$this->params->render('params', $group, $customfields);
												foreach ($customfields as $cf)
												{
													echo "<li>". $cf["label"] .  "<div class='forminput'>".$cf["input"] . "</div></li>";
													if ($cf["type"]=="Editor"){
														$editorfields[] = $cf["name"];
													}													
												}
												?>
												</ul>
												<?php										
											}
										}
										?>
										</ul>
									</div>
								</div>
							</fieldset>

						</div>
					</td>
				</tr>  
			</table>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="defaults.edit" />
			<input type="hidden" name="act" value="" />
			<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
			<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid", 0); ?>" />
			<?php if (JRequest::getString("tmpl") == "component")
			{
				?>
				<input type="hidden" name="tmpl" value="component" />
<?php } ?>
		</form>
		<script type="text/javascript" >
			Joomla.submitbutton = function (pressbutton) {
				if (pressbutton == 'cancel' || pressbutton == 'templates.cancel') {
					Joomla.submitform( pressbutton , document.adminForm);
					return;
				}
				var form = document.adminForm;
<?php
foreach ($editorfields as $editorfield){
	echo $editor->save($editorfield)."\n";	
}
?>
						// do field validation
						if (form.title.value == "") {
							alert ( "<?php echo html_entity_decode(JText::_('Missing Title')); ?>" );
						}
						else {
							Joomla.submitform(pressbutton, document.adminForm);
						}
					}
		</script>
	</div>
</div>

<?php
// make sure the form isn't too big!'
$max_input_vars = intval(@ini_get("max_input_vars"));
if ($max_input_vars == 0){
	$max_input_vars = 999999;
}
?>
<script type="text/javascript">
	var inputvars = $('adminForm').getElements('input');
	var selectvars = $('adminForm').getElements('select');
	var textareavars = $('adminForm').getElements('textarea');
	if(inputvars.length+ selectvars.length+textareavars.length > <?php echo  $max_input_vars;?> * 0.90 ){
		alert("<?php echo JText::_("RSVP_FORM_GETTING_CLOSE_TO_MAXIMUM_SIZE_CHECK_HTACESS_SETTINGS", true)?>\n"+(inputvars.length+ selectvars.length+textareavars.length)+"  vs " + <?php echo  $max_input_vars;?>);
	}
</script>