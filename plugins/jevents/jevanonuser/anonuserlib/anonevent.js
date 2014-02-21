
var anonurlroot = "";

function submitform(pressbutton){

	if (!(pressbutton == 'icalevent.save' || pressbutton == 'icalevent.apply')) {
		document.adminForm.task.value = pressbutton;
		document.adminForm.submit();
		return true;
	}

	if (document.adminForm.custom_anonusername.value=="" ||  document.adminForm.custom_anonemail.value=="") {
		alert(missingnameoremail);
		return false;
	}
	
	document.adminForm.task.value = pressbutton;
	document.adminForm.submit();

}
// 6Lc5WsISAAAAABDJoU2hdM-5A3KU8q-Eski55Hp5 