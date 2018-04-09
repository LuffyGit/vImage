<?php
/***
 * 
 * A watermark demo with vImage
 * 
 */

//ini_set ( "error_reporting", E_ALL );
//ini_set ( "display_errors", TRUE );
//ini_set ('memory_limit', '1024M');
require './luffygit/vImage.php';
  
// Create a new vImage object
$image = (new vImage());

$image->fromFile("./assets/image/chengdu.jpg")
	->fitToWidth(640)
//watermark with alpha
	->text('唱吧前端组', [
	  'fontFile' => './assets/font/STXINGKA.TTF',
	  'size' =>36,
	  'color' => ['red'=>0xff, 'green'=>0xff, 'blue'=>0xff, 'alpha'=>0.5],
	  'anchor' => 'bottom left',
	  'xOffset' => 15,
	  'yOffset' => -15,
	  'angle'=>0
	])
//mark with a logo
	->overlay("./assets/image/logo.png",'bottom right',1,-15,-15)	
//output to the screen
	->toScreen('image/jpeg')
//recycle resources.
	->__destruct();
