<?php

function drawVocals($im, $x, $y, $meas, $vox, $events) {
    global $timebase, $black, $blue;

	static $leftovers;
	if ($meas["number"] == 1) $leftovers = array();

    if (isset($leftovers["length"])) {
        // leftover lyric
        if ($leftovers["length"] >= $meas["numerator"]) {
            // it goes entirely through this measure into the next one
            $newLeftovers = array();
            $newLeftovers["length"] = $leftovers["length"] - $meas["numerator"];
            $newLeftovers["where"] = $leftovers["where"];

            imagesetthickness($im, 3);
            imageline($im, $x, $y + $leftovers["where"], $x + PXPERBEAT*$meas["numerator"], $y + $leftovers["where"], $blue);

            $leftovers = $newLeftovers;
            
            return;
        }
        else {
            // it ends in this measure
            
            $nEX = $leftovers["length"] * PXPERBEAT;
            $nEX += $x;
            
            imagesetthickness($im, 3);
            imageline($im, $x, $y + $leftovers["where"], $nEX, $y + $leftovers["where"], $blue);
            
            $leftovers = array();
            
        }
    }
    
    foreach ($vox as $lyricIndex => $lyric) {
        
        if ($lyric["time"] >= $meas["time"] && $lyric["time"] < $meas["time"]+$timebase*$meas["numerator"]) {
        
            $nX = $lyric["time"] - $meas["time"];
            $nX /= $timebase;
            $nX *= PXPERBEAT;
            $nX += $x;
            
            if (isset($lyric["percussion"])) {
                imagestring($im, 3, $nX - 3, $y + 8 * STAFFHEIGHT - 5, "*", $black);
                $leftovers = array();
            }
            else {
                #if ($lyric["lyric"] != "+") {
                    imagestring($im, 2, $nX , $y + 8 * STAFFHEIGHT - 5, $lyric["lyric"], $black);
                    
                #}
                
                // draw the pitch line
                
                $nEX = $lyric["duration"];
                $nEX /= $timebase;
                $nEX *= PXPERBEAT;
                $nEX += $nX;
                
                $nyOffset = 0;
                if ($lyric["talky"]) {
                    $nyOffset = STAFFHEIGHT * 7 + 3;
                }
                else {
                    // TODO
                    $nyOffset = 100 - $lyric["pitch"];
                }
                
                if ($lyric["time"] + $lyric["duration"] > $meas["time"] + $timebase*$meas["numerator"]) {
                    // this lyric crosses measures
                    $leftovers = array();
                    $leftovers["length"] = $lyric["time"] + $lyric["duration"] - ($meas["time"] + $timebase*$meas["numerator"]);
                    $leftovers["length"] /= $timebase;
                    $leftovers["where"] = $nyOffset;
                    
                    $nEX = $x + $meas["numerator"] * PXPERBEAT;
                }
                else {
                    $leftovers = array();
                }
                
                imagesetthickness($im, 3);
                
                imageline($im, $nX, $y + $nyOffset, $nEX, $y + $nyOffset, $blue);
            }
            
        }
        
    } // foreach vox
} // drawVocals

/*
function fontlen($string, $font) {
    return 0;
    
    
    switch ($font) {
        case 1:
            return 0;

        case 2:
            return 5 * strlen($string);

        case 3:
            return 0;

        case 4:
            return 0;

        case 5:
            return 0;

        default:
            return -1;
    }
}
*/

?>