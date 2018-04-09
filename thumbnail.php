<?php
/***
 * 
 * A thumbnail demo with vImage
 * 
 */
 
//ini_set ( "error_reporting", E_ALL );
//ini_set ( "display_errors", TRUE );
//ini_set ('memory_limit', '1024M');
require './luffygit/vImage.php';

// Create a new vImage object
(new vImage())
	->fromFile("./assets/image/beijing.jpeg")
	->brighten(20)
	->thumbnail(100,100,'center')
	->toScreen('image/jpeg')
->__destruct();
  