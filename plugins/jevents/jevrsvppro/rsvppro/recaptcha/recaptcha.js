
var urlroot = "";

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
	
	var requestObject = new Object();
	//requestObject.challengeField =  Recaptcha.get_challenge();
	//requestObject.responseField =  Recaptcha.get_respose();
	
	requestObject.challengeField =  document.adminForm.recaptcha_challenge_field.value;
	requestObject.responseField =  document.adminForm.recaptcha_response_field.value;
	requestObject.error = false;

	url = urlroot + "json.recaptcha.php";
	var success=0;
	var jSonRequest = new Json.Remote(url, {
		method:'get',
		// Must NOT be asynchronous
		'async':false,		
		onComplete: function(json){
			if (json.error){
				try {
					Recaptcha.reload();
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				if(json.result == "success"){
					success=1;					
					if (json.secretcaptcha && $("secretcaptcha")){
						$("secretcaptcha").value = json.secretcaptcha;
					}
				}
			}
		},
		onFailure: function(){
			alert('Something went wrong with recaptcha...')			
		}
	}).send(requestObject);

	if (!success) {
		return false;
	}
	return true;
}