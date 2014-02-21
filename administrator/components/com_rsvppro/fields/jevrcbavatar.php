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

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrcbavatar extends JevrField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevrcbavatar';
	const name = 'jevrcbavatar';

	static function isEnabled(){
		return is_dir( JPATH_SITE . '/components/com_comprofiler' );
	}

	function loadScript($field=false){
		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevrcbavatar.js' );

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
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRCBAVATAR");?><?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field, self::name);
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
	<div id="pdv<?php echo $id;?>">
	<?php
		$baseurl = JURI::root();
		$fieldImgPath =$baseurl . 'components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
		echo "<img src='$fieldImgPath' alt='field'  class='rsvpavatar'/>";
	?>
	</div>
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

		$user =& JFactory::getUser();

		$html = "";
		$lang = JFactory::getLanguage();
		$baseurl = JURI::root();

		if ($user->id==0){
			$avatarImgPath =$baseurl . 'components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
			$html = "<input type='hidden' value='0' name='".$name."' />";
			$html .= "<img src='$avatarImgPath' alt='avatar' class='rsvpavatar'/>";
		}
		else {
			if (!isset($user->cbProfile)){
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery( 'SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
				'FROM #__comprofiler AS cbprofile ' .
				'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
				' WHERE ( cbprofile.user_id = \'' . $user->id . '\' ) ' );
				$user->cbProfile  = $db->loadObject( );
			}
			if ($user->cbProfile){

				if ( !empty( $user->cbProfile->avatar ) )
				{
					if ( $user->cbProfile->avatarapproved )
					{
						// Photo could be from the built-in gallery or user submitted.
						// Each category is stored in a different location.
						// Test 'gallery' images by looking for 'gallery' prefix on image name.
						if ( strcmp( 'gallery', substr( $user->cbProfile->avatar, 0, 7 ) ) ) {
							$avatarImgPath =$baseurl . '/images/comprofiler/tn' . $user->cbProfile->avatar;
						} else {
							$avatarImgPath =$baseurl . '/images/comprofiler/' . $user->cbProfile->avatar;
						}
					}

					else
					{
						$avatarImgPath =$baseurl . '/components/com_comprofiler/plugin/language/default_language/images/tnpendphoto.jpg';
					}
				}

				else
				{
					$avatarImgPath =$baseurl . '/components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
				}

				$html = "<input type='hidden' value='".$user->id."' name='$name' />";
				$img = "<img src='$avatarImgPath' alt='avatar'  class='rsvpavatar'/>";
				$html .=  "<a href='" . JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $user->id) . "'>$img</a>";

			}
			else {
				$avatarImgPath =$baseurl . 'components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
				$html = "<input type='hidden' value='".$user->id."' name='$name' />";
				$img = "<img src='$avatarImgPath' alt='avatar'  class='rsvpavatar'/>";
				$html .=  "<a href='" . JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $user->id) . "'>$img</a>";
			}
		}

		return $html;
	}

	public function convertValue($value){
		$baseurl = JURI::root();

		if (intval($value)==0 && $this->attendee && isset($this->attendee->user_id)) $value = $this->attendee->user_id;

		if (intval($value)==0) {
			$avatarImgPath =$baseurl . 'components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
			$html = "<img src='$avatarImgPath' alt='avatar'  class='rsvpavatar'/>";
		}
		else {
			$user =& JFactory::getUser($value);
			if (!isset($user->cbProfile)){
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery( 'SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
				'FROM #__comprofiler AS cbprofile ' .
				'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
				' WHERE ( cbprofile.user_id = \'' . $user->id . '\' ) ' );
				$user->cbProfile  = $db->loadObject( );
			}

			if ($user->cbProfile){

				if ( !empty( $user->cbProfile->avatar ) )
				{
					if ( $user->cbProfile->avatarapproved )
					{
						// Photo could be from the built-in gallery or user submitted.
						// Each category is stored in a different location.
						// Test 'gallery' images by looking for 'gallery' prefix on image name.
						if ( strcmp( 'gallery', substr( $user->cbProfile->avatar, 0, 7 ) ) ) {
							$avatarImgPath =$baseurl . '/images/comprofiler/tn' . $user->cbProfile->avatar;
						} else {
							$avatarImgPath =$baseurl . '/images/comprofiler/' . $user->cbProfile->avatar;
						}
					}

					else
					{
						$avatarImgPath =$baseurl . '/components/com_comprofiler/plugin/language/default_language/images/tnpendphoto.jpg';
					}
				}

				else
				{
					$avatarImgPath =$baseurl . '/components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
				}

				$html = "<img src='$avatarImgPath' alt='avatar'  class='rsvpavatar'/>";
				$html =  "<a href='" . JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $user->id) . "'>$html</a>";

			}
			else {
				$avatarImgPath =$baseurl . 'components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg';
				$html = "<img src='$avatarImgPath' alt='avatar'  class='rsvpavatar'/>";
				$html =  "<a href='" . JRoute::_("index.php?option=com_comprofiler&task=userProfile&user=" . $user->id) . "'>$html</a>";
			}

		}
		return $html;

	}
}