<?php

function setHexCode($color) {
    // Strip out the leading hashtag if there
    if(substr($color, 0, 1) == "#") $color = substr($color, 1);

    // If we don't now have 6 digits, we have a problem
    if(strlen($color) != 6) return false;

    // Get our red, blue, and green values in fractional form
    $red = ((float) hexdec(substr($color, 0, 2)))/255;
    $blue = ((float) hexdec(substr($color, 4, 2)))/255;
    $green = ((float) hexdec(substr($color, 2, 2)))/255;

    // we need these later
    $highest = max($red, $green, $blue);
    $diff = $highest - min($red, $green, $blue);

    // We want the light to be as bright as our brightest individual color.
    $brightness = $highest;

    // Calculate our saturation as well as values used in the hue formula
    if($diff == 0) {
      $base = 0;
      $delta = 0;
      $saturation = 0;
    } else {
      $saturation = $diff / $highest;
      $base = 0;
      $delta = 0;

      switch($highest) {
        case $red:
          $base = 0;
          $delta = ($green-$blue)/($diff*2);
          break;
        case $green:
          $base = 25500; // Pure green, per the documentation
          $delta = ($blue-$red)/($diff*2);
          break;
        case $blue:
          $base = 46920; // Pure blue, per the documentation
          $delta = ($red-$green)/($diff*2);
          break;
      }
    }

    // Correction for red-dominant purples to a positive value above blue instead of a negative value below red
    if($delta < 0) {
      $base = 46920;
      $delta = (1 + $delta);
    }

    // Determine the conversion value for our delta value
    if($base < 2) $scaling = 25500; // red to green occupies 38.910505836% of Hue's color space
    elseif($base < 4) $scaling = 21420; // green to blue occupies occupy 32.684824902% of Hue's color space
    else $scaling = 18615; // blue to red occupies 28.40466926% of Hue's color space

    // Determine our appropriately-scaled hue value
    $color2['hue'] = (int) ($base + ($delta * $scaling));

    // Scale up our brightness and saturation to the right units
    $color2['saturation'] = (int) ($saturation * 255);
    $color2['brightness'] = (int) ($brightness * 255);

    return $color2;
  }
function setHexCode2($rgb) {
    // Strip out the leading hashtag if there
    if(substr($rgb, 0, 1) == "#") $rgb = substr($rgb, 1);

    // If we don't now have 6 digits, we have a problem
    if(strlen($rgb) != 6) return false;

if (strlen($rgb)==6) {
$r=hexdec(substr($rgb,0,2))/255;
$g=hexdec(substr($rgb,2,2))/255;
$b=hexdec(substr($rgb,4,2))/255;

if ($r>0.04045)
{$rf=pow(($r + 0.055) / (1.0 + 0.055), 2.4);} else {$rf=$r/12.92;};
if ($r>0.04045) {$gf=pow(($g + 0.055) / (1.0 + 0.055), 2.4);} else {$gf=$g/12.92;};
if ($r>0.04045) {$bf=pow(($b + 0.055) / (1.0 + 0.055), 2.4);} else {$bf=$b/12.92;};

$x = $rf * 0.649926 + $gf * 0.103455 + $bf * 0.197109; 
$y = $rf * 0.234327 + $gf * 0.743075 + $bf * 0.022598;
$z = $rf * 0.000000 + $gf * 0.053077 + $bf * 1.035763;

$cx = $x / ($x + $y + $z);
$cy = $y / ($x + $y + $z);

if (is_nan($cx)) {$cx=0;};
if (is_nan($cy)) {$cy=0;};

$xy[0]=$cx;
$xy[1]=$cy;
$arrData['bri'] = intval($y*254); //This isn't quite right
$arrData['xy']=$xy;
} else {
$arrData['ct'] = 150;
$arrData['bri'] = 254;
};
    return $arrData;
  }
/*
function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function rgb2hex($rgb) {
   $hex = "#";
   $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

   return $hex; // returns the hex value including the number sign (#)
}

function rgb2xy($r,$g,$b) {
	$X = 1.076450 * $r - 0.237662 * $g + 0.161212 * $b;
	$Y = 0.410964 * $r + 0.554342 * $g + 0.034694 * $b;
	$Z = -0.010954 * $r - 0.013389 * $g + 1.024343 * $b;
	$x = $X / ($X + $Y + $Z);
	$y = $Y / ($X + $Y + $Z);
	
	return "[".$x.",".$y."]";
}
*/
/*
--------------------------------------------------------------------------------------------
Function Name: HexHSL( String(HexColor) )

$HexColor    - Can include the '#' sign or not, but must have either 3 digits or 6. Anything
               between 3 and 6 digits will have an unexpected result.


HexHSL converts a hexadecimal color into hue, saturation, and luminance.
--------------------------------------------------------------------------------------------
*/

