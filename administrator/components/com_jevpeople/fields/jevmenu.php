<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id: jevmenu.php 3157 2012-01-05 13:12:19Z geraintedwards $
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

class JFormFieldJEVmenu extends JFormFieldList
{

	protected $type = 'JEVmenu';

	public function getOptions()
	{
		// Trap to stop the config from being editing from the categories page
		if (JRequest::getString("option") =="com_config" && JRequest::getString("component") =="com_categories"){
			?>
			<script type="text/javascript">
				alert("<?php echo JText::_("JEV_SET_CONFIG_VIA_COMPONENT",true);?>");
				window.parent.SqueezeBox.close();
			</script>
			<?php
			exit();
		}

		// Must load admin language files
		$lang =& JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$node = $this->element;
		$value = $this->value;
		$name = $this->name;
		$control_name = $this->type;
		
		$db =& JFactory::getDBO();

		// assemble menu items to the array
		$options 	= array();
		$options[]	= JHTML::_('select.option', '', '- '.JText::_( 'SELECT_ITEM' ).' -');
		
		// load the list of menu types
		// TODO: move query to model
		$query = 'SELECT menutype, title' .
				' FROM #__menu_types' .
				' ORDER BY title';
		$db->setQuery( $query );
		$menuTypes = $db->loadObjectList();

		$menu =& JApplication::getMenu('site');
		$menuItems = $menu->getMenu();		
		foreach ($menuItems as &$item) {
		 	
			if ($item->component =="com_jevents"){
				if (version_compare(JVERSION, '1.6.0', ">=")){
					$item->title  = "*** ".$item->title." ***";
				}
				else {
					$item->name = "*** ".$item->name." ***";
				}
			}
			unset($item);
		 } 
				
		// establish the hierarchy of the menu
		$children = array();

		if ($menuItems)
		{
			// first pass - collect children
			foreach ($menuItems as $v)
			{
				$pt 	= 0; //(version_compare(JVERSION, '1.6.0', ">=")) ? $v->parent_id: $v->parent;  // RSH 10/4/10 in J!1.5 - parent was always 0, this changed in J!.16 to a real parent_id, so force id to 0 for compatibility
				$list 	= @$children[0] ? $children[0] : array();
				array_push( $list, $v );
				$children[0] = $list;
			}
		}

		// second pass - get an indent list of the items
		$list = JHTML::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0 );

		// assemble into menutype groups
		$n = count( $list );
		$groupedList = array();
		foreach ($list as $k => $v) {
			$groupedList[$v->menutype][] = &$list[$k];
		}

		foreach ($menuTypes as $type)
		{
			$options[]	= JHTML::_('select.option',  $type->menutype, $type->title , 'value', 'text', true );  // these are disabled! (true)
			if (isset( $groupedList[$type->menutype] ))
			{
				$n = count( $groupedList[$type->menutype] );
				for ($i = 0; $i < $n; $i++)
				{
					$item = &$groupedList[$type->menutype][$i];
					
					//If menutype is changed but item is not saved yet, use the new type in the list
					if ( JRequest::getString('option', '', 'get') == 'com_menus' ) {
						$currentItemArray = JRequest::getVar('cid', array(0), '', 'array');
						$currentItemId = (int) $currentItemArray[0];
						$currentItemType = JRequest::getString('type', $item->type, 'get');
						if ( $currentItemId == $item->id && $currentItemType != $item->type) {
							$item->type = $currentItemType;
						}
					}
					
					$disable = false; //strpos($node->attributes('disable'), $item->type) !== false ? true : false;
					// RSH 10/4/10 - J!1.6 does a htmlspecialentities and html_entity_decode to screw up the text!  Make sure the correct values are passed for the respective versions
					//$text = (version_compare(JVERSION, '1.6.0', ">=")) ? utf8_encode(html_entity_decode('&nbsp;&nbsp;&nbsp;' . $item->treename)) : '&nbsp;&nbsp;&nbsp;' . $item->treename;
					$text = (version_compare(JVERSION, '1.6.0', ">=")) ? '     ' .html_entity_decode( $item->treename) : '&nbsp;&nbsp;&nbsp;' . $item->treename;
					$text = str_repeat("&nbsp;",(isset($item->level)?$item->level:$item->sublevel) * 4) . $text;
					$options[] = JHTML::_('select.option',  $item->id, $text , 'value', 'text', $disable );

				}
			}
		}

		return $options;
		return JElementJevmenu::fetchElement($this->name, $this->value, $this->element, $this->type, true);  // RSH 10/4/10 - Use the original code for J!1.6
	}
}
