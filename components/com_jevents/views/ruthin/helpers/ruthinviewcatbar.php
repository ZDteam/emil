<?php 
defined('_JEXEC') or die('Restricted access');

function RuthinViewCatbar($view){

	$db = JFactory::getDBO();
	$aid = $view->datamodel->aid;
	$sectionname = JEV_COM_COMPONENT;

	// Get all top level categories
	$query = "SELECT * FROM #__categories AS c"
	. "\n WHERE c.access <= $aid"
	. "\n AND c.published = 1"
	. "\n AND c.section = '".$sectionname."'"
	. "\n AND c.parent_id = 0"
	. "\n ORDER BY c.title"
	;
	$db->setQuery($query);
	$topcatlist =  $db->loadObjectList('id');
	$fulltopcatlist =  $topcatlist;


	// Get all sub level categories
	$query = "SELECT * FROM #__categories AS c "
	. "\n WHERE c.access <= $aid"
	. "\n AND c.published = 1"
	. "\n AND c.section = '".$sectionname."'"
	. "\n AND c.parent_id > 0"
	. "\n ORDER BY c.title"
	;
	$db->setQuery($query);
	$subcatlist =  $db->loadObjectList('id');
	$fullsubcatlist = $subcatlist;

	if (!isset($view->catids)){
		$view->catids 	= JRequest::getVar( 	'catids', 		"") ;
		$view->catids= explode("|",$view->catids);
	}
	$catinfo = $view->datamodel->queryModel->getCategoryInfo($view->catids);
	$subcats = $view->datamodel->queryModel->getChildCategories($view->catids);

	foreach ($topcatlist as &$cat) {
		$cat->subcats = array();
		$subcatactive = false;
		foreach ($subcatlist as $subcat) {
			if ($subcat->parent_id == $cat->id){
				if (array_key_exists($subcat->id,$catinfo)){
					$subcat->isActive = true;
					$subcatactive = true;
				}
				else {
					$subcat->isActive = false;
				}
				$cat->subcats[$subcat->id] = $subcat;
			}
		}
		if (array_key_exists($cat->id,$catinfo)){
			$cat->isActive = true;
		}
		else {
			$cat->isActive = false;
		}
		if ($subcatactive){
			$cat->isSubActive = true;
		}
		else {
			$cat->isSubActive = false;
		}
		unset($cat);
	}

	//$fullsubcatlist[$subcat->id] = true;
	//unset($fullsubcatlist[$subcat->id]);
	//$fulltopcatlist[$cat->id] = true;
	//unset($fulltopcatlist[$cat->id]);


	$Itemid = JEVHelper::getItemid();
	list($year,$month,$day) = JEVHelper::getYMD();

	$task = JRequest::getVar("jevtask", "cat.listevents");
	if (strpos($task,"detail")>0){
		$task = "cat.listevents";
	}

	?>
<div id="jevcatbar">
	<ul class="menu">
	<?php
	foreach ($topcatlist as $cat) {
			?>
			<li class="level1 <?php echo $cat->isActive?'active':($cat->isSubActive?'subactive':'');?>">
				<?php
				$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=".$cat->id."&year=$year&month=$month&day=$day&Itemid=".$Itemid);
				echo "<a class='level1 ".($cat->isActive?'active':($cat->isSubActive?'subactive':''))."' href='$link' title='".htmlspecialchars($cat->title)."'><span style='border-color:".$cat->color."'>".$cat->title."</span></a>";

				$opensubcat = false;
				foreach ($subcatlist as $subcat) {
					if ($subcat->parent_id == $cat->id){
						if (!$opensubcat){
							echo "<ul class='level2'>\n";
							$opensubcat	=true;
						}
						echo "<li class='level2  ".($subcat->isActive?'active':'')."'>\n";
						$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=".$subcat->id."&year=$year&month=$month&day=$day&Itemid=".$Itemid);
						echo "<a  class='level2  ".($subcat->isActive?'active':'')."' href='$link' title='".htmlspecialchars($subcat->title)."'><span style='border-color:".$subcat->color."'>".$subcat->title."</span></a>";

						$opensubsubcat = false;
						foreach ($subcatlist as $subsubcat) {
							if ($subsubcat->parent_id == $subcat->id){
								if (!$opensubsubcat){
									echo "<ul class='level3'>\n";
									$opensubsubcat	=true;
								}
								echo "<li class='level3'>\n";
								$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=".$subsubcat->id."&year=$year&month=$month&day=$day&Itemid=".$Itemid);
								echo "<a  class='level3' href='$link' title='".htmlspecialchars($subsubcat->title)."'><span style='border-color:".$subsubcat->color."'>".$subsubcat->title."</span></a>";
								echo "</li>\n";
							}
						}
						if ($opensubsubcat) {
							echo "</ul>\n";
						}
						echo "</li>\n";
					}
				}
				if ($opensubcat) {
					echo "</ul>\n";
				}
				?>
			</li>
			<?php
	}
	?>
		<li class="level1">
				<?php
				$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=0&year=$year&month=$month&day=$day&Itemid=".$Itemid);
				echo "<a class='level1' href='$link' title='Alle'><span style='border-width:0px;border-color:transparent'>Alle</span></a>";
				?>
		</li>
	</ul>
</div>
<?php
$html = "";
unset($cat);
foreach ($topcatlist as $cat) {
	if ($cat->isActive || $cat->isSubActive){
		$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=".$cat->id."&year=$year&month=$month&day=$day&Itemid=".$Itemid);
		$html .= "<a  href='$link' title='".htmlspecialchars($cat->title)."'>".$cat->title."</a>";
	}
}

unset($subcat);
foreach ($subcatlist as $subcat) {
	if (isset($subcat->isActive) && $subcat->isActive){
		$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=".$subcat->id."&year=$year&month=$month&day=$day&Itemid=".$Itemid);
		$html .= " -> <a  href='$link' title='".htmlspecialchars($subcat->title)."'>".$subcat->title."</a>";
	}
}
if ($html != "" && count($view->catids)==1 && $view->catids[0]!=0){

	$link = JRoute::_("index.php?option=".JEV_COM_COMPONENT."&task=".$task."&catids=0&year=$year&month=$month&day=$day&Itemid=".$Itemid);
	$html = "<a  href='$link' title='Alle'>Alle</a> -> " . $html;

	$html = "<div style='margin:5px 0px;color:gray'>Events in der Kategorie: " . $html."</div>";
	echo $html;
}
else echo "<br/>";

}
