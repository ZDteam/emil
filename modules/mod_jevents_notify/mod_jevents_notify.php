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


defined( '_JEXEC' ) or die( 'Restricted access' );

// Not to run for anonymous users
$user = JFactory::getUser();
if ($user->id==0) return " ";

require_once (dirname(__FILE__)."/".'helper.php');
require_once(JModuleHelper::getLayoutPath('mod_jevents_notify',"default"));

