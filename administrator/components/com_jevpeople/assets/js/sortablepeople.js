var trashimages = 0;
var sortablePeople = {
	setup:function (){
		new Sortables('sortablePeople',{"onComplete":sortablePeople.fieldsHaveReordered});
		var uls = $('sortablePeople');
		var lis = uls.getChildren();
		lis.each(function(item, i){
			sortablePeople.copyTrash(item);
		},this);
		/*
		var trashitems = $$('.sortabletrash');
		trashitems.each(function(item, i){
		sortablePeople.setupTrashImage(item);
		},this);
		*/
	},
	copyTrash:function(item){
		var trashimage = $('trashimage');
		var child = trashimage.clone();
		child.style.display="inline";
		child.style.marginLeft="5px";
		child.style.lineHeight = item.style.lineHeight = "16px";
		child.id = "trashimage"+trashimages;
		item.style.backgroundImage="none";
		item.style.listStyleType="none";
		item.style.paddingLeft="0px";
		//item.appendChild(child);
		child.inject(item,"top");
		sortablePeople.setupTrashImage(child);
		trashimages ++;
	},
	fieldsHaveReordered:function(targetNode){
		// Now rebuild the select list items
		var custom_person = $("custom_person");
		if (custom_person){
			var options = custom_person.getChildren();

			// new dummy selectlist
			var selectList = new Element('select');

			options.each(function (item,i){
				selectList.appendChild(item);
				//item.remove();
			});

			var uls = $('sortablePeople');
			var lis = uls.getChildren();
			lis.each(function(item, i){
				selectList.getChildren().each(function(opt,j){
					if (opt.id==item.id+"option"){
						custom_person.appendChild(opt);
						opt.selected = true;
					}
				});

			});
		}
		else {
			var menuperson = $("menuperson");
			var compatmenuperson = $("compat_menuperson");
			if (menuperson) {
				menuperson.value = "";
				compatmenuperson.value = "";
				var uls = $('sortablePeople');
				var lis = uls.getChildren();
				lis.each(function(item, i){

					var id = item.id.replace("sortablepers","");
					menuperson.value += 'jevp:'+id+",";
					compatmenuperson.value += 'jevp:'+id+",";
				});

			}

		}

	},
	setupTrashImage:function(item){
		item.addEvent('mousedown',function(event){
			if (!event){
				event = new Event(event);
			}
			try {
				event.stop();
			}
			catch (e) {
				event.stopImmediatePropagation();
			}
			if (!confirm(peopleDeleteWarning)) return;			
			try {
				// mootools
				var id = event.target.parentNode.id;
				// remove the item from the li list
				event.target.parentNode.dispose();
			}
			catch (e) {
				var id = event.explicitOriginalTarget.parentNode.id;				
				// remove the item from the li list
				event.explicitOriginalTarget.parentNode.remove();
			}
			// remove the item from the select list
			var option = $(id+"option");
			if (option) {
				try {
					option.remove();
				}
				catch (e) {
					option.dispose();
				}
			}

			var menuperson = $("menuperson");
			id = id.replace("sortablepers","");
			if (menuperson)  menuperson.value = menuperson.value.replace('jevp:'+id+",", "");
		});
	},
	selectThisPerson:function (personid, elem, typename){
		var duplicateTest = $("sortablepers"+personid);
		if (duplicateTest) {
			alert(jevpeople.duplicateWarning);
			SqueezeBox.close();
			return false;
		}
		var title = elem.innerHTML;
		var custom_person = $('custom_person');
		var opt = new Element('option',{value:personid,id:"sortablepers"+personid+"option"});
		if (custom_person){
			custom_person.appendChild(opt);
		}
		opt.text = title + " ("+typename+")";
		opt.selected = 1;
		// No do the visible list item too
		var uls = $('sortablePeople');
		var li = new Element('li',{id:"sortablepers"+personid});
		li.appendText(opt.text);
		if (uls){
			uls.appendChild(li);
			sortablePeople.copyTrash(li);

			// reset the sortable list
			new Sortables('sortablePeople',{"onComplete":sortablePeople.fieldsHaveReordered});
		}

		// If actually selecting a person for a menu item we do something different:
		var menuperson = $('menuperson');
		if (menuperson){
			menuperson.value += "jevp:"+personid+",";
		}

		SqueezeBox.close();
		return false;
	},
	selectPerson:function (url){

		SqueezeBox.initialize({});
		SqueezeBox.setOptions(SqueezeBox.presets,{'handler': 'iframe','size': {'x': 850, 'y': 500},'closeWithOverlay': 0});
		SqueezeBox.url = url;

		SqueezeBox.setContent('iframe', SqueezeBox.url );
		return;// SqueezeBox.call(SqueezeBox, true);

	},
	exists:function(){
		return l
	}
}
