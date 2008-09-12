<?php

function drawVocals($im, $x, $y, $meas, $vox, $events) {
    global $timebase, $black, $blue;

	static $leftovers;
	if ($meas["number"] == 1) $leftovers = array();

    if (isset($leftovers["length"])) {
        // leftover lyric
        if ($leftovers["length"] >= $meas["num"]) {
            // it goes entirely through this measure into the next one
            $newLeftovers = array();
            $newLeftovers["length"] = $leftovers["length"] - $meas["num"];
            $newLeftovers["where"] = $leftovers["where"];

            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["where"], $x + PXPERBEAT*$meas["num"], $y + $leftovers["where"], $blue);

            $leftovers = $newLeftovers;
            
            return;
        }
        else {
            // it ends in this measure
            
            $nEX = $leftovers["length"] * PXPERBEAT;
            $nEX += $x;
            
            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["where"], $nEX, $y + $leftovers["where"], $blue);
            
            $leftovers = array();
            
        }
    }
    
    foreach ($vox as $lyricIndex => $lyric) {
        
        if ($lyric["time"] >= $meas["time"] && $lyric["time"] < $meas["time"]+$timebase*$meas["num"]*4/$meas["denom"]) {
        
            $nX = $lyric["time"] - $meas["time"];
            $nX /= $timebase;
            $nX *= PXPERBEAT;
            $nX += $x;
            
            if (isset($lyric["percussion"])) {
                imagestring($im, 3, $nX - 3, $y + 8 * (STAFFHEIGHT/2) - 3, "*", $black);
                $leftovers = array();
            }
            else {
                imagestring($im, 2, $nX , $y + 8 * (STAFFHEIGHT/2) - 3, $lyric["lyric"], $black);
                    
                // draw the pitch line
                
                $nEX = $lyric["duration"];
                $nEX /= $timebase;
                $nEX *= PXPERBEAT;
                $nEX += $nX;
                
                $nyOffset = 0;
                if ($lyric["talky"]) {
                    $nyOffset = (STAFFHEIGHT/2) * 7 + 5;
                }
                else {
                    // pata70 gets credit for this
                    $nyOffset = $lyric["pitch"] - 48; 
                    $nyOffset *= 6*(STAFFHEIGHT/2) / 24; 
                    $nyOffset = 6*(STAFFHEIGHT/2) - $nyOffset;
                    
                }
                
                if ($lyric["time"] + $lyric["duration"] > $meas["time"] + $timebase*$meas["num"]) {
                    // this lyric crosses measures
                    $leftovers = array();
                    $leftovers["length"] = $lyric["time"] + $lyric["duration"] - ($meas["time"] + $timebase*$meas["num"]);
                    $leftovers["length"] /= $timebase;
                    $leftovers["where"] = $nyOffset;
                    
                    $nEX = $x + $meas["num"] * PXPERBEAT;
                }
                else {
                    $leftovers = array();
                }
                
                imagesetthickness($im, 2);
                
                imageline($im, $nX, $y + $nyOffset, $nEX, $y + $nyOffset, $blue);
            }
            
        }
        
    } // foreach vox
} // drawVocals

?>