function HexHSL( $HexColor )
{

        $HexColor    = str_replace( '#', '', $HexColor );

        if( strlen( $HexColor ) < 3 ) str_pad( $HexColor, 3 - strlen( $HexColor ), '0' );

        $Add         = strlen( $HexColor ) == 6 ? 2 : 1;
        $AA          = 0;
        $AddOn       = $Add == 1 ? ( $AA = 16 - 1 ) + 1 : 1;

        $Red         = round( ( hexdec( substr( $HexColor, 0, $Add ) ) * $AddOn + $AA ) / 255, 6 );
        $Green       = round( ( hexdec( substr( $HexColor, $Add, $Add ) ) * $AddOn + $AA ) / 255, 6 );
        $Blue        = round( ( hexdec( substr( $HexColor, ( $Add + $Add ) , $Add ) ) * $AddOn + $AA ) / 255, 6 );


        $HSLColor    = array( 'Hue' => 0, 'Saturation' => 0, 'Luminance' => 0 );

        $Minimum     = min( $Red, $Green, $Blue );
        $Maximum     = max( $Red, $Green, $Blue );

        $Chroma      = $Maximum - $Minimum;


        $HSLColor['Luminance'] = ( $Minimum + $Maximum ) / 2;

        if( $Chroma == 0 )
        {
                $HSLColor['Luminance'] = round( $HSLColor['Luminance'] * 255, 0 );

                return $HSLColor;
        }


        $Range = $Chroma * 6;


        $HSLColor['Saturation'] = $HSLColor['Luminance'] <= 0.5 ? $Chroma / ( $HSLColor['Luminance'] * 2 ) : $Chroma / ( 2 - ( $HSLColor['Luminance'] * 2 ) );

        if( $Red <= 0.004 || $Green <= 0.004 || $Blue <= 0.004 )
                $HSLColor['Saturation'] = 1;


        if( $Maximum == $Red )
        {
                $HSLColor['Hue'] = round( ( $Blue > $Green ? 1 - ( abs( $Green - $Blue ) / $Range ) : ( $Green - $Blue ) / $Range ) * 255, 0 );
        }
        else if( $Maximum == $Green )
        {
                $HSLColor['Hue'] = round( ( $Red > $Blue ? abs( 1 - ( 4 / 3 ) + ( abs ( $Blue - $Red ) / $Range ) ) : ( 1 / 3 ) + ( $Blue - $Red ) / $Range ) * 255, 0 );
        }
        else
        {
                $HSLColor['Hue'] = round( ( $Green < $Red ? 1 - 2 / 3 + abs( $Red - $Green ) / $Range : 2 / 3 + ( $Red - $Green ) / $Range ) * 255, 0 );
        }




        $HSLColor['Saturation'] = round( $HSLColor['Saturation'] * 255, 0 );
        $HSLColor['Luminance']  = round( $HSLColor['Luminance'] * 255, 0 );



        return $HSLColor;


}
function xyBriToRgb($x, $y, $bri){
			$hex_RGB="";
			$hex_value="";
			if(isset($x) && isset($y) && isset($bri)){
            $z = 1.0 - $x - $y;
            $Y = $bri / 255.0; // Brightness of lamp
            $X = ($Y / $y) * $x;
            $Z = ($Y / $y) * $z;
            $r = $X * 1.612 - $Y * 0.203 - $Z * 0.302;
            $g = -$X * 0.509 + $Y * 1.412 + $Z * 0.066;
            $b = $X * 0.026 - $Y * 0.072 + $Z * 0.962;
            $r = ($r <= 0.0031308)? 12.92 * $r : (1.0 + 0.055) * pow($r, (1.0 / 2.4)) - 0.055;
            $g = ($g <= 0.0031308)? 12.92 * $g : (1.0 + 0.055) * pow($g, (1.0 / 2.4)) - 0.055;
            $b = ($b <= 0.0031308)? 12.92 * $b : (1.0 + 0.055) * pow($b, (1.0 / 2.4)) - 0.055;
            $maxValue = max($r,$g,$b);
            $r /= $maxValue;
            $g /= $maxValue;
            $b /= $maxValue;
            $r = $r * 255;   if ($r < 0) { $r = 255; }
            $hex_value = dechex($r);
			if(strlen($hex_value)<2){$hex_value="0".$hex_value;}
				$hex_RGB.=$hex_value;
            $g = $g * 255;   if ($g < 0) { $g = 255; }
			$hex_value = dechex($g);
			if(strlen($hex_value)<2){$hex_value="0".$hex_value;}
			$hex_RGB.=$hex_value;			
            $b = $b * 255;   if ($b < 0) { $b = 255; }
			$hex_value = dechex($b);
			if(strlen($hex_value)<2){$hex_value="0".$hex_value;}
			$hex_RGB.=$hex_value;
			return "#".$hex_RGB;
			}else{
			return '#FFFFFF';	
			}
            
        }

