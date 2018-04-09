<?php
//
// vImage
//
//  A PHP GD toolkit reordered  from "SimpleImage"->
//	Developed and maintained by Vanch <https://github.com/LuffyGit>.
//  
//===========================================================================
//  SimpleImage
//  A PHP class that makes working with images as simple as possible.
//
//  Developed and maintained by Cory LaViska <https://github.com/claviska>.
//
//  Copyright A Beautiful Site, LLC.
//
//  Source: https://github.com/claviska/vImage
//
//  Licensed under the MIT license <http://opensource.org/licenses/MIT>
//

require_once __DIR__ .'\vImage\ErrorCodeConstant.php';
require_once __DIR__ .'\vImage\ColorUtils.php';
require_once __DIR__ .'\vImage\vImageConfig.php';
require_once __DIR__ .'\vImage\ImageFrom.php';
require_once __DIR__ .'\vImage\ImageSizer.php';
require_once __DIR__ .'\vImage\ImageRotateFlip.php';
require_once __DIR__ .'\vImage\ImageColorPalette.php';
require_once __DIR__ .'\vImage\ImagePaintBrush.php';
require_once __DIR__ .'\vImage\ImageFilter.php';
require_once __DIR__ .'\vImage\ImageTo.php';

class vImage {
	protected $image, $mimeType, $exif;

	// Creates a new ImageCreator object.
	//
	//  $image (string) - An image file or a data URI to load.
	//
	public function __construct($imageFileUri = null) {
		// Check for the required GD extension
		if (extension_loaded('gd')) {
			// Ignore JPEG warnings that cause imagecreatefromjpeg() to fail
			ini_set('gd.jpeg_ignore_warning', 1);
		} else {
			throw new \Exception('Required extension GD is not loaded.', ErrorCodeConstant::ERR_GD_NOT_ENABLED);
		}

		// Load an image through the constructor
		if (preg_match('/^data:(.*?);/', $imageFileUri)) {
			$imageFrom = ImageFrom::fromDataUri($imageFileUri);
			$this ->image = $imageFrom['image'];
			$this ->mimeType = $imageFrom['mimeType'];
			$this ->exif = $imageFrom['exif'];
		} elseif ($imageFileUri) {
			$imageFrom = ImageFrom::fromFile($imageFileUri);
			$this ->image = $imageFrom['image'];
			$this ->mimeType = $imageFrom['mimeType'];
			$this ->exif = $imageFrom['exif'];
		}
	}

