<?php

/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(dirname(__FILE__) . "/field.php");

/**
 * Template Field class
 *
 */
class TableJevrcheckbox extends TableField {

    /**
     * Overloaded bind function
     *
     */
    function bind($array, $ignore='', $fieldid) {
        $success = parent::bind($array, $ignore, $fieldid);
        // convert row index to row value!
        /* Not needed the javascript in the template editing takes care of this
        $data = $array['dv'];
        if (array_key_exists($fieldid, $data)) {
            $this->defaultvalue = $data[$fieldid];
            $options = json_decode($this->options);
            if (is_array($this->defaultvalue)) {
                foreach ($this->defaultvalue as $key => $dv){
                    $this->defaultvalue[$key] = $options->value[$key];
                }
                $this->defaultvalue = json_encode($this->defaultvalue);
            }
            else if (array_key_exists($this->defaultvalue, $options->value)){
                $this->defaultvalue = $options->value[$this->defaultvalue];
                
            }
        }
         * 
         */

        return $success;
    }

}
