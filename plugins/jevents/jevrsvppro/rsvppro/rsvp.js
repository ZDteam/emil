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
function showRSVP(){
	document.getElement("div#jevsimplereg").style.display="block";
}

function enableattendance(){
	document.getElement("div#jevattendance").style.display="block";

}
function disableattendance(){
	document.getElement("div#jevattendance").style.display="none";
	document.getElement("input#custom_rsvp_showattendees0").checked=true;
}

function enableinvites(){
	document.getElement("#jev_invites").style.display="block";
	document.getElement("div#jev_allinvites").style.display="block";
	document.getElement("div#jevmessage").style.display="block";
}
function disableinvites(){
	document.getElement("#jev_invites").style.display="none";
	document.getElement("div#jev_allinvites").style.display="none";
	document.getElement("div#jevmessage").style.display="none";
}
function enablereminders(){
	document.getElement("div#jevreminder").style.display="block";
}
function disablereminders(){
	document.getElement("div#jevreminder").style.display="none";
}

function addInvitees(){
	if (window.ie6 || window.ie7) $$(".jevcol1").each (function (el){
		el.style.display="";
	});
	else $$(".jevcol1").each (function (el){
		el.style.display="table-cell";
	});
	$("jev_name").style.display="block";
	if ($("jev_email")) $("jev_email").style.display="block";
	
	if ($('jugroups')) $('jugroups').style.display='none' ;	
	if ($('jsgroups')) $('jsgroups').style.display='none' ;	
	if ($('cbgroups')) $('cbgroups').style.display='none' ;	
	
}
function addInvitee(link) {
	SqueezeBox.initialize({});
	SqueezeBox.setOptions(SqueezeBox.presets,{
		'handler': 'iframe',
		'size': {
			'x': 750,
			'y': 500
		},
		'closeWithOverlay': 0
	});
	SqueezeBox.url = link;
	SqueezeBox.setContent('iframe', SqueezeBox.url );
}
var rsvpjsonactive = false;
var cancelSearch = true;
var rsvptimeout=false;
var ignoreSearch=false;

function findUser(e,elem, url, client){

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
	requestObject.task = "checkTitle";
	requestObject.title = elem.value;
	requestObject.client = client;
	requestObject.ev_id = document.getElementById('rsvp_evid').value;

	minlength=2;

	if (elem.value.length>=minlength || elem.value=="*"){
		if (rsvpjsonactive) return;

		currentSearch = elem.value;

		if (rsvptimeout) {
			clearTimeout(rsvptimeout);
		}

		//url += '?start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';

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
						var shownotes = false;
						for (var jp=0;jp<json.titles.length;jp++){
							// If have started another search already then cancel this one
							if (cancelSearch) {
								return;
							}
							// Do not add if already in list of invitees
							if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
							shownotes = true;
							//var option = new Element('option', {id:"rsvp_pot_"+json.titles[jp]["id"]});
							var option = new Element('div', {
								id:"rsvp_pot_"+json.titles[jp]["id"],
								'style':'margin:0px;padding:2px;cursor:pointer;'
							});
							option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");
							option.inject(rsvpmatches);

							option.addEvent('mousedown', rsvpaddInvitee.bind(option));
						}
						if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
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
			onFailure: function(){
				if (ignoreSearch) return;
				rsvpjsonactive = false;
				alert('Something went wrong...')
				rsvpClearMatches();
			}
		}).get({
			'json':JSON.encode(requestObject)
		});
	}
}

function rsvpClearMatches(){
	if (rsvptimeout) {
		clearTimeout(rsvptimeout);
	}
	document.getElement("#rsvpclicktoinvite").style.display="none";
	var rsvpmatches = document.getElement("#rsvpmatches");
	rsvpmatches.innerHTML = "";
}

function updateInvitees(button){
	/// make sure we take focus from the input box
	ignoreSearch=true;
	document.updateinvitees.submit();
}

function emailInvitees(button){
	/// make sure we take focus from the input box
	ignoreSearch=true;
	document.getElement("#rsvp_email").value = "email";
	document.updateinvitees.submit();
}

function saveInvitees(button){
	/// make sure we take focus from the input box
	ignoreSearch=true;
	document.getElement("#rsvp_email").value = "savelist";

	if (document.getElement("#inviteelistname").value==""){		
		alert("please provide a value");
	}
	else {
		document.getElement("#jevrsvp_listid").value = document.getElement("#inviteelistname").value;
		document.updateinvitees.submit();
	}
}

function reemailInvitees(button){
	/// make sure we take focus from the input box
	ignoreSearch=true;
	document.getElement("#rsvp_email").value = "reemail";

	document.updateinvitees.submit();
}
function resendFailed(button){
	/// make sure we take focus from the input box
	ignoreSearch=true;
	document.getElement("#rsvp_email").value = "failed";
	document.updateinvitees.submit();
}

function rsvpaddInvitee(event){
	var oldid = this.id;
	var newid = this.id.replace("rsvp_pot","rsvp_inv");
	var invitetable = document.getElement("#invitetable").getElement('tbody');
	var tr = new Element('tr');
	var td = new Element('td');
	td.appendText(this.innerHTML);
	var input = new Element('input', {
		id:newid,
		type:'hidden',
		name:'jevinvitee[]',
		value:newid
	});
	input.inject(td);
	td.inject(tr);

	td = new Element('td',{
		align:'center'
	});
	var imgpath= urlroot+'plugins/jevents/jevrsvppro/rsvppro/assets/Trash.png';
	// if joomla 1.5 ?
	var mtversion = MooTools.version.split(".");
	if (mtversion[1]<3){
		imgpath= urlroot+'plugins/jevents/rsvppro/assets/Trash.png';
	}
	var img = new Element('img',{
		src:imgpath,
		style:"height:16px;cursor:pointer",
		onclick:"cancelInvite(this)"
	});
	img.inject(td);
	td.inject(tr);

	// email sent?
	td = new Element('td');
	td.inject(tr);

	tr.inject(invitetable);
	Element.dispose($(this));

	document.getElement("#rsvpupdateinvites").style.display="inline";
	document.getElement("#rsvpemailinvites").style.display="inline";
	document.getElement("#rsvpreemailinvites").style.display="inline";
	document.getElement("#saveinvitees").style.display="block";
	document.getElement("#invitetable").style.display="block";

}
function addEmailInvitee(){

	var emailname = document.getElement("#jev_emailname").value;
	document.getElement("#jev_emailname").value = "";
	emailaddress = document.getElement("#jev_emailaddress").value;
	document.getElement("#jev_emailaddress").value = "";

	if (emailaddress =="") return;

	var newid = "rsvp_inv_"+emailname+"{"+emailaddress+"}";
	var invitetable = document.getElement("#invitetable").getElement('tbody');
	var tr = new Element('tr');
	var td = new Element('td');
	td.appendText(emailname+"{"+emailaddress+"}");
	var input = new Element('input', {
		id:newid,
		type:'hidden',
		name:'jevinvitee[]',
		value:newid
	});
	input.inject(td);
	td.inject(tr);

	td = new Element('td',{
		align:'center'
	});
	var imgpath= urlroot+'plugins/jevents/jevrsvppro/rsvppro/assets/Trash.png';
	// if joomla 1.5 ?
	var mtversion = MooTools.version.split(".");
	if (mtversion[1]<3){
		imgpath= urlroot+'plugins/jevents/rsvppro/assets/Trash.png';
	}
	var img = new Element('img',{
		src:imgpath,
		style:"height:16px;cursor:pointer",
		onclick:"cancelInvite(this)"
	});
	img.inject(td);
	td.inject(tr);

	// email sent?
	td = new Element('td');
	td.inject(tr);

	tr.inject(invitetable);

	document.getElement("#rsvpupdateinvites").style.display="inline";
	document.getElement("#rsvpemailinvites").style.display="inline";
	document.getElement("#rsvpreemailinvites").style.display="inline";
	document.getElement("#invitetable").style.display="block";
}

