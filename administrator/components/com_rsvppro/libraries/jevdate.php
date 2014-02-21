<?php

// No direct access
defined('JPATH_BASE') or die;

// Class to fix Joomla 1.6 date class bugs
jimport("joomla.utilities.date");

if (!JVersion::isCompatible("1.6.0"))
{
	class JevDate extends JDate
	{
		public function getDate($time = 'now', $tzOffset = null) {
			return JFactory::getDate($time,$tzOffset);
		}

		public static function strtotime($time, $now=null){
			if ($now!=null){
				$res = strtotime($time, $now);
			}
			else {
				$res = strtotime($time);
			}
			return $res;
		}

		public static function mktime(){
			$arg = func_get_args();

			$name ="mktime";
			if (is_callable($name)){
				return call_user_func_array($name,$arg);
			}
		}

		public static function strftime(){
			$name ="strftime";
			$arg = func_get_args();
			if (is_callable($name)){
				return call_user_func_array($name,$arg);
			}
		}

		public function __call($name, $arguments){
			$args = array_unshift($arguments,$this);

			if (is_callable($name)){
				return call_user_func_array($name,$arg);
			}
		}

	}
}