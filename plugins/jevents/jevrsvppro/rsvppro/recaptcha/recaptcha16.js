

function checkRecaptcha(form){
	
	var requestObject = new Object();
	
	requestObject.challengeField =  form.recaptcha_challenge_field.value;
	requestObject.responseField =  form.recaptcha_response_field.value;
	requestObject.error = false;

	url = recaptchaurlroot + "json.recaptcha.php";
	
	var success=0;
	var jSonRequest = new Request.JSON({
		'url':url,
		// Must NOT be asynchronous
		'async':false,		
		onSuccess: function(json){
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
	}).get({'json':JSON.encode(requestObject)});

	if (!success) {
		return false;
	}
	return true;
}