function cancelInvite(img){
	tr = $(img.parentNode.parentNode);
	table =  tr.parentNode;
	if (table.tagName.toUpperCase()!="TABLE") {
		table =  tr.parentNode.parentNode;
	}
	Element.dispose(tr);
	if (table.getElements("tr").length==1){
		document.getElement("#rsvpemailinvites").style.display="none";
		document.getElement("#rsvpreemailinvites").style.display="none";
		document.getElement("#rsvpsendfailed").style.display="none";
	}
	document.getElement("#rsvpupdateinvites").style.display="inline";
}

function cancelAttendance(attendee){
	document.getElement("#jevattendlist_id").value = attendee;
	document.attendeelist.submit();

}

function approveAttendance(attendee){
	document.getElement("#jevattendlist_id_approve").value = attendee;
	document.attendeelist.submit();

}


function inviteAll(){
	var rsvpmatches = document.getElement("#rsvpmatches");
	var options = rsvpmatches.getElements('div');
	options.each(function(item,index){
		// auto add
		rsvpaddInvitee.apply(item);
	});
}
function inviteFriends(url,client){
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteFriends";
	requestObject.ev_id = document.getElementById('rsvp_evid').value;
	requestObject.client = client || "site";

	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				var rsvpmatches = document.getElement("#rsvpmatches");
				if (json.titles.length>0){
					rsvpClearMatches();
					var shownotes = false;
					for (var jp=0;jp<json.titles.length;jp++){
						// Do not add if already in list of invitees
						if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
						shownotes = true;
						var option = new Element('option', {
							id:"rsvp_pot_"+json.titles[jp]["id"]
						});
						//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

						option.inject(rsvpmatches);

						// auto add
						rsvpaddInvitee.apply(option);
					}
					if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
				}
				else {
					rsvpClearMatches();
				}

			}
		},
		onFailure: function(){
			rsvpjsonactive = false;
			alert('Something went wrong...')
			rsvpClearMatches();
		}
	}).get({
		'json':JSON.encode(requestObject)
	});

}

function inviteJSGroup(url, groupid,client){
	if (groupid=="NONE") return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteJSGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = document.getElementById('rsvp_evid').value;
	requestObject.client = client || "site";

	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				var rsvpmatches = document.getElement("#rsvpmatches");
				if (json.titles.length>0){
					rsvpClearMatches();
					var shownotes = false;
					for (var jp=0;jp<json.titles.length;jp++){
						// Do not add if already in list of invitees
						if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
						shownotes = true;
						var option = new Element('option', {
							id:"rsvp_pot_"+json.titles[jp]["id"]
						});
						//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

						option.inject(rsvpmatches);

						// auto add
						rsvpaddInvitee.apply(option);
					}
					if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
				}
				else {
					rsvpClearMatches();
				}

			}
		},
		onFailure: function(){
			rsvpjsonactive = false;
			alert('Something went wrong...')
			rsvpClearMatches();
		}
	}).get({
		'json':JSON.encode(requestObject)
	});

}

function inviteJoomlaGroup(url, groupid,client){
	if (groupid=="NONE") return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteJUGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = document.getElementById('rsvp_evid').value;
	requestObject.client = client || "site";

	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				var rsvpmatches = document.getElement("#rsvpmatches");
				if (json.titles.length>0){
					rsvpClearMatches();
					var shownotes = false;
					for (var jp=0;jp<json.titles.length;jp++){
						// Do not add if already in list of invitees
						if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
						shownotes = true;
						var option = new Element('option', {
							id:"rsvp_pot_"+json.titles[jp]["id"]
						});
						//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

						option.inject(rsvpmatches);

						// auto add
						rsvpaddInvitee.apply(option);
					}
					if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
				}
				else {
					rsvpClearMatches();
				}

			}
		},
		onFailure: function(){
			rsvpjsonactive = false;
			alert('Something went wrong...')
			rsvpClearMatches();
		}
	}).get({
		'json':JSON.encode(requestObject)
	});

}

function inviteCBGroup(url, groupid,client){
	if (groupid=="NONE") return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteCBGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = document.getElementById('rsvp_evid').value;
	requestObject.client = client || "site";

	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				var rsvpmatches = document.getElement("#rsvpmatches");
				if (json.titles.length>0){
					rsvpClearMatches();
					var shownotes = false;
					for (var jp=0;jp<json.titles.length;jp++){
						// Do not add if already in list of invitees
						if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
						shownotes = true;
						var option = new Element('option', {
							id:"rsvp_pot_"+json.titles[jp]["id"]
						});
						//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

						option.inject(rsvpmatches);

						// auto add
						rsvpaddInvitee.apply(option);
					}
					if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
				}
				else {
					rsvpClearMatches();
				}

			}
		},
		onFailure: function(){
			rsvpjsonactive = false;
			alert('Something went wrong...')
			rsvpClearMatches();
		}
	}).get({
		'json':JSON.encode(requestObject)
	});

}


function inviteList(url, listid, client){
	if (listid=="NONE") return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteList";
	requestObject.listid = listid;
	requestObject.ev_id = document.getElementById('rsvp_evid').value;
	requestObject.client = client || "site";

	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				var rsvpmatches = document.getElement("#rsvpmatches");
				if (json.titles.length>0){
					rsvpClearMatches();
					var shownotes = false;
					for (var jp=0;jp<json.titles.length;jp++){
						// Do not add if already in list of invitees
						if (document.getElement("#rsvp_inv_"+json.titles[jp]["id"])) continue;
						shownotes = true;
						if (json.titles[jp]["id"]==0){
							var option = new Element('option', {
								id:"rsvp_pot_"+json.titles[jp]["name"]+"("+json.titles[jp]["username"]+")"
							});
						}
						else {
							var option = new Element('option', {
								id:"rsvp_pot_"+json.titles[jp]["id"]
							});
						}
						//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

						option.inject(rsvpmatches);

						// auto add
						rsvpaddInvitee.apply(option);
					}
					if (shownotes) 	document.getElement("#rsvpclicktoinvite").style.display="block";
				}
				else {
					rsvpClearMatches();
				}

			}
		},
		onFailure: function(){
			rsvpjsonactive = false;
			alert('Something went wrong...')
			rsvpClearMatches();
		}
	}).get({
		'json':JSON.encode(requestObject)
	});

}

