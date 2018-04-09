<?php
//
// ImagePaintBrush
//
//  A PHP class that makes working with images as simple as possible.
//
//  Developed and maintained by Cory LaViska <https://github.com/claviska>.
//
//  Copyright A Beautiful Site, LLC.
//
//  Source: https://github.com/claviska/SimpleImage
//
//  Licensed under the MIT license <http://opensource.org/licenses/MIT>
//

//namespace vanch;

require_once __DIR__ . '/phpqrcode/qrlib.php';

class ImagePaintBrush {
	
  //////////////////////////////////////////////////////////////////////////////////////////////////
  // ImagePaintBrush
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Same as PHP's imagecopymerge, but works with transparent images.
  //
  public static function imageCopyMergeAlpha($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct) {
    // Are we merging with transparency?
    if($pct < 100) {
      // Disable alpha blending and "colorize" the image using a transparent color
      imagealphablending($srcIm, false);
      imagefilter($srcIm, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * ((100 - $pct) / 100));
    }

    imagecopy($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH);

    return true;
  }
  
  //
  // Place an image on top of the current image.
  //
  //  $srcImage* (string|SimpleImage) - The image to overlay. This can be a filename, a data URI, or
  //    a SimpleImage object.
  //  $anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right', 'top left',
  //    'top right', 'bottom left', 'bottom right' (default 'center')
  //  $opacity (float) - The opacity level of the overlay 0-1 (default 1).
  //  $xOffset (int) - Horizontal offset in pixels (default 0).
  //  $yOffset (int) - Vertical offset in pixels (default 0).
  //
  // Returns a SimpleImage object.
  //
  public static function overlay($dstImage, $srcImageFileUri, $anchor = 'center', $opacity = 1, $xOffset = 0, $yOffset = 0) {
	$is_resource = false;
	//Use the given resource of image $srcImageFileUri
	if(is_resource($srcImageFileUri)){
		$imageFrom = array();
		$imageFrom['image'] = $srcImageFileUri;
		$is_resource = true;
	}else
	// Load image by 'ImageFrom'
    if(preg_match('/^data:(.*?);/', $srcImageFileUri)) {
      $imageFrom = ImageFrom::fromDataUri($srcImageFileUri);
    } elseif($srcImageFileUri) {
      $imageFrom = ImageFrom::fromFile($srcImageFileUri);
    }
	$srcImage = $imageFrom['image'];

    // Convert opacity
    $opacity = self::keepWithin($opacity, 0, 1) * 100;
	$dstWidth = ImageSizer::getWidth($dstImage);
	$dstHeight = ImageSizer::getHeight($dstImage);
	$srcWidth = ImageSizer::getWidth($srcImage);
	$srcHeight = ImageSizer::getHeight($srcImage);
    // Determine placement
    switch($anchor) {
      case 'top left':
        $x = $xOffset;
        $y = $yOffset;
        break;
      case 'top right':
        $x = $dstWidth - $srcWidth + $xOffset;
        $y = $yOffset;
        break;
      case 'top':
        $x = ($dstWidth / 2) - ($srcWidth / 2) + $xOffset;
        $y = $yOffset;
        break;
      case 'bottom left':
        $x = $xOffset;
        $y = $dstHeight - $srcHeight + $yOffset;
        break;
      case 'bottom right':
        $x = $dstWidth - $srcWidth + $xOffset;
        $y = $dstHeight - $srcHeight + $yOffset;
        break;
      case 'bottom':
        $x = ($dstWidth / 2) - ($srcWidth / 2) + $xOffset;
        $y = $dstHeight - $srcHeight + $yOffset;
        break;
      case 'left':
        $x = $xOffset;
        $y = ($dstHeight / 2) - ($srcHeight / 2) + $yOffset;
        break;
      case 'right':
        $x = $dstWidth - $srcWidth + $xOffset;
        $y = ($dstHeight / 2) - ($srcHeight / 2) + $yOffset;
        break;
      default:
        $x = ($dstWidth / 2) - ($srcWidth / 2) + $xOffset;
        $y = ($dstHeight / 2) - ($srcHeight / 2) + $yOffset;
        break;
    }

    // Perform the overlay
    self::imageCopyMergeAlpha(
      $dstImage,
      $srcImage,
      $x, $y,
      0, 0,
      $srcWidth,
      $srcHeight,
      $opacity
    );
	
	if(!$is_resource)
	@imagedestroy($srcImage);
    return $dstImage;
  }

