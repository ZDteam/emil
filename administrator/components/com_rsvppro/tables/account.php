<?php
/**
 * @brief   generic merchant account registered with a generic gateway
 * @version $Id: gateway.php 168 2010-02-20 14:24:17Z pcarr $
 */

// Require Single Entry Point to CommerceKit
defined( '_COMMERCEKIT' ) or die( 'Unauthorised Access' );

class rsvpAccount extends JTable
{
	// local mysql id
	public $id = null;

	public $name = "";

	// defunct
	public $response_id = null;
	
	// This is the xml file for the form parameters
	public $xmlfile = null;

	public $params = null;

	public function __construct()
	{
		$db = JFactory::getDBO();
		parent::__construct("#__commerce_accounts","id", $db);
	}

	public function bind( $array, $ignore=array() )
	{

		if (is_array( $array['params'] ))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = $registry->toString();
		}

		return parent::bind($array, $ignore);
	}

}

