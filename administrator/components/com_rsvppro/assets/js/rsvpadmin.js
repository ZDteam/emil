var JevRsvpLanguage = {
	strings: new Object(),
	translate:function(val){
		if (val in JevRsvpLanguage.strings){
			return JevRsvpLanguage.strings[val];
		}
		else {
			return "?? "+val+" ??";
		}
	}
}

var rsvpjsonactive = false;
var cancelSearch = true;
var rsvptimeout=false;
var ignoreSearch=false;

function findUser(e,elem, url){

	if (ignoreSearch) return;
	var key = 0;
	if (window.event){
		key = e.keyCode;
	}
	else if (e.which){
		key = e.which;
	}
	if (elem.value.length == 0 || key==8 || key==46){
		// clearing
		rsvpClearMatches();
		currentSearch = "";
		return;
	}

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.client = jsonclient;	
	requestObject.task = "checkTitle";
	requestObject.title = elem.value;
	requestObject.ev_id = document.updateattendance?document.updateattendance.ev_id.value : 0;

	minlength=2;

	if (elem.value.length>=minlength || elem.value=="*"){
		if (rsvpjsonactive) return;

		currentSearch = elem.value;

		if (rsvptimeout) {
			clearTimeout(rsvptimeout);
		}

		rsvpjsonactive = true;
		var jSonRequest = new Request.JSON({
			'url':url,
			onSuccess: function(json, responsetext){
				cancelSearch = false;
				rsvpjsonactive = false;
				if (json.error){
					try {
						eval(json.error);
					}
					catch (e){
						alert('could not process error handler');
					}
				}
				else {
					// If have started another search already then cancel this one
					if (cancelSearch) {
						return;
					}
					var rsvpmatches = document.getElement("#rsvpmatches");
					//alert(json.timing);
					if (json.titles.length>0){
						rsvpClearMatches();
						for (var jp=0;jp<json.titles.length;jp++){
							// If have started another search already then cancel this one
							if (cancelSearch) {
								return;
							}
							var option = new Element('div', {id:"rsvp_pot_"+json.titles[jp]["id"], 'style':'margin:0px;padding:2px;cursor:pointer;'});
							option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");
							option.inject(rsvpmatches);
							option.addEvent('mousedown', setAttendee.bind(option));
						}
						rsvpmatches.style.display='block';

					}
					else {
						rsvpClearMatches();
					}

					// If have started another search already then cancel this one
					if (cancelSearch) {
						return;
					}
				}
			},
			onFailure: function(x){
				if (ignoreSearch) return;
				rsvpjsonactive = false;
				alert('Something went wrong... ')
				rsvpClearMatches();
			}
		}).get({'json':JSON.encode(requestObject)});
	}
}

function rsvpClearMatches(){
	if (rsvptimeout) {
		clearTimeout(rsvptimeout);
	}
	var rsvpmatches = document.getElement("#rsvpmatches");
	rsvpmatches.innerHTML = "";
	rsvpmatches.style.display='none';
}

function setAttendee(event){
	var oldid = this.id;
	var newid = this.id.replace("rsvp_pot_","");
	var attendee = $('attendee');
	attendee.innerHTML = this.innerHTML;
	attendee = $('user_id');
	attendee.value = newid;

	var nameattendeenames = $$(".attendeename");
	nameattendeenames.each(function(attendeename){
		var fieldid = attendeename.id.replace('params','');
		if (fieldid.indexOf("_xxx")<0 && fieldid.indexOf("field")==0 && fieldid.indexOf("_")>0){
			var attendee = $('attendee');
			attendeename.value = attendee.innerHTML.replace(/ \((.*)\)/g, '');
		}
	});

	document.updateattendance.jev_name.value = "";
	rsvpClearMatches();
}