  //
  // Adds text to the image.
  //
  //  $text* (string) - The desired text.
  //  $options (array) - An array of options.
  //    - fontFile* (string) - The TrueType (or compatible) font file to use.
  //    - size (int) - The size of the font in pixels (default 12).
  //    - color (string|array) - The text color (default black).
  //    - anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right',
  //      'top left', 'top right', 'bottom left', 'bottom right' (default 'center').
  //    - xOffset (int) - The horizontal offset in pixels (default 0).
  //    - yOffset (int) - The vertical offset in pixels (default 0).
  //    - shadow (array) - Text shadow params.
  //      - x* (int) - Horizontal offset in pixels.
  //      - y* (int) - Vertical offset in pixels.
  //      - color* (string|array) - The text shadow color.
  //  $boundary (array) - If passed, this variable will contain an array with coordinates that
  //    surround the text: [x1, y1, x2, y2, width, height]. This can be used for calculating the
  //    text's position after it gets added to the image.
  //
  // Returns a SimpleImage object.
  //
  public static function text($dstImage, $text, $options, &$boundary = null) {
    // Check for freetype support
    if(!function_exists('imagettftext')) {
      throw new \Exception(
        'Freetype support is not enabled in your version of PHP.',
        ErrorCodeConstant::ERR_FREETYPE_NOT_ENABLED
      );
    }

    // Default options
    $options = array_merge([
      'fontFile' => null,
      'size' => 12,
      'color' => 'black',
      'anchor' => 'center',
      'xOffset' => 0,
      'yOffset' => 0,
      'shadow' => null,
      'angle'=>0
    ], $options);

    // Extract and normalize options
    $fontFile = $options['fontFile'];
    $size = ($options['size'] / 96) * 72; // Convert px to pt (72pt per inch, 96px per inch)
    $color = ImageColorPalette::allocateColor($dstImage, $options['color']);
    $anchor = $options['anchor'];
    $xOffset = $options['xOffset'];
    $yOffset = $options['yOffset'];
    $angle = $options['angle'];

    // Calculate the bounding box dimensions
    //
    // Since imagettfbox() returns a bounding box from the text's baseline, we can end up with
    // different heights for different strings of the same font size. For example, 'type' will often
    // be taller than 'text' because the former has a descending letter.
    //
    // To compensate for this, we create two bounding boxes: one to measure the cap height and
    // another to measure the descender height. Based on that, we can adjust the text vertically
    // to appear inside the box with a reasonable amount of consistency.
    //
    // See: https://github.com/claviska/SimpleImage/issues/165
    //
    $box = imagettfbbox($size, $angle, $fontFile, $text);
    if(!$box) {
      throw new \Exception("Unable to load font file: $fontFile", ErrorCodeConstant::ERR_FONT_FILE);
    }
    $boxWidth = abs($box[6] - $box[2]);
    $boxHeight = $options['size'];

    // Determine cap height
    $box = imagettfbbox($size, $angle, $fontFile, 'X');
    $capHeight = abs($box[7] - $box[1]);

    // Determine descender height
    $box = imagettfbbox($size, $angle, $fontFile, 'X Qgjpqy');
    $fullHeight = abs($box[7] - $box[1]);
    $descenderHeight = $fullHeight - $capHeight;
	$dstWidth = ImageSizer::getWidth($dstImage);
	$dstHeight = ImageSizer::getHeight($dstImage);
    // Determine position
    switch($anchor) {
    case 'top left':
      $x = $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'top right':
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'top':
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'bottom left':
      $x = $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'bottom right':
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'bottom':
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'left':
      $x = $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    case 'right';
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    default: // center
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    }

    $x = (int) round($x);
    $y = (int) round($y);

    // Pass the boundary back by reference
    $boundary = [
      'x1' => $x,
      'y1' => $y - $boxHeight, // $y is the baseline, not the top!
      'x2' => $x + $boxWidth,
      'y2' => $y,
      'width' => $boxWidth,
      'height' => $boxHeight
    ];

    // Text shadow
    if(is_array($options['shadow'])) {
      imagettftext(
        $dstImage,
        $size,
        $angle,
        $x + $options['shadow']['x'],
        $y + $options['shadow']['y'] - $descenderHeight,
        ImageColorPalette::allocateColor($dstImage, $options['shadow']['color']),
        $fontFile,
        $text
      );
    }

    // Draw the text
    imagettftext($dstImage, $size, $angle, $x, $y - $descenderHeight, $color, $fontFile, $text);

    return $dstImage;
  }