function confirmUpdate(confirmmsg){
	var newname = $('inviteelistname').value;
	if (newname=="" ) return false;
	// no lists in existence
	if (!$('custom_jevuser_inviteelist')) return true;
	var inviteelist = $('custom_jevuser_inviteelist').options;
	if (inviteelist.length<=1) return true;
	var matched = false;
	//$A(inviteelist).each(function(list,index){
	// $A => Array.from(item).slice()
	Array.from(inviteelist).slice().each (function(list,index){
		if (list.text==newname){
			matched = true;
		}
	});
	if (matched){
		return confirm(confirmmsg);
	}
	return true;
}
function showJevStatus(){
	$("jevstatus").style.display="block";
	$("jevstatusbutton").style.display="none";

}
function showSubmitButton(){
	$("jevattendsubmit").style.display="";
//$("jevattend").checked;
}


function checkRegDates(item){
	if (item == 'regopentime'){
		var reg = $('custom_rsvp_regopen');
		var regdate = $('regopen');
		var regtime = $('hiddenregopentime');
	}
	else if(item == 'regclosetime'){
		var reg = $('custom_rsvp_regclose');
		var regdate = $('regclose');
		var regtime = $('hiddenregclosetime');
	}
	else {
		var reg = $('custom_rsvp_cancelclose');
		var regdate = $('cancelclose');
		var regtime = $('hiddencancelclosetime');
	}
	reg.value = regdate.value + " "+ regtime.value;
}

function updateCancelClose(val) {
	val = $("custom_rsvp_allowcancellation1").checked || $("custom_rsvp_allowchanges1").checked;
	var jevendcancel = $("jevendcancel");
	jevendcancel.style.display = val?"block":"none";	
}
//See http://www.silverscripting.com/MooTimePick/test.html
var HoverPick = new Class({
	Implements: Options,

	options: {
		panels: [],
		resetOnHide: true,
		img: 'clock_red.png'
	},

	initialize: function(el, options) {

		// initialize all properties...
		this.el = typeOf(el) === 'string' ? $(el) : el;
		this.setOptions(options);

		// Used later to prevent switching the choice when the panel is fading
		this.panelVisible = false;
		this.panelsUl = [];
		this.panelValues = new Hash({});
		// build everything...
		this.buildElements();
	},

	buildElements: function() {
		this.el.addEvent('click', this.showPanel.bind(this));

		// The timepicker itself
		this.timePicker = new Element('img', {
			'src': this.options.img,
			'styles': {
				'position': 'absolute',
				'margin': '3px 0 0 3px',
				'cursor': 'pointer'
			}
		});
		this.timePicker.inject(this.el, 'after');
		this.timePicker.addEvent('click',function(){
			this.showPanel()
		}.bind(this));

		// The main DIV
		this.mainDiv = new Element('div', {
			'styles' : {
				'position': 'absolute',
				'height': 80,
				'margin': 0,
				'padding': 0,
				'display':'none',
				'left': this.el.getPosition(this.el.getParent()).x,
				'top': this.el.getPosition(this.el.getParent()).y + (this.el.getSize().y|30)
			}
		});
		this.mainDiv.inject(this.timePicker, 'after');
		this.fadeFx = new Fx.Tween(this.mainDiv, {
			duration: 300
		});
		this.fadeFx.start('opacity', 0, 1);

		// The panels...
		var itemCount = 0;
		this.options.panels.each(function(panel, index) {
			itemCount = itemCount + panel.length;
			var margin = index * 26;
			var panelType = typeOf(panel[0]);
			this['panel' + (index + 1)] = new Element('ul', {
				'class': 'moo-pick-ul',
				'styles' : {
					'margin': margin + "px 0 0 0",
					'display': 'none'
				}
			});
			this.panelsUl.push(this['panel' + (index + 1)]);
			this.panelValues[index + 1] = panel[0];
			this['panel' + (index + 1)].inject(this.mainDiv);

			panel.each(function(item, index2) {
				var itemEl = new Element('li');
				itemEl.innerHTML = item+ "";
				//itemEl.store('level', (index + 1));
				//itemEl.store('value', item);
				itemEl.xlevel = index+1;
				itemEl.xvalue = item;
				itemEl.inject(this['panel' + (index + 1)]);
				itemEl.addEvent('mouseover', function() {
					this.itemOver(itemEl)
				}.bind(this));
				itemEl.addEvent('click', function() {
					this.itemClicked()
				}.bind(this));

			}, this);

			//clearer
			var clearer = new Element('div', {
				'styles': {
					'clear': 'both'
				}
			});
			clearer.inject(this.mainDiv);
		}, this);

		var divWidth = itemCount * 26;
		this.mainDiv.setStyle('width', divWidth);
	},

	showPanel: function() {
		$("rsvpspacer").setStyle('display', 'block');
		this.mainDiv.setStyle('display', 'block');
		this.fadeFx.start('opacity', 0, 1);
		this.panelsUl[0].setStyle('display', 'block');
		this.panelVisible = true;
	},

	itemOver: function(el) {
		//alert('over');
		if(this.panelVisible) {
			//var level = el.retrieve('level');
			var level = el.xlevel;
			// update the values...
			//this.panelValues[level] = el.retrieve('value');
			this.panelValues[level] = el.xvalue;

			this.updateText();
			// highlight selected item
			this['panel' + level].getChildren('li').removeClass('hover');
			el.addClass('hover');

			// display panel under it if it exists and it is not displayed...
			if(this['panel' + (level + 1)] && this['panel' + (level + 1)].getStyle('display') === 'none') {
				this['panel' + (level + 1)].setStyle('display', 'block');
			}

			// move panels under it...
			if(this['panel' + (level + 1)]) {
				this.movePanel(level + 1);
			}
		}
	},

	movePanel: function(level) {
		// find item highlighted at previous level & move panel!
		var currentItem = this['panel' + (level - 1)].getElements('li.hover')[0];
		//this['panel' + level].setStyle('left', currentItem.getCoordinates([this.mainDiv]).left);
		this['panel' + level].setStyle('left',currentItem.offsetLeft+currentItem.offsetParent.offsetLeft );

		// reccursively move panels under it... if they exist, and are displayed...
		if(this['panel' + (level + 1)] && this['panel' + (level + 1)].getStyle('display') === 'block') {
			this.movePanel(level + 1);
		}
	},

	itemClicked: function() {
		this.panelVisible = false;
		this.fadeFx.start('opacity',1, 0).chain(function() {
			if(this.options.resetOnHide) {
				this.panelsUl.each(function(el) {
					el.setStyle('display', 'none');
					el.getChildren('li').removeClass('hover');
				});
			}
		}.bind(this));
	},

	updateText: function() {
		var finalValue = "";
		this.panelValues.each(function(value, key) {
			finalValue = finalValue + value + "";
		});
		this.el.set('value', finalValue);
	}
});
HoverPick.implement(new Options);

