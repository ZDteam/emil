<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

jimport('joomla.application.component.controlleradmin');

class AdminCpanelController extends JControllerAdmin {
	/**
	 * Controler for the Control Panel
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'show',  'cpanel' );
		$this->registerDefaultTask("cpanel");

		// setupdatabase
		$this->_checkDatabase();

		$this->cleanUpOrphans();
		
		// setup filesystem for images
		$this->_checkFilesystem();
	}

	function cpanel( )
	{
		// get the view
		$this->view = & $this->getView("cpanel","html");

		// Set the layout
		$this->view->setLayout('cpanel');
		$this->view->assign('title'   , JText::_( 'CONTROL_PANEL' ));

		$this->view->cpanel();
	}

	function _checkDatabase(){
	
		// Make sure DB is up to date - with definitions for layout defaults		
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jev_peopletypes");
		$ptypes =$db->loadObjectList("type_id");

		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' ");
		$defaults =$db->loadObjectList('name');
		
		foreach ($ptypes as $type_id=>$ptype){
			if (!isset($defaults['com_jevpeople.people.'.$type_id.'.detail'])){
				$db->setQuery("INSERT INTO  #__jev_defaults set name='com_jevpeople.people.$type_id.detail',
							title=".$db->Quote(JText::sprintf("JEV_PEOPLE_DETAIL_PAGE",$ptype->title )).",
							subject='',
							value='',
							state=0");
				$success = $db->query();
			}
			else if ($defaults['com_jevpeople.people.'.$type_id.'.detail']->title=="JEV_PEOPLE_DETAIL_PAGE" || $defaults['com_jevpeople.people.'.$type_id.'.detail']->title=="Managed People/Resources Details for %s"){
				$db->setQuery("UPDATE #__jev_defaults set 
							title=".$db->Quote(JText::sprintf("JEV_PEOPLE_DETAIL_PAGE",$ptype->title ))."
							WHERE id=".$defaults['com_jevpeople.people.'.$type_id.'.detail']->id);
				$success = $db->query();				
			}
		}
		
		// remove dead layouts
		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' ");
		$defaults =$db->loadObjectList('name');
		foreach ($defaults as $def) {
			$type=str_replace('com_jevpeople.people.','',$def->name);
			$type=str_replace('.detail','',$type);
			if (!array_key_exists($type, $ptypes)){
				$db->setQuery("DELETE FROM #__jev_defaults where name='com_jevpeople.people.$type.detail'");
				$success = $db->query();
			}
		}
		 // clean up duplicates - keep the first instance always
		$db->setQuery("SELECT * FROM #__jev_defaults where name like 'com_jevpeople.people.%' order by id asc");
		$defaults =$db->loadObjectList();
		$types=array();
		foreach ($defaults as $def) {
			if (in_array( $def->name,$types)){
				//echo "remove $def->name : $def->title<br/>";
				$db->setQuery("DELETE FROM #__jev_defaults where id=". $def->id);
				$success = $db->query();
			}
			$types[] =  $def->name;
		}
		
	}

	function _checkFilesystem(){
		jimport ("joomla.filesystem.folder");
		// folder relative to media folder
		// Get the media component configuration settings
		$params =& JComponentHelper::getParams('com_media');
		// Set the path definitions
		define('JEVP_MEDIA_BASE',    JPATH_ROOT."/".$params->get('image_path', 'images'."/".'stories'));
		define('JEVP_MEDIA_BASEURL', JURI::root(true).'/'.$params->get('image_path', 'images/stories'));

		$folder = "jevents/jevpeople";
		// ensure folder exists
		if (!JFolder::exists(JEVP_MEDIA_BASE."/".$folder)) {
			JFolder::create(JEVP_MEDIA_BASE."/".$folder,0777);
		}
		$folder = "jevents/jevpeople/thumbnails";
		// ensure folder exists
		if (!JFolder::exists(JEVP_MEDIA_BASE."/".$folder)) {
			JFolder::create(JEVP_MEDIA_BASE."/".$folder,0777);
		}
	}

	function cleanUpOrphans() {
		$db = JFactory::getDBO();
		if (JVersion::isCompatible("1.6.0"))  {
			$sql = "SELECT DISTINCT cat.extension FROM #__categories as cat WHERE cat.extension LIKE ('com_jevpeople_type%')";
		}
		else {
			$sql = "SELECT DISTINCT cat.section FROM #__categories as cat WHERE cat.section LIKE ('com_jevpeople_type%')";
		}
		$db->setQuery($sql);
		$cats = $db->loadObjectList();
		
		$sql = "SELECT * FROM #__jev_peopletypes";
		$db->setQuery($sql);
		$types = $db->loadObjectList('type_id');

		foreach ($cats as $cat) {
			$type = str_replace('com_jevpeople_type','',$cat->section);
			if (!array_key_exists($type,$types)){
				if (JVersion::isCompatible("1.6.0"))  {
					$db->setQuery("DELETE FROM  #__categories WHERE section=".$db->Quote($cat->section));	
				}
				else {
					$db->setQuery("DELETE FROM  #__categories WHERE section=".$db->Quote($cat->section));	
				}
				$db->query();
			}
		}
	}
}
