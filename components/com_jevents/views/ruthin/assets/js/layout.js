window.addEvent('domready', function(){
	// adjust for the border width
	var adjust = 1;
	if ($("jevents_body").hasClass("jeventsdark")){
//		adjust = 2;
	}
	$$(".jev_dayoutofmonth").each(
	function(el){
		el.style.width=(Math.max(0, parseInt(el.offsetWidth) - adjust)) +"px";
		if (el.getParent().hasClass("slots1")){
			el.style.height = "81px";
		}
		else {
			var psize = el.getParent().getSize();
			el.style.height=psize.y+"px";
		}
		if (el.hasClass("jevblocks1")){
			el.style.borderRightWidth="1px";
		}
		else {
			el.style.borderRightWidth="0px";
		}
	},this);

});