HoverPickTime = new Class({
	Extends: HoverPick,

	options: {
		hours: [1,2,3,4,5,6,7,8,9,10,11,12],
		minutes: [0,5,10,15,20,25,30,35,40,45,50,55],
		amPm: ['am', 'pm'],
		seconds: [],
		format: "HH:MM aa",
		resetOnHide: false
	},

	initialize: function(el, options) {
		this.setOptions(options);
		this.options.panels = [this.options.hours, this.options.minutes];
		if(this.options.seconds.length > 0) {
			this.options.panels.push(this.options.econds);
		}
		if(this.options.amPm.length > 0) {
			this.options.panels.push(this.options.amPm);
		}

		this.parent(el, options);
	},

	updateText: function() {
		var hour = this.panelValues[1] + "";
		var minute = this.panelValues[2] + "";
		var hourPadded = this.panelValues[1] < 10 ? "0" + this.panelValues[1] : this.panelValues[1] + "";
		var minutePadded = this.panelValues[2] < 10 ? "0" + this.panelValues[2] : this.panelValues[2] + "";
		var amPm = "";
		if(this.options.seconds.length > 0) {
			var second = this.panelValues[3];
			var secondPadded = this.panelValues[3] < 10 ? "0" + this.panelValues[3] : this.panelValues[3] + "";
			if (this.options.amPm.length>0){
				 amPm =  this.panelValues[4];
			}
		}
		else {
			if (this.options.amPm.length>0){
				 amPm =  this.panelValues[3];
			}
		}
		var time = this.options.format.replace("HH", hourPadded).replace("H", hour).replace("MM", minutePadded).replace("M", minute).replace("SS", secondPadded).replace("S", second).replace("aa", amPm);
		this.el.value = time;
		var hiddenel = $("hidden"+this.el.id);
		hour = this.panelValues[1];
		// 12am is midnight so the hour must be 0!
		//window.status = "panelValues = "+this.panelValues[3]+" hour = "+hour;
		if(this.options.amPm.length>0 && this.options.amPm.indexOf(this.panelValues[3])==0 && hour==12){
			hour = 0;
		}
		else if(this.options.amPm.length>0 && this.options.amPm.indexOf(this.panelValues[3])){
			hour+=12;
			// Make sure 12pm is 12 and NOT 24!!
			if (hour==24){
				hour	=12;
			}
		}
		hourPadded = hour < 10 ? "0" + hour : hour + "";
		hiddenel.value = hourPadded + ":"+minutePadded+":00";
		checkRegDates(this.el.id);
	}
});

function priceJevrList(name, prices){
	var elems=$(document).getElements(".rsvp_"+name);
	if (!elems) return 0;
	var result  = 0;
	elems.each(function(elem){
		//if (elem.tagName.toLowerCase()!="select") return;
		// find parent span which has the elemtn id and individual count in it
		var indivcount = elem.getParent().id.replace("rsvp_"+name+"_span_","");
		// NB this is the name not the element!
		if (JevrConditionalFields.isVisible(name, indivcount)){
			// is it a disabled first parameter
			if (elem.hasClass("disabledfirstparam")) return;
			if (elem.hasClass("paramtmpl")) return;
			var option=elem.value;
			if (prices[option]){
				result += parseFloat(prices[option]);
			}
		}
	});
	return result;
}
function priceJevrRadio(name, prices){
	var elems=$(document).getElements(".rsvp_"+name+ " input");
	if (!elems) return 0;
	var result  = 0;
	elems.each(function(elem){
		// find parent span which has the elemtn id and individual count in it
		var indivcount = elem.getParent().id.replace("rsvp_"+name+"_span_","");
		if (indivcount == "" && elem.getParent().tagName.toLowerCase()=="label") {
			indivcount = elem.getParent().getParent().id.replace("rsvp_"+name+"_span_","");
		} 
		// NB this is the name not the element!
		if (JevrConditionalFields.isVisible(name, indivcount)){
			// is it a disabled first parameter
			if (elem.getParent().hasClass("disabledfirstparam") ||  elem.getParent().getParent().hasClass("disabledfirstparam") ) return;
			if (elem.getParent().hasClass("paramtmpl") || elem.getParent().getParent().hasClass("paramtmpl")) return;
			if (!elem.checked) return;
			var option=elem.value;
			if (prices[option]){
				//alert(prices[option]);
				result += parseFloat(prices[option]);
			}
		}
	});
	return result;
}

function surchargeList(name, surcharges){
	var elems=$(document).getElements(".rsvp_"+name);
	if (!elems) return 0;
	var result  = 0;
	elems.each(function(elem){
		if (elem.tagName.toLowerCase()!="select") return;
		var option=elem.value;
		if (surcharges[option]){
			result += parseFloat(surcharges[option]);
		}
	});
	return result;
}


