var myMap = false;
var myMarker = false;
var myGeocoder = null;
var peopleDeleteWarning = "";

function myMapload(){

	if (typeof google == 'undefined' || !$("gmap")) return;
	var point =  new google.maps.LatLng(globallat,globallong);

	var myOptions = {
		center: point,
		zoom: globalzoom,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	myMap = new google.maps.Map(document.getElementById("gmap"), myOptions);

	var markerOptions = {
		position:point,
		map:myMap,
		animation: google.maps.Animation.DROP,
		draggable:true
	};

	myMarker = new google.maps.Marker( markerOptions);

	google.maps.event.addListener(myMap, 'click',  function(e) {
		if (e.latLng) {
			document.getElementById('geolat').value=e.latLng.lat();
			document.getElementById('geolon').value=e.latLng.lng();
			document.getElementById('geozoom').value=myMap.getZoom();

			myMarker.setPosition(e.latLng);
			myMap.setCenter(e.latLng);
		}
	});

	google.maps.event.addListener(myMarker, "dragend", function(e) {
		if (e.latLng) {
			document.getElementById('geolat').value=e.latLng.lat();
			document.getElementById('geolon').value=e.latLng.lng();
			document.getElementById('geozoom').value=myMap.getZoom();
			myMap.setCenter(e.latLng);
		}
	});

	google.maps.event.addListener(myMap,  "zoom_changed", function() {
		document.getElementById('geozoom').value=myMap.getZoom();
	});
	
	myGeocoder = new google.maps.Geocoder();			
	
};

window.addEvent("load",myMapload);


function findAddress(){
	address = document.getElementById("googleaddress");
	if (address){
		address = address.value
		country = document.getElementById("googlecountry").value;
		address += ","+country;
	}
	else {
		try {
			street = document.getElementById("street").value;
			city = document.getElementById("city").value;
			state = document.getElementById("state").value;
			country = document.getElementById("country").value;
			postcode = document.getElementById("postcode").value;
			address = "";
			if (street.length>0){
				address+=street+",";
			}
			if (city.length>0){
				address+=city+",";
			}
			if (state.length>0){
				address+=state+",";
			}
			if (postcode.length>0){
				address+=postcode+",";
			}
			if (country.length>0){
				address+=country;
			}
		}
		catch (e){}
	}
	if (myGeocoder) {
		myGeocoder.geocode( { 'address': address}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				point = results[0].geometry.location;
				myMap.setCenter(point);
				myMarker.setPosition(point);
				document.getElementById('geolat').value=point.lat();
				document.getElementById('geolon').value=point.lng();
				document.getElementById('geozoom').value=myMap.getZoom();
			}
			else {
				alert(address + " not found");
			}
		});
	}
}

var ltujsonactive = false;
var cancelSearch = true;
var ltutimeout=false;
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
		ltuClearMatches();
		currentSearch = "";
		return;
	}

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "checkTitle";
	requestObject.title = elem.value;
	requestObject.type = $("type_id").value;
	requestObject.client = client;

	minlength=2;

	if (elem.value.length>=minlength){
		if (ltujsonactive) return;

		currentSearch = elem.value;

		if (ltutimeout) {
			clearTimeout(ltutimeout);
		}

		//url += '?start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';

		ltujsonactive = true;
		var jSonRequest = new Request.JSON({
			'url':url,
			onSuccess: function(json, responsetext){
				cancelSearch = false;
				ltujsonactive = false;
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
					var ltumatches = document.getElement("#ltumatches");
					//alert(json.timing);
					if (json.titles.length==1){
						var ltumatches = document.getElement("#ltumatches");
						ltumatches.style.display="none";
						ltuClearMatches();

						var newid = json.titles[0]["id"]

						var linktouser = $("linktouser");
						var linktousertext = $("linktousertext");
						linktouser.value = newid;
						linktousertext.innerHTML=json.titles[0]["name"]+" ("+json.titles[0]["username"]+")";
					}
					else if (json.titles.length>1){
						ltumatches.style.display="block";
						ltuClearMatches();
						var shownotes = false;
						for (var jp=0;jp<json.titles.length;jp++){
							// If have started another search already then cancel this one
							if (cancelSearch) {
								return;
							}
							var option = new Element('div', {
								id:"ltu_"+json.titles[jp]["id"]
								});
							option.addEvent('mousedown', ltuaddInvitee.bindWithEvent(option));
							option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")");

							option.injectInside(ltumatches);
						}
					}
					else {
						ltuClearMatches();
					}

					// If have started another search already then cancel this one
					if (cancelSearch) {
						return;
					}
				}
			},
			onFailure: function(){
				if (ignoreSearch) return;
				ltujsonactive = false;
				alert('Something went wrong...')
				ltuClearMatches();
			}
		}).post({
			'json':JSON.encode(requestObject)
		});
	}
}

function ltuClearMatches(){
	if (ltutimeout) {
		clearTimeout(ltutimeout);
	}
	var ltumatches = document.getElement("#ltumatches");
	ltumatches.innerHTML = "";
}

function ltuaddInvitee(event){
	var oldid = this.id;
	var newid = this.id.replace("ltu_","");

	var linktouser = $("linktouser");
	var linktousertext = $("linktousertext");
	linktouser.value = newid;
	linktousertext.innerHTML=this.innerHTML;

	var ltumatches = document.getElement("#ltumatches");
	ltumatches.style.display="none";
	ltuClearMatches();

}
