/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrjsfield = {
	setvalue: function(id){
		$('pdv'+id).innerHTML = $('fieldname'+id).options[$('fieldname'+id).selectedIndex].text;
	}
}