/*
--------------------------------------------------------------------------------------------
Function Name: HSLtoHex( Mixed(Hue), Mixed(Saturation), Mixed(Luminance) )

$Hue, $Saturation, $Luminance   - (Mixed) Can be string, float, or integer.
                                  Pct ( 0% - 100% ) | decimal ( 0 to 1.0 ) | integer ( 0 - 255 )


HSLtoHex converts an HSL ( Hue, Saturation, and Luminance ) color into a Hexadecimal Color.
Maximum for any value is 100%, 1.0, or 255. Invalid values passed will result in 0.
--------------------------------------------------------------------------------------------
*/

function HSLtoHex( $Hue = 0, $Saturation = 0, $Luminance = 0 )
{


        $HSLColor    = array( 'Hue' => $Hue, 'Saturation' => $Saturation, 'Luminance' => $Luminance );
        $RGBColor    = array( 'Red' => 0, 'Green' => 0, 'Blue' => 0 );


        foreach( $HSLColor as $Name => $Value )
        {
                if( is_string( $Value ) && strpos( $Value, '%' ) !== false )
                        $Value = round( round( (int)str_replace( '%', '', $Value ) / 100, 2 ) * 255, 0 );

                else if( is_float( $Value ) )
                        $Value = round( $Value * 255, 0 );

                $Value    = (int)$Value * 1;
                $Value    = $Value > 255 ? 255 : ( $Value < 0 ? 0 : $Value );
                $ValuePct = round( $Value / 255, 6 );

                define( "{$Name}", $ValuePct );

        }


        $RGBColor['Red']   = Luminance;
        $RGBColor['Green'] = Luminance;
        $RGBColor['Blue']  = Luminance;



        $Radial  = Luminance <= 0.5 ? Luminance * ( 1.0 + Saturation ) : Luminance + Saturation - ( Luminance * Saturation );



        if( $Radial > 0 )
        {

                $Ma   = Luminance + ( Luminance - $Radial );
                $Sv   = round( ( $Radial - $Ma ) / $Radial, 6 );
                $Th   = Hue * 6;
                $Wg   = floor( $Th );
                $Fr   = $Th - $Wg;
                $Vs   = $Radial * $Sv * $Fr;
                $Mb   = $Ma + $Vs;
                $Mc   = $Radial - $Vs;


                // Color is between yellow and green
                if ($Wg == 1)
                {
                        $RGBColor['Red']   = $Mc;
                        $RGBColor['Green'] = $Radial;
                        $RGBColor['Blue']  = $Ma;
                }
                // Color is between green and cyan
                else if( $Wg == 2 )
                {
                        $RGBColor['Red']   = $Ma;
                        $RGBColor['Green'] = $Radial;
                        $RGBColor['Blue']  = $Mb;
                }

                // Color is between cyan and blue
                else if( $Wg == 3 )
                {
                        $RGBColor['Red']   = $Ma;
                        $RGBColor['Green'] = $Mc;
                        $RGBColor['Blue']  = $Radial;
                }

                // Color is between blue and magenta
                else if( $Wg == 4 )
                {
                        $RGBColor['Red']   = $Mb;
                        $RGBColor['Green'] = $Ma;
                        $RGBColor['Blue']  = $Radial;
                }

                // Color is between magenta and red
                else if( $Wg == 5 )
                {
                        $RGBColor['Red']   = $Radial;
                        $RGBColor['Green'] = $Ma;
                        $RGBColor['Blue']  = $Mc;
                }

                // Color is between red and yellow or is black
                else
                {
                        $RGBColor['Red']   = $Radial;
                        $RGBColor['Green'] = $Mb;
                        $RGBColor['Blue']  = $Ma;
                }

         }



         $RGBColor['Red']   = ($C = round( $RGBColor['Red'] * 255, 0 )) < 15 ? '0'.dechex( $C ) : dechex( $C );
         $RGBColor['Green'] = ($C = round( $RGBColor['Green'] * 255, 0 )) < 15 ? '0'.dechex( $C ) : dechex( $C );
         $RGBColor['Blue']  = ($C = round( $RGBColor['Blue'] * 255, 0 )) < 15 ? '0'.dechex( $C ) : dechex( $C );



         return '#' . $RGBColor['Red'].$RGBColor['Green'].$RGBColor['Blue'];


}

?>