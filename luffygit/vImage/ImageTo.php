<?php
//
// ImageTo
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

class ImageTo {
	
  //////////////////////////////////////////////////////////////////////////////////////////////////
  // Savers
  //////////////////////////////////////////////////////////////////////////////////////////////////

  //
  // Generates an image.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns an array containing the image data and mime type.
  //
  private static function generate($image, $mimeType = null, $quality = 100) {
    // Format defaults to the original mime type
    $mimeType = $mimeType ?: ImageHelperConfig::DEFAULT_MINETYPE;

    // Ensure quality is a valid integer
    if($quality === null) $quality = 100;
    $quality = self::keepWithin((int) $quality, 0, 100);

    // Capture output
    ob_start();

    // Generate the image
    switch($mimeType) {
    case 'image/gif':
      imagesavealpha($image, true);
      imagegif($image, null);
      break;
    case 'image/jpeg':
      imageinterlace($image, true);
      imagejpeg($image, null, $quality);
      break;
    case 'image/png':
      imagesavealpha($image, true);
      imagepng($image, null, round(9 * $quality / 100));
      break;
    case 'image/webp':
      // Not all versions of PHP will have webp support enabled
      if(!function_exists('imagewebp')) {
        throw new \Exception(
          'WEBP support is not enabled in your version of PHP.',
          self::ERR_WEBP_NOT_ENABLED
        );
      }
      imagesavealpha($image, true);
      imagewebp($image, null, $quality);
      break;
    default:
      throw new \Exception('Unsupported format: ' . $mimeType, ErrorCodeConstant::ERR_UNSUPPORTED_FORMAT);
    }

    // Stop capturing
    $data = ob_get_contents();
    ob_end_clean();

    return [
      'data' => $data,
      'mimeType' => $mimeType
    ];
  }

  //
  // Generates a data URI.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a string containing a data URI.
  //
  public static function toDataUri($image, $mimeType = null, $quality = 100) {
    $image = self::generate($image, $mimeType, $quality);

    return 'data:' . $image['mimeType'] . ';base64,' . base64_encode($image['data']);
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
  public static function toDownload($image, $filename, $mimeType = null, $quality = 100) {
    $image = self::generate($image, $mimeType, $quality);

    // Set download headers
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . strlen($image['data']));
    header('Content-Transfer-Encoding: Binary');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo $image['data'];

    return $image;
  }

  //
  // Writes the image to a file.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public static function toFile($image, $file, $mimeType = null, $quality = 100) {
    $image = self::generate($image, $mimeType, $quality);

    // Save the image to file
    if(!file_put_contents($file, $image['data'])) {
      throw new \Exception("Failed to write image to file: $file", ErrorCodeConstant::ERR_WRITE);
    }

    return $image;
  }

  //
  // Outputs the image to the screen. Must be called before any output is sent to the screen.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public static function toScreen($image, $mimeType = null, $quality = 100) {
    $image = self::generate($image, $mimeType, $quality);

    // Output the image to stdout
    header('Content-Type: ' . $image['mimeType']);
    echo $image['data'];

//  return $image;
  }

  //
  // Generates an image string.
  //
  //  $mimeType (string) - The image format to output as a mime type (defaults to the original mime
  //    type).
  //  $quality (int) - Image quality as a percentage (default 100).
  //
  // Returns a SimpleImage object.
  //
  public static function toString($image, $mimeType = null, $quality = 100) {
    return self::generate($image, $mimeType, $quality)['data'];
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