  //
  // Adds text to the image.
  //
  //  $text* (string) - The desired text.
  //  $rect  (array)  - [x1, y1, x2, y2]
  //  $options (array) - An array of options.
  //    - fontFile* (string) - The TrueType (or compatible) font file to use.
  //    - size (int) - The size of the font in pixels (default 12).
  //    - color (string|array) - The text color (default black).
  //    - anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right',
  //      'top left', 'top right', 'bottom left', 'bottom right' (default 'center').
  //    - xOffset (int) - The horizontal offset in pixels (default 0).
  //    - yOffset (int) - The vertical offset in pixels (default 0).
  //    - shadow (array) - Text shadow params.
  //      - x* (int) - Horizontal offset in pixels.
  //      - y* (int) - Vertical offset in pixels.
  //      - color* (string|array) - The text shadow color.
  //  $boundary (array) - If passed, this variable will contain an array with coordinates that
  //    surround the text: [x1, y1, x2, y2, width, height]. This can be used for calculating the
  //    text's position after it gets added to the image.
  //
  // Returns a SimpleImage object.
  //
  public static function textInRect($dstImage, $rect, $text, $options, &$boundary = null) {
    // Check for freetype support
    if(!function_exists('imagettftext')) {
      throw new \Exception(
        'Freetype support is not enabled in your version of PHP.',
        ErrorCodeConstant::ERR_FREETYPE_NOT_ENABLED
      );
    }

    // Default options
    $options = array_merge([
      'fontFile' => null,
      'size' => 12,
      'color' => 'black',
      'anchor' => 'center',
      'xOffset' => 0,
      'yOffset' => 0,
      'shadow' => null
    ], $options);

    // Extract and normalize options
    $fontFile = $options['fontFile'];
    $size = ($options['size'] / 96) * 72; // Convert px to pt (72pt per inch, 96px per inch)
    $color = ImageColorPalette::allocateColor($dstImage, $options['color']);
    $anchor = $options['anchor'];
    $xOffset = $options['xOffset'];
    $yOffset = $options['yOffset'];
    $angle = 0;

    // Calculate the bounding box dimensions
    //
    // Since imagettfbox() returns a bounding box from the text's baseline, we can end up with
    // different heights for different strings of the same font size. For example, 'type' will often
    // be taller than 'text' because the former has a descending letter.
    //
    // To compensate for this, we create two bounding boxes: one to measure the cap height and
    // another to measure the descender height. Based on that, we can adjust the text vertically
    // to appear inside the box with a reasonable amount of consistency.
    //
    // See: https://github.com/claviska/SimpleImage/issues/165
    //
    $box = imagettfbbox($size, $angle, $fontFile, $text);
    if(!$box) {
      throw new \Exception("Unable to load font file: $fontFile", ErrorCodeConstant::ERR_FONT_FILE);
    }
    $boxWidth = abs($box[6] - $box[2]);
    $boxHeight = $options['size'];

    // Determine cap height
    $box = imagettfbbox($size, $angle, $fontFile, 'X');
    $capHeight = abs($box[7] - $box[1]);

    // Determine descender height
    $box = imagettfbbox($size, $angle, $fontFile, 'X Qgjpqy');
    $fullHeight = abs($box[7] - $box[1]);
    $descenderHeight = $fullHeight - $capHeight;
	$dstWidth =$rect[2] -$rect[0];//ImageSizer::getWidth($dstImage);
	$dstHeight = $rect[3] -$rect[1];//ImageSizer::getHeight($dstImage);
	if($dstWidth <=0 || $dstHeight<=0){
      throw new \Exception("Unable to load font file: $fontFile", ErrorCodeConstant::ERR_INVALID_COORDINATE);
	}
	$xOffset += $rect[0];
	$yOffset += $rect[1]; 
    // Determine position
    switch($anchor) {
    case 'top left':
      $x = $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'top right':
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'top':
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = $yOffset + $boxHeight;
      break;
    case 'bottom left':
      $x = $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'bottom right':
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'bottom':
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = $dstHeight - $boxHeight + $yOffset + $boxHeight;
      break;
    case 'left':
      $x = $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    case 'right';
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    default: // center
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = ($dstHeight / 2) - (($boxHeight / 2) - $boxHeight) + $yOffset;
      break;
    }

    $x = (int) round($x);
    $y = (int) round($y);

    // Pass the boundary back by reference
    $boundary = [
      'x1' => $x,
      'y1' => $y - $boxHeight, // $y is the baseline, not the top!
      'x2' => $x + $boxWidth,
      'y2' => $y,
      'width' => $boxWidth,
      'height' => $boxHeight
    ];

    // Text shadow
    if(is_array($options['shadow'])) {
      imagettftext(
        $dstImage,
        $size,
        $angle,
        $x + $options['shadow']['x'],
        $y + $options['shadow']['y'] - $descenderHeight,
        ImageColorPalette::allocateColor($dstImage, $options['shadow']['color']),
        $fontFile,
        $text
      );
    }

    // Draw the text
    imagettftext($dstImage, $size, $angle, $x, $y - $descenderHeight, $color, $fontFile, $text);

    return $dstImage;
  }

