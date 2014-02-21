<?php
/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted Access' );


class JevRsvpInvitees
{

	public $params;
	public $jomsocial = false;
	public $cbuilder = false;
	public $groupjive= false;

	public function __construct($params, $jomsocial, $cbuilder=false, $groupjive=false){
		$this->params = $params;
		$this->jomsocial = $jomsocial;
		$this->cbuilder = $cbuilder;
		$this->groupjive = $groupjive;

		jimport('joomla.application.component.view');

		$theme = JEV_CommonFunctions::getJEventsViewName();

		if (JVersion::isCompatible("1.6.0")){
			$this->_basepath = JPATH_SITE.'/plugins/jevents/jevrsvppro/rsvppro/';
		}
		else {
			$this->_basepath = JPATH_SITE.'/plugins/jevents/rsvppro/';
		}
		$this->view = new JViewLegacy(array( 'base_path'=>$this->_basepath,
		"template_path"=>$this->_basepath."tmpl/default"
		,"name"=>$theme
		));

		$this->view->addTemplatePath($this->_basepath."tmpl/".$theme);

		$this->view->addTemplatePath($this->_basepath."tmpl/".$theme);
		$this->view->addTemplatePath( JPATH_SITE .'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'."plg_rsvppro".'/'."default");
		$this->view->addTemplatePath( JPATH_SITE .'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'."plg_rsvppro".'/'.$theme);

		$this->view->setLayout("invites");

		$this->view->assign("jomsocial",$this->jomsocial);
		$this->view->assign("cbuilder",$this->cbuilder);
		$this->view->assign("groupjive",$this->groupjive);
		$this->view->assignRef("params",$this->params);

		include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/libraries/inviteehelper.php");
		$this->helper = new RsvpInviteeHelper($this->params);

	}

	public function createInvitations($row, $rsvpdata){

		$html = "";
		$user=JFactory::getUser();
		if ($user->id==0){
			return $html;
		}
		if (!$rsvpdata->invites) return $html;

		if ($user->id==$row->created_by() ||  JEVHelper::isAdminUser($user) || JEVHelper::isEventDeletor(true)){
			$invitees = $this->helper->fetchInvitees($row,$rsvpdata);
			$this->view->assignRef("invitees",$invitees);
		}

		if ($this->params->get("invitejoomlagroups",0)){
		
			if (JVersion::isCompatible("1.6.0"))
			{
				$where = array();
				if (!$user->authorise("core.admin")){
					$where[] ="map2.user_id =".$user->id;
				}
				$inviteusertype = $this->params->get("inviteusertype", array(1,2,3,4,5,6));
				if ($inviteusertype)
				{
					if (is_array($inviteusertype)){
						JArrayHelper::toInteger($inviteusertype);
						$inviteusertype[] = -1;
						$inviteusertype = implode(",",$inviteusertype);
					}
					$where[] = ' map2.group_id IN ( ' . $inviteusertype .')';
				}
				$db = JFactory::getDBO();
				$sql = 'SELECT DISTINCT(ug.title) as name, ug.* FROM #__usergroups as ug 
					LEFT JOIN #__user_usergroup_map AS map2 ON map2.group_id = ug.id					';
				$sql .= " WHERE ".implode(" AND ", $where);
				$db->setQuery($sql);
				$jugroups = $db->loadObjectList();
				echo $db->getErrorMsg();
				$this->view->assignRef("jugroups",$jugroups);
			}
		}
		
		if ($this->jomsocial){
			$db = JFactory::getDBO();
			$db->setQuery("select * from #__community_groups as a LEFT JOIN #__community_groups_members as b ON a.id=b.groupid where (a.published=1 OR (b.approved=1 AND b.memberid=".$user->id.")) group by a.id ORDER BY a.name");

			$jsgroups = $db->loadObjectList();
			$this->view->assignRef("jsgroups",$jsgroups);
		}
		
		if ($this->groupjive){
			$db = JFactory::getDBO();
			$user = JFactory::getUser();
			$aid = JEVHelper::getAid($user);
			if (JVersion::isCompatible("1.6")){
				// everybody or registered users
				$aid.=",-2";
				$aid.=",-1";
			}
			$db->setQuery("select * from #__groupjive_groups WHERE access ". (JVersion::isCompatible("1.6") ?  ' IN (' .$aid . ')'  :  ' <=  ' . $aid) ." AND (type=1 OR (user_id=".$user->id.")) and published=1 ORDER BY name" );
			$cbgroups = $db->loadObjectList();

			$this->view->assignRef("cbgroups",$cbgroups);
		}

		$db = JFactory::getDBO();
		$db->setQuery("select * from #__jev_invitelist  where user_id =".$user->id);
		$invitelists = $db->loadObjectList();
		$this->view->assignRef("invitelists",$invitelists);

		$this->view->assignRef("row",$row);
		$this->view->assignRef("rsvpdata",$rsvpdata);
		return $this->view->loadTemplate("invitesform");

	}

	// This redirects calls to the helper class is possible
	public function __call($name, $arguments){
		if (is_callable(array($this->helper, $name))){
			return call_user_func_array(array($this->helper, $name),$arguments);
		}
	}




}