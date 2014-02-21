<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

if (!JVersion::isCompatible("1.6.0"))
{

	class JElementJevdefaultcf extends JElement
	{

		/**
		 * Element name
		 *
		 * @access	protected
		 * @var		string
		 */
		var $_name = 'Jevdefaultcf';

		function fetchElement($name, $value, &$node, $control_name)
		{

			// Must load admin language files
			$lang = & JFactory::getLanguage();
			$lang->load("com_jevents", JPATH_ADMINISTRATOR);

			$options = array();
			$options[] = JHtml::_('select.option', "", JText::_( 'JEV_SELECT_TEMPLATE' ), 'var', 'text');
			
			include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");
			JLoader::register('JevTemplateHelper',JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/templatehelper.php");
			include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/models/templates.php");
			$model = & JModelLegacy::getInstance("Templates", "TemplatesModel");			
			$templates = $model->getData();
			if ($templates && count($templates)>0){
				foreach ($templates as $template)
				{
					if ($template->published && $template->global  && $template->istemplate) {
						$options[] = JHtml::_('select.option', $template->id, ucfirst($template->title), 'var', 'text');
					}
				}				
			}
			
			jimport("joomla.filesystem.file");
			$templates = JFolder::files(dirname(__FILE__) . "/params/", ".xml");
			// only offer extra fields templates if there is more than one available
			if (count($templates) > 1 || (count($templates) == 1 && $templates[0] != "fieldssample.xml"))
			{
				foreach ($templates as $template)
				{
					if ($template == "fieldssample.xml")
						continue;
					$options[] = JHtml::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'var', 'text');
				}

			}

			if (count($options) == 2){
				array_shift($options);
			}
			if (count($options) < 2){
				return "";				
			}
			
			return JHtml::_('select.genericlist', $options, $control_name . '[' . $name . ']', '', 'var', 'text', $value);
			

		}

	}

}
else if (JVersion::isCompatible("1.6.0"))
{
	jimport('joomla.html.html');
	jimport('joomla.form.formfield');
	jimport('joomla.form.helper');

	class JFormFieldJevdefaultcf extends JFormField
	{

		/**
		 * Element name
		 *
		 * @access	protected
		 * @var		string
		 */
		var $_name = 'Jevdefaultcf';

		public function getInput() 
		{

			// Must load admin language files
			$lang = & JFactory::getLanguage();
			$lang->load("com_jevents", JPATH_ADMINISTRATOR);

			$options = array();
			$options[] = JHtml::_('select.option', "", JText::_( 'JEV_SELECT_TEMPLATE' ), 'var', 'text');
			
			include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");
			JLoader::register('JevTemplateHelper',JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/templatehelper.php");
			include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/models/templates.php");
			$model = & JModelLegacy::getInstance("Templates", "TemplatesModel");			
			$templates = $model->getData();
			if ($templates && count($templates)>0){
				foreach ($templates as $template)
				{
					if ($template->published && $template->global  && $template->istemplate) {
						$options[] = JHtml::_('select.option', $template->id, ucfirst($template->title), 'var', 'text');
					}
				}				
			}
						
			jimport("joomla.filesystem.file");
			$templates = JFolder::files(dirname(__FILE__) . "/params/", ".xml");
			// only offer extra fields templates if there is more than one available
			if (count($templates) > 1 || (count($templates) == 1 && $templates[0] != "fieldssample.xml"))
			{
				foreach ($templates as $template)
				{
					if ($template == "fieldssample.xml")
						continue;
					$options[] = JHtml::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'var', 'text');
				}

			}

			if (count($options) == 2){
				array_shift($options);
			}
			if (count($options) < 2){
				return "";				
			}
			
			return JHtml::_('select.genericlist', $options, $this->name, '', 'var', 'text', $this->value);

		}

	}

}
