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

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/jevrtext.php");

class JFormFieldJevrurl extends JFormFieldJevrtext
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrurl';
	const name = 'jevrurl';

	function loadScript($field=false){

		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevrurl.js' );

		if ($field){
			$id = 'field'.$field->field_id;
		}
		else {
			$id = '###';
		}
		ob_start();
?>
<div class='rsvpfieldinput'>

	<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE");?></div>
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRURL");?><?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field, self::name);
	RsvpHelper::tooltip($id,  $field);
	?>
	
	<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE");?></div>
	<div class="rsvpinputs">
		<input type="text" name="dv[<?php echo $id;?>]" id="dv<?php echo $id;?>" size="<?php echo $field?$field->size:50;?>"  value="<?php echo $field?$field->defaultvalue:"http://www.jevents.net";?>"  onchange="jevrurl.setvalue('<?php echo $id;?>');"  onkeyup="jevrurl.setvalue('<?php echo $id;?>');"/>
	</div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::size($id,  $field, self::name);
	RsvpHelper::maxlength($id,  $field, self::name);
	RsvpHelper::required($id,  $field);
	RsvpHelper::requiredMessage($id,  $field);
	//RsvpHelper::conditional($id,  $field);
	RsvpHelper::peruser($id,  $field);
	RsvpHelper::formonly($id,  $field);
	RsvpHelper::showinform($id,  $field);
	RsvpHelper::showindetail($id,  $field);
	RsvpHelper::showinlist($id,  $field);
	RsvpHelper::allowoverride($id,  $field);
	RsvpHelper::accessOptions($id,  $field);
	RsvpHelper::applicableCategories("facc[$id]","facs[$id]", $id,  $field?$field->applicablecategories:"all");
	?>

	<div class="rsvpclear"></div>
	
</div>
<div class='rsvpfieldpreview'  id='<?php echo $id;?>preview'>
	<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW");?></div>
	<div class="rsvplabel rsvppl" id='pl<?php echo $id;?>'><?php echo $field?$field->label:JText::_("RSVP_FIELD_LABEL");?></div>
	<input type="text"  id="pdv<?php echo $id;?>" value="<?php echo $field?$field->defaultvalue:"http://www.jevents.net";?>" size="<?php echo $field?$field->size:50;?>"  />
</div>
<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id,  $field, $html, self::name	);

	}


	public function convertValue($value){
		if (strpos($value,"/")===0){
			$value = $value;
		}
		else if (strpos($value,"index.php")===0){
			$value = JRoute::_($value);
		}
		else if (strpos($value,"http")!==0){
			$value = "http://".$value;
		}

		if ($value!="") return  "<a href='$value' >$value</a>";
	}
}