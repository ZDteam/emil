<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');
// myst set value to initial state to process pending registrations
echo '<label for="jevattend_yes"><input type="radio" name="jevattend" id="jevattend_yes" value="' . $this->initialstate . '"  ' . (($this->attendstate == 1 || $this->attendstate == 3 || $this->attendstate == 4) ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_( 'JEV_ATTEND_YES' ) . '</label>';