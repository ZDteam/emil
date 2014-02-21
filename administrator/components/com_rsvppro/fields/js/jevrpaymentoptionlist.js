/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrpaymentoptionlist = {
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
	updatePreview:function(id){
		var countel = 0;
		// remove exising options
		Array.from($('pdv'+id).options).slice().each(function(opt){
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
			if (el.value!="" && el.value!="xxx"){
				opt = new Element('option');
				$('pdv'+id).appendChild(opt);
				opt.text= el.value;
				//$('pdv'+id).options[countel].text = el.value;
				countel++;
			}
		});
	},
	defaultOption:function(el, id, op){
		var value = $("options"+id+"_v_"+op).value;
		$("dv"+id).value = value;
		var text =$("options"+id+"_t_"+op).value;
		Array.from($('pdv'+id).options).slice().each(function(opt){
			opt.selected = false;
			if (opt.text== text){
				opt.selected = true;
			};
		});

	}
}