  //
  // Adds text to the image.
  //
  //  $text* (string) - The desired text.
  //  $rect  (array)  - [x1, y1, x2, y2]
  //  $options (array) - An array of options.
  //    - fontFile* (string) - The TrueType (or compatible) font file to use.
  //    - size (int) - The size of the font in pixels (default 12).
  //    - color (string|array) - The text color (default black).
  //    - anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right',
  //      'top left', 'top right', 'bottom left', 'bottom right' (default 'center').
  //    - xOffset (int) - The horizontal offset in pixels (default 0).
  //    - yOffset (int) - The vertical offset in pixels (default 0).
  //    - shadow (array) - Text shadow params.
  //      - x* (int) - Horizontal offset in pixels.
  //      - y* (int) - Vertical offset in pixels.
  //      - color* (string|array) - The text shadow color.
  //  $boundary (array) - If passed, this variable will contain an array with coordinates that
  //    surround the text: [x1, y1, x2, y2, width, height]. This can be used for calculating the
  //    text's position after it gets added to the image.
  //
  // Returns a SimpleImage object.
  //
  public static function textInVerticalRectWithDiv($dstImage, $text, $rect, $options, &$boundary = null) {
    // Check for freetype support
    if(!function_exists('imagettftext')) {
      throw new \Exception(
        'Freetype support is not enabled in your version of PHP.',
        ErrorCodeConstant::ERR_FREETYPE_NOT_ENABLED
      );
    }

    // Default options
    $options = array_merge([
      'fontFile' => null,
      'size' => 12,
      'color' => 'black',
      'anchor' => 'center',
      'xOffset' => 0,
      'yOffset' => 0,
      'shadow' => null,
      'align_border'=>true
    ], $options);

    // Extract and normalize options
    $fontFile = $options['fontFile'];
    $size = ($options['size'] / 96) * 72; // Convert px to pt (72pt per inch, 96px per inch)
    $color = ImageColorPalette::allocateColor($dstImage, $options['color']);
    $anchor = $options['anchor'];
    $xOffsetOri = $options['xOffset'];
    $yOffsetOri = $options['yOffset'];
	$align_border = $options['align_border'];
    $angle = 0;

    // Calculate the bounding box dimensions
    //
    // Since imagettfbox() returns a bounding box from the text's baseline, we can end up with
    // different heights for different strings of the same font size. For example, 'type' will often
    // be taller than 'text' because the former has a descending letter.
    //
    // To compensate for this, we create two bounding boxes: one to measure the cap height and
    // another to measure the descender height. Based on that, we can adjust the text vertically
    // to appear inside the box with a reasonable amount of consistency.
    //
    // See: https://github.com/claviska/SimpleImage/issues/165
    //
    $box = imagettfbbox($size, $angle, $fontFile, mb_substr($text, 0, 1));//$text
    if(!$box) {
      throw new \Exception("Unable to load font file: $fontFile", ErrorCodeConstant::ERR_FONT_FILE);
    }
    $boxWidth = abs($box[6] - $box[2]);
    $boxHeight = $options['size'];

    // Determine cap height
    $box = imagettfbbox($size, $angle, $fontFile, 'X');
    $capHeight = abs($box[7] - $box[1]);

    // Determine descender height
    $box = imagettfbbox($size, $angle, $fontFile, 'X Qgjpqy');
    $fullHeight = abs($box[7] - $box[1]);
    $descenderHeight = $fullHeight - $capHeight;
	$dstWidth =$rect[2] -$rect[0];//ImageSizer::getWidth($dstImage);
	$dstHeight = $rect[3] -$rect[1];//ImageSizer::getHeight($dstImage);
	if($dstWidth <=0 || $dstHeight<=0){
      throw new \Exception("rect data error", ErrorCodeConstant::ERR_INVALID_COORDINATE);
	}
	$length= mb_strlen($text);
	$char = mb_substr($text, 0,1);
	$chars_height = round($length*$options['size']);
	if($dstHeight-$chars_height <0){
		throw new \Exception("the rect is too small for the whole words", ErrorCodeConstant::ERR_INVALID_COORDINATE);
	}
	$char_div = ($dstHeight-$chars_height)/($length+($align_border?-1:1));
	$xOffsetOri += $rect[0];
	$yOffsetOri += $rect[1];
	for($i=0; $i<$length; $i++){
	$xOffset = $xOffsetOri;
	$yOffset = $yOffsetOri +$boxHeight*(1+$i) +$char_div*(($align_border?0:1)+$i); 
    // Determine position
    switch($anchor) {
    case 'top left':
      $x = $xOffset;
      $y = $yOffset /*+ $boxHeight*/;
      break;
    case 'top right':
      $x = $dstWidth - $boxWidth + $xOffset;
      $y = $yOffset /*+ $boxHeight*/;
      break;
    case 'top':
	default: // top
      $x = ($dstWidth / 2) - ($boxWidth / 2) + $xOffset;
      $y = $yOffset /*+ $boxHeight*/;
      break;
    }

    $x = (int) round($x);
    $y = (int) round($y);

    // Pass the boundary back by reference
    $boundary = [
      'x1' => $x,
      'y1' => $y - $boxHeight, // $y is the baseline, not the top!
      'x2' => $x + $boxWidth,
      'y2' => $y,
      'width' => $boxWidth,
      'height' => $boxHeight
    ];

    // Text shadow
    if(is_array($options['shadow'])) {
      imagettftext(
        $dstImage,
        $size,
        $angle,
        $x + $options['shadow']['x'],
        $y + $options['shadow']['y'] - $descenderHeight,
        ImageColorPalette::allocateColor($dstImage, $options['shadow']['color']),
        $fontFile,
        $text
      );
    }

    // Draw the text
    imagettftext($dstImage, $size, $angle, $x, $y - $descenderHeight, $color, $fontFile, mb_substr($text, $i, 1));
    }
    return $dstImage;
  }

