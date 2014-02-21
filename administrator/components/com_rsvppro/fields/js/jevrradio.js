/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrradio = {
	setvalue: function(id){
		$('pdv'+id).value = $('dv'+id).value;
	},
	changeMultiple:function(id){
		$('pdv'+id).size = $('size'+id).value;
		$('dv'+id).size = $('size'+id).value;
	},
	showNext:function(el, id, op){
		if ($("options"+id+"_t_"+op).value!=""){
			var sib =$(el).parentNode.parentNode.getNext();
			if (sib) sib.style.display='';
		}
	},
	newOption:function(id){
		var newdone = false;
		$("options"+id).getElements("tr").each(function(el){
			if (!newdone && el.style.display=="none"){
				newdone = true;
				el.style.display="";
			}
		});
	},
	deleteOption: function(el){
		el = $(el);
		try {
			// Mootools 1.2
			el.parentNode.parentNode.remove();
		}
		catch (e){
			// Mootools 1.3
			el.parentNode.parentNode.dispose();
		}
	},
	updatePreview:function(id, op){
		var countel = 0;
		// remove exising options
		Array.from($('pdv'+id).getChildren()).slice().each(function(opt){
			try {
				// Mootools 1.2
				opt.remove();
			}
			catch (e){
				// Mootools 1.3
				opt.dispose();
			}
		});
		// create the new options
		$("options"+id).getElements("input.inputlabel").each(function(el){
			if (el.value!="" ){
				var label = new Element('label');
				var radio = new Element('input', {type:'radio'});
				label.appendChild(radio);
				$('pdv'+id).appendChild(label);
				label.appendText(el.value);

				var rid = el.id.replace("_t_","_r_");
				rid = rid.replace("optionsfield","defaultfield");

				radio.checked= $(rid).checked;
				//var br = new Element('br');
				//$('pdv'+id).appendChild(br);
			}
		});
	},
	defaultOption:function(el, id, op){
		$(id+"preview").getElements("label").each(function(lab){
			$("options"+id).getElements("input.inputlabel").each(function(el){
				var text;
				try {
					// Mootools version 1.3 version 
					text = lab.get('text');					
				}
				catch (e){
					// Mootools 1.2 version
					text = lab.getText();
				}
				if (text == el.value){
					var rid = el.id.replace("_t_","_r_");
					rid = rid.replace("optionsfield","defaultfield");
					var radio = lab.getElement('input');
					radio.checked= $(rid).checked;
					var vid = el.id.replace("_t_","_v_");
					$(rid).value = $(vid).value;
				}
			});
		})
	}



}