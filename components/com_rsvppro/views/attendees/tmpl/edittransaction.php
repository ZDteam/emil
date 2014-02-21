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
defined('_JEXEC') or die('Restricted Access');

JPluginHelper::importPlugin("rsvppro");

$transaction = $this->transaction;
$rsvpdata = $this->rsvpdata;
$repeat = $this->repeat;
$template = $this->template;
$attendee= $this->attendee;

if ($transaction->transaction_id > 0)
{
	$activePlugin = false;

	JRequest::setVar("gateway", $transaction->gateway);
	$dispatcher = & JDispatcher::getInstance();
	$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "paymentpage"));

	if ($activePlugin && class_exists($activePlugin) && method_exists($activePlugin, "editTransaction"))
	{
		// load plugin parameters
		$pluginname = strtolower(str_replace("plgRsvppro", "", $activePlugin));
		$plugin = & JPluginHelper::getPlugin("rsvppro", $pluginname);
		$gateway = new $activePlugin($dispatcher, (array) ($plugin));
		echo $gateway->editTransaction($attendee, $rsvpdata, $repeat, $transaction, $template);
	}
}
else
{
	// currently only support manual payment - down the line offer a choice!
	/*
	  $activePlugins = array();
	  $dispatcher	=& JDispatcher::getInstance();
	  $dispatcher->trigger( 'activeGateways', array(&$activePlugins,"paymentpage"));
	 */
	JRequest::setVar("gateway", 'manual');
	$dispatcher = & JDispatcher::getInstance();
	$dispatcher->trigger('activeGatewayClass', array(&$activePlugin, "paymentpage"));

	if ($activePlugin && class_exists($activePlugin) && method_exists($activePlugin, "editTransaction"))
	{
		// load plugin parameters
		$pluginname = strtolower(str_replace("plgRsvppro", "", $activePlugin));
		$plugin = & JPluginHelper::getPlugin("rsvppro", $pluginname);
		$gateway = new $activePlugin($dispatcher, (array) ($plugin));
		echo $gateway->editTransaction($attendee, $rsvpdata, $repeat, $transaction, $template);
	}
}
