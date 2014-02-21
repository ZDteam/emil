<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1804 2011-03-17 14:36:02Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * JEVMenu Field class for the JEvents Component
 *
 * @package		JEvents.fields
 * @subpackage	com_banners
 * @since		1.6
 */
class JFormFieldJevType extends JFormFieldList
{
	/**
	 * The form field type.s
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JevType';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	public function getOptions()
	{
		// Initialize variables.
		$options = array();
		$db = &JFactory::getDBO();

		$query = 'SELECT tp.type_id AS value, tp.title AS text FROM #__jev_peopletypes AS tp order by title';
		$db =& JFactory::getDBO();
		$db->setQuery($query);
		$options = $db->loadObjectList();		

		array_unshift($options, JHTML::_("select.option","",JText::_("COM_JEVPEOPLE_SELECT_TYPE"),"value","text"));

		// add script to auto open the basic options tab!
		$doc = JFactory::getDocument();
		$script = <<<SCRIPT
window.addEvent('load', function() {
	var basicoptions = document.getElement('#basic-options')
	if (basicoptions && !basicoptions.hasClass('pane-toggler-down')) {
	   basicoptions.fireEvent('click', basicoptions, 500);
	};
});
SCRIPT;
		$doc->addScriptDeclaration($script);
		return $options;
	}
}
