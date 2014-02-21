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

class JFormFieldJevrtext extends JevrFieldText
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrtext';
	const name = 'jevrtext';

	function loadScript($field=false){
		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevrtext.js' );

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
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRTEXT");?><?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field, self::name);
	JFormFieldJevrtext::namefield($id,  $field);
	RsvpHelper::tooltip($id,  $field);
	?>
	
	<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE");?></div>
	<div class="rsvpinputs">
		<input type="text" name="dv[<?php echo $id;?>]" id="dv<?php echo $id;?>" size="<?php echo $field?$field->size:5;?>"  value="<?php echo $field?$field->defaultvalue:"";?>"  onchange="jevrtext.setvalue('<?php echo $id;?>');"  onkeyup="jevrtext.setvalue('<?php echo $id;?>');"/>
	</div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::size($id,  $field, self::name);
	RsvpHelper::maxlength($id,  $field, self::name);
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
	<div class="rsvplabel rsvppl" id='pl<?php echo $id;?>' ><?php echo $field?$field->label:JText::_("RSVP_FIELD_LABEL");?></div>
	<input type="text"  id="pdv<?php echo $id;?>" value="<?php echo $field?$field->defaultvalue:"";?>" size="<?php echo $field?$field->size:5;?>"  />
</div>
<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id,  $field, $html, self::name	);

	}

	function getInput()
	{
		//$name, $value, &$node
		$node =  $this->element;
		$name = $this->name;
		$value = $this->value;
		$id = $this->id;
		$fieldname = $this->fieldname;

		$size = ( $this->attribute('size') ? 'size="'.$this->attribute('size').'"' : '' );
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').' xxx"' : 'class="text_area xxx"' );

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
		
		$change = "";
		if ($this->attribute("isname")){
			$change = " onchange='setAttendeeName(this)' onblur='setAttendeeName(this)' ";
			$document = JFactory::getDocument();
			$document->addScriptDeclaration("window.addEvent('domready', function (){try { regTabs.setInitialAttendeeNames();} catch (e) {} });");
			$class = str_replace("xxx", " attendeename xxx", $class);
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
						// is this the attendee name field?						
						if ($val=="" && !JFactory::getApplication()->isAdmin() && $this->attribute("isname")){
							if ($this->attendee && $this->attendee->user_id>0){
								$atdee = JFactory::getUser($this->attendee->user_id);
							}
							else {
								$atdee = JFactory::getUser();
							}
							$val  = $atdee->name;
						}
						$thisclass = str_replace(" xxx"," rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
					}
				}
				else {
					$thisclass = str_replace(" xxx"," rsvpparam rsvpparam".$i."  rsvp_" . $fieldname,$class);
				}
				$html .= '<input type="text" name="'.$elementname.'" id="'.$id."_".$i.'" value="'.$val.'" '.$thisclass.' '.$size.'  '.$change.' />';
				$i++;
			}
			$val = $this->attribute("default");
			$thisclass = str_replace(" xxx"," paramtmpl rsvp_" . $fieldname,$class);
			$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' '.$size.'  '.$change.' />';
		}
		else {
			// is this the attendee name field?
			if ($value=="" && !JFactory::getApplication()->isAdmin() && $this->attribute("isname")) {
				if ($this->attendee && $this->attendee->user_id>0){
					$atdee = JFactory::getUser($this->attendee->user_id);
				}
				else {
					$atdee = JFactory::getUser();
				}
				$value  = $atdee->name;
			}
						
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
			$html = '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$thisclass.' '.$size.'  '.$change.' />';
		}
		return $html;
	}

	function currentAttendeeCount($node, $value){
		if (is_array($value) && count($value)>1) {
			return count($value)-1;
		}
		return 1;
	}

	static function namefield($id, $field, $default = 0)
	{
		$isname = 0;
		if ($field)
		{
			try {
				$params = json_decode($field->params);
				$isname = isset($params->isname)?$params->isname:0;
			}
			catch (Exception $e) {
				$isname = 0;
			}
		}
		?>
		<div class="rsvplabel"  ><?php echo JText::_("RSVP_IS_ATTENDEE_NAME_FIELD"); ?></div>
		<div class="rsvpinputs"  >
			<label for="isname1<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?></label>
			<input type="radio" name="params[<?php echo $id; ?>][isname]"  id="isname1<?php echo $id; ?>" value="1" <?php
		if ($isname)
		{
			echo 'checked="checked"';
		}
		?> />
			<label for="isname0<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?></label>
			<input type="radio" name="params[<?php echo $id; ?>][isname]" id="isname0<?php echo $id; ?>" value="0" <?php
		   if (!$isname)
		   {
			   echo 'checked="checked"';
		   }
		?> />
		</div>
		<div class="rsvpclear"></div>
		<?php

	}

}