<?php 
defined('_JEXEC') or die('Restricted access');

function RuthinViewHelperViewNavAdminPanel($view){
	$is_event_editor = JEVHelper::isEventCreator();
	$user = & JFactory::getUser();

	JEVHelper::script( 'view_detail.js', 'components/'.JEV_COM_COMPONENT."/assets/js/" );
	
	JLoader::register('jevFilterProcessing',JEV_PATH."/libraries/filters.php");
	$pluginsDir = JPATH_ROOT.'/'.'plugins'.'/'.'jevents';
	$filters = jevFilterProcessing::getInstance(array("published","justmine","category","reset"));

	$cfg = & JEVConfig::getInstance();

	if (JRequest::getInt( 'pop', 0 )) return;

		if( $is_event_editor) { ?>
		<div class="ev_adminpanel">
		<table border="0" align="center">
			<tr>
				<td align="left" class="nav_bar_cell">
                        <?php
                        $editLink = JRoute::_('index.php?option=' . JEV_COM_COMPONENT
                        . '&task=icalevent.edit' . '&year=' . $view->year . '&month=' . $view->month . '&day=' . $view->day
                        . '&Itemid=' . $view->Itemid, true);
                        $popup=false;
                        $params = JComponentHelper::getParams(JEV_COM_COMPONENT);
                        if ($params->get("editpopup",0)){
                        	JHTML::_('behavior.modal');
			JHTML::script('components/'.JEV_COM_COMPONENT.'/assets/js/editpopup.js');
                        	$popup=true;
                        	$popupw = $params->get("popupw",800);
                        	$popuph = $params->get("popuph",600);
                        }
                        $eventlinkadd = $popup?"javascript:jevEditPopup('".$editLink."',$popupw, $popuph);":$editLink;
                        ?>
                        <a href="<?php echo $eventlinkadd; ?>" title="<?php echo JText::_('JEV_ADDEVENT');?>">
                            <b><?php echo JText::_('JEV_ADDEVENT');?></b>
                        </a>
                        <?php
                        // offer frontend import ?
                        if ($params->get("feimport",0)){
                        	$importLink = JRoute::_('index.php?option=' . JEV_COM_COMPONENT
                        	. '&task=icals.importform&tmpl=component&Itemid=' . $view->Itemid, true);
                        	JHTML::_('behavior.modal');
                        	JHTML::script('components/'.JEV_COM_COMPONENT.'/assets/js/editpopup.js');
                        	$eventimport = "javascript:jevImportPopup('".$importLink."',400, 400);";
	                        ?>
	                        <br/><a href="<?php echo $eventimport; ?>" title="<?php echo JText::_('JEV_IMPORT_ICALEVENT', true);?>">
	                            <b><?php echo JText::_('JEV_IMPORT_ICALEVENT');?></b>
	                        </a>
	                        <?php
                        }

                        if (JEVHelper::isEventCreator()) {
                        	$datamodel	=new JEventsDataModel();
                        	// find appropriate Itemid and setup catids for datamodel
                        	$myItemid = JEVHelper::getItemid();
                        	$datamodel->setupComponentCatids();

                        	list($year,$month,$day) = JEVHelper::getYMD();
                        	$evid = JRequest::getVar("evid",false);
                        	$jevtype = JRequest::getVar("jevtype",false);
                        	// FORM for filter submission
                        	$form_link = JRoute::_(
                        	'index.php?option=' . JEV_COM_COMPONENT
                        	. '&task=' .JRequest::getVar("jevtask", "month.calendar")
                        	. ($evid ? '&evid=' . $evid : '')
                        	. ($jevtype ? '&jevtype=' . $jevtype : '')
                        	. ($year ? '&year=' . $year : '')
                        	. ($month ? '&month=' . $month : '')
                        	. ($day ? '&day=' . $day : '')
                        	. "&Itemid=".$myItemid
                        	,false);

							?>
						<form action="<?php echo $form_link;?>"  method="post">
						<?php

						$filterHTML = $filters->getFilterHTML();

						$Itemid	= JEVHelper::getItemid();

						foreach ($filterHTML as $filter){
							echo "<div>".$filter["title"]." ".$filter["html"]."</div>";
						}
						/*
						$eventmylinks = JRoute::_( 'index.php?option=' . JEV_COM_COMPONENT . '&task=admin.listevents'
						. '&year=' . $view->year . '&month=' . $view->month . '&day=' . $view->day
						. '&Itemid=' . $view->Itemid ); ?>
						<a href="<?php echo $eventmylinks; ?>" title="<?php echo JText::_('JEV_MYEVENTS'); ?>">
						<b><?php echo JText::_('JEV_MYEVENTS'); ?></b>
						</a>
						<?php
						*/
						?>
						</form>
						<?php
                        }?>
				</td>
			</tr>
		</table>
		</div>
		<?php	} 
}
