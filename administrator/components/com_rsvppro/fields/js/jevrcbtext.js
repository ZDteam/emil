/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrcbtext = {
	setvalue: function(id){
		$('pdv'+id).value = $('dv'+id).value;
	},
	changeSize:function(id){
		$('pdv'+id).size = $('size'+id).value;
		//$('dv'+id).size = $('size'+id).value;
	},

	changeMaxlength:function(id){
		$('pdv'+id).maxlength = $('maxlength'+id).value;
		//$('dv'+id).maxlength = $('maxlength'+id).value;
	}
}