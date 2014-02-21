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

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrFieldText.php");

class JFormFieldJevrtextarea extends JevrFieldText
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrtextarea';
	const name = 'jevrtextarea';

	function loadScript($field=false){
		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevrtextarea.js' );

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
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRTEXTAREA");?><?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field, self::name);
	RsvpHelper::tooltip($id,  $field);
	?>

	<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE");?></div>
	<div class="rsvpinputs">
		<textarea name="dv[<?php echo $id;?>]" id="dv<?php echo $id;?>" onchange="jevrtextarea.setvalue('<?php echo $id;?>');" onkeyup="jevrtextarea.setvalue('<?php echo $id;?>');"
				rows="<?php echo $field?$field->rows:5;?>" cols="<?php echo $field?$field->cols:20;?>" ><?php echo $field?$field->defaultvalue:"";?></textarea>
	</div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::cols($id,  $field, self::name);
	RsvpHelper::rows($id,  $field, self::name);
	RsvpHelper::required($id,  $field);
	RsvpHelper::requiredMessage($id,  $field);
	RsvpHelper::conditional($id,  $field);
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
	<textarea id="pdv<?php echo $id;?>"
				rows="<?php echo $field?$field->rows:5;?>" cols="<?php echo $field?$field->cols:20;?>" ><?php echo $field?$field->defaultvalue:"";?></textarea>
</div>
<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id,  $field, $html, self::name	);

	}

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		$size = ( $this->attribute('size') ? 'size="'.$this->attribute('size').'"' : '' );
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').' xxx"' : 'class="text_area xxx"' );
		$rows = $this->attribute('rows');
		$cols = $this->attribute('cols');

		/*
		 * Required to avoid a cycle of encoding &
		 * html_entity_decode was used in place of htmlspecialchars_decode because
		 * htmlspecialchars_decode is not compatible with PHP 4
		 */
		if (is_array($value)){
			foreach ($value as &$val){
				$val = htmlspecialchars(html_entity_decode($val, ENT_QUOTES), ENT_QUOTES);
			 }
			 unset($val);
		}
		else {
			$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);
		}
		if ($this->attribute("peruser")==1 || $this->attribute("peruser")==2){
			if (!is_array($value)){
				$value = array($value);
			}
			if (count($value)<$this->currentAttendees){
				// flesh out the value if there are not the right number of items
				for ($i=0;$i<=$this->currentAttendees-count($value);$i++){
					$value[] = $this->attribute("default");
				}
			}
			$elementname = $name.'[]';
			$html = "";
			$i = 0;
			foreach ($value as $val){
				if ($i==0){
					if ($this->attribute("peruser")==2){
						$thisclass = str_replace(" xxx"," disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
					}
					else {
						$thisclass = str_replace(" xxx"," rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
					}
				}
				else {
					$thisclass = str_replace(" xxx"," rsvpparam rsvpparam".$i." rsvp_" . $fieldname.$i,$class);
				}
				// convert <br /> tags so they are not visible when editing
				$val = str_replace('<br />', "\n", JText::_($val));

				$html .= '<textarea name="'.$elementname.'" cols="'.$cols.'" rows="'.$rows.'" '.$thisclass.' id="'.$id."_".$i.'" >'.$val.'</textarea>';
				//$html .= '<input type="text" name="'.$elementname.'" id="'.$id."_".$i.'" value="'.$val.'" '.$thisclass.' '.$size.' />';
				$i++;
			}
			// convert <br /> tags so they are not visible when editing
			$val = $this->attribute("default");
			$val = str_replace('<br />', "\n", JText::_($val));
			$thisclass = str_replace(" xxx"," paramtmpl rsvp_" . $fieldname,$class);
			$html .= '<textarea name="paramtmpl_'.$elementname.'" cols="'.$cols.'" rows="'.$rows.'" '.$thisclass.' id="'.$id.'_xxx" >'.$val.'</textarea>';
			//$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' '.$size.' />';
		}
		else {
			$elementname = $name;
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
			$value = str_replace('<br />', "\n", JText::_($value));
			$html = '<textarea name="'.$elementname.'" cols="'.$cols.'" rows="'.$rows.'" '.$thisclass.' id="'.$id.'" >'.$value.'</textarea>';
			//$html = '<input type="text" name="'.$elementname.'" id="'.$id.'" value="'.$value.'" '.$thisclass.' '.$size.' />';
		}
		return $html;
	}

	function currentAttendeeCount($node, $value){
		if (is_array($value) && count($value)>1) {
			return count($value)-1;
		}
		return 1;
	}

}