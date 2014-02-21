<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

$user=JFactory::getUser();
$mainframe = JFactory::getApplication();$Itemid=JRequest::getInt("Itemid");
$client = $mainframe->isAdmin()?"administrator":"site";
$html = "";
if ($user->id==$this->row->created_by() || JEVHelper::isAdminUser($user)){

	JHtml::_('behavior.modal');
	JHtml::script( 'plugins/jevents/jevrsvppro/rsvppro/rsvp.js' );
	JHtml::stylesheet('plugins/jevents/jevrsvppro/rsvppro/rsvp.css');

	$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';

	$script = "var urlroot = '".JURI::root()."';\n";
	$script .= "var jsontoken = '".JSession::getFormToken()."';\n";

	$document = JFactory::getDocument();
	$document->addScriptDeclaration($script);

	$anyfailed = false;
	foreach ($this->invitees as $invitee) {
		if ($invitee->sentmessage == 0 ){
			$anyfailed = true;
			break;
		}
	}

	if ($this->jomsocial){
		$html = '<div class="cModule jevattendees"><h3><span>'.JText::_("JEV_INVITEES").'</span></h3>';
	}
	else {
		$html =" <h3>".JText::_("JEV_INVITEES")."</h3>";
	}

	$html .='
	<div class="button2-left"   style="margin-right:10px;">
		<div class="blank">
			<a href="#'.JText::_("JEV_ADD_INVITEES").'" onclick="addInvitees();return false;"  title="'.JText::_("JEV_ADD_INVITEES").'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_ADD_INVITEES").'</a>
		</div>
	</div>	
	';
	if ($this->jomsocial){
		$html .='
		<div class="button2-left"   style="margin-right:10px;">
			<div class="blank">
				<a href="#'.JText::_("JEV_INVITE_FRIENDS").'" onclick="inviteFriends(\''.JURI::root().$pluginpath."invitefriends.php".'\', \''.$client.'\');return false;"  title="'.JText::_("JEV_INVITE_FRIENDS").'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_INVITE_FRIENDS").'</a>
			</div>
		</div>
		';

		if ($this->jsgroups && count($this->jsgroups)>0){

			$html .='
			<div class="button2-left"   style="margin-right:10px;">
				<div class="blank">
					<a href="#'.JText::_("JEV_INVITE_GROUP_JS").'" onclick="addInvitees();document.getElementById(\'jsgroups\').style.display=\'block\' ;return false;"  title="'.JText::_("JEV_INVITE_GROUP_JS").'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_INVITE_GROUP_JS").'</a>
				</div>
			</div>
			';

			$html .= "<div id='jsgroups' style='display:none'>";

			$html .= "<select name='custom_jevuser[]' id='custom_jevuser_jsgroupselection' size='".(count($this->jsgroups)<6?count($this->jsgroups)+1:6)."' onchange='inviteJSGroup(\"".JURI::root().$pluginpath."invitefriends.php"."\",this.options[this.selectedIndex].value)', \"$client\")'>";
			$html .= "<option value='NONE' selected='selected'>".JText::_("JEV_SELECT_GROUP")."</option>";
			foreach ($this->jsgroups as $group) {
				$html .= "<option value='invitationgroup_$group->id'>$group->name</option>";
			}
			$html .= "</select>";
			$html .= "</div>";

		}

	}

	if ($this->invitelists && count($this->invitelists)>0){
		$html .='
			<div class="button2-left"   style="margin-right:10px;">
				<div class="blank">
					<a href="#'.JText::_("JEV_LOAD_LIST").'" onclick="addInvitees();document.getElementById(\'listid\').style.display=\'block\'" ;return false;"  title="'.JText::_("JEV_LOAD_LIST").'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_LOAD_LIST").'</a>
				</div>
			</div>
			';

		$html .= "<div id='listid' style='display:none'>";

		$html .= "<select name='custom_jevuser[]' id='custom_jevuser_jsgroupselection' size='".(count($this->invitelists)<6?count($this->invitelists)+1:6)."' onchange='inviteList(\"".JURI::root().$pluginpath."invitefriends.php"."\",this.options[this.selectedIndex].value, \"".$client."\")'>";
		$html .= "<option value='NONE' selected='selected'>".JText::_("JEV_SELECT_LIST")."</option>";
		foreach ($this->invitelists as $list) {
			$html .= "<option value='invitationlist_$list->id'>$list->listname</option>";
		}
		$html .= "</select>";
		$html .= "</div>";

	}

	$html .='
	<div style="clear:both"></div>	
	<div id="jev_name"  style="display:none" >
		<div class="jevusername">'.JText::_("JEV_INVITEE_SEARCH").'
		<input type="text"	name="jev_name" onchange="findUser(event,this,\''.JURI::root().$pluginpath."finduser.php".'\', \''.$client.'\')" onkeyup="findUser(event,this,\''.JURI::root().$pluginpath."finduser.php".'\', \''.$client.'\')" size="30"  />
		</div>
	</div>
	<input type="hidden" id="rsvp_evid"  value="'.$this->row->ev_id().'" />
	<div style="clear:both"></div>	
	<table cellspacing="0" cellpadding="0" border="0" style="margin-top:3px;">
	<tr>
	   <th class="jevcol1" style="display:none;" >'.JText::_("JEV_POTENTIAL_INVITEES").'</th>
	   <th >'.JText::_("JEV_CURRENT_INVITEES").'</th>
	</tr>
	<tr>	   
		<td valign="top"  class="jevcol1" style="display:none;padding-right:20px;">
			<div id="rsvpclicktoinvite" style="display:none;font-weight:bold">
				'.JText::_("JEV_CLICK_TO_INVITE").'
				<div class="button2-left" id="rsvpinviteall" style="float:right">
					<div class="blank">
						<a href="#'.JText::_("JEV_INVITE_ALL").'" onclick="inviteAll();return false;"  title="'.htmlspecialchars(JText::_("JEV_INVITE_ALL")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_INVITE_ALL").'</a>
					</div>
				</div>	
			</div>
