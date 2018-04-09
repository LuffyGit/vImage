<?php
/***
 * 
 * A namecard demo with vImage
 * 
 */


//ini_set ( "error_reporting", E_ALL );
//ini_set ( "display_errors", TRUE );
//ini_set ('memory_limit', '1024M');
require './luffygit/vImage.php';

//namecard content
$name = '北京d路飞';
$homeurl = 'https://github.com/LuffyGit/vImage';
$tel = '13800013800';
$email = '912977349@qq.com'; 
$note = "邮件QQ请备注vImage和公司姓名";
//namecard format
$qrtext = "MECARD:N:$name;URL:".$homeurl.";TEL:$tel;EMAIL:$email;NOTE:$note";

//process the photo
$headphoto = (new vImage())
	->fromFile('./assets/image/haki.jpg')
	->thumbnail(100,100, 'top')
	->rotate(0)
	->flip('y')
	->maxColors(9);
	
//generate a QRCode
(new vImage())
	->fromNew(640, 640, 'white')
	->qrCode($qrtext, 600, 600, 'center', 'L', 4)
	->overlay($headphoto)
	->toScreen('image/jpeg')
//recycle resources.
->__destruct();
$headphoto->__destruct();