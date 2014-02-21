/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrboolean = {
	settrue : function(id){
		$('defaultvalue'+id).value = 1;
		$('pdv'+1+id).checked=true;
	},
	setfalse : function(id){
		$('defaultvalue'+id).value = 0;
		$('pdv'+0+id).checked=true;
	}
}