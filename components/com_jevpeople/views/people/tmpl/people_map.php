<?php
defined('_JEXEC') or die('Restricted access');

if (JRequest::getInt("pop", 0))
	return;

$compparams = JComponentHelper::getParams("com_jevpeople");
$googlekey = JevPeopleHelper::getApiKey(); //$compparams->get("googlemapskey","");
$googleurl = JevPeopleHelper::getApiUrl(); //$compparams->get("googlemaps",'http://maps.google.com');

$lang = JFactory::getLanguage();
$hl = substr($lang->getTag(), 0, 2);
if ($googlekey != "")
{
	JHTML::script($googleurl.'/maps/api/js?key=' . $googlekey . "&amp;sensor=false",  true);
}
else
{
	JHTML::script($googleurl.'/maps/api/js?sensor=false', true);
}
$task = JRequest::getString("jevtask", "");

if (!$this->items || count($this->items) == 0)
	return;

$zoom = 10;
$document = JFactory::getDocument();
$document->addStyleDeclaration("div.mainpersonmap {clear:left;} div#gmapMulti{margin:5px auto}");
?>
<div class='mainpersonmap'>
	<?php
	$root = JURI::root();
	$Itemid = JRequest::getInt("Itemid");
	$script = "var urlroot = '" . JURI::root() . "media/com_jevpeople/images/';\n";
	$script.=<<<SCRIPT
var myMapMulti = false;


function addPoint(lat, lon, locid, loctitle, evttitle, icon){
		// Create our "tiny" marker icon
		var blueIcon = new google.maps.MarkerImage(urlroot + icon,
		// This marker is 32 pixels wide by 32 pixels tall.
		new google.maps.Size(32, 32),
		// The origin for this image is 0,0 within a sprite
		new google.maps.Point(0,0),
		// The anchor for this image is the base of the flagpole at 0,32.
		new google.maps.Point(16, 32));
		                
		// Set up our GMarkerOptions object
		var point = new google.maps.LatLng(lat,lon);
		markerOptions = { icon:blueIcon, draggable:false , map:myMapMulti, icon:blueIcon, position:point};	
		
		var myMarkerMulti = new google.maps.Marker(markerOptions);
		
		var infowindow = new google.maps.InfoWindow({content: "<div style='color:rgb(134,152,150);font-weight: bold;max-width:300px!important;'>"+evttitle+"<br/><br/><span style='color:#454545;font-weight:normal'>"+loctitle+"</span></div>"});
		google.maps.event.addListener(myMarkerMulti, "mouseover", function(e) {
			  infowindow.open(myMapMulti,myMarkerMulti);
		});
		google.maps.event.addListener(myMarkerMulti, "mouseout", function(e) {
			  infowindow.close(myMapMulti,myMarkerMulti);
		});
		google.maps.event.addListener(myMarkerMulti, "click", function(e) {			
			// use for event detail page
			document.location.replace("$root/index.php?option=com_jevpeople&task=people.detail&se=1&Itemid=$Itemid&pers_id="+locid);
		});

}

function myMaploadMulti(){
		
SCRIPT;
	$minlon = 0;
	$minlat = 0;
	$maxlon = 0;
	$maxlat = 0;
	$first = true;
	foreach ($this->items as $person)
	{
		if ($person->geozoom == 0)
			continue;
		if ($first)
		{
			$minlon = floatval($person->geolon);
			$minlat = floatval($person->geolat);
			$maxlon = floatval($person->geolon);
			$maxlat = floatval($person->geolat);
			$first = false;
		}
		$minlon = floatval($person->geolon) > $minlon ? $minlon : floatval($person->geolon);
		$minlat = floatval($person->geolat) > $minlat ? $minlat : floatval($person->geolat);
		$maxlon = floatval($person->geolon) < $maxlon ? $maxlon : floatval($person->geolon);
		$maxlat = floatval($person->geolat) < $maxlat ? $maxlat : floatval($person->geolat);
	}
	if ($minlon == $maxlon)
	{
		$minlon-=0.002;
		$maxlon+=0.002;
	}
	if ($minlat == $maxlat)
	{
		$minlat-=0.002;
		$maxlat+=0.002;
	}
	$midlon = ($minlon + $maxlon) / 2.0;
	$midlat = ($minlat + $maxlat) / 2.0;

	$script.=<<<SCRIPT

var myOptions = {
	center: new google.maps.LatLng($midlat,$midlon),
	mapTypeId: google.maps.MapTypeId.ROADMAP
}

myMapMulti = new google.maps.Map(document.getElementById("gmapMulti"),myOptions );

var bounds = new google.maps.LatLngBounds(new google.maps.LatLng($minlat,$minlon), new google.maps.LatLng($maxlat,$maxlon));

SCRIPT;
	foreach ($this->items as $person)
	{
		 if (!isset($person->mapicon) || $person->mapicon==""){
			 $person->mapicon = "blue-dot.png";
		 }
		if ($person->pers_id == 0)
			continue;

		$script.="	addPoint($person->geolat,$person->geolon,$person->pers_id, '" . addslashes(str_replace(array("\n", "\r"), "", $person->description)) . "', '" . addslashes(str_replace("\n", "", $person->title)) . "', '" . $person->mapicon . "');\n";
	}
	$script.=<<<SCRIPT
   myMapMulti.fitBounds(bounds);
};
window.addEvent("load",function (){window.setTimeout("myMaploadMulti()",1000);});
SCRIPT;
	$document = JFactory::getDocument();
	$document->addScriptDeclaration($script);
	JHTML::_('behavior.modal');
	?>
	<div id="gmapMulti" style="width: 600px; height:600px;overflow:hidden;"></div>

</div>