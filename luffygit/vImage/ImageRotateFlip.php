<?php
//
// ImageRotateFlip
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

class ImageRotateFlip {
	
  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Orientation Rotate Flip
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Gets the image's current orientation.
  //
  // Returns a string: 'landscape', 'portrait', or 'square'
  //
  public static function getOrientation($image) {
    $width = ImageSizer::getWidth($image);
    $height = ImageSizer::getHeight($image);

    if($width > $height) return 'landscape';
    if($width < $height) return 'portrait';
    return 'square';
  }
  
  //
  // Rotates an image so the orientation will be correct based on its exif data. It is safe to call
  // this method on images that don't have exif data (no changes will be made).
  //
  // Returns a SimpleImage object.
  //
  public static function autoOrient($image, $exif) {
    if(!$exif || !isset($exif['Orientation'])){
      return $image;
    }

    switch($exif['Orientation']) {
    case 1: // Do nothing!
      break;
    case 2: // Flip horizontally
      $image = self::flip($image, 'x');
      break;
    case 3: // Rotate 180 degrees
      $image = self::rotate($image, 180);
      break;
    case 4: // Flip vertically
      $image = self::flip($image, 'y');
      break;
    case 5: // Rotate 90 degrees clockwise and flip vertically
      $image = self::flip($image, 'y');
	  $image = self::rotate($image,90);
      break;
    case 6: // Rotate 90 clockwise
      $image = self::rotate($image, 90);
      break;
    case 7: // Rotate 90 clockwise and flip horizontally
      $image = $self::flip($image, 'x');
      $image = self::rotate($image, 90);
      break;
    case 8: // Rotate 90 counterclockwise
      $image = $self::rotate($image, -90);
      break;
    }

    return $image;
  }
  
  //
  // Rotates the image.
  //
  // $angle* (int) - The angle of rotation (-360 - 360).
  // $backgroundColor (string|array) - The background color to use for the uncovered zone area
  //   after rotation (default 'transparent').
  //
  // Returns a SimpleImage object.
  //
  public static function rotate($image, $angle, $backgroundColor = 'transparent') {
    // Rotate the image on a canvas with the desired background color
    $backgroundColor = ImageColorPalette::allocateColor($image, $backgroundColor);

    $image = imagerotate(
      $image,
      -(self::keepWithin($angle, -360, 360)),
      $backgroundColor
    );

    return $image;
  }    

  //
  // Flip the image horizontally or vertically.
  //
  //  $direction* (string) - The direction to flip: x|y|both
  //
  // Returns a SimpleImage object.
  //
  public static function flip($image, $direction) {
    switch($direction) {
    case 'x':
      imageflip($image, IMG_FLIP_HORIZONTAL);
      break;
    case 'y':
      imageflip($image, IMG_FLIP_VERTICAL);
      break;
    case 'both':
      imageflip($image, IMG_FLIP_BOTH);
      break;
    }

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
