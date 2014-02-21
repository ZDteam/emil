<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
class JFormFieldJEVTemplate extends JFormFieldList
{

	/**
	 * The form field type.s
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JEVTemplate';

	public function getInput()
	{
		JPlugin::loadLanguage('plg_jevents_jevcustomfields', JPATH_ADMINISTRATOR);

		return parent::getInput();

	}

	public function getOptions()
	{
// Initialize variables.
		$options = array();

		jimport("joomla.filesystem.folder");
		if (JFolder::exists(JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/")) {
			$templates = JFolder::files(JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/", ".xml");
		}
		else {
			$templates = array();
		}

// only offer extra fields templates if there is more than one available
		if (count($templates) > 0)
		{

// this loads the language strings ! BIZZARE!
			JPluginHelper::importPlugin('jevents');
// I can't do this since it only returns published plugins and I may want to configure an unpublished plugin!
//$plugin = JPluginHelper::getPlugin("jevents","jevcustomfields");
			$db = JFactory::getDBO();
			if (JVersion::isCompatible("1.6.0"))  {
				$db->setQuery('SELECT folder AS type, element AS name, params  FROM #__extensions  where folder="jevents" and element="jevcustomfields" ');
			}
			else {
				$db->setQuery('SELECT folder AS type, element AS name, params  FROM #__plugins where folder="jevents" and element="jevcustomfields" ');
			}
			$plugin = $db->loadObject();

			$options = array();
			$options[] = JHTML::_('select.option', "", JText::_("JEV_SELECT_TEMPLATE"), 'value', 'text');
			foreach ($templates as $template)
			{
				if ($template == "fieldssample.xml" || $template == "fieldssample16.xml")
					continue;
				$options[] = JHTML::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'value', 'text');
			}
		}
		return $options;

	}

}
