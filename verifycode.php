<?php
/***
 * 
 * A verifycode demo with vImage
 * 
 */

//ini_set ( "error_reporting", E_ALL );
//ini_set ( "display_errors", TRUE );
//ini_set ('memory_limit', '1024M');
require './luffygit/vImage.php';

$verify_code = substr(md5(time().''.rand(1, 10000)), 0, 6);
// Create a new vImage object
$vImage = (new vImage());
//generate VerifyCode image
$vImage->verifycode($verify_code, 100,40);
//output to the screen
$vImage->toScreen('image/jpeg'); 
//output with base64 data
//echo $vImage->toDataUri('image/jpeg'); 
$vImage->__destruct();