  //
  // Draws a single pixel dot.
  //
  //  $x* (int) - The x coordinate of the dot.
  //  $y* (int) - The y coordinate of the dot.
  //  $color* (string|array) - The dot color.
  //
  // Returns a SimpleImage object.
  //
  public static function dot($image, $x, $y, $color) {
    $color = ImageColorPalette::allocateColor($image, $color);
    imagesetpixel($image, $x, $y, $color);

    return $image;
  }
  
  public static function qrCode($dstImage, $text, $w, $h, $anchor = 'center', $errorCorrectionLevel='L', $matrixPointSize=4, $opacity = 1, $xOffset = 0, $yOffset = 0){
	$qrCodeImage = \QRcode::imageResource($text, $errorCorrectionLevel, $matrixPointSize,0);
	$qrCodeImage = ImageSizer::resize($qrCodeImage, $w, $h);
	return self::overlay($dstImage, $qrCodeImage, $anchor, $opacity, $xOffset, $yOffset);
  }
  
  //
  // Draws a line.
  //
  //  $x1* (int) - The x coordinate for the first point.
  //  $y1* (int) - The y coordinate for the first point.
  //  $x2* (int) - The x coordinate for the second point.
  //  $y2* (int) - The y coordinate for the second point.
  //  $color (string|array) - The line color.
  //  $thickness (int) - The line thickness (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function line($image, $x1, $y1, $x2, $y2, $color, $thickness = 1) {
    // Allocate the color
    $color = ImageColorPalette::allocateColor($image, $color);

