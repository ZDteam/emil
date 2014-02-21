<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

$Itemid=JRequest::getInt("Itemid");
$rp_id = intval($this->row->rp_id());
$atd_id = intval($this->rsvpdata->id);
$link = "index.php?option=com_rsvppro&task=attendees.record&at_id=$atd_id&rp_id=$rp_id&Itemid=$Itemid";
if (JRequest::getCmd("tmpl","")=="component"){
	$link .= "&tmpl=component";
}

// will  need this for tickets
if (!isset($this->templateInfo )) {
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) &&  $xmlfile>0){
		$db = JFactory::getDbo();
		$db->setQuery("Select * from #__jev_rsvp_templates where id=" . intval($xmlfile));
		$this->templateInfo = $db->loadObject();
		if ($this->templateInfo){
			$this->templateParams = $this->templateInfo->params;
			$this->templateParams = json_decode($this->templateParams);
		}
		else {
			$this->templateParams= false;
		}		
	}	
	else {
		$this->templateParams= false;
	}
}

// Do we need the email address security code?
if ($this->emailaddress!=""){
	$code = base64_encode($this->emailaddress.":".md5($this->params->get("emailkey","email key").$this->emailaddress));
	$link = $link."&em=".$code;
}
$link = JRoute::_($link, false);
$this->assign("link",$link);

$html ='
<form action="'.$link.'"  method="post"  name="updateattendance"  enctype="multipart/form-data" >';
$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
$html .=' 
<input type="text" name="jevattend_hidden" value="1"  style="display:none"/>
<input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
<input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
<input type="hidden" name="tmpl" value="component" />
</form>';

echo $html;