	//
	// Destroys the image resource
	//
	public function __destruct() {
		if ($this -> image !== null && get_resource_type($this -> image) === 'gd') {
			imagedestroy($this -> image);
		}
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	// Loaders
	//////////////////////////////////////////////////////////////////////////////////////////////////

	//
	// Loads an image from a data URI.
	//
	//  $uri* (string) - A data URI.
	//
	// Returns a vImage object.
	//
	public function fromDataUri($uri) {
		$imageFrom = ImageFrom::fromDataUri($uri);
		$this ->image = $imageFrom['image'];
		$this ->mimeType = $imageFrom['mimeType'];
		$this ->exif = $imageFrom['exif'];
		return $this;
	}

	//
	// Loads an image from a file.
	//
	//  $file* (string) - The image file to load.
	//
	// Returns a vImage object.
	//
	public function fromFile($file) {
		$imageFrom = ImageFrom::fromFile($file);
		$this ->image = $imageFrom['image'];
		$this ->mimeType = $imageFrom['mimeType'];
		$this ->exif = $imageFrom['exif'];
		return $this;
	}

	//
	// Creates a new image.
	//
	//  $width* (int) - The width of the image.
	//  $height* (int) - The height of the image.
	//  $color (string|array) - Optional fill color for the new image (default 'transparent').
	//
	// Returns a vImage object.
	//
	public function fromNew($width, $height, $color = 'transparent') {
		$imageFrom = ImageFrom::fromNew($width, $height, $color);
		$this ->image = $imageFrom['image'];
		$this ->mimeType = $imageFrom['mimeType'];
		$this ->exif = $imageFrom['exif'];
		return $this;
	}

	//
	// Creates a new image from a string.
	//
	//  $string* (string) - The raw image data as a string. Example:
	//
	//    $string = file_get_contents('image.jpg');
	//
	// Returns a vImage object.
	//
	public function fromString($string) {
		$imageFrom = ImageFrom::fromString($string);
		$this ->image = $imageFrom['image'];
		$this ->mimeType = $imageFrom['mimeType'];
		$this ->exif = $imageFrom['exif'];
		return $this;
	}
	
  //
  // Gets the mime type of the loaded image.
  //
  // Returns a mime type string.
  //
  public function getMimeType() {
    return $this->mimeType;
  }

  //
  // Gets the image's exif data.
  //
  // Returns an array of exif data or null if no data is available.
  //
  public function getExif() {
    return isset($this->exif) ? $this->exif : null;
  }
  
  //////////////////////////////////////////////////////////////////////////////////////////////////
  // ImageSizer
  //////////////////////////////////////////////////////////////////////////////////////////////////  

  //
  // Gets the image's current width.
  //
  // Returns the width as an integer.
  //
  public function getWidth() {
    return ImageSizer::getWidth($this->image);
  }
  
  //
  // Gets the image's current height.
  //
  // Returns the height as an integer.
  //
  public function getHeight() {
    return ImageSizer::getHeight($this->image);
  }  
  
  //
  // Gets the image's current aspect ratio.
  //
  // Returns the aspect ratio as a float.
  //
  public function getAspectRatio() {
    return ImageSizer::getAspectRatio($this->image);
  }

  //
  // Resize an image to the specified dimensions. If only one dimension is specified, the image will
  // be resized proportionally.
  //
  //  $width* (int) - The new image width.
  //  $height* (int) - The new image height.
  //
  // Returns a vImage object.
  //
  public function resize($width = null, $height = null) {
  	$this->image = ImageSizer::resize($this->image,$width,$height);
	return $this;
  }

  //
  // Proportionally resize the image to fit inside a specific width and height.
  //
  //  $maxWidth* (int) - The maximum width the image can be.
  //  $maxHeight* (int) - The maximum height the image can be.
  //
  // Returns a vImage object.
  //
  public function bestFit($maxWidth, $maxHeight) {
  	$this->image = ImageSizer::bestFit($this->image,$maxWidth,$maxHeight);
	return $this;
  }
  
  //
  // Proportionally resize the image to a specific width.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize($width, null)` instead.
  //
  //  $width* (int) - The width to resize the image to.
  //
  // Returns a vImage object.
  //
  public function fitToWidth($width) {
    $this->image = ImageSizer::fitToWidth($this->image,$width);
	return $this;
  }

  //
  // Proportionally resize the image to a specific height.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize(null, $height)` instead.
  //
  //  $height* (int) - The height to resize the image to.
  //
  // Returns a vImage object.
  //
  public function fitToHeight($height) {
    $this->image = ImageSizer::fitToHeight($this->image,$height);
	return $this;
  }

  //
  // Crop the image.
  //
  //  $x1 - Top left x coordinate.
  //  $y1 - Top left y coordinate.
  //  $x2 - Bottom right x coordinate.
  //  $y2 - Bottom right x coordinate.
  //
  // Returns a vImage object.
  //
  public function crop($x1, $y1, $x2, $y2) {
  	$this->image = ImageSizer::crop($this->image,$x1, $y1, $x2, $y2);
  	return $this;
  }
  
  //
  // Creates a thumbnail image. This function attempts to get the image as close to the provided
  // dimensions as possible, then crops the remaining overflow to force the desired size. Useful
  // for generating thumbnail images.
  //
  //  $width* (int) - The thumbnail width.
  //  $height* (int) - The thumbnail height.
  //  $anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right', 'top left',
  //    'top right', 'bottom left', 'bottom right' (default 'center').
  //
  // Returns a vImage object.
  //
  public function thumbnail($width, $height, $anchor = 'center') {
  	$this->image = ImageSizer::thumbnail($this->image, $width, $height, $anchor);
	return $this;
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // ImageRotateFlip
  //////////////////////////////////////////////////////////////////////////////////////////////////
  
  //
  // Gets the image's current orientation.
  //
  // Returns a string: 'landscape', 'portrait', or 'square'
  //
  public function getOrientation() {
  	return ImageRotateFlip::getOrientation($this->image);
  }

  //
  // Rotates an image so the orientation will be correct based on its exif data. It is safe to call
  // this method on images that don't have exif data (no changes will be made).
  //
  // Returns a vImage object.
  //
  public function autoOrient() {
  	$this->image = ImageRotateFlip::autoOrient($this->image,$this->exif);
	return $this;
  }

  //
  // Rotates the image.
  //
  // $angle* (int) - The angle of rotation (-360 - 360).
  // $backgroundColor (string|array) - The background color to use for the uncovered zone area
  //   after rotation (default 'transparent').
  //
  // Returns a vImage object.
  //
  public function rotate($angle, $backgroundColor = 'transparent') {
  	$this->image = ImageRotateFlip::rotate($this->image,$angle, $backgroundColor);
	return $this;
  } 
  
  //
  // Flip the image horizontally or vertically.
  //
  //  $direction* (string) - The direction to flip: x|y|both
  //
  // Returns a vImage object.
  //
  public function flip($direction) {
  	ImageRotateFlip::flip($this->image,$direction);
	return $this;
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // ImageColorPalette
  //////////////////////////////////////////////////////////////////////////////////////////////////
  
  //
  // Gets the RGBA value of a single pixel.
  //
  //  $x* (int) - The horizontal position of the pixel.
  //  $y* (int) - The vertical position of the pixel.
  //
  // Returns an RGBA color array or false if the x/y position is off the canvas.
  //
  public function getColorAt($x, $y) {
  	return ImageColorPalette::getColorAt($this->image, $x, $y);
  }

  //
  // Converts a "friendly color" into a color identifier for use with GD's image functions.
  //
  //  $image (resource) - The target image.
  //  $color (string|array) - The color to allocate.
  //
  // Returns a color identifier.
  //
  public function allocateColor($color) {
  	return ImageColorPalette::allocateColor($this->image, $color);
  }  

  //
  // Changes the image's opacity level.
  //
  //  $opacity* (float) - The desired opacity level (0 - 1).
  //
  // Returns a vImage object.
  //
  public function opacity($opacity) {
  	$this->image = ImageColorPalette::opacity($this->image, $opacity);
	return $this;
  }

  //
  // Applies a duotone filter to the image.
  //
  //  $lightColor* (string|array) - The lightest color in the duotone.
  //  $darkColor* (string|array) - The darkest color in the duotone.
  //
  // Returns a vImage object.
  //
  function duotone($lightColor, $darkColor) {
  	ImageColorPalette::duotone($this->image, $lightColor, $darkColor);
	return $this;
  }


  //
  // Reduces the image to a maximum number of colors.
  //
  //  $max* (int) - The maximum number of colors to use.
  //  $dither (bool) - Whether or not to use a dithering effect (default true).
  //
  // Returns a vImage object.
  //
  public function maxColors($max, $dither = true) {
  	ImageColorPalette::maxColors($this->image, $max, $dither);
	return $this;
  }

  //
  // Same as PHP's imagecopymerge, but works with transparent images. Used internally for overlay.
  //
  public static function imageCopyMergeAlpha($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct) {
  	return ImagePaintBrush::imageCopyMergeAlpha($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct);
  }

  //
  // Place an image on top of the current image.
  //
  //  $overlay* (string|vImage) - The image to overlay. This can be a filename, a data URI, or
  //    a vImage object.
  //  $anchor (string) - The anchor point: 'center', 'top', 'bottom', 'left', 'right', 'top left',
  //    'top right', 'bottom left', 'bottom right' (default 'center')
  //  $opacity (float) - The opacity level of the overlay 0-1 (default 1).
  //  $xOffset (int) - Horizontal offset in pixels (default 0).
  //  $yOffset (int) - Vertical offset in pixels (default 0).
  //
  // Returns a vImage object.
  //
  public function overlay($overlay, $anchor = 'center', $opacity = 1, $xOffset = 0, $yOffset = 0) {
  	$srcImage = $overlay;
  	if($overlay instanceof vImage){
  		$srcImage = $overlay->image;
  	}
  	ImagePaintBrush::overlay($this->image, $srcImage, $anchor, $opacity, $xOffset, $yOffset);
	return $this;
  }
  
  public function qrCode($text, $w, $h, $anchor = 'center', $errorCorrectionLevel='L', $matrixPointSize=4, $opacity = 1, $xOffset = 0, $yOffset = 0){
  	ImagePaintBrush::qrCode($this->image, $text, $w, $h, $anchor, $errorCorrectionLevel, $matrixPointSize, $opacity, $xOffset, $yOffset);
	return $this;
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
  // Returns a vImage object.
  //
  public function text($text, $options, &$boundary = null) {
  	ImagePaintBrush::text($this->image, $text, $options,$boundary);
	return $this;
  }

  //
  // Adds text to the image in a specified rect.
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
  // Returns a vImage object.
  //
  public function textInRect($text, $rect, $options, &$boundary = null) {
  	ImagePaintBrush::textInRect($this->image, $rect, $text, $options,$boundary);
	return $this;
  }

  //
  // Adds text to the image in a specified rect.
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
  // Returns a vImage object.
  //
  public function textInVerticalRectWithDiv($text, $rect, $options, &$boundary = null) {
  	ImagePaintBrush::textInVerticalRectWithDiv($this->image, $text, $rect, $options,$boundary);
	return $this;
  }
    
  //
  // Draws a single pixel dot.
  //
  //  $x* (int) - The x coordinate of the dot.
  //  $y* (int) - The y coordinate of the dot.
  //  $color* (string|array) - The dot color.
  //
  // Returns a vImage object.
  //
  public function dot($x, $y, $color) {
  	ImagePaintBrush::dot($this->image, $x, $y, $color);
	return $this;
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
  // Returns a vImage object.
  //
  public function line($x1, $y1, $x2, $y2, $color, $thickness = 1) {
  	ImagePaintBrush::line($this->image, $x1, $y1, $x2, $y2, $color, $thickness);
	return $this;
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
  // Returns a vImage object.
  //
  public function polygon($vertices, $color, $thickness = 1) {
  	ImagePaintBrush::polygon($this->image, $vertices, $color, $thickness);
	return $this;
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
  // Returns a vImage object.
  //
  public function rectangle($x1, $y1, $x2, $y2, $color, $thickness = 1) {
  	ImagePaintBrush::rectangle($this->image, $x1, $y1, $x2, $y2, $color, $thickness);
	return $this;
  }
  
  //
  // Draws a border around the image.
  //
  //  $color* (string|array) - The border color.
  //  $thickness (int) - The thickness of the border (default 1).
  //
  // Returns a vImage object.
  //
  public function border($color, $thickness = 1) {
  	ImagePaintBrush::border($this->image, $color, $thickness);
	return $this;
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
  // Returns a vImage object.
  //
  public function arc($x, $y, $width, $height, $start, $end, $color, $thickness = 1) {
  	ImagePaintBrush::arc($this->image, $x, $y, $width, $height, $start, $end, $color, $thickness);
	return $this;
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
  // Returns a vImage object.
  //
  public function ellipse($x, $y, $width, $height, $color, $thickness = 1) {
  	ImagePaintBrush::ellipse($this->image, $x, $y, $width, $height, $color, $thickness);
	return $this;
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
  // Returns a vImage object.
  //
  public function roundedRectangle($x1, $y1, $x2, $y2, $radius, $color, $thickness = 1) {
  	ImagePaintBrush::roundedRectangle($this->image, $x1, $y1, $x2, $y2, $radius, $color, $thickness);
	return $this;
  }

  //
  // Fills the image with a solid color.
  //
  //  $color (string|array) - The fill color.
  //
  // Returns a vImage object.
  //
  public function fill($color) {
  	ImagePaintBrush::fill($this->image, $color);
	return $this;
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // ImageFilter
  //////////////////////////////////////////////////////////////////////////////////////////////////
  
  //
  // Applies the brightness filter to brighten the image.
  //
  //  $percentage* (int) - Percentage to brighten the image (0 - 100).
  //
  // Returns a vImage object.
  //
  public function brighten($percentage) {
  	ImageFilter::brighten($this->image, $percentage);
	return $this;
  }

  //
  // Applies the brightness filter to darken the image.
  //
  //  $percentage* (int) - Percentage to darken the image (0 - 100).
  //
  // Returns a vImage object.
  //
  public function darken($percentage) {
  	ImageFilter::darken($this->image, $percentage);
	return $this;
  }

  //
  // Applies the colorize filter.
  //
  //  $color* (string|array) - The filter color.
  //
  // Returns a vImage object.
  //
  public function colorize($color) {
  	ImageFilter::colorize($this->image, $color);
	return $this;
  }
  
  //
  // Applies the contrast filter.
  //
  //  $percentage* (int) - Percentage to adjust (-100 - 100).
  //
  // Returns a vImage object.
  //
  public function contrast($percentage) {
  	ImageFilter::contrast($this->image, $percentage);
	return $this;
  }  

  //
  // Applies the desaturate (grayscale) filter.
  //
  // Returns a vImage object.
  //
  public function desaturate() {
  	ImageFilter::desaturate($this->image);
	return $this;
  }

  //
  // Applies the edge detect filter.
  //
  // Returns a vImage object.
  //
  public function edgeDetect() {
  	ImageFilter::edgeDetect($this->image);
	return $this;
  }

  //
  // Applies the blur filter.
  //
  //  $type (string) - The blur algorithm to use: 'selective', 'gaussian' (default 'gaussian').
  //  $passes (int) - The number of time to apply the filter, enhancing the effect (default 1).
  //
  // Returns a vImage object.
  //
  public function blur($type = 'selective', $passes = 1) {
  	$this->image = ImageFilter::blur($this->image, $type, $passes);
	return $this;
  }
  
  //
  // Applies the emboss filter.
  //
  // Returns a vImage object.
  //
  public function emboss() {
  	$this->image = ImageFilter::emboss($this->image);
	return $this;
  }

  //
  // Inverts the image's colors.
  //
  // Returns a vImage object.
  //
  public function invert() {
  	$this->image = ImageFilter::invert($this->image);
	return $this;
  }
  
  //
  // Applies the pixelate filter.
  //
  //  $size (int) - The size of the blocks in pixels (default 10).
  //
  // Returns a vImage object.
  //
  public function pixelate($size = 10) {
  	$this->image = ImageFilter::pixelate($this->image,$size);
	return $this;
  }

  //
  // Simulates a sepia effect by desaturating the image and applying a sepia tone.
  //
  // Returns a vImage object.
  //
  public function sepia() {
  	$this->image = ImageFilter::sepia($this->image);
	return $this;
  }

  //
  // Sharpens the image.
  //
  // Returns a vImage object.
  //
  public function sharpen() {
  	$this->image=ImageFilter::sharpen($this->image);
	return $this;
  }

  //
  // smooth filter----------with no effect???
  //
  //  $level (int) - The level of the smooth filter (default 10).
  //
  // Returns a vImage object.
  //
  public function smooth($level=10) {
  	$this->image = ImageFilter::smooth($this->image,$level);
	return $this;
  }
  
  //
  // Applies the mean remove filter to produce a sketch effect.
  //
  // Returns a vImage object.
  //
  public function sketch() {
  	$this->image = ImageFilter::sketch($this->image);
	return $this;
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Savers
  //////////////////////////////////////////////////////////////////////////////////////////////////


  //
  // Generates a data URI.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a string containing a data URI.
  //
  public function toDataUri($mimeType = null, $quality = 100) {
  	$mimeType = $mimeType?:$this->mineType;
  	return ImageTo::toDataUri($this->image, $mimeType, $quality);
  }
  
  //
  // Forces the image to be downloaded to the clients machine. Must be called before any output is
  // sent to the screen.
  //
  //  $filename* (string) - The filename (without path) to send to the client (e.g. 'image.jpeg').
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  public function toDownload($filename, $mimeType = null, $quality = 100) {
  	$mimeType = $mimeType?:$this->mineType;
  	ImageTo::toDownload($this->image, $filename, $mimeType, $quality);
	return $this;
  }

  //
  // Writes the image to a file.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a vImage object.
  //
  public function toFile($file, $mimeType = null, $quality = 100) {
  	$mimeType = $mimeType?:$this->mineType;
  	ImageTo::toFile($this->image, $file, $mimeType, $quality);
	return $this;
  }
  
  //
  // Outputs the image to the screen. Must be called before any output is sent to the screen.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a vImage object.
  //
  public function toScreen($mimeType = null, $quality = 100) {
  	$mimeType = $mimeType?:$this->mineType;
  	ImageTo::toScreen($this->image, $mimeType, $quality);
  	return $this;
  }
  
  //
  // Generates an image string.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a vImage object.
  //
  public function toString($mimeType = null, $quality = 100) {
  	$mimeType = $mimeType?:$this->mineType;
    return ImageTo::toString($this->image, $mimeType, $quality);
  }
  
  public function verifyCode($text, $width, $height, $bg_color='white', $options=array(), $noisycolor='black'){
  	$linenum=3;
  	$dotnum=100;
  	$vImage = $this->fromNew($width, $height, $bg_color);
	$defaultOptions = [
      'fontFile' => __DIR__.'/../assets/font/ARIAL.TTF',
      'size' =>$height/2,
      'color' => 'black',
      'anchor' => 'center', //锚点
      'xOffset' => 0,
      'yOffset' => 0
    ];
	$options = array_merge($defaultOptions, $options);
	$vImage->text($text, $options);
	for($i=0;$i<$linenum;$i++){
		$startx = rand(0,$width);
		$starty = rand(0,$height);
		$endx = rand($startx, $width);
		$endy = rand($starty, $height);
		$vImage->line($startx,$starty,$endx,$endy,$noisycolor);
	}
	for($i=0;$i<$dotnum;$i++){
		$dotx = rand(0,$width);
		$doty = rand(0,$height);
		$vImage->dot($dotx,$doty,$noisycolor);
	}
	return $vImage;
  }
}
