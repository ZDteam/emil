<?php

/*
  Barcode Render Class for PHP using the GD graphics library
  Copyright (C) 2001  Karim Mribti

  Version  0.0.7a  2001-04-01

  This library is free software; you can redistribute it and/or
  modify it under the terms of the GNU Lesser General Public
  License as published by the Free Software Foundation; either
  version 2.1 of the License, or (at your option) any later version.

  This library is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public
  License along with this library; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  Copy of GNU Lesser General Public License at: http://www.gnu.org/copyleft/lesser.txt

  Source code home page: http://www.mribti.com/barcode/
  Contact author at: barcode@mribti.com
 */


require("barcode.php");
require("c39object.php");

$code = $_GET["bc"];

$output = "png";
$width = "300";
$height = "80";
$xres = "1";
$font = "3";
/* * ****************************** */

$style = BCS_ALIGN_CENTER;
$style |= BCS_IMAGE_PNG;
$style |= BCS_BORDER;
$style |= BCS_DRAW_TEXT;
//$style |= ( $stretchtext == "on" ) ? BCS_STRETCH_TEXT : 0;

$parts = split("-", $code);
$parts[0] = crc32($parts[0]);
$parts[0] = strtoupper(base_convert($parts[0], 10, 26));
$parts[1] = strtoupper(base_convert($parts[1], 10, 26));
$code = $parts[0] . "-" . $parts[1];
$obj = new C39Object($width, $height, $style, $code);

if ($obj) {
	$obj->SetFont($font);
	$obj->DrawObject($xres);
	$obj->FlushObject();
	$obj->DestroyObject();
	unset($obj);  /* clean */
}