var jevrsvpRequiredFields = {
	fields: new Array(),
	verify:function (form){
		var messages =  new Array();
		if (!jevrsvpRequiredFields.emailcheck()){
			messages.push(rsvpInvalidEmail);
		}
		jevrsvpRequiredFields.fields.each(function (item,i) {
			if (item.requiredCheckScript && window[item.requiredCheckScript]) {
				// does it fail the specific check script
				if (!window[item.requiredCheckScript](form)){
					messages.push(item.reqmsg);
				}
			}
			else if (item.type && item.type=='radio'){
				messages = jevrsvpRequiredFields.verifyRadio(item, form, messages);
			}
			else if (item.type && item.type=='checkbox'){
				messages = jevrsvpRequiredFields.verifyCheckbox(item, form, messages);
			}			
			else {
				var name = item.name;
				var matches = new Array();
				//$A => Array.from(item).slice()
				Array.from(form.elements).slice().each (function (testitem,testi) {

					if (item.name){
						if(testitem.name == item.name && testitem.id.indexOf("_xxx")<0){
							matches.push(testitem);
						};
					}
					else {
						if(testitem.id == item.id){
							matches.push(testitem);
						};
					}
				});
			
				// extract field name for conditionality check
				var conditionalfieldname = item.id;
				// remove the xmlfile_ prefix
				conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
				// remove the count
				if (conditionalfieldname.indexOf("_")>0){
					conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
				}
				var value = "";
				matches.each (function (match, index){
					
					if (match.hasClass("disabledfirstparam") || match.getParent().hasClass("disabledfirstparam")  || match.getParent().getParent().hasClass("disabledfirstparam")) {
						return ;
					}
					
					value = match.value;
					
					//if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "" || value.length < 3)){
					if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "" )){

						//highlight the bad element values
						match.style.backgroundColor="red";
						if(item.reqmsg!=""){
							messages.push(item.reqmsg);
						}
					}
					else {
						try {
							match.style.backgroundColor="inherit";
						}
						catch (e){
							match.style.backgroundColor="transparent";
						}						
					}
				});
				
			}
		});
		if (messages.length>0){
			var message = "";
			messages.each (function (msg, index){
				message += msg+'\n';
			});
			alert(message);
		}
		return (messages.length==0);
	},
	verifyRadio : function(item, form, messages){
		var name = item.name;
		var matches = new Array();
		var testname = item.name.replace("[xxxyyyzzz]","");
		Array.from(form.elements).slice().each (function (testitem,testi) {
			if(testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && testitem.checked){
				matches.push(testitem);
			};
		});

		// extract field name for conditionality check
		var conditionalfieldname = item.id;
		// remove the xmlfile_ prefix
		conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
		// remove the count
		if (conditionalfieldname.indexOf("_")>0){
			conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
		}

		var value = "";
		matches.each (function (match, index){
			value = match.value;

			if (match.hasClass("disabledfirstparam") || match.getParent().hasClass("disabledfirstparam")  || match.getParent().getParent().hasClass("disabledfirstparam")) {
				return ;
			}

			if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "")){

				//highlight the bad element values
				match.parentNode.parentNode.style.backgroundColor="red";
				if(item.reqmsg!=""){
					messages.push(item.reqmsg);
				}
				else {
					messages.push(" ");
				}
			}
			else {
				try {
					match.parentNode.parentNode.style.backgroundColor="inherit";
				}
				catch (e){
					match.parentNode.parentNode.style.backgroundColor="transparent";
				}				
			}
		});
		return messages;
	},
	verifyCheckbox : function(item, form, messages){
		var name = item.name;
		var matches = new Array();
		var failures = new Array();
		var testname = item.name.replace("[xxxyyyzzz]","");
		Array.from(form.elements).slice().each (function (testitem,testi) {
			if (testitem.type!="checkbox") return;
			if(testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && testitem.checked){
				matches.push(testitem);
			}
			else if (testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && !testitem.checked){
				failures.push(testitem);
			}			
		});

		// extract field name for conditionality check
		var conditionalfieldname = item.id;
		// remove the xmlfile_ prefix
		conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
		// remove the count
		if (conditionalfieldname.indexOf("_")>0){
			conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
		}

		if (matches.length>0){
			matches.each (function (match, index){
				if (match.hasClass("disabledfirstparam") || match.getParent().hasClass("disabledfirstparam")  || match.getParent().getParent().hasClass("disabledfirstparam")) {
					return ;
				}
				
				try {
					match.parentNode.style.backgroundColor="inherit";
				}
				catch (e){
					match.parentNode.style.backgroundColor="transparent";
				}
			});
		}
		if (failures.length>0){
			failures.each (function (failure, index){
				if (failure.hasClass("disabledfirstparam") || failure.getParent().hasClass("disabledfirstparam")  || failure.getParent().getParent().hasClass("disabledfirstparam")) {
					return ;
				}
				
				// index value here is for each checkbox NOT the overall 'element' so extract the real index'
				var checkboxindex = failure.name.replace("xmlfile["+conditionalfieldname+"][", "").replace("][]","");
				//alert(checkboxindex+" vs "+failure.name+" from "+conditionalfieldname);
				if (!JevrConditionalFields.isVisible(conditionalfieldname, checkboxindex)) {
					return;
				}
				
				//highlight the bad element values
				failure.parentNode.style.backgroundColor="red";
				if(item.reqmsg!=""){
					// Don't output the message more than once!'
					if (messages.indexOf(item.reqmsg) <0) {
						messages.push(item.reqmsg);
					}
				}
				else {
					messages.push(" ");
				}
			});
		}
		return messages;
	},
	emailcheck : function () {
		if (!$("jevattend_email") || ( $('user_id') && $('user_id').value >0) ) {
			return true;
		}
		var str = $("jevattend_email").value;
		var at="@";
		var dot=".";
		var lat=str.indexOf(at);
		var lstr=str.length;
		var ldot=str.indexOf(dot);
		var valid = true;

		// must have an @ and must not start or end with @
		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr-1){
			valid=false;
		}
		// Must have a . and must not start or end with a .
		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr-1){
			valid=false;
		}
		// must not have more than one @
		if (str.indexOf(at,(lat+1))!=-1){
			valid=false;
		}
		// must not have a . straight before or after a ?
		if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
			valid=false;
		}
		// there must be a . after the @
		if (str.indexOf(dot,(lat+2))==-1){
			valid=false;
		}
		// no spaces
		if (str.indexOf(" ")!=-1){
			valid=false;
		}

		if (!valid){
			$("jevattend_email").style.backgroundColor="red";
			return false;
		}
		else {
			try {
				$("jevattend_email").style.backgroundColor="inherit";
			}
			catch (e){
				$("jevattend_email").style.backgroundColor="transparent";
			}						

			return true;				
		}
	}

}

// fix the attendance repeat options based on whether the event is repeating or not!
function setupRepeatListener() {
	if (!document.adminForm) return;
	var rrfreq = document.adminForm.freq;
	if (rrfreq) {
		checkRepeatBox();
		window.setTimeout("checkRepeatBox()", 800);
		Array.from(rrfreq).slice().each(function (rbox){
			$(rbox).addEvent('click', function(){
				checkRepeatBox();
			});
		});
	}	
}
function checkRepeatBox(){
	var rrfreq = document.adminForm.freq;
	if (rrfreq) {
		Array.from(rrfreq).slice().each(function (rbox){
			if (rbox.checked) {
				var allrepeats = document.getElement('div.rsvp_allrepeats');
				var allinvites = document.getElement('div.rsvp_allinvites');
				var allreminders = document.getElement('div.rsvp_allreminders');
				
				if (rbox.value.toUpperCase()=="NONE" ){					
					if (allrepeats ) {
						allrepeats.style.display="none";
						if (document.adminForm.evid.value==0) $('custom_rsvp_allrepeats1').checked = true;
					}
					if (allinvites){
						allinvites.style.display="none";
						if (document.adminForm.evid.value==0) $('custom_rsvp_allinvites1').checked = true;
					}
					if (allreminders){
						allreminders.style.display="none";
						if (document.adminForm.evid.value==0) $('custom_rsvp_remindallrepeats1').checked = true;
					}
				}
				else {
					if (allrepeats ) {
						allrepeats.style.display="block";
					}
					if (allinvites ) {
						allinvites.style.display="block";
					}
					if (allreminders ) {
						allreminders.style.display="block";
					}
				}
			}
			
		});
	}
}

