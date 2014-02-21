/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrtextarea = {
	setvalue: function(id){
		$('pdv'+id).value = $('dv'+id).value;
	},
	changeCols:function(id){
		$('pdv'+id).cols = $('cols'+id).value;
		$('dv'+id).cols = $('cols'+id).value;
	},
	changeRows:function(id){
		$('pdv'+id).rows = $('rows'+id).value;
		$('dv'+id).rows = $('rows'+id).value;
	}
}