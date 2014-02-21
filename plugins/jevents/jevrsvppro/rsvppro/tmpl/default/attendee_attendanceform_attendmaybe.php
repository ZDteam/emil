<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

 echo ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" id="jevattend_maybe" value="2"  ' . ($this->attendstate == 2 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_( 'JEV_ATTEND_MAYBE' ) . '</label>' : '');