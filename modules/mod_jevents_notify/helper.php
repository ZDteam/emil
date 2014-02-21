<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @subpackage  Module JEvents Calendar
 * @copyright   Copyright (C) 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://joomlacode.org/gf/project/jevents
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

class modJeventsNotifyHelper
{

	function modJeventsNotifyHelper($params)
	{
		// setup for all required function and classes
		$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
		if (file_exists($file))
		{
			include_once($file);
			include_once(JEV_LIBS . "/modfunctions.php");
		}
		else
		{
			die("JEvents Calendar\n<br />This module needs the JEvents component");
		}

		// load language constants
		JEVHelper::loadLanguage('modcal');

		$this->params = $params;

	}

	public function categoriesTree($size=15, $order="parent, ordering")
	{

		$values = "0";

		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		$db->setQuery("SELECT cat_id FROM #__jev_notification_map WHERE user_id=$user->id");
		$cats = $db->loadColumn();
		if ($cats && count($cats) > 0)
		{
			$values = implode("|", $cats);
		}

		$reveal = $this->params->get("enlarge", "[+]");
		$hide = $this->params->get("reduce", "[-]");

		static $script;
		if (!isset($script))
		{
			$token = JSession::getFormToken();
			$success = JText::_("JEV_NOTIFY_SUCCESS", true);
			$failure = JText::_("JEV_NOTIFY_FAILURE", false);
			$script = <<<SCRIPT
function resetNotificationOptions(elem){
	var select = $(elem);
	\$A(select.options).each(
		function(item,index){
			if (item.selected) {
				// if select none - reset everything else
				if (item.value=="0") {
					select.selectedIndex=0;
					return;
				}
				else {
					select.options[0].selected = false;
				}
			}
		}
	);
}

var jsontoken = '$token';
function updateNotifications(url){
	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "updateNotifications";
	var elem = \$('notificationcategories');
	var selected = new Array();
	for (var i = 0; i < elem.options.length; i++) if (elem.options[ i ].selected) selected.push(elem.options[ i ].value);
	requestObject.cats = selected;

	rsvpjsonactive = true;
	var jSonRequest = new Request.JSON({
		'url': url, 
		onSuccess: function(json){
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert("$failure");
				}
			}
			else {
				alert("$success");
			}
		},
		onFailure: function(){
			alert("$failure");
		}
	}).get({'json':JSON.encode(requestObject)});
}

SCRIPT;
			$document = JFactory::getDocument();
			JHtml::_('behavior.framework', true);
			$document->addScriptDeclaration($script);
		}

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('a.id, a.title, a.level, a.parent_id');
			$query->from('#__categories AS a');
			$query->where('a.parent_id > 0');

			// Filter on extension.
			$query->where('extension = ' . $db->quote(JEV_COM_COMPONENT));

			// Filter on the published state
			$query->where('a.published = 1');

			// filter on access
			$user = JFactory::getUser();
			$aids = JEVHelper::getAid($user);

			$query->where('a.access IN (' . $aids . ')');

			$query->order('a.lft');

			$db->setQuery($query);
			$items = $db->loadObjectList();

			$mitems = array();
			$mitems[] = JHTML::_('select.option', 0, JText::_("JEV_NO_NOTIFICATIONS"));
			foreach ($items as $item)
			{
				$repeat = ( $item->level - 1 >= 0 ) ? $item->level - 1 : 0;
				$item->title = "--".str_repeat('- ', $repeat) . $item->title;
				$mitems[] = JHtml::_('select.option', $item->id, $item->title);
			}

		$onchange = " onchange='resetNotificationOptions(this);' ";
		if (intval($size) > 0)
		{
			return JHTML::_('select.genericlist', $mitems, 'notificationcategories[]', 'multiple="multiple" size="' . $size . '"' . $onchange, 'value', 'text', explode("|", $values));
		}
		else
		{
			return JHTML::_('select.genericlist', $mitems, 'notificationcategories[]', 'multiple="multiple" ' . $onchange, 'value', 'text', explode("|", $values));
		}

	}

}
