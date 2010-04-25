<?php

// TODO: alte files l�chen


$number = $num;

// Variables
//////////////////////////////////////////////////////////////////////////
$x               = 133;                                     // Image width
$y               = 17;                                     // Image height
$freq            = 40;                                    // Number of noise dots
$noise_method    = "dots";                        // line | dots | rectangle
$font_selection  = "random";                    // random | fixed
$font_folder	   = PATH_site . 'typo3conf/ext/user_kontakt2tine/pi1/code_image/fonts/';
$fonts           = array("75749___.ttf", "Flubber.ttf", "FOLION.ttf", "GOTHIC13.ttf", "Justov.ttf", "RUBSTAMP.ttf");
$default_font    = 4;        // Array index of default font in $fonts array.
$angle_selection = "random";                // random | fixed
$max_angle       = 20;                            // Max angle
$default_angle   = 0;                            // Default angle.
$font_size       = 18;                            // Font size in points
$letters         = array();
//////////////////////////////////////////////////////////////////////////

// Set font
if ($font_selection == "random")
{
  for($i=0;$i<strlen($number);$i++)
  {
    $font = rand(0, (count($fonts)-1));
    $font = $font_folder.$fonts[$font];
    $letters[$i] = $font;
  }
}
else
{
  $font = $font_folder.$fonts[$default_font];
}

// Set Text Angle
if ($angle_selection=="random")
{
  $angle = rand((-1)*($max_angle/2), ($max_angle/2));
}
else
{
  $angle = $default_angle;
}

// Create image with specified size.
$img = @ImageCreate($x, $y) or die("Couldn't create image");

// Allocate colors
$white = ImageColorAllocate($img, 255, 255, 255);
$black = ImageColorAllocate($img, 0, 0, 0);
/* Get background , noise and font color randomly from get_random_colors() function. This function returns contrary colors for readability.*/
function get_random_colors()
{
  $bck = array();
  $dot = array();
  $txt = array();
  // i=O =>Red | i=1 =>Green | i=2 =>Blue
  for ($i=0; $i<3; $i++)
  {
    $x = rand(0,132);
    $y = rand(191,255);
    array_push($bck, $x);
    array_push($dot, (255-$x));
    array_push($txt, $y);
  }
  // Return array of 3 arrays : [0..2, 0..2]
  return array($bck, $dot, $txt);
}

#$rnd_col    = get_random_colors();
#$background = ImageColorAllocate($img, $rnd_col[0][0], $rnd_col[0][1], $rnd_col[0][2]);
#$dots_color = ImageColorAllocate($img, $rnd_col[1][0], $rnd_col[1][1], $rnd_col[1][2]);
#$text_color = ImageColorAllocate($img, $rnd_col[2][0], $rnd_col[2][1], $rnd_col[2][2]);

$background = ImageColorAllocate($img, 255, 255, 255);
$dots_color = ImageColorAllocate($img, 0, 0, 0);
$text_color = ImageColorAllocate($img, 0, 0, 0);

// Stop execution if any error occured before
if (isset($error_msg))
{
  //Fill image background with white
  ImageFill ($img, 100, 50, $white);
  //Display error
  ImageString($img, 2, 20, 10, $error_msg, $black);
}
else
{
  //Fill image with background color
  ImageFill ($img, 100, 50, $background);
  // Add centered text

  // Unremark the line below to see what $arr have in
  // echo ("<PRE>"); print_r($arr); die();
  $text_x = array();
  $text_y = array();
  $arr    = array();
  $tx     = 0;
  $strl   = 0;
  $xstart = 30;

  for($p=0;$p<strlen($number);$p++)
  {
    $arr[$p] = ImageTtfbBox($font_size, $angle, $letters[$p], substr($number,$p,1));
    if($p != 0)
    {
      $xstart = $xstart+ceil((abs($arr[$p][2]-$arr[$p][0])))+6;
    }
    $text_y[$p] = round(($y-(abs($arr[$p][5]-$arr[$p][3]))) / 2, 0);
    ImageTTFText($img, $font_size, $angle, $xstart, $text_y[$p] - $arr[$p][5], $text_color, $letters[$p], substr($number,$p,1));
  }

  $i = 0;        //<---------Noise Counter

  // Add Noise Points
  while ($i < $freq)
  {
    $dotX = rand(0, $x); $dotY = rand(0, $y);
    switch ($noise_method)
    {
      case "line":
        $line_width = rand(4,20);
        if (rand(0,10) >= 5)
        {
          // Draw horizontal line
          ImageLine($img, $dotX, $dotY, $dotX+$line_width, $dotY, $dots_color);
        }
        else
        {
          // Draw vertical line
          ImageLine($img, $dotX, $dotY, $dotX, $dotY+$line_width, $dots_color);
        }
        break;
      case "dots":
        ImageSetPixel($img, $dotX, $dotY, $dots_color);
        break;
      case "rectangle":
        ImageRectangle($img, $dotX-1, $dotY-1, $dotX+1, $dotY+1, $dots_color);
        break;
    }
    $i++;
  }
}

//Finalize the image. Free memory

ImagePNG($img, PATH_site . 'typo3conf/ext/user_kontakt2tine/pi1/code_image/code_image_files/' . $num_codiert . '.png');
ImageDestroy($img);
?>
