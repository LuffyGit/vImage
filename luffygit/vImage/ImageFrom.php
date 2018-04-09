<?php
//
// ImageFrom
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

class ImageFrom {
	//////////////////////////////////////////////////////////////////////////////////////////////////
	// Loaders
	//////////////////////////////////////////////////////////////////////////////////////////////////

	//
	// Loads an image from a data URI.
	//
	//  $uri* (string) - A data URI.
	//
	// Returns a SimpleImage object.
	//
	public static function fromDataUri($uri) {
		// Basic formatting check
		preg_match('/^data:(.*?);/', $uri, $matches);
		if (!count($matches)) {
			throw new \Exception('Invalid data URI.', ErrorCodeConstant::ERR_INVALID_DATA_URI);
		}

		// Determine mime type
		$mimeType = $matches[1];
		if (!preg_match('/^image\/(gif|jpeg|png)$/', $mimeType)) {
			throw new \Exception('Unsupported format: ' . $mimeType, ErrorCodeConstant::ERR_UNSUPPORTED_FORMAT);
		}

		// Get image data
		$uri = base64_decode(preg_replace('/^data:(.*?);base64,/', '', $uri));
		$image = imagecreatefromstring($uri);
		if (!$image) {
			throw new \Exception("Invalid image data.", ErrorCodeConstant::ERR_INVALID_IMAGE);
		}
		
		$exif = null;		
		return array('image'=>$image, 'mimeType'=>$mimeType,'exif'=>$exif);
	}

	//
	// Loads an image from a file.
	//
	//  $file* (string) - The image file to load.
	//
	// Returns a SimpleImage object.
	//
	public static function fromFile($file) {
		if(ImageHelperConfig::CHECK_IMAGE_FILE){
			// Check if the file exists and is readable. We're using fopen() instead of file_exists()
			// because not all URL wrappers support the latter.
			$handle = @fopen($file, 'r');
			if ($handle === false) {
				throw new \Exception("File not found: $file", ErrorCodeConstant::ERR_FILE_NOT_FOUND);
			}
			fclose($handle);	
		}

		// Get image info
		$info = getimagesize($file);
		if ($info === false) {
			throw new \Exception("Invalid image file: $file", ErrorCodeConstant::ERR_INVALID_IMAGE);
		}
		$mimeType = $info['mime'];

		// Create image object from file
		switch($mimeType) {
			case 'image/gif' :
				// Load the gif
				$gif = imagecreatefromgif($file);
				if ($gif) {
					// Copy the gif over to a true color image to preserve its transparency. This is a
					// workaround to prevent imagepalettetruecolor() from borking transparency.
					$width = imagesx($gif);
					$height = imagesy($gif);
					$image = imagecreatetruecolor($width, $height);
					$transparentColor = imagecolorallocatealpha($image, 0, 0, 0, 127);
					imagecolortransparent($image, $transparentColor);
					imagefill($image, 0, 0, $transparentColor);
					imagecopy($image, $gif, 0, 0, 0, 0, $width, $height);
					imagedestroy($gif);
				}
				break;
			case 'image/jpeg' :
				$image = imagecreatefromjpeg($file);
				break;
			case 'image/png' :
				$image = imagecreatefrompng($file);
				break;
			case 'image/webp' :
				$image = imagecreatefromwebp($file);
				break;
		}
		if (!$image) {
			throw new \Exception("Unsupported image: $file", ErrorCodeConstant::ERR_UNSUPPORTED_FORMAT);
		}

		// Convert pallete images to true color images
		imagepalettetotruecolor($image);

		$exif = null;
		// Load exif data from JPEG images
		if (ImageHelperConfig::USE_IMAGE_EXIF && $mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
			$exif = @exif_read_data($file);
		}

		return array('image'=>$image, 'mimeType'=>$mimeType,'exif'=>$exif);
	}

	//
	// Creates a new image.
	//
	//  $width* (int) - The width of the image.
	//  $height* (int) - The height of the image.
	//  $color (string|array) - Optional fill color for the new image (default 'transparent').
	//
	// Returns a SimpleImage object.
	//
	public static function fromNew($width, $height, $color = 'transparent') {
		$image = imagecreatetruecolor($width, $height);

		// Use PNG for dynamically created images because it's lossless and supports transparency
		$mimeType = 'image/png';

		$color = ImageColorPalette::allocateColor($image, $color);
		// Fill the image with color
		imagesetthickness($image, 1);
      	imagefilledrectangle($image, 0, 0, $width, $height, $color);
	    imagefill($image, 0, 0, $color);

		return array('image'=>$image, 'mimeType'=>$mimeType,'exif'=>null);
	}

	//
	// Creates a new image from a string.
	//
	//  $string* (string) - The raw image data as a string. Example:
	//
	//    $string = file_get_contents('image.jpg');
	//
	// Returns a SimpleImage object.
	//
	public static function fromString($string) {
		return fromFile('data://;base64,' . base64_encode($string));
	}
	
//	  //
//// Draws a rectangle.
////
////  $x1* (int) - The upper left x coordinate.
////  $y1* (int) - The upper left y coordinate.
////  $x2* (int) - The bottom right x coordinate.
////  $y2* (int) - The bottom right y coordinate.
////  $color* (string|array) - The rectangle color.
////  $thickness (int|string) - Line thickness in pixels or 'filled' (default 1).
////
//// Returns a SimpleImage object.
////
//private static function rectangle($image, $x1, $y1, $x2, $y2, $color, $thickness = 1) {
//  // Allocate the color
//  $color = ImageColorPalette::allocateColor($image, $color);
//
//  // Draw a rectangle
//  if($thickness === 'filled') {
//    
//  } else {
//    imagesetthickness($image, $thickness);
//    imagerectangle($image, $x1, $y1, $x2, $y2, $color);
//  }
//
//  return $image;
//}
}
