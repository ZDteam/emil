/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrcoupon = {
	setvalue: function(id){
		$('pdv'+id).value = $('dv'+id).value;
	},
	changeSize:function(id){
		$('pdv'+id).size = $('size'+id).value;
		$('dv'+id).size = $('size'+id).value;
	},
	changeMaxlength:function(id){
		$('pdv'+id).maxlength = $('maxlength'+id).value;
		$('dv'+id).maxlength = $('maxlength'+id).value;
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
	}



}