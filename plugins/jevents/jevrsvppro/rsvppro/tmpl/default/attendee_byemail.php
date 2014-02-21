<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$user = JFactory::getUser();
$html = "";
$this->checkemail = "";
// if not logged in and allowing email based attendence then put in the input box
if ($this->params->get("attendemails", 0) && $user->id == 0)
{
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress));
	$this->checkemail = "if (document.getElementById('jevattend_email').value=='') {alert('" . JText::_("JEV_MISSING_EMAIL", true) . "');return false};";
	$html .= '
				<div class="jevattend_email type0param" >
				<label for="jevattend_email">' . JText::_( 'JEV_ATTEND_EMAIL' ) . JText::_("JEV_REQUIRED") .'</label>
				<input type="text" name="jevattend_email" id="jevattend_email" value="' . $this->emailaddress . '" size="50" onchange="return false;" />
				</div>';
	if ($this->emailaddress != "")
	{
		$html .= '<input type="hidden" name="em" id="em" value="' . $code . '" />';
	}
			
	// if a link from an invitation email then skip the need to confirm.
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress."invited"));
	if (JRequest::getString("em2","")==$code){
		$html .= '<input type="hidden" name="em2" id="em2" value="' . $code . '" />';
	}
	$registry = & JRegistry::getInstance("jevents");
	$registry->set("showingemailaddress", true);
}

echo $html;