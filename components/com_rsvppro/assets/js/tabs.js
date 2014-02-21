// trimmed down from http://www.silverscripting.com/mootabs/
var mootabs = new Class({
	
	initialize: function(element, options) {
		this.options = Object.extend({
			mouseOverClass:		'active',
			activateOnLoad:		'tab0'
		}, options || {});
		
		this.el = $(element);
		this.elid = element;
				
		this.titles = $$('#' + this.elid + ' ul.mootabs_title li');
		this.panelHeight = this.el.getSize().y - (this.titles[0].getSize().y + 4);
		if (this.panelHeight<0) {
		// This causes problems in MSIE7
		//this.panelHeight = "50";
		}

		this.titles.each(function(item) {
			item.addEvent('click', function(){
				item.removeClass(this.options.mouseOverClass);
				this.activate(item);
			}.bind(this)
				);
			
			item.addEvent('mouseover', function() {
				if(item != this.activeTitle)
				{
					item.addClass(this.options.mouseOverClass);
				}
			}.bind(this));
			
			item.addEvent('mouseout', function() {
				if(item != this.activeTitle)
				{
					item.removeClass(this.options.mouseOverClass);
				}
			}.bind(this));
		}.bind(this));		

		if(this.options.activateOnLoad != 'none')
		{
			if(this.options.activateOnLoad == 'tab0')
			{
				this.activate(this.titles[0], 0);
			}
			else
			{
				this.activate(this.options.activateOnLoad, 0);
			}
		}
	},
	
	activate: function(tab){
		if(typeOf(tab) == 'string') 
		{
			//myTab = $$('#' + this.elid + ' ul li').filterByAttribute('title', '=', tab)[0];
			myTab = $$('#' + this.elid + ' ul li').filter('[title='+ tab+']')[0];
			tab = myTab;
		}

		if(typeOf(tab) == 'element')
		{
			var activetab = 0;
			var t=0;
			this.titles.each(function(item) {
				if (item.title == tab.title){
					activetab=t;
				}
				t++;
			});
			this.titles.removeClass('active');			
			tab.addClass('active');
			this.activeTitle = tab;
			this.activeTab = activetab ;

			var pane = $("registration-tab-pane");
			if (activetab==0){
				// first find the rows that only apply to the first attendee and display these
				var elems = pane.getElements(".type0param");
				elems.each(function(elem){
					//elem.style.display="table-row";
					elem.removeClass("type0paramHidden")
				});

				// next find the rows that apply to the second and over and display there
				elems = pane.getElements(".type2param");
				elems.each(function(elem){
					//elem.style.display="none";
					elem.addClass("type2paramHidden")
				});

			}
			else {
				// first find the rows that only apply to the first attendee and hide these
				elems = pane.getElements(".type0param");
				elems.each(function(elem){
					//elem.style.display="none";
					elem.addClass("type0paramHidden")
				});

				// next find the rows that apply to the second and over and display there
				elems = pane.getElements(".type2param");
				elems.each(function(elem){
					//elem.style.display="table-row";
					elem.removeClass("type2paramHidden")
				});

			}

			// next find the rows that apply to the second and over and display there
			elems = pane.getElements(".type1param");
			if (pane.getElements(".type2param").length>0){
				// this doesn't work in Chrome - must be a Mootools bug!'
				//  elems.extend(pane.getElements(".type2param"));
				pane.getElements(".type2param").each (function(elem) {
					elems.push(elem);
				});
			}
			elems.each(function(elem){
				var paramelements = elem.getElements('.rsvpparam');
				var e = 0;
				paramelements.each(function(pelem){
					if (pelem.hasClass("rsvpparam"+activetab)) {
						pelem.removeClass("hideparam");
					}
					else {
						pelem.addClass("hideparam");
					}
					e++;
				})
			});

			if (activetab>0) $("killguest").style.display="block";
			else $("killguest").style.display="none";
		}
		if (JevrConditionalFields) {
			JevrConditionalFields.setup(false);
		}

	},
		
	addTab: function(title, label, count){
		//the new title
		var newTitle = new Element('li', {
			'title': title
		});
		newTitle.appendText(label);
		this.titles.include(newTitle);
		$$('#' + this.elid + ' ul').adopt(newTitle);
		newTitle.addEvent('click', function() {
			this.activate(newTitle);
		}.bind(this));
		
		newTitle.addEvent('mouseover', function() {
			if(newTitle != this.activeTitle)
			{
				newTitle.addClass(this.options.mouseOverClass);
			}
		}.bind(this));
		newTitle.addEvent('mouseout', function() {
			if(newTitle != this.activeTitle)
			{
				newTitle.removeClass(this.options.mouseOverClass);
			}
		}.bind(this));

		//Now for the form content!
		var elems = $(document).getElements(".paramtmpl");
		elems.each(function(item){
			// do not clone elements whose parents are also to be cloned - otherwise we may get duplicate elements
			if (item.getParent().hasClass("paramtmpl") || item.getParent().getParent().hasClass("paramtmpl")) {
				return;
			}
			var clone = item.clone();
			// radio boxes etc. have their own ids set and cloning will drop these to avoid double ids with the same values so we replace the innerHTML
			if ((!clone.name || !clone.id) && item.innerHTML!="" ){
				var html = item.innerHTML;
				// For the radio lists etc only
				html = html.replace(/\'xxxyyyzzz\'/g,(count-1));
				html = html.replace(/xxxyyyzzz/g,(count-1));
				html = html.replace(/paramtmpl_/g,"");
				html = html.replace(/paramtmpl/g,"");
				// fix the ID for radio fields!
				clone.innerHTML = html.replace(/_xxx/g,"_"+(count-1));				
			}
			clone.removeClass("paramtmpl");
			clone.addClass("rsvpparam");
			clone.addClass("rsvpparam"+(count-1));
			// replace id and names
			if (item.id){   
				var cloneid = item.id.replace("xxxyyyzzz",(count-1));
				clone.id = cloneid.replace("xxx",(count-1));
			}
			if (item.name){
				clone.name = clone.name.replace("paramtmpl_","");
			}
			// insert the clone in the DOM
			clone.inject(item,'before');

		});
		
		// activate the new tab
		this.activate(title);
	},
	
	removeTab: function(title){
		if(this.activeTitle.title == title)
		{
			this.activate(this.titles[0]);
		}
		try {
			this.activeTitle.remove();
		}
		catch (e){
			this.activeTitle.dispose();
		}
		
		// Now remove the form content!
		// find the relevant form entries
		var pane = $("registration-tab-pane");
		var paramelements = pane.getElements('.rsvpparam'+this.activeTab);
		var e = 0;
		paramelements.each(function(pelem){
			pelem.parentNode.removeChild(pelem);
			e++;
		});

		this.activate(this.titles[0], 0);
		
	},

	removeActiveTab: function(){
		this.removeTab(this.activeTitle);
	},
	
	next: function(){
		var nextTab = this.activeTitle.getNext();
		if(!nextTab) {
			nextTab = this.titles[0];
		}
		this.activate(nextTab);
	},
	
	setTitle: function (title, label) {
		$$('#' + this.elid + ' ul li').filterByAttribute('title', '=', title)[0].innerHTML = label;
	}, 
	previous: function(){
		var previousTab = this.activeTitle.getPrevious();
		if(!previousTab) {
			previousTab = this.titles[this.titles.length - 1];
		}
		this.activate(previousTab);
	}, 
	setInitialAttendeeNames: function (){
		var nameattendeenames = $$(".attendeename");
		nameattendeenames.each(function(attendeename){
			var fieldid = attendeename.id.replace('params','');
			if (fieldid.indexOf("_xxx")<0 && fieldid.indexOf("field")==0 && fieldid.indexOf("_")>0){
				var fieldid= fieldid.split("_");

				if (regTabs.titles[fieldid[1]]){
					regTabs.titles[fieldid[1]].innerHTML = 	attendeename.value;
					if (attendeename.value==""){
						regTabs.titles[fieldid[1]].innerHTML  = $("jevnexttabtitle").value.replace('xxx',$('lastguest').value);
					}
				}
			};
			
		});
	}	

});

function setAttendeeName(field){
	try { 
		if (regTabs.activeTitle && field.value!=""){
			regTabs.activeTitle.innerHTML = field.value;
		}
	}
	catch (e) {}
}