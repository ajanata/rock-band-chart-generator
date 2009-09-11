<?php

function drawVocals($im, $x, $y, $meas, $vox, $events, $harm1 = array(), $harm1Events = array(), $harm2 = array(), $harm2Events = array()) {
    global $timebase, $black, $blue, $orange, $red;
    
	static $leftovers;
	// UGLY
	// HAX
	if ($meas["number"] == 1) {
		$leftovers = array();
		$leftovers["vox"] = array();
		$leftovers["harm1"] = array();
		$leftovers["harm2"] = array();
	}
	
	   if (isset($leftovers["harm2"]["length"])) {
        // leftover lyric
        if ($leftovers["harm2"]["length"] >= $meas["num"]) {
            // it goes entirely through this measure into the next one
            $newLeftovers["harm2"] = array();
            $newLeftovers["harm2"]["length"] = $leftovers["harm2"]["length"] - $meas["num"];
            $newLeftovers["harm2"]["where"] = $leftovers["harm2"]["where"];

            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["harm2"]["where"], $x + PXPERBEAT*$meas["num"], $y + $leftovers["harm2"]["where"], $red);

            $leftovers["harm2"] = $newLeftovers["harm2"];
            
            //return;
        }
        else {
            // it ends in this measure
            
            $nEX = $leftovers["harm2"]["length"] * PXPERBEAT;
            $nEX += $x;
            
            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["harm2"]["where"], $nEX, $y + $leftovers["harm2"]["where"], $yellow);
            
            $leftovers["harm2"] = array();
        }
    }  

    
    if (isset($leftovers["harm1"]["length"])) {
        // leftover lyric
        if ($leftovers["harm1"]["length"] >= $meas["num"]) {
            // it goes entirely through this measure into the next one
            $newLeftovers["harm1"] = array();
            $newLeftovers["harm1"]["length"] = $leftovers["harm1"]["length"] - $meas["num"];
            $newLeftovers["harm1"]["where"] = $leftovers["harm1"]["where"];

            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["harm1"]["where"], $x + PXPERBEAT*$meas["num"], $y + $leftovers["harm1"]["where"], $orange);

            $leftovers["harm1"] = $newLeftovers["harm1"];
            
            //return;
        }
        else {
            // it ends in this measure
            
            $nEX = $leftovers["harm1"]["length"] * PXPERBEAT;
            $nEX += $x;
            
            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["harm1"]["where"], $nEX, $y + $leftovers["harm1"]["where"], $orange);
            
            $leftovers["harm1"] = array();
        }
     }
    
        if (isset($leftovers["vox"]["length"])) {
        // leftover lyric
        if ($leftovers["vox"]["length"] >= $meas["num"]) {
            // it goes entirely through this measure into the next one
            $newLeftovers["vox"] = array();
            $newLeftovers["vox"]["length"] = $leftovers["vox"]["length"] - $meas["num"];
            $newLeftovers["vox"]["where"] = $leftovers["vox"]["where"];

            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["vox"]["where"], $x + PXPERBEAT*$meas["num"], $y + $leftovers["vox"]["where"], $blue);

            $leftovers["vox"] = $newLeftovers["vox"];
            
            //return;
        }
        else {
            // it ends in this measure
            
            $nEX = $leftovers["vox"]["length"] * PXPERBEAT;
            $nEX += $x;
            
            imagesetthickness($im, 2);
            imageline($im, $x, $y + $leftovers["vox"]["where"], $nEX, $y + $leftovers["vox"]["where"], $blue);
            
            $leftovers["vox"] = array();
        }
    }
    
    
    
        foreach ($harm2 as $lyricIndex => $lyric) {
        
        if ($lyric["time"] >= $meas["time"] && $lyric["time"] < $meas["time"]+$timebase*$meas["num"]*4/$meas["denom"]) {
        
            $nX = $lyric["time"] - $meas["time"];
            $nX /= $timebase;
            $nX *= PXPERBEAT;
            $nX += $x;
            
            if (isset($lyric["percussion"])) {
                imagestring($im, 3, $nX - 3, $y + 10 * (STAFFHEIGHT/2) - 3, "*", $red);
                $leftovers["harm2"] = array();
            }
            else {
                imagestring($im, 2, $nX , $y + 12 * (STAFFHEIGHT/2) - 3, $lyric["lyric"], $red);
                    
                // draw the pitch line
                
                $nEX = $lyric["duration"];
                $nEX /= $timebase;
                $nEX *= PXPERBEAT;
                $nEX += $nX;
                
                $nyOffset = 0;
                if ($lyric["talky"]) {
                    $nyOffset = (STAFFHEIGHT/2) * 7 + 7;
                }
                else {
                    // pata70 gets credit for this
                    $nyOffset = $lyric["pitch"] - 48; 
                    $nyOffset *= 6*(STAFFHEIGHT/2) / 24; 
                    $nyOffset = 6*(STAFFHEIGHT/2) - $nyOffset;
                    
                }
                
                if ($lyric["time"] + $lyric["duration"] > $meas["time"] + $timebase*$meas["num"]) {
                    // this lyric crosses measures
                    $leftovers["harm2"] = array();
                    $leftovers["harm2"]["length"] = $lyric["time"] + $lyric["duration"] - ($meas["time"] + $timebase*$meas["num"]);
                    $leftovers["harm2"]["length"] /= $timebase;
                    $leftovers["harm2"]["where"] = $nyOffset;
                    
                    $nEX = $x + $meas["num"] * PXPERBEAT;
                }
                else {
                    $leftovers["harm2"] = array();
                }
                
                imagesetthickness($im, 2);
                
                imageline($im, $nX, $y + $nyOffset, $nEX, $y + $nyOffset, $red);
            }            
        }    
    } // foreach harm2
    
        
     foreach ($harm1 as $lyricIndex => $lyric) {
        
        if ($lyric["time"] >= $meas["time"] && $lyric["time"] < $meas["time"]+$timebase*$meas["num"]*4/$meas["denom"]) {
        
            $nX = $lyric["time"] - $meas["time"];
            $nX /= $timebase;
            $nX *= PXPERBEAT;
            $nX += $x;
            
            if (isset($lyric["percussion"])) {
                imagestring($im, 3, $nX - 3, $y + 9 * (STAFFHEIGHT/2) - 3, "*", $orange);
                $leftovers["harm1"] = array();
            }
            else {
                imagestring($im, 2, $nX , $y + 10 * (STAFFHEIGHT/2) - 3, $lyric["lyric"], $orange);
                    
                // draw the pitch line
                
                $nEX = $lyric["duration"];
                $nEX /= $timebase;
                $nEX *= PXPERBEAT;
                $nEX += $nX;
                
                $nyOffset = 0;
                if ($lyric["talky"]) {
                    $nyOffset = (STAFFHEIGHT/2) * 7 + 3;
                }
                else {
                    // pata70 gets credit for this
                    $nyOffset = $lyric["pitch"] - 48; 
                    $nyOffset *= 6*(STAFFHEIGHT/2) / 24; 
                    $nyOffset = 6*(STAFFHEIGHT/2) - $nyOffset;
                    
                }
                
                if ($lyric["time"] + $lyric["duration"] > $meas["time"] + $timebase*$meas["num"]) {
                    // this lyric crosses measures
                    $leftovers["harm1"] = array();
                    $leftovers["harm1"]["length"] = $lyric["time"] + $lyric["duration"] - ($meas["time"] + $timebase*$meas["num"]);
                    $leftovers["harm1"]["length"] /= $timebase;
                    $leftovers["harm1"]["where"] = $nyOffset;
                    
                    $nEX = $x + $meas["num"] * PXPERBEAT;
                }
                else {
                    $leftovers["harm1"] = array();
                }
                
                imagesetthickness($im, 2);
                
                imageline($im, $nX, $y + $nyOffset, $nEX, $y + $nyOffset, $orange);
            }            
        }    
    } // foreach harm1
    
    foreach ($vox as $lyricIndex => $lyric) {
        
        if ($lyric["time"] >= $meas["time"] && $lyric["time"] < $meas["time"]+$timebase*$meas["num"]*4/$meas["denom"]) {
        
            $nX = $lyric["time"] - $meas["time"];
            $nX /= $timebase;
            $nX *= PXPERBEAT;
            $nX += $x;
            
            if (isset($lyric["percussion"])) {
                imagestring($im, 3, $nX - 3, $y + 8 * (STAFFHEIGHT/2) - 3, "*", $black);
                $leftovers["vox"] = array();
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
                    $leftovers["vox"] = array();
                    $leftovers["vox"]["length"] = $lyric["time"] + $lyric["duration"] - ($meas["time"] + $timebase*$meas["num"]);
                    $leftovers["vox"]["length"] /= $timebase;
                    $leftovers["vox"]["where"] = $nyOffset;
                    
                    $nEX = $x + $meas["num"] * PXPERBEAT;
                }
                else {
                    $leftovers["vox"] = array();
                }
                
                imagesetthickness($im, 2);
                
                imageline($im, $nX, $y + $nyOffset, $nEX, $y + $nyOffset, $blue);
            }            
        }    
    } // foreach vox

    
} // drawVocals

?>