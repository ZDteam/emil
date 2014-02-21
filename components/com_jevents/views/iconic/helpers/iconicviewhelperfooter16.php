<?php 
defined('_JEXEC') or die('Restricted access');
		
function IconicViewHelperFooter16($view){
if (JRequest::getInt('pop', 0)) { ?>
	<div class="ev_noprint"><p align="center">
	<a href="#close" onclick="if (window.parent==window){self.close();} else try {window.parent.SqueezeBox.close(); return false;} catch(e) {self.close();return false;}" title="<?php echo JText::_('JEV_CLOSE');?>"><?php echo JText::_('JEV_CLOSE');?></a>
	</p></div>
<?php
}
$view->loadHelper("JevViewCopyright");
JevViewCopyright(); ?>
</div>
<?php
	$dispatcher	=& JDispatcher::getInstance();
	$dispatcher->trigger( 'onJEventsFooter');
	
	$task = JRequest::getString("jevtask");
	$view->loadModules("jevpostjevents");
	$view->loadModules("jevpostjevents_".$task);
	
	// New experimental scalable layout
	$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
	if ($params->get('icscalable',0)==2 && $task=="month.calendar"){
?>
<script type="text/javascript">
	var myCSS = false;
	var processedClones = false;
	function setJEventsSize(){
		var jeventsBody = $("jevents_body");
		var jeventsBodyParent = jeventsBody.getParent();
		var size = jeventsBodyParent.getSize();
		var narrow = false;

		if (!myCSS){
		//	myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w905.css', {id: 'myStyle', title: 'myStyle'});
		}
		//return;

		if (!myCSS){
			if (size.x>905){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w905.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>835){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w835.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>765){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w765.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>695){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w695.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>625){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w625.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>555){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w555.css', {id: 'myStyle', title: 'myStyle'});
			}
			else if (size.x>485){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/w485.css', {id: 'myStyle', title: 'myStyle'});
			}
			else {
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/narrow.css', {id: 'myStyle', title: 'myStyle'});
				narrow = true;
			}
		}
		else {			
			if (size.x>905){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w905.css';
			}
			else if (size.x>835){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w835.css';
			}
			else if (size.x>765){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w765.css';
			}
			else if (size.x>695){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w695.css';
			}
			else if (size.x>625){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w625.css';
			}
			else if (size.x>555){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w555.css';
			}
			else if (size.x>485){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/w485.css';
			}
			else {
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/narrow.css';
				narrow = true;				
			}
		}
		if (narrow){
			cloneEvents();
			var listrowblock = document.getElement(".jev_listrowblock");
			if(listrowblock){
				listrowblock.style.display= "block";
			}
		}
		else {
			var listrowblock = document.getElement(".jev_listrowblock");
			if(listrowblock){
				listrowblock.style.display= "none";
			}
			setOutOfMonthSize.delay(1000);
		}
	}
	function setOutOfMonthSize(){
		$$(".jev_dayoutofmonth").each(
		function(el){
			if (el.getParent().hasClass("slots1")){
				el.style.height = "81px";
			}
			else {
				var psize = el.getParent().getSize();
				el.style.height=psize.y+"px";
			}
		},this);
	}
	function cloneEvents(){		
		if (!processedClones){
			processedClones = true;

			var myEvents = $$(".eventfull");
			if (myEvents.length==0){
				return;
			}
			var listrowblock = new Element('div', {'class':'jev_listrowblock'});

			var event_legend_container = document.getElement(".event_legend_container");
			if (event_legend_container){
				listrowblock.inject(event_legend_container, 'before');
			}
			else {
				var toprow = $("jev_maincal").getElement(".jev_toprow");
				listrowblock.inject(toprow, 'after');
				var clearrow = new Element('div', {'class':'jev_clear'});
				clearrow.inject(listrowblock, 'after');
			}
					
			myEvents.each(function(el) { 
				// really should be for each separate date!
				var listrow = new Element('div', {'class':'jev_listrow'});
				listrow.inject(listrowblock, 'bottom');
				var myClone = el.getParent().clone();				
				myClone.inject(listrow, 'bottom');
			});
		}
	}
	window.addEvent("domready",setJEventsSize);
	// set load event too incase template sets its own domready trigger
	window.addEvent("load",setJEventsSize);
	window.addEvent("resize",setJEventsSize);
		
</script>
	<?php
	}
	else 	if (($params->get('icscalable',0)==1 || $params->get("iconicwidth",905)=="scalable") && (($task=="month.calendar" && !$params->get('iclistmonth',0)) || ($task=="week.listevents" && $params->get('ictabularweek',0)) )){
?>
<script type="text/javascript">
	var myCSS = false;
	var processedClones = false;
	function setJEventsSize(){

		var jeventsBody = $("jevents_body");
		var jeventsBodyParent = jeventsBody.getParent();
		var size = jeventsBodyParent.getSize();
		var narrow = false;

		if (!myCSS){
			if (size.x<485){
				myCSS = Asset.css('/components/com_jevents/views/iconic/assets/css/narrowscalable.css', {id: 'myStyle', title: 'myStyle'});
				narrow = true;
			}
		}
		else {			
			if (size.x<485){
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/narrowscalable.css';
				narrow = true;				
			}
			else {
				myCSS.href = '/components/com_jevents/views/iconic/assets/css/scalable.css';
				narrow = false;
			}
		}
		if (narrow){
			cloneEvents();
			var listrowblock = document.getElement(".jev_listrowblock");
			if(listrowblock){
				listrowblock.style.display= "block";
			}
		}
		else {
			var listrowblock = document.getElement(".jev_listrowblock");
			if(listrowblock){
				listrowblock.style.display= "none";
			}
			setOutOfMonthSize.delay(1000);
		}
	}
	function setOutOfMonthSize(){
		$$(".jev_dayoutofmonth").each(
		function(el){
			if (el.getParent().hasClass("slots1")){
				el.style.height = "81px";
			}
			else {
				var psize = el.getParent().getSize();
				el.style.height=psize.y+"px";
			}
		},this);
	}
	function cloneEvents(){	
		if (!processedClones){
			processedClones = true;

			var myEvents = $$(".eventfull");
			// sort these to be safe!!
			myEvents.sort(function(a, b){
				if (!a.sortval){
					var aparentclasses = a.getParent().className.split(" ");
					for (var i=0;i<aparentclasses.length;i++){
						if (aparentclasses[i].indexOf("jevstart_")>=0){
							a.sortval =  aparentclasses[i].replace("jevstart_","");						
						}
					}
				}
				if (!b.sortval){
					var bparentclasses = b.getParent().className.split(" ");
					for (var i=0;i<bparentclasses.length;i++){
						if (bparentclasses[i].indexOf("jevstart_")>=0){
							b.sortval = bparentclasses[i].replace("jevstart_","");
						}
					}
				}
				return a.sortval>b.sortval;
			});
			
			if (myEvents.length==0){
				return;
			}
			var listrowblock = new Element('div', {'class':'jev_listrowblock'});

			var event_legend_container = document.getElement(".event_legend_container");
			if (event_legend_container){
				listrowblock.inject(event_legend_container, 'before');
			}
			else {
				var toprow = $("jev_maincal").getElement(".jev_toprow");
				listrowblock.inject(toprow, 'after');
				var clearrow = new Element('div', {'class':'jev_clear'});
				clearrow.inject(listrowblock, 'after');
			}

			var listrow = new Element('div', {'class':'jev_listrow'});

			var hasdaynames = false;
			myEvents.each(function(el) { 
				if (!hasdaynames ){
					var dayname = el.getParent().getElement(".hiddendayname");
					if (dayname ) {
						hasdaynames = true;
					}
				}
			});
			
			myEvents.each(function(el) { 

				var dayname = el.getParent().getElement(".hiddendayname");
				if (dayname ) {
					dayname.style.display="block";
					dayname.inject(listrowblock, 'bottom');
				}
				if (dayname  || !hasdaynames ){
					// really should be for each separate date!
					listrow = new Element('div', {'class':'jev_listrow'});
					listrow.style.marginBottom="10px";
					listrow.style.marginTop="5px";
					listrow.inject(listrowblock, 'bottom');					
				}


				var hiddenicon = el.getParent().getElement(".hiddenicon");
				hiddenicon = hiddenicon.getElement("a");
				hiddenicon.removeClass("hiddenicon");
				hiddenicon.inject(listrow, 'bottom');
				
				var myClone = el.getParent().clone();
				myClone.addClass("jev_daywithevents");
				myClone.removeClass("jev_dayoutofmonth");
				myClone.removeClass("jevblocks0");
				myClone.removeClass("jevblocks1");				
				myClone.removeClass("jevblocks2");				
				myClone.removeClass("jevblocks3");				
				myClone.removeClass("jevblocks4");				
				myClone.removeClass("jevblocks5");				
				myClone.removeClass("jevblocks6");				
				myClone.removeClass("jevblocks7");				
				myClone.style.height="inherit";
				myClone.inject(listrow, 'bottom');

				var clearrow = new Element('div', {'class':'jev_clear'});
				clearrow.inject(listrow, 'bottom');
			});
		}
	}
	window.addEvent("domready",setJEventsSize);
	// set load event too incase template sets its own domready trigger
	window.addEvent("load",setJEventsSize);
	window.addEvent("resize",setJEventsSize);
		
</script>
	<?php
	}
	JEVHelper::componentStylesheet($view, "extra.css");
	
}