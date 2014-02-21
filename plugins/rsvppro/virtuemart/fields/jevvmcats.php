<?php


defined('JPATH_BASE') or die;
if (!class_exists('VmConfig'))
    require(JPATH_ROOT . '/' . 'administrator' . '/' . 'components' . '/' . 'com_virtuemart' . '/' . 'helpers' . '/' . 'config.php');
if (!class_exists('ShopFunctions'))
    require(JPATH_VM_ADMINISTRATOR . '/' . 'helpers' . '/' . 'shopfunctions.php');
if (!class_exists('TableCategories'))
    require(JPATH_VM_ADMINISTRATOR . '/' . 'tables' . '/' . 'categories.php');
jimport('joomla.form.formfield');

/**
 * Supports a modal product picker.
 *
 *
 */
class JFormFieldJevVmcats extends JFormField
{ 
	protected $type = 'jevvmcats';

	/**
	 * Method to get the field input markup.
	 *
         * @author      Valerie Cartan Isaksen
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
  
     function getInput() {
        $multiple= ($this->element['multiple'] ? 'multiple="multiple"' : "");
        $size= ($this->element['size'] ? 'size="'.$this->element['size'].'"' : "");
	
        JPlugin::loadLanguage('com_virtuemart', JPATH_ADMINISTRATOR);
        $categorylist = ShopFunctions::categoryListTree($this->value);
        $class = '';
        $html = '<select class="inputbox"   name="' . $this->name . '" '.$multiple.' '.$size.'>';
        $html .= '<option value="0">' . JText::_('COM_VIRTUEMART_CATEGORY_FORM_TOP_LEVEL') . '</option>';
        $html .= $categorylist;
        $html .="</select>";
        return $html;
        

    }



}