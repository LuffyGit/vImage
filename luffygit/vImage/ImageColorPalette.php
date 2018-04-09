<?php
//
// ImageColorPalette
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

class ImageColorPalette {
	
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
  public static function getColorAt($image, $x, $y) {
    // Coordinates must be on the canvas
    if($x < 0 || $x > ImageSizer::getWidth($image) || $y < 0 || $y > ImageSizer::getHeight($image)) {
      return false;
    }

    // Get the color of this pixel and convert it to RGBA
    $color = imagecolorat($image, $x, $y);
    $rgba = imagecolorsforindex($image, $color);
    $rgba['alpha'] = 127 - ($color >> 24) & 0xFF;

    return $rgba;
  }
  
  //
  // Converts a "friendly color" into a color identifier for use with GD's image functions.
  //
  //  $image (resource) - The target image.
  //  $color (string|array) - The color to allocate.
  //
  // Returns a color identifier.
  //
  public static function allocateColor($image, $color) {
    $color = ColorUtils::normalizeColor($color);

    // Was this color already allocated?
    $index = imagecolorexactalpha(
      $image,
      $color['red'],
      $color['green'],
      $color['blue'],
      127 - ($color['alpha'] * 127)
    );
    if($index > -1) {
      // Yes, return this color index
      return $index;
    }

    // Allocate a new color index
    return imagecolorallocatealpha(
      $image,
      $color['red'],
      $color['green'],
      $color['blue'],
      127 - ($color['alpha'] * 127)
    );
  }

  //
  // Same as PHP's imagecopymerge, but works with transparent images. Used internally for overlay.
  //
  private static function imageCopyMergeAlpha($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct) {
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
  // Changes the image's opacity level.
  //
  //  $opacity* (float) - The desired opacity level (0 - 1).
  //
  // Returns a SimpleImage object.
  //
  public static function opacity($image, $opacity) {
    // Create a transparent image
    $ImageFrom = ImageFrom::fromNew(ImageSizer::getWidth($image), ImageSizer::getHeight($image));
	$newImage = $ImageFrom['image'];
    // Copy the current image (with opacity) onto the transparent image
    self::imageCopyMergeAlpha(
      $newImage,
      $image,
      0, 0,
      0, 0,
      ImageSizer::getWidth($image),
      ImageSizer::getHeight($image),
      self::keepWithin($opacity, 0, 1) * 100
    );
	@imagedestroy($image);
    return $newImage;
  }

  //
  // Applies a duotone filter to the image.
  //
  //  $lightColor* (string|array) - The lightest color in the duotone.
  //  $darkColor* (string|array) - The darkest color in the duotone.
  //
  // Returns a SimpleImage object.
  //
  public static function duotone($image, $lightColor, $darkColor) {
    $lightColor = ColorUtils::normalizeColor($lightColor);
    $darkColor = ColorUtils::normalizeColor($darkColor);

    // Calculate averages between light and dark colors
    $redAvg = $lightColor['red'] - $darkColor['red'];
    $greenAvg = $lightColor['green'] - $darkColor['green'];
    $blueAvg = $lightColor['blue'] - $darkColor['blue'];

    // Create a matrix of all possible duotone colors based on gray values
    $pixels = [];
    for($i = 0; $i <= 255; $i++) {
      $grayAvg = $i / 255;
      $pixels['red'][$i] = $darkColor['red'] + $grayAvg * $redAvg;
      $pixels['green'][$i] = $darkColor['green'] + $grayAvg * $greenAvg;
      $pixels['blue'][$i] = $darkColor['blue'] + $grayAvg * $blueAvg;
    }

    // Apply the filter pixel by pixel
    $width = ImageSizer::getWidth($image);
	$height = ImageSizer::getHeight($image);
    for($x = 0; $x < $width; $x++) {
      for($y = 0; $y < $height; $y++) {
        $rgb = self::getColorAt($image, $x, $y);
        $gray = min(255, round(0.299 * $rgb['red'] + 0.114 * $rgb['blue'] + 0.587 * $rgb['green']));
        ImagePaintBrush::dot($image, $x, $y, [
          'red' => $pixels['red'][$gray],
          'green' => $pixels['green'][$gray],
          'blue' => $pixels['blue'][$gray]
        ]);
      }
    }

    return $image;
  }

  //
  // Reduces the image to a maximum number of colors.
  //
  //  $max* (int) - The maximum number of colors to use.
  //  $dither (bool) - Whether or not to use a dithering effect (default true).
  //
  // Returns a SimpleImage object.
  //
  public static function maxColors($image, $max, $dither = true) {
    imagetruecolortopalette($image, $dither, max(1, $max));

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
