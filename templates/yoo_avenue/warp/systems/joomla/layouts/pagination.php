<?php
/**
* @package   Warp Theme Framework
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die;

if (in_array($app->scope, array('com_content', 'com_finder', 'com_search', 'com_tags'))) {

	function pagination_list_render($list) {

		$end     = count($list['pages']);

		$current = 1;
		$max     = 3;

		foreach ($list['pages'] as $i => $page) {
			if (!$page['active']) $current = $i;
		}

		$range   = ($current + $max < $end) ? range($current, $current + $max) : range($current - ($current + $max - $end), $end);

		// Initialize variables
		$html = '<ul class="uk-pagination">';

		if ($list['previous']['active']==1) $html .= $list['previous']['data'];

		foreach ($list['pages'] as $i => $page) {

			$item = ($i != $current) ? $page['data'] : str_replace('<li class="uk-disabled">', '<li class="uk-active">', $page['data']);

			if($i==1 || $i==$end || in_array($i, $range)) {
				$html .= $item;
			}else{
				$html .= '#';
			}
		}

		$html = preg_replace('/>#+</', '><li><span>...</span></li><', $html);

		if ($list['next']['active']==1) $html .= $list['next']['data'];

		$html .= "</ul>";

		return $html;
	}

	function pagination_item_active($item) {

		$cls = '';
		$title = '';

	    if ($item->text == JText::_('JNEXT')) { $item->text = '<i class="uk-icon-angle-double-right"></i>'; $cls = "next"; $title = JText::_('JNEXT'); }
	    if ($item->text == JText::_('JPREV')) { $item->text = '<i class="uk-icon-angle-double-left"></i>'; $cls = "previous"; $title = JText::_('JPREV'); }
		if ($item->text == JText::_('JLIB_HTML_START')) { $cls = "first"; }
	    if ($item->text == JText::_('JLIB_HTML_END')) { $cls = "last"; }

	    return '<li><a class="'.$cls.'" href="'.$item->link.'" title="'.$title.'">'.$item->text.'</a></li>';
	}

	function pagination_item_inactive(&$item) {
		return '<li class="uk-disabled"><span>'.$item->text.'</span></li>';
	}

}