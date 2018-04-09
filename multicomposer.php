<?php
/***
 * 
 * A composite demo with vImage
 * 
 */
 
//ini_set ( "error_reporting", E_ALL );
//ini_set ( "display_errors", TRUE );
//ini_set ('memory_limit', '1024M');
require './luffygit/vImage.php';

try {
  // Create a new vImage object
  $image1 = new vImage();

  $image1
    ->fromFile('./assets/image/ganzhou.jpg')              // load file
    ->fitToWidth(600)
	->thumbnail(600, 300)
	->opacity(0.9)
	->brighten(10)
	->polygon([['x'=>530,'y'=>0],['x'=>600,'y'=>0],['x'=>600,'y'=>70]], 'red','filled')
	->text('HOT', [
      'fontFile' => './assets/font/ARIAL.TTF',
      'size' =>20,
      'color' => 'yellow',
      'anchor' => 'top right',
      'xOffset' => -25,
      'yOffset' => 0,
      'shadow' => '',
      'angle'=>-45
    ])
	->border('yellow',3)
	->rotate(-8);
//	->toScreen('image/jpeg');

	// Create a new vImage object
  $image2 = new vImage();
	$image2
    ->fromFile('./assets/image/chengdu.jpg')              // load file
    ->fitToWidth(600)
	->thumbnail(600, 300,'bottom')
	->opacity(0.9)
	->brighten(10)
	->polygon([['x'=>530,'y'=>0],['x'=>600,'y'=>0],['x'=>600,'y'=>70]], 'red','filled')
	->text('NEW', [
	      'fontFile' => './assets/font/ARIAL.TTF',
	      'size' =>20,
	      'color' => 'white',
	      'anchor' => 'top right',
	      'xOffset' => -25,
	      'yOffset' => 0,
	      'shadow' => '',
	      'angle'=>-45
	    ])
	->border('yellow',3)
	->rotate(8);
//	->toScreen('image/jpeg');
		
	$image3 = new vImage();
	$image3
    ->fromFile('./assets/image/beijing.jpeg')              // load file
    ->fitToWidth(600)
	->thumbnail(600, 300)
	->opacity(0.9)
	->brighten(10)
	->polygon([['x'=>530,'y'=>0],['x'=>600,'y'=>0],['x'=>600,'y'=>70]], 'green','filled')
	->text('NEW', [
	      'fontFile' => './assets/font/ARIAL.TTF',
	      'size' =>20,
	      'color' => 'white',
	      'anchor' => 'top right',
	      'xOffset' => -25,
	      'yOffset' => 0,
	      'shadow' => '',
	      'angle'=>-45
	    ])
	->border('yellow',3)
	->rotate(-8);
//	->toScreen('image/jpeg');

	$offsetXSubTitleLine = 30;
	$endXSubTitleLine = 125;
	$offsetXImage = 50;
	$offsetYImage1 = 120;
	$offsetYImage2 = $offsetYImage1+$image1->getHeight()+20;
	$offsetYImage3 = $offsetYImage2+$image2->getHeight()+20;
	$offsetSubTitle = 100;
	$height = $offsetYImage3+$image3->getHeight()+100;
	
	// Create a new vImage object
	$imageCombo = new vImage();
	$imageCombo
	//创建组合图框架
  	->fromNew(750, $height, 'white')
	->line($offsetXSubTitleLine, 36, 285, 36, 'red', 2)
	->text('三地修行', [
      'fontFile' => './assets/font/STXINGKA.TTF',
      'size' => 48,
      'color' => 'red',
      'anchor' => 'top',
      'xOffset' => 10,
      'yOffset' => 10,
      'shadow' => ''
    ])
	->line($offsetXSubTitleLine, 36, $offsetXSubTitleLine, $height, 'red', 2)
	//组合图1
    ->textInVerticalRectWithDiv('赣州', [$offsetXSubTitleLine+22, $offsetYImage1, $offsetXSubTitleLine+70, $offsetYImage1+$offsetSubTitle-2],
    [
	  	'fontFile' => './assets/font/STXINGKA.TTF',
      'size' => 48,
      'color' => 'red',
      'anchor' => 'top left'
    ])
	->line($offsetXSubTitleLine,$offsetYImage1+$offsetSubTitle,$endXSubTitleLine,$offsetYImage1+$offsetSubTitle, 'yellow', 3)
	->overlay($image1, 'top', 1, $offsetXImage, $offsetYImage1)
	//组合图2
	->textInVerticalRectWithDiv('成都', [$offsetXSubTitleLine+22, $offsetYImage2, $offsetXSubTitleLine+70, $offsetYImage2+$offsetSubTitle-2],
  	[  
	  	'fontFile' => './assets/font/STXINGKA.TTF',
      'size' => 48,
      'color' => 'red',
      'anchor' => 'top left'
    ])
	->line($offsetXSubTitleLine,$offsetYImage2+$offsetSubTitle,$endXSubTitleLine,$offsetYImage2+$offsetSubTitle, 'yellow', 3)
	->overlay($image2, 'top', 1, $offsetXImage, $offsetYImage2)
	//组合图3
	->textInVerticalRectWithDiv('北京', [$offsetXSubTitleLine+22, $offsetYImage3, $offsetXSubTitleLine+70, $offsetYImage3+$offsetSubTitle-2],
	[
      'fontFile' => './assets/font/STXINGKA.TTF',
      'size' => 48,
      'color' => 'red',
      'anchor' => 'center'
    ])
	->line($offsetXSubTitleLine,$offsetYImage3+$offsetSubTitle,$endXSubTitleLine,$offsetYImage3+$offsetSubTitle, 'yellow', 3)
	->overlay($image3, 'top', 1, $offsetXImage, $offsetYImage3)
	//底栏
	->polygon([['x'=>0,'y'=>$height -50],['x'=>$offsetXSubTitleLine-1,'y'=>$offsetYImage3+$image3->getHeight()-30],['x'=>280,'y'=>$height -50]], 'red','filled')
	->rectangle(0,$height -50, 750, $height, 'red', 'filled')
  	->textInVerticalRectWithDiv('长按扫描二维码', [$offsetXSubTitleLine-8, $offsetYImage3+$image3->getHeight()-15, $offsetXSubTitleLine+7, $height-10],
		[
      'fontFile' => './assets/font/STXINGKA.TTF',
      'size' => 15,
      'color' => 'white',
      'anchor' => 'center'
    ])
	->qrCode('http://changba.com', 80, 80, 'bottom left', 'L', 4, 1, $offsetXSubTitleLine+18, -10)
  	->overlay("./assets/image/logo.png",'bottom right',1,-5,-5)
	->toScreen('image/jpeg');
	//回收资源
	$image1->__destruct();
	$image2->__destruct();
	$image3->__destruct();
	$imageCombo->__destruct();
} catch(Exception $err) {
  // Handle errors
  echo $err->getMessage();
}
