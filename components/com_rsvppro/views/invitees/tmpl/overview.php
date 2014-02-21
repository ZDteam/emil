<?php 
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

JHtml::_('behavior.tooltip');

// Are we updating the invitee list
$this->jevrinvitees->updateInvitees($this->rsvpdata, $this->repeat);

//echo $this->jevrinvitees->createInvitations($this->repeat, $this->rsvpdata);

$row = $this->repeat;
$rsvpdata = $this->rsvpdata;

$html = "";
$user=JFactory::getUser();
if ($user->id==0){
	return $html;
}
if (!$rsvpdata->invites) return $html;

if ($user->id==$row->created_by() ||  JEVHelper::isAdminUser($user)){
	$invitees = $this->jevrinvitees->fetchInvitees($row,$rsvpdata);
	$this->assignRef("invitees",$invitees);
}

if ($this->jevrinvitees->jomsocial){
	$db = JFactory::getDBO();
	$db->setQuery("select * from #__community_groups as a LEFT JOIN #__community_groups_members as b ON a.id=b.groupid where (a.published=1 OR (b.approved=1 AND b.memberid=".$user->id.")) group by a.id ORDER BY a.name");

	$jsgroups = $db->loadObjectList();
	$this->assignRef("jsgroups",$jsgroups);
}

$db = JFactory::getDBO();
$db->setQuery("select * from #__jev_invitelist  where user_id =".$user->id);
$invitelists = $db->loadObjectList();
$this->assignRef("invitelists",$invitelists);

$this->assignRef("params", $this->jevrinvitees->params);
$this->assignRef("row",$row);
$this->assignRef("rsvpdata",$rsvpdata);
echo $this->loadTemplate("invitesform");

