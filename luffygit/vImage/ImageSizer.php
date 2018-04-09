<?php
//
// ImageSizer
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

class ImageSizer {
	
  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Sizer
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Gets the image's current width.
  //
  // Returns the width as an integer.
  //
  public static function getWidth($image) {
    return (int) imagesx($image);
  }
  
  //
  // Gets the image's current width.
  //
  // Returns the width as an integer.
  //
  public static function getHeight($image) {
    return (int) imagesy($image);
  }
  
  //
  // Gets the image's current aspect ratio.
  //
  // Returns the aspect ratio as a float.
  //
  public static function getAspectRatio($image) {
    return self::getWidth($image) / self::getHeight($image);
  }
  
  //
  // Resize an image to the specified dimensions. If only one dimension is specified, the image will
  // be resized proportionally.
  //
  //  $width* (int) - The new image width.
  //  $height* (int) - The new image height.
  //
  // Returns a SimpleImage object.
  //
  public static function resize($image, $width = null, $height = null) {
    // No dimentions specified
    if(!$width && !$height) {
      return $image;
    }

    // Resize to width
    if($width && !$height) {
      $height = $width / self::getAspectRatio($image);
    }

    // Resize to height
    if(!$width && $height) {
      $width = $height * self::getAspectRatio($image);
    }

    // If the dimensions are the same, there's no need to resize
    if(self::getWidth($image) === $width && self::getHeight($image) === $height) {
      return $image;
    }

    // We can't use imagescale because it doesn't seem to preserve transparency properly. The
    // workaround is to create a new truecolor image, allocate a transparent color, and copy the
    // image over to it using imagecopyresampled.
    $newImage = imagecreatetruecolor($width, $height);
    $transparentColor = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
    imagecolortransparent($newImage, $transparentColor);
    imagefill($newImage, 0, 0, $transparentColor);
    imagecopyresampled(
      $newImage,
      $image,
      0, 0, 0, 0,
      $width,
      $height,
      self::getWidth($image),
      self::getHeight($image)
    );
	@imagedestroy($image);
    return $newImage;
  }
  
    //
  // Proportionally resize the image to fit inside a specific width and height.
  //
  //  $maxWidth* (int) - The maximum width the image can be.
  //  $maxHeight* (int) - The maximum height the image can be.
  //
  // Returns a SimpleImage object.
  //
  public static function bestFit($image, $maxWidth, $maxHeight) {
    // If the image already fits, there's nothing to do
    if(self::getWidth($image) <= $maxWidth && self::getHeight($image) <= $maxHeight) {
      return $image;
    }

    // Calculate max width or height based on orientation
    if(ImageRotateFlip::getOrientation($image) === 'portrait') {
      $height = $maxHeight;
      $width = $maxHeight * self::getAspectRatio($image);
    } else {
      $width = $maxWidth;
      $height = $maxWidth / self::getAspectRatio($image);
    }

    // Reduce to max width
    if($width > $maxWidth) {
      $width = $maxWidth;
      $height = $width / self::getAspectRatio($image);
    }

    // Reduce to max height
    if($height > $maxHeight) {
      $height = $maxHeight;
      $width = $height * self::getAspectRatio($image);
    }

    return self::resize($image, $width, $height);
  }

  //
  // Proportionally resize the image to a specific width.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize($width, null)` instead.
  //
  //  $width* (int) - The width to resize the image to.
  //
  // Returns a SimpleImage object.
  //
  public static function fitToWidth($image, $width) {
    return self::resize($image, $width, null);
  }  
  
  //
  // Proportionally resize the image to a specific height.
  //
  // **DEPRECATED:** This method was deprecated in version 3.2.2 and will be removed in version 4.0.
  // Please use `resize(null, $height)` instead.
  //
  //  $height* (int) - The height to resize the image to.
  //
  // Returns a SimpleImage object.
  //
  public static function fitToHeight($image, $height) {
    return self::resize(null, $height);
  }

  //
  // Crop the image.
  //
  //  $x1 - Top left x coordinate.
  //  $y1 - Top left y coordinate.
  //  $x2 - Bottom right x coordinate.
  //  $y2 - Bottom right x coordinate.
  //
  // Returns a SimpleImage object.
  //
  public static function crop($image, $x1, $y1, $x2, $y2) {
  	$width = self::getWidth($image);
	$height = self::getHeight($image);
    // Keep crop within image dimensions
    $x1 = self::keepWithin($x1, 0, $width);
    $x2 = self::keepWithin($x2, 0, $width);
    $y1 = self::keepWithin($y1, 0, $height);
    $y2 = self::keepWithin($y2, 0, $height);

    // Crop it
    $newImage = imagecrop($image, [
      'x' => min($x1, $x2),
      'y' => min($y1, $y2),
      'width' => abs($x2 - $x1),
      'height' => abs($y2 - $y1)
    ]);
	@imagedestroy($image);
    return $newImage;
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
  // Returns a SimpleImage object.
  //
  public static function thumbnail($image, $width, $height, $anchor = 'center') {
  	$curWidth = self::getWidth($image);
  	$curHeight = self::getHeight($image);
    // Determine aspect ratios
    $currentRatio =  $curHeight/ $curWidth;
    $targetRatio = $height / $width;

    // Fit to height/width
    if($targetRatio > $currentRatio) {
      $resizeImage = self::resize($image, null, $height);
    } else {
      $resizeImage = self::resize($image, $width, null);
    }
	
	$curWidth = self::getWidth($resizeImage);
	$curHeight = self::getHeight($resizeImage);
    switch($anchor) {
    case 'top':
      $x1 = floor(($curWidth / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = 0;
      $y2 = $height;
      break;
    case 'bottom':
      $x1 = floor(($curWidth / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = $curHeight - $height;
      $y2 = $curHeight;
      break;
    case 'left':
      $x1 = 0;
      $x2 = $width;
      $y1 = floor(($curHeight / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    case 'right':
      $x1 = $curWidth - $width;
      $x2 = $curWidth;
      $y1 = floor(($curHeight / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    case 'top left':
      $x1 = 0;
      $x2 = $width;
      $y1 = 0;
      $y2 = $height;
      break;
    case 'top right':
      $x1 = $curWidth - $width;
      $x2 = $curWidth;
      $y1 = 0;
      $y2 = $height;
      break;
    case 'bottom left':
      $x1 = 0;
      $x2 = $width;
      $y1 = $curHeight - $height;
      $y2 = $curHeight;
      break;
    case 'bottom right':
      $x1 = $curWidth - $width;
      $x2 = $curWidth;
      $y1 = $curHeight - $height;
      $y2 = $curHeight;
      break;
    default:
      $x1 = floor(($curWidth / 2) - ($width / 2));
      $x2 = $width + $x1;
      $y1 = floor(($curHeight / 2) - ($height / 2));
      $y2 = $height + $y1;
      break;
    }

    // Return the cropped thumbnail image
    return self::crop($resizeImage, $x1, $y1, $x2, $y2);
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