<!--
			<select name="rsvpmatches" id="rsvpmatches" size="10" multiple="multiple" >
			</select>
			//-->
			<div id="rsvpmatches"></div>
		</td>
		<td  valign="top" >
			<div >
				<div class="button2-left" id="rsvpupdateinvites" style="display:none;">
					<div class="blank">
						<a href="#'.JText::_("JEV_CLICK_TO_UPDATE_INVITEES").'" onclick="updateInvitees(this);return false;"  title="'.htmlspecialchars(JText::_("JEV_CLICK_TO_UPDATE_INVITEES")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_CLICK_TO_UPDATE_INVITEES").'</a>
					</div>
				</div>	

				<div class="button2-left" id="rsvpemailinvites"  style="display:none;">
					<div class="blank">
						<a href="#'.JText::_("JEV_CLICK_TO_EMAIL_NEW_INVITEES").'" onclick="emailInvitees(this);return false;"  title="'.htmlspecialchars(JText::_("JEV_CLICK_TO_EMAIL_NEW_INVITEES")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_CLICK_TO_EMAIL_NEW_INVITEES").'</a>
					</div>
				</div>	

				<div class="button2-left" id="rsvpreemailinvites" '.(count($this->invitees)==0?'style="display:none"':'style="display:inline"').'>
					<div class="blank">
						<a href="#'.JText::_("JEV_CLICK_TO_RESEND_EMAIL_INVITATIONS").'" onclick="reemailInvitees(this);return false;"  title="'.htmlspecialchars(JText::_("JEV_CLICK_TO_RESEND_EMAIL_INVITATIONS")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_CLICK_TO_RESEND_EMAIL_INVITATIONS").'</a>
					</div>
				</div>	

				<div class="button2-left" id="rsvpsendfailed" '.($anyfailed?'style="display:inline;"':'style="display:none"').'>
					<div class="blank">
						<a href="#'.JText::_("JEV_CLICK_TO_RESEND_FAILED_MESSAGES").'" onclick="resendFailed(this);return false;"  title="'.htmlspecialchars(JText::_("JEV_CLICK_TO_RESEND_FAILED_MESSAGES")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_CLICK_TO_RESEND_FAILED_MESSAGES").'</a>
					</div>
				</div>
			</div>
			<div style="clear:both"></div>	
			';

	if ($mainframe->isAdmin()){
		$repeating = JRequest::getInt("repeating",0);
		$atd_id = JRequest::getVar("atd_id","post","array");
		if (!isset($atd_id[0]) || strpos($atd_id[0],"|")===false){
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|",$atd_id[0]);

		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$link = "index.php?option=com_rsvppro&task=invitees.overview&atd_id[]=$atd_id|$rp_id&repeating=$repeating";
	}
	else {
		list($year,$month,$day) = JEVHelper::getYMD();
		$link = $this->row->viewDetailLink($year,$month,$day,true, $Itemid);
	}

	$html .='
				<form action="'.$link.'"  method="post" name="updateinvitees" >
			    <input type="hidden" name="jevrsvp_listid" id="jevrsvp_listid" value="" />    
			    <input type="hidden" name="jevattend_hiddeninitees" value="1" />    
				<input type="hidden" id="rsvp_email"  name="rsvp_email" value="0" />

				<table cellspacing="0" cellpadding="0" border="0" id="invitetable" '.(count($this->invitees)==0?'style="display:none"':'').'>
					<tr  valign="top">
					   <th>'.JText::_("JEV_INVITEE").'</th>
					   <th>'.JText::_("JEV_CLICK_TO_REMOVE").'</th>
					   <th>'.JText::_("JEV_EMAIL_SENT").'</th>
					   <th>'.JText::_("JEV_EVENT_VIEWED").'</th>
					   <th>'.JText::_("JEV_ATTENDING_EVENT").'</th>
					   </tr>
				';
	foreach ($this->invitees as $invitee) {
		if ($invitee->user_id>0){
			$inviteeid='rsvp_inv_'.$invitee->user_id;
			$inviteevalue=$inviteeid;
			$label = $invitee->name.' ('.$invitee->username.')';
		}
		else if ($invitee->email_address!="") {
			$inviteeid='rsvp_inv_'.$invitee->email_name."{".$invitee->email_address."}";
			$inviteevalue=$inviteeid;
			$label = $invitee->email_name.' {'.$invitee->email_address.'}';
		}
		else continue;

		$html .='
					<tr  valign="top">
						<td >'.$label.'
						<input type="hidden" name="jevinvitee[]" id="'.$inviteeid.'" value="'.$inviteeid.'" />
						</td>
						<td align="center">
						<img src="'.JURI::root().$pluginpath.'assets/Trash.png" onclick="cancelInvite(this);" style="height:16px;cursor:pointer" />
						</td>
						<td align="center">
						<img src="'.JURI::root().$pluginpath.'assets/'.($invitee->sentmessage?'Tick.png':'Cross.png').'" style="height:16px;" />
						</td>
						<td align="center">';
		// can only track "viewed" for registered users at present
		//if ($invitee->user_id>0){
		$html .='
						<img src="'.JURI::root().$pluginpath.'assets/'.($invitee->viewedevent?'Tick.png':'Cross.png').'" style="height:16px;" />';
		//}

		if (is_null($invitee->attendstate)){
			$html .='
							</td>
							<td align="center">
							</td>
						</tr>
					';
		}
		else {
			$images = array ("Cross.png","Tick.png","Question.png","Pending.png", "MoneyBag.png", "RedMoneyBag.png");
			$img =  $images[$invitee->attendstate];

			$html .='
							</td>
							<td align="center">
							<img src="'.JURI::root().$pluginpath.'assets/'.$img.'" style="height:16px;" />
							</td>
						</tr>
					';
		}
	}
	$html .='
				</table>
				</form>
				';

	$html .='
			<div id="saveinvitees" '.(count($this->invitees)==0?'style="display:none"':'style="display:block"').'>
				<label for="inviteelistname" >'.JText::_("Save List as").' '.'	
					<input id="inviteelistname" name="inviteelistname" type="text" />
				</label>
				<div class="button2-left" >
					<div class="blank">
						<a href="#'.JText::_("JEV_CLICK_TO_SAVE_INVITEE_LIST").'" onclick="saveInvitees(this);return false;"  title="'.htmlspecialchars(JText::_("JEV_CLICK_TO_SAVE_INVITEE_LIST")).'"  style="padding:0px 5px;text-decoration:none">'.JText::_("JEV_CLICK_TO_SAVE_INVITEE_LIST").'</a>
					</div>
				</div>
			</div>
			<div style="clear:both"></div>	

		</td>
	</tr>
	</table>
	
				';

	if ($this->params->get("inviteemails",0)){
		$html .='
	<div id="jev_email"  style="display:none" >
		<div class="jevemailadd">'.JText::_("JEV_INVITEE_EMAIL_ADD").'</div>
		<div class="jevemailname"><span>'.JText::_("JEV_INVITEE_NAME").'</span><input type="text"	name="jev_emailname" id="jev_emailname" size="30" maxlength="250" /></div>
		<div class="jevemailaddress"><span>'.JText::_("JEV_INVITEE_ADDRESS").'</span><input type="text"	name="jev_emailaddress" id="jev_emailaddress" size="30" maxlength="250" /></div>
		<br/><br/><input type="button" onclick="addEmailInvitee();" value="'.JText::_("JEV_INVITEE_ADD").'" />&nbsp;
	</div>
			';
	}
	if ($this->jomsocial){
		$html.='</div>';
	}
}

echo $html;
?>
<form action="index.php" method="post" name="adminForm" >
  <input type="hidden" name="task" value="invitses.overview" />
  <input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid",0); ?>" />
</form>

