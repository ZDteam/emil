<?php

/**
 * @version		1.0.17
 * @package		Hs - Highlighter 2014-01-21 
 * @author		Hayato Sugimoto
 * @copyright	Copyright (C) 2005 - 2014  Hayatos. All rights reserved.
 * @license		GNU/GPL
 */
 

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * 
 */
class plgeditorsxtdhs_highlighterInstallerScript {
        /**
         * Constructor
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         */
        public function __constructor(JAdapterInstance $adapter){
        	
        }
 
        /**
         * インストール・アンインストール前に呼び出される
         *
         * @param   string  $route  処理のタイプ (install|uninstall|discover_install)
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function preflight($route, JAdapterInstance $adapter){
        	
        }
 
        /**
         * インストール・安易ストール後に呼び出される
         *
         * @param   string  $route  Which action is happening (install|uninstall|discover_install)
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function postflight($route, JAdapterInstance $adapter){
			if($route=='install'){
				$db = JFactory::getDbo();
				$query = 'UPDATE #__extensions SET enabled=1 WHERE name='.$db->quote('plg_editors-xtd_hs_highlighter');
				$db->setQuery($query);
				$db->execute();
			}
		}
 
        /**
         * インストール時に呼び出し
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function install(JAdapterInstance $adapter){
        	
        }
 
        /**
         * アップデート時に呼び出し
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function update(JAdapterInstance $adapter){
        	
        }
 
        /**
         * アンインストール時に呼び出し
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         */
        public function uninstall(JAdapterInstance $adapter){
        	
        }


}
