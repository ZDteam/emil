<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Example User Plugin
 *
 * @package		Joomla
 * @subpackage	JFramework
 * @since 		1.5
 */
class plgUserJevuser extends JPlugin
{

	public function onUserLogin($user, $options = array())
	{
		return $this->onLoginUser($user, $options);

	}

	function onLoginUser($user, $options)
	{
		// Initialize variables
		$success = true;
		if (JVersion::isCompatible("1.6.0"))
		{
			$juser = $this->_getUser($user, $options);
		}
		else
		{
			$juser = JFactory::getUser();
		}
		if (strtolower($juser->username) != strtolower($user["username"]))
			return $success;

		// restrict usage to certain user types
		$create = false;
		if (JVersion::isCompatible("1.6.0"))
		{
			$userGroups = $juser->getAuthorisedGroups();
			if (in_array($this->params->get("minuserlevel", 2), $userGroups))
			{
				$create = true;
			}
		}
		else
		{
			if ($juser->gid >= $this->params->get("minuserlevel", 999))
			{
				$create = true;
			}
		}

		if (!$create)
			return $success;

		$db = JFactory::getDBO();
		$db->setQuery("Select * from #__jev_users where user_id=" . intval($juser->id));
		$jevuser = $db->loadObject();
		if (is_null($jevuser))
		{
			include_once (JPATH_ADMINISTRATOR . "/components/com_jevents/tables/jevuser.php");

			$temp = new TableUser();
			$temp->id = 0;
			$temp->user_id = intval($juser->id);

			$temp->published = $this->params->get("enabled", 1);
			$temp->canuploadimages = $this->params->get("uploadimages", 1);
			$temp->canuploadmovies = $this->params->get("uploadfiles", 1);
			$temp->cancreate = $this->params->get("cancreate", 1);
			$temp->canedit = $this->params->get("editall", 1);
			$temp->canpublishown = $this->params->get("publishown", 1);
			$temp->candeleteown = $this->params->get("deleteown", 0);
			$temp->canpublishall = $this->params->get("publishall", 0);
			$temp->candeleteall = $this->params->get("deleteall", 0);

			$temp->cancreateown = $this->params->get("ownextras", 0);
			$temp->cancreateglobal = $this->params->get("globalextras", 0);

			$temp->categories = str_replace(",", "|", $this->params->get("categories", ''));
			$temp->calendars = str_replace(",", "|", $this->params->get("calendars", ''));

			return $temp->store();
		}
		return $success;

	}

	/**
	 * This method will return a user object
	 *
	 * If options['autoregister'] is true, if the user doesn't exist yet he will be created
	 *
	 * @param	array	$user		Holds the user data.
	 * @param	array	$options	Array holding options (remember, autoregister, group).
	 *
	 * @return	object	A JUser object
	 * @since	1.5
	 */
	protected function &_getUser($user, $options = array())
	{
		$instance = JUser::getInstance();
		if ($id = intval(JUserHelper::getUserId($user['username'])))
		{
			$instance->load($id);
			return $instance;
		}

		//TODO : move this out of the plugin
		jimport('joomla.application.component.helper');
		$config = JComponentHelper::getParams('com_users');
		// Default to Registered.
		$defaultUserGroup = $config->get('new_usertype', 2);

		$acl = JFactory::getACL();

		$instance->set('id', 0);
		$instance->set('name', $user['fullname']);
		$instance->set('username', $user['username']);
		$instance->set('password_clear', $user['password_clear']);
		$instance->set('email', $user['email']); // Result should contain an email (check)
		$instance->set('usertype', 'deprecated');
		$instance->set('groups', array($defaultUserGroup));

		//If autoregister is set let's register the user
		$autoregister = isset($options['autoregister']) ? $options['autoregister'] : $this->params->get('autoregister', 1);

		if ($autoregister)
		{
			if (!$instance->save())
			{
				return JError::raiseWarning('SOME_ERROR_CODE', $instance->getError());
			}
		}
		else
		{
			// No existing user and autoregister off, this is a temporary user.
			$instance->set('tmp_user', true);
		}

		return $instance;

	}

}
