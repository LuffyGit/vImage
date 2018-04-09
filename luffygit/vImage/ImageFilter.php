<?php
//
// ImageFilter
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

class ImageFilter {
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// ImageFilter
	//////////////////////////////////////////////////////////////////////////////////////////////////
	
  //
  // Applies the brightness filter to brighten the image.
  //
  //  $percentage* (int) - Percentage to brighten the image (0 - 100).
  //
  // Returns a SimpleImage object.
  //
  public static function brighten($image, $percentage) {
    $percentage = self::keepWithin(255 * $percentage / 100, 0, 255);

    imagefilter($image, IMG_FILTER_BRIGHTNESS, $percentage);

    return $image;
  }
  
  //
  // Applies the brightness filter to darken the image.
  //
  //  $percentage* (int) - Percentage to darken the image (0 - 100).
  //
  // Returns a SimpleImage object.
  //
  public static function darken($image, $percentage) {
    $percentage = self::keepWithin(255 * $percentage / 100, 0, 255);

    imagefilter($image, IMG_FILTER_BRIGHTNESS, -$percentage);

    return $image;
  }

  //
  // Applies the colorize filter.
  //
  //  $color* (string|array) - The filter color.
  //
  // Returns a SimpleImage object.
  //
  public static function colorize($image, $color) {
    $color = self::normalizeColor($color);

    imagefilter(
      $image,
      IMG_FILTER_COLORIZE,
      $color['red'],
      $color['green'],
      $color['blue'],
      127 - ($color['alpha'] * 127)
    );

    return $image;
  }

  //
  // Applies the contrast filter.
  //
  //  $percentage* (int) - Percentage to adjust (-100 - 100).
  //
  // Returns a SimpleImage object.
  //
  public static function contrast($image, $percentage) {
    imagefilter($image, IMG_FILTER_CONTRAST, self::keepWithin($percentage, -100, 100));

    return $image;
  }

  //
  // Applies the desaturate (grayscale) filter.
  //
  // Returns a SimpleImage object.
  //
  public static function desaturate($image) {
    imagefilter($image, IMG_FILTER_GRAYSCALE);

    return $image;
  }

  //
  // Applies the edge detect filter.
  //
  // Returns a SimpleImage object.
  //
  public static function edgeDetect($image) {
    imagefilter($image, IMG_FILTER_EDGEDETECT);

    return $image;
  }

  //
  // Applies the blur filter.
  //
  //  $type (string) - The blur algorithm to use: 'selective', 'gaussian' (default 'gaussian').
  //  $passes (int) - The number of time to apply the filter, enhancing the effect (default 1).
  //
  // Returns a SimpleImage object.
  //
  public static function blur($image, $type = 'selective', $passes = 1) {
    $filter = $type === 'gaussian' ? IMG_FILTER_GAUSSIAN_BLUR : IMG_FILTER_SELECTIVE_BLUR;

    for($i = 0; $i < $passes; $i++) {
      imagefilter($image, $filter);
    }

    return $image;
  }

  //
  // Applies the emboss filter.
  //
  // Returns a SimpleImage object.
  //
  public static function emboss($image) {
    imagefilter($image, IMG_FILTER_EMBOSS);

    return $image;
  }

  //
  // Inverts the image's colors.
  //
  // Returns a SimpleImage object.
  //
  public static function invert($image) {
    imagefilter($image, IMG_FILTER_NEGATE);

    return $image;
  }

  //
  // Applies the pixelate filter.
  //
  //  $size (int) - The size of the blocks in pixels (default 10).
  //
  // Returns a SimpleImage object.
  //
  public static function pixelate($image, $size = 10) {
    imagefilter($image, IMG_FILTER_PIXELATE, $size, true);

    return $image;
  }

  //
  // Simulates a sepia effect by desaturating the image and applying a sepia tone.
  //
  // Returns a SimpleImage object.
  //
  public static function sepia($image) {
    imagefilter($image, IMG_FILTER_GRAYSCALE);
    imagefilter($image, IMG_FILTER_COLORIZE, 70, 35, 0);

    return $image;
  }

  //
  // Sharpens the image.
  //
  // Returns a SimpleImage object.
  //
  public static function sharpen($image) {
    $sharpen = [
      [0, -1, 0],
      [-1, 5, -1],
      [0, -1, 0]
    ];
    $divisor = array_sum(array_map('array_sum', $sharpen));

    imageconvolution($image, $sharpen, $divisor, 0);

    return $image;
  }
  
  //
  // smooth filter----------with no effect???
  //
  //  $level (int) - The level of the smooth filter (default 10).
  //
  // Returns a SimpleImage object.
  //
  public static function smooth($image,$level=10) {
    imagefilter($image, IMG_FILTER_SMOOTH, 70);

    return $image;
  }
  
  //
  // Applies the mean remove filter to produce a sketch effect.
  //
  // Returns a SimpleImage object.
  //
  public static function sketch($image) {
    imagefilter($image, IMG_FILTER_MEAN_REMOVAL);

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