    // Draw a line
    imagesetthickness($image, $thickness);
    imageline($image, $x1, $y1, $x2, $y2, $color);

    return $image;
  }
  
  //
  // Draws a polygon.
  //
  //  $vertices* (array) - The polygon's vertices in an array of x/y arrays. Example:
  //    [
  //      ['x' => x1, 'y' => y1],
  //      ['x' => x2, 'y' => y2],
  //      ['x' => xN, 'y' => yN]
  //    ]
  //  $color* (string|array) - The polygon color.
  //  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function polygon($image, $vertices, $color, $thickness = 1) {
    // Allocate the color
    $color = ImageColorPalette::allocateColor($image, $color);

    // Convert [['x' => x1, 'y' => x1], ['x' => x1, 'y' => y2], ...] to [x1, y1, x2, y2, ...]
    $points = [];
    foreach($vertices as $vals) {
      $points[] = $vals['x'];
      $points[] = $vals['y'];
    }

    // Draw a polygon
    if($thickness === 'filled') {
      imagesetthickness($image, 1);
      imagefilledpolygon($image, $points, count($vertices), $color);
    } else {
      imagesetthickness($image, $thickness);
      imagepolygon($image, $points, count($vertices), $color);
    }
    return $image;
  }

  //
  // Draws a rectangle.
  //
  //  $x1* (int) - The upper left x coordinate.
  //  $y1* (int) - The upper left y coordinate.
  //  $x2* (int) - The bottom right x coordinate.
  //  $y2* (int) - The bottom right y coordinate.
  //  $color* (string|array) - The rectangle color.
  //  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function rectangle($image, $x1, $y1, $x2, $y2, $color, $thickness = 1) {
    // Allocate the color
    $color = ImageColorPalette::allocateColor($image, $color);

    // Draw a rectangle
    if($thickness === 'filled') {
      imagesetthickness($image, 1);
      imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);
    } else {
      imagesetthickness($image, $thickness);
      imagerectangle($image, $x1, $y1, $x2, $y2, $color);
    }

    return $image;
  }
  
  //
  // Draws a border around the image.
  //
  //  $color* (string|array) - The border color.
  //  $thickness (int) - The thickness of the border (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function border($image, $color, $thickness = 1) {
    $x1 = 0;
    $y1 = 0;
    $x2 = ImageSizer::getWidth($image) - 1;
    $y2 = ImageSizer::getHeight($image) - 1;

    // Draw a border rectangle until it reaches the correct width
    for($i = 0; $i < $thickness; $i++) {
      self::rectangle($image, $x1++, $y1++, $x2--, $y2--, $color);
    }

    return $image;
  }
  
  //
  // Draws an arc.
  //
  //  $x* (int) - The x coordinate of the arc's center.
  //  $y* (int) - The y coordinate of the arc's center.
  //  $width* (int) - The width of the arc.
  //  $height* (int) - The height of the arc.
  //  $start* (int) - The start of the arc in degrees.
  //  $end* (int) - The end of the arc in degrees.
  //  $color* (string|array) - The arc color.
  //  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function arc($image, $x, $y, $width, $height, $start, $end, $color, $thickness = 1) {
    // Allocate the color
    $color = ImageColorPalette::allocateColor($image, $color);

    // Draw an arc
    if($thickness === 'filled') {
      imagesetthickness($image, 1);
      imagefilledarc($image, $x, $y, $width, $height, $start, $end, $color, IMG_ARC_PIE);
    } else {
      imagesetthickness($image, $thickness);
      imagearc($image, $x, $y, $width, $height, $start, $end, $color);
    }

    return $image;
  }
  
  //
  // Draws an ellipse.
  //
  //  $x* (int) - The x coordinate of the center.
  //  $y* (int) - The y coordinate of the center.
  //  $width* (int) - The ellipse width.
  //  $height* (int) - The ellipse height.
  //  $color* (string|array) - The ellipse color.
  //  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function ellipse($image, $x, $y, $width, $height, $color, $thickness = 1) {
    // Allocate the color
    $color = ImageColorPalette::allocateColor($image, $color);

    // Draw an ellipse
    if($thickness === 'filled') {
      imagesetthickness($image, 1);
      imagefilledellipse($image, $x, $y, $width, $height, $color);
    } else {
      // imagesetthickness doesn't appear to work with imageellipse, so we work around it.
      imagesetthickness($image, 1);
      $i = 0;
      while($i++ < $thickness * 2 - 1) {
        imageellipse($image, $x, $y, --$width, $height--, $color);
      }
    }

    return $image;
  }
  
  //
  // Draws a rounded rectangle.
  //
  //  $x1* (int) - The upper left x coordinate.
  //  $y1* (int) - The upper left y coordinate.
  //  $x2* (int) - The bottom right x coordinate.
  //  $y2* (int) - The bottom right y coordinate.
  //  $radius* (int) - The border radius in pixels.
  //  $color* (string|array) - The rectangle color.
  //  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function roundedRectangle($image, $x1, $y1, $x2, $y2, $radius, $color, $thickness = 1) {
    if($thickness === 'filled') {
      // Draw the filled rectangle without edges
      self::rectangle($image, $x1 + $radius + 1, $y1, $x2 - $radius - 1, $y2, $color, 'filled');
      self::rectangle($image, $x1, $y1 + $radius + 1, $x1 + $radius, $y2 - $radius - 1, $color, 'filled');
      self::rectangle($image, $x2 - $radius, $y1 + $radius + 1, $x2, $y2 - $radius - 1, $color, 'filled');
      // Fill in the edges with arcs
      self::arc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, 'filled');
      self::arc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, 'filled');
      self::arc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, 'filled');
      self::arc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 360, 90, $color, 'filled');
    } else {
      // Draw the rectangle outline without edges
      self::line($image, $x1 + $radius, $y1, $x2 - $radius, $y1, $color, $thickness);
      self::line($image, $x1 + $radius, $y2, $x2 - $radius, $y2, $color, $thickness);
      self::line($image, $x1, $y1 + $radius, $x1, $y2 - $radius, $color, $thickness);
      self::line($image, $x2, $y1 + $radius, $x2, $y2 - $radius, $color, $thickness);
      // Fill in the edges with arcs
      self::arc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, $thickness);
      self::arc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, $thickness);
      self::arc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, $thickness);
      self::arc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 360, 90, $color, $thickness);
    }

    return $image;
  }

  //
  // Fills the image with a solid color.
  //
  //  $color (string|array) - The fill color.
  //
  // Returns a SimpleImage object.
  //
  public function fill($image, $color) {
  	$width = getWidth();
  	$height = getHeight();
    // Draw a filled rectangle over the entire image
    self::rectangle($image, 0, 0, $width,  $height, 'white', 'filled');

    // Now flood it with the appropriate color
    $color = ImageColorPalette::allocateColor($image, $color);
    imagefill($image, 0, 0, $color);

    return $image;
  }        
    

  //
  // Ensures a numeric value is always within the min and max range.
  //
  //  $value* (int|float) - A numeric value to test.
  //  $min* (int|float) - The minimum allowed value.
  //  $max* (int|float) - The maximum allowed value.
  //
  // Returns an int|float value.
  //
  private static function keepWithin($value, $min, $max) {
    if($value < $min) return $min;
    if($value > $max) return $max;
    return $value;
  }
        
}
