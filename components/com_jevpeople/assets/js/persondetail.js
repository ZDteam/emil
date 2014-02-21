var myMap = false;
var myMarker = false;
var myGeocoder = null;

function myMapload(){
	

	var point =  new google.maps.LatLng(globallat,globallong);
                
	var myOptions = {
		center: point,
		zoom: globalzoom,
		mapTypeId: google.maps.MapTypeId[maptype]
	};
        
	myMap = new google.maps.Map(document.getElementById("gmapppl"), myOptions);
	//myMap.addControl( new GSmallMapControl() );
	//myMap.addControl( new GMapTypeControl()) ;
	//myMap.addControl( new GOverviewMapControl(new GSize(60,60)) );
	//myMap.setMapType(maptype );

	if (!markerOptions) {
		//markerOptions = {draggable:true };
		var markerOptions = {
			position:point,
			map:myMap,
			animation: google.maps.Animation.DROP,
			draggable:false
		};
	}

	//var point = new GLatLng(globallat,globallong);
	//myMap.setCenter(point, globalzoom );

	myMarker = new google.maps.Marker( markerOptions);
	//myMap.addOverlay(myMarker);


	google.maps.event.addListener(myMap, "click", function(e) {
		window.open (googlemapsurl+"/maps?f=q&geocode=&time=&date=&ttype=&ie=UTF8&t=h&om=1&q="+globaltitle+"@"+globallat+","+globallong+"&ll="+globallat+","+globallong+"&z="+globalzoom+"&iwloc=addr","map");
	});

	google.maps.event.addListener(myMarker, "click", function(e) {
		window.open (googlemapsurl+"/maps?f=q&geocode=&time=&date=&ttype=&ie=UTF8&t=h&om=1&q="+globaltitle+"@"+globallat+","+globallong+"&ll="+globallat+","+globallong+"&z="+globalzoom+"&iwloc=addr","map");
	});
				

};

window.addEvent("load",myMapload);