var JevrTotalFee = 0;
var JevrFees = {
	fields: new Array(),
	calculate:function (form){
		if ($('jevrtotalfee') && $('guestcount')){
			JevrTotalFee=0;
			JevrFees.fields.each(function (item,i) {
				var multiplier = 1;
				if (item.byguest && item.byguest>0){
					if ( JevrConditionalFields) {
						// is this field visible - count how many times
						multiplier = JevrConditionalFields.countVisible(item) ;
					}
					else if (item.byguest==1){						
						multiplier =$('guestcount').value;
					}
					else {
						multiplier =$('guestcount').value - 1;
					}
				}
				else {
					if ( JevrConditionalFields) {
						// is this field visible - count how many times
						multiplier = JevrConditionalFields.countVisible(item) ;
					}
				}
				var linefee = 0;
				if (item.price){
					linefee = parseFloat(item.price(item.name)) ;
				}
				else {
					linefee = parseFloat(item.amount);
				}
				linefee *= multiplier;
				JevrTotalFee += linefee;
			});
			
			// now the surcharges
			JevrFees.fields.each(function (item,i) {
				var linemultiplier = 0;
				if (item.surchargefunction){
					linemultiplier = parseFloat(item.surchargefunction(item.name)) ;
				}
				else if (!isNaN(parseFloat(item.surcharge))){ 
					linemultiplier = parseFloat(item.amount);
				}
				JevrTotalFee *= 1 + linemultiplier/100;
			});
			
			// set the total
			$('jevrtotalfee').innerHTML = rsvpMoneyFormat(JevrTotalFee);
			$('xmlfile_totalfee').value = JevrTotalFee;

			// update the balance
			if ($('xmlfile_feepaid')){
				var feepaid = $('xmlfile_feepaid').value;
			}
			else {
				var feepaid =0;
			}
			if ($('jevrfeebalance') && $('xmlfile_totalfee')) $('jevrfeebalance').innerHTML = rsvpMoneyFormat($('xmlfile_totalfee').value - feepaid) ;
			if ($('xmlfile_feebalance') && $('xmlfile_totalfee')) $('xmlfile_feebalance').value = $('xmlfile_totalfee').value - feepaid ;


		}
	}
}
window.addEvent('domready',function() {
	setupRepeatListener();
	JevrFees.calculate(document.updateattendance);
})

function addGuest(limit){
	// update the guest count
	// Do not change this to remove +1 without changing call to addStates
	$('guestcount').value = parseInt($('guestcount').value)+1;
	$('lastguest').value = parseInt($('lastguest').value)+1;
	var title = $("jevnexttabtitle").value;
	
	title = title.replace('xxx',$('lastguest').value);
	regTabs.addTab(title,title,$('lastguest').value);

	// Watch this count is 1 less than the label on the tab!
	// call before the fees since the bisibility affect the calculation
	JevrConditionFieldState.addStates($('lastguest').value - 1);

	// recalculate the fees!
	JevrFees.calculate(document.updateattendance);
	
	if (limit>0 && $('guestcount').value>=limit){
		if ($('addguest')) $('addguest').style.display="none";
	}
	// scroll tabs into view	
	var myElem = $('registration-tab-pane');
	if (myElem){
		//myElem = myElem.getParent().getParent();
		var myFx = new Fx.Scroll(window).toElement(myElem, 'y');
	}
}

function removeGuest(limit){

	// update the guest count
	$('guestcount').value = parseInt($('guestcount').value)-1;
	
	regTabs.removeActiveTab();

	// recalculate the fees!
	JevrFees.calculate(document.updateattendance);
	
	if (limit>0 && $('guestcount').value<limit){
		if ($('addguest')) $('addguest').style.display="block";
	}
	
	// scroll tabs into view
	var myElem = $('registration-tab-pane');
	if (myElem){
		//myElem = myElem.getParent().getParent();
		var myFx = new Fx.Scroll(window).toElement(myElem, 'y');
	}	
}

var resizeTimer;
var SqueezeBox;
function customiseTemplate(url){

	id = $('custom_rsvp_template').value;
	url = url.replace("xxGGxx",id);

	SqueezeBox.initialize({});
	var wsize=$(window).getSize();
	var x = Math.min(950,wsize.x-80);
	var y = wsize.y-60;
	SqueezeBox.setOptions(SqueezeBox.presets,{
		'handler': 'iframe',
		'size': {
			'x': x, 
			'y': y
		},
		'closeWithOverlay': 0
	});
	var evid = document.adminForm.evid.value;
	if (url.indexOf("?")>0){
		url += "&evid="+evid;
	}
	else {
		url += "?evid="+evid;
	}
	SqueezeBox.url = url;	
	// I can't stop the squeezebox from disappearing even doing this!
	//SqueezeBox.overlay['addEvent' ]('click', function(e){var stoppable = (typeOf(e) == 'event');if (stoppable) e.stop();});
	SqueezeBox.setContent('iframe', SqueezeBox.url );
	$(window).addEvent('resize', function() {
		$clear(resizeTimer);
		resizeTimer=(function(){
			templateResize();
		}).delay(100);
	});
	return;
}
function templateResize(){
	var wsize=$(window).getSize();
	var x = Math.min(950,wsize.x-80);
	var y = wsize.y-60;
	SqueezeBox.resize({
		'x':x,
		'y':y
	});
	var iframe = $("sbox-content").getElementsByTagName('iframe');
	if (iframe){
		iframe = iframe[0];
		iframe.style.width=x+"px";
		iframe.style.height=y+"px";
	}
}
function templateEditClose(){
	window.parent.SqueezeBox.close();
}
function setTemplate(id, title)
{
	var template_select = $("custom_rsvp_template");
	var option = new Element('option');
	template_select.appendChild(option);
	option.value = id;
	option.text = title;
	template_select.value  = id;
}
function setTemplateTitle(id, title){
	var template_select = $("custom_rsvp_template");
	Array.from(template_select.options).slice().each(function(el){
		if (el.value == id){
			el.text = title;
		}
	});
}

function changeTemplateSelection(){
	var template_select = $("custom_rsvp_template");
	var selectedNode= template_select.options[template_select.selectedIndex]
	if (selectedNode.value.indexOf(".xml")>0 || selectedNode.value==0 || $(selectedNode).getAttribute('locked')){
		$("custom_rsvp_template_link").style.display='none';
	}
	else {
		$("custom_rsvp_template_link").style.display='inline';
	}
}

function checkCoupon(e,elem, url, client, fieldid, rpid, atd_id, atdee_id){

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "checkCoupon";
	requestObject.title = elem.value;
	requestObject.client = client;
	requestObject.rp_id = rpid;
	requestObject.atd_id = atd_id;
	requestObject.atdee_id = atdee_id;
	requestObject.fieldid = fieldid;

	minlength=1;

	if (elem.value.length>=minlength){

		//url += '?start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';
		var jSonRequest = new Request.JSON({
			'url':url,
			onSuccess: function(json, responsetext){
				if (json.error){
					try {
						elem.value="";
						eval(json.error);
					}
					catch (e){
						alert('could not process error handler');
					}
				}
				else {
					setDiscount(json.discount, fieldid);
				}
			},
			onFailure: function(){
				alert('Something went wrong...')
				elem.value="";
				setDiscount(0, fieldid);
			}
		}).get({
			'json':JSON.encode(requestObject)
		});
	}
	else {
		setDiscount(0, fieldid);		
	}
}

function setDiscount(amount, fieldid) {
	eval('field'+fieldid+'discount='+amount);
	if (JevrFees && document.updateattendance){
		JevrFees.calculate(document.updateattendance);
	}
}

var JevrConditionFieldState = {
	fields: new Hash(),
	changeState : function (elem, name){
		$(elem).getElements("input[type=radio]").each (function(boolel){
			var indivcount = boolel.getParent().id.replace("rsvp_"+name+"_span_","");
			if (indivcount == "" && boolel.getParent().tagName.toLowerCase()=="label") {
				indivcount = boolel.getParent().getParent().id.replace("rsvp_"+name+"_span_","");
			} 			
			var fieldname = (indivcount!="") ? name+"_"+indivcount : name;
			if (boolel.checked && JevrConditionFieldState.fields.get(fieldname)){
				JevrConditionFieldState.fields.get(fieldname).value = boolel.value;
			//alert(boolel.value + " \n"+name+"  \n"+fieldname+"\n "+elem.innerHTML+"\n"+JevrConditionFieldState.fields.get(fieldname)+"\n"+JevrConditionFieldState.fields.get(fieldname).value);
			}
		});
		// Change State may not be called in the correct sequence of events so we call it again to be sure
		if (JevrFees && document.updateattendance){
			JevrFees.calculate(document.updateattendance);
		}
	}, 
	// new guest field has been added so we need a new condition state field
	addStates : function (guestid){
		JevrConditionFieldState.fields.each (function(field){
			// is it a indiv/guest field
			// is this the template state field
			// and does this guest field not exist ?
			//alert(field.peruser + " "+field.guestcount);
			if (field.peruser>0 && field.guestcount=='xxxyyyzzz' && !JevrConditionFieldState.fields.get(field.name+"_"+guestid)){
				// must clone to not change the original
				var newfield = Object.clone(field);
				newfield.guestcount = guestid;
				var newfieldname = newfield.name+"_"+guestid;
				// this uses the text newfieldname instead of the value of the variable!
				//JevrConditionFieldState.fields.extend( {newfieldname : newfield} );
				Hash.set(JevrConditionFieldState.fields, newfieldname , newfield);
			}
		});
	}, 
	isVisible : function (name, guestid, requiredstate){		
		// TODO : This could bubble up to parent fields!
		if (JevrConditionFieldState.fields.get(name+"_"+guestid) || JevrConditionFieldState.fields.get(name) ){
			var conditionstatefield = JevrConditionFieldState.fields.get(name+"_"+guestid)? JevrConditionFieldState.fields.get(name+"_"+guestid) :  JevrConditionFieldState.fields.get(name) ;
			if (guestid==0 && conditionstatefield.peruser==2){
				return 0;
			}
			if (conditionstatefield.value == requiredstate){
				return 1;
			}
		}
		return 0;
	}
}

