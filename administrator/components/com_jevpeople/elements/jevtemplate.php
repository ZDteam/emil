<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class JElementJevtemplate extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevtemplate';

	function fetchElement($name, $value, &$node, $control_name)
	{
		JPlugin::loadLanguage( 'plg_jevents_jevcustomfields',JPATH_ADMINISTRATOR );

		$content= "";
		jimport("joomla.utilities.folder");
		if (JFolder::exists(JPATH_SITE."/plugins/jevents/customfields/templates/")){
			$templates = JFolder::files(JPATH_SITE."/plugins/jevents/customfields/templates/",".xml");
			// only offer extra fields templates if there is more than one available
			if (count($templates)>1){

				JPluginHelper::importPlugin('jevents');
				$plugin = JPluginHelper::getPlugin("jevents","jevcustomfields");
				ob_start();
				$options = array();
				$options[] = JHTML::_('select.option', "", JText::_( 'JEV_SELECT_TEMPLATE' ), 'var', 'text');
				foreach ($templates as $template) {
					if ($template=="fieldssample.xml") continue;
					$options[] = JHTML::_('select.option', $template, ucfirst(str_replace(".xml","",$template)), 'var', 'text');
				}

				echo JHTML::_('select.genericlist',  $options, $control_name.'['.$name.']', '', 'var', 'text', $value);
				$content = ob_get_clean();
			}
			return $content;
		}
		else return "";

		$rows = $node->attributes('rows');
		$cols = $node->attributes('cols');
		$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="text_area"' );
		// convert <br /> tags so they are not visible when editing
		$value = str_replace('<br />', "\n", JText::_($value));

		return '<textarea name="'.$control_name.'['.$name.']" cols="'.$cols.'" rows="'.$rows.'" '.$class.' id="'.$control_name.$name.'" >'.$value.'</textarea>';
	}

}