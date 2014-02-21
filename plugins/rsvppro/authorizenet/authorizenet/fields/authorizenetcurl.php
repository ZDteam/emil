<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1331 2010-10-19 12:35:49Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

class JFormFieldAuthorizenetCurl extends JFormField
{

	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'authorizenetcurl';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		if (!function_exists('curl_init'))
		{
			return JTExt::_('RSVPPRO_AUTHORIZENET_CURL_LIBRARY_NOT_INSTALLED');
		}
		else
		{
			return JTExt::_('RSVPPRO_AUTHORIZENET_CURL_LIBRARY_INSTALLED');
		}

	}

}