var JevrConditionalFields = {
	fields: new Array(),
	setup: function(firstpass){
		JevrConditionalFields.fields.each (function (cf) {
			// TR version - doesn't support guests'
			/*
			var cfel = document.getElement('.param'+cf.cf);
			if (cfel){
				cfel.getElements("input[type=radio]").each (function(radel){
					// reveal initially where valid - hidden by default in PHP
					if (radel.checked && radel.value == cf.cfvfv){
						document.getElement('.param'+cf.name).removeClass("conditionalhidden");
					}
					radel.addEvent('change', function(){
						if (radel.value == cf.cfvfv) {
							document.getElement('.param'+cf.name).removeClass("conditionalhidden");
						}
						else {
							document.getElement('.param'+cf.name).addClass("conditionalhidden");
						}
					});
				});
			}
			*/
			var cfels = document.getElements('.rsvp_'+cf.cf);
			if (cfels && cfels.length>=1){
				if (cfels.length==1){
					// no guests
					// TR version - doesn't support guests'
					var cfel = document.getElement('.param'+cf.cf);
					if (cfel){
						cfel.getElements("input[type=radio]").each (function(radel){
							radel.removeEvent('click', JevrConditionalFields.setup);
							radel.addEvent('click', JevrConditionalFields.setup);

							// reveal initially where valid - hidden by default in PHP
							if (radel.value == cf.cfvfv){
								if (radel.checked) {
									document.getElement('.param'+cf.name).removeClass("conditionalhidden");
								}
								else {
									document.getElement('.param'+cf.name).addClass("conditionalhidden");
								}
							}
						});
					}
				}
				else {
					cfels.each (function (cfel){
						cfel.getElements("input[type=radio]").each (function(radel){
							radel.removeEvent('click', JevrConditionalFields.setup);
							radel.addEvent('click', JevrConditionalFields.setup);
							
							// temp fields - we don't process'
							if (cfel.hasClass("paramtmpl")) return;
							
							var radelid = radel.id.replace("xmlfile_"+cf.cf+"_", "").substring(0,1);							
							if (cfel.hasClass("rsvpparam"+radelid)){
								var dependentFields = document.getElements(".rsvp_"+cf.name);
								if (radel.value == cf.cfvfv){
									if (radel.checked){
										dependentFields.each (function (depfield){
											if (depfield.hasClass("rsvpparam"+radelid)){
												depfield.removeClass("conditionalhidden");		
												// and the label
												if (!depfield.hasClass("hideparam")){
													document.getElement('.param'+cf.name).removeClass("conditionalhidden");
												}
											}
										});										
									}
									else {
										dependentFields.each (function (depfield){
											if (depfield.hasClass("rsvpparam"+radelid)){
												depfield.addClass("conditionalhidden");		
												// and the label
												if (!depfield.hasClass("hideparam")){
													document.getElement('.param'+cf.name).addClass("conditionalhidden");
												}
											}
										});
									}
								}
							}
						});
					});
					
				/*
					var dependentFields = document.getElements(".rsvp_"+cf.name);
					var visiblecount = 0;
					dependentFields.each (function (depfield){
						if (!depfield.hasClass("conditionalhidden")){
							visiblecount ++;
						}
					});
					// are they all hidden  - if so then hide the TR !
					if (visiblecount+1 == dependentFields.length){
						
					}
					// Some are visible  - if so then reveal the TR !
					*/
				}
			}
			
		});
		
		// recalculate the fees if appropriate
		if (JevrFees) {
			JevrFees.calculate(document.updateattendance);
		}

	},
	countVisible : function(field){
		// if field has price function then this takes care of visible fields
		if (field.price){
			return 1;
		}
		// make sure field id doesn't have xmlfile_ at the start of its name
		fieldid = field.name.replace("xmlfile_","");
		var visiblefieldcount = 0;
				
		var isFieldConditional = false;
		
		// scan through the condition fields 
		JevrConditionalFields.fields.each (function (cf) {
			// We matched our field in the conditional triggers
			if (cf.name==fieldid){
				isFieldConditional = true;
				// Find the conditional field triggers in the DOM
				var cfels = document.getElements('.rsvp_'+cf.cf);
				if (cfels && cfels.length>=1){
					if (field.peruser==0){
						// no guests
						var cfel = document.getElement('.param'+cf.cf);
						if (cfel){
							// some might be spans etc so find the embedded radio buttons
							cfel.getElements("input[type=radio]").each (function(radel){
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv && radel.checked){
									visiblefieldcount +=1;
								}
								
							});
						}
					}
					else {
						cfels.each (function (cfel){
							// temp fields - we don't process'
							if (cfel.hasClass("paramtmpl") ) return;
							// we don't process the disabled first element
							if (cfel.hasClass("disabledfirstparam")) return;	

							// some might be spans etc so find the embedded radio buttons
							cfel.getElements("input[type=radio]").each (function(radel){
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv && radel.checked){
								
									var radelid = radel.id.replace("xmlfile_"+cf.cf+"_", "").substring(0,1);
									if (radelid == "p"){
										// in this case we have a radio button trigger that is a group field
										// This has a count of 0 of course since its the first entry and we will therefore match all dependents
										radelid = "";
									}
									if (cfel.hasClass("rsvpparam"+radelid)){
										var dependentFields = $(document).getElements(".rsvp_"+cf.name);
										dependentFields.each (function (depfield){
											// we don't process the disabled first element
											if (depfield.hasClass("disabledfirstparam")) return;	
											// temp fields - we don't process'
											if (depfield.hasClass("paramtmpl")) return;
											
											if (depfield.hasClass("rsvpparam"+radelid)){
												visiblefieldcount +=1;
											}
										});										
									}
								
								}
							});
						});
					}
				}
			}
		});
		if (!isFieldConditional) {
			var dependentFields = $(document).getElements(".rsvp_"+fieldid);
			dependentFields.each (function (depfield){
				// we don't process the disabled first element
				if (depfield.hasClass("disabledfirstparam")) return;	
				// temp fields - we don't process'
				if (depfield.hasClass("paramtmpl")) return;

				//	if (depfield.hasClass("rsvpparam"+radelid)){
				visiblefieldcount +=1;
			//	}
			});										
		}
		return visiblefieldcount;
	}, 
	isVisible : function (fieldname, individual){
		// No longer used
		/*
		var field = false;
		JevrFees.fields.each(function (feefield) {
			if (feefield.name == fieldname){
				field = feefield;
			}
		});
		if (!field){
			return true;
		}
		*/
		// make sure field id doesn't have xmlfile_ at the start of its name
		fieldid = fieldname.replace("xmlfile_","");
		var visiblefieldcount = 0;
				
		var isFieldConditional = false;
		
		// scan through the condition fields 
		var isVisible = false;
		var matchedConditionalFields = false;
		JevrConditionalFields.fields.each (function (cf) {
			// We matched our field in the conditional triggers
			if (cf.name==fieldid){
				matchedConditionalFields = true;
				isVisible = JevrConditionFieldState.isVisible(cf.cf, individual, cf.cfvfv);
			}
		});
		if (matchedConditionalFields){
			return isVisible;
		}
		else {
			// in this case no conditions!
			return true;
		}
		
	/* dead code
		// scan through the condition fields 
		JevrConditionalFields.fields.each (function (cf) {
			// We matched our field in the conditional triggers
			if (cf.name==fieldid){
				JevrConditionFieldState.isVisible(cf.name, individual, cf.cfvfv);

				isFieldConditional = true;
				var fieldIsVisible = false;
				// Find the conditional field triggers in the DOM
				var cfels = document.getElements('.rsvp_'+cf.cf);
				if (cfels && cfels.length>=1){
					if (field.peruser==0){
						// no guests
						var cfel = document.getElement('.param'+cf.cf);
						if (cfel){
							// some might be spans etc so find the embedded radio buttons
							var guestcount = 0;
							cfel.getElements("input[type=radio]").each (function(radel){
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv){
									if (radel.checked && guestcount==individual){
										fieldIsVisible = true;
									}
									guestcount++;
								}
								
							});
						}
					}
					else {
						cfels.each (function (cfel){
							// temp fields - we don't process'
							if (cfel.hasClass("paramtmpl") ) return;
							// we don't process the disabled first element
							if (cfel.hasClass("disabledfirstparam")) return;	

							// some might be spans etc so find the embedded radio buttons
							var guestcount = 0;
							cfel.getElements("input[type=radio]").each (function(radel){
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv && radel.checked){
								
									var radelid = radel.id.replace("xmlfile_"+cf.cf+"_", "").substring(0,1);
									if (radelid == "p"){
										// in this case we have a radio button trigger that is a group field
										// This has a count of 0 of course since its the first entry and we will therefore match all dependents
										radelid = "";
									}
									if (cfel.hasClass("rsvpparam"+radelid)){
										var dependentFields = $(document).getElements(".rsvp_"+cf.name);
										dependentFields.each (function (depfield){
											// we don't process the disabled first element
											if (depfield.hasClass("disabledfirstparam")) {
												guestcount++;
												return;	
											}
											// temp fields - we don't process'
											if (depfield.hasClass("paramtmpl")) {
												guestcount++;
												return;
											}
											
											if (depfield.hasClass("rsvpparam"+radelid) && guestcount==individual ){
												fieldIsVisible = true;
											}
											guestcount++;
										});										
									}
								
								}
							});
						});
					}
				}
				return fieldIsVisible;
			}
		});
		return true;
		*/
	}
}

window.addEvent('domready',function() {
	if (JevrConditionalFields) {
		JevrConditionalFields.setup(true);
	}
})


// to submit reminders in background
Element.implement ({
	formToJson: function(){
		var json = {};
		this.getElements('input, textarea, select').each(function(el){
			var name = el.name;
			var value = el.get('value');
			if (value === false || !name || el.disabled) return;
			// multi selects
			if (name.contains('[]') && (el.tagName.toLowerCase() =='select' ) && el.get('multiple')==true){
				name = name.substr(0,name.length-2);
				if (!json[name]) json[name] = [];
				el.getElements('option').each(function(opt){
					if (opt.selected ==true) json[name].push(opt.value);
				});
			}
			else if (name.contains('[]') && (el.type=='radio' || el.type=='checkbox') ){
				if (!json[name]) json[name] = [];
				if (el.checked==true) json[name].push(value);
			}
			else if (el.type=='radio' || el.type=='checkbox'){
				//alert(el+" "+el.name+ " "+el.checked+ " "+value);
				if (el.checked==true) {
					json[name] = value;
				}
			}
			else json[name] = value;
		});
		return json;
	}

});

function updateReminder(){
	var requestObject = new Object();
	requestObject.error = false;
	requestObject.formdata = $(document.jevreminderform).formToJson();
	var url = $(document.jevreminderform).action;
	
	var jSonRequest = new Request.JSON({
		'url':url,
		onSuccess: function(json, responsetext){
			if (!json){
				// TODO make these strings translateable
				alert(JevRsvpLanguage.translate("JEV_COULD_NOT_RECORD_REMINDER"));
			}
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				if (json.message){
					alert(json.message);
				}
			}
		},
		onFailure: function(x){
			alert('Something went wrong...'+x);
		}
	}).post({
		'json':JSON.encode(requestObject)
	});
}

function toggleSessionAccessMessage() {
	if (document.getElement("[name=custom_rsvp_sessionaccess]").value>0){
		$("rsvp_sessionaccessmessage").style.display="block";
	}
	else {
		$("rsvp_sessionaccessmessage").style.display="none";
	}
}