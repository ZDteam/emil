<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted access');
?>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td><?php echo JText::_("JEV_PLUGIN_INSTRUCTIONS", true); ?></td>
		<td><select id="jevdefaults" onchange="defaultsEditorPlugin.insert('value','jevdefaults' )" ></select></td>
	</tr>
</table>

<script type="text/javascript">
	defaultsEditorPlugin.node($('jevdefaults'),"<?php echo JText::_("JEV_PLUGIN_SELECT", true); ?>","");
	// built in group
	var optgroup = "";

<?php
// get list of enabled plugins
$jevplugin = JPluginHelper::getPlugin("jevents", "jevpeople");
if (JPluginHelper::importPlugin("jevents", $jevplugin->name))
{
	$classname = "plgJevents" . ucfirst($jevplugin->name);
	if (is_callable(array($classname, "fieldNameArray")))
	{
		$lang = JFactory::getLanguage();
		$lang->load("plg_jevents_" . $jevplugin->name, JPATH_ADMINISTRATOR);
		$fieldNameArray = call_user_func(array($classname, "fieldNameArray"));
		if (!isset($fieldNameArray['labels']))
			continue;
		?>
					optgroup = defaultsEditorPlugin.optgroup($('jevdefaults') , '<?php echo $fieldNameArray["group"]; ?>');
		<?php
		for ($i = 0; $i < count($fieldNameArray['labels']); $i++)
		{
			// skip the summary
			if ($fieldNameArray['values'][$i]=="JEV_PEOPLE_SUMMARY") continue;
			?>
							defaultsEditorPlugin.node(optgroup , "<?php echo $fieldNameArray['labels'][$i]; ?>", "<?php echo $fieldNameArray['values'][$i]; ?>");
			<?php
			if (false && strpos($fieldNameArray['values'][$i], "_lbl") === false)
			{
				?>
							defaultsEditorPlugin.node(optgroup , "<?php echo  $fieldNameArray['labels'][$i].' Label'; ?>", "<?php echo str_replace('}}','_lbl}}',$fieldNameArray['values'][$i]); ?>");
				<?php
			}
		}
		?>
		<?php
	}
}
?>
</script>
