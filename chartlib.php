<?php
	
	if (!defined('DRAWPULSES')) define("DRAWPULSES", false);
    if (!defined('SHOWFORCED')) define("SHOWFORCED", false);


    require_once "vocalchartlib.php";
 
 

function makeChart($notetracks, $measures_all, $timetrack, $events_all, $vocals, $diff, $game,
    $do_guitar, $do_bass, $do_drums, $do_vocals, $songname, $beat) {
    
    global $NAMES, $APICOLORS;
	$APICOLORS = array();
	
    $game = strtoupper($game);
	
	$num_inst = $do_guitar + $do_bass + $do_drums + $do_vocals;
	
	$sections = $events_all["sections"];
	
	$instruments =  ($do_vocals ? "vocals " : "") . ($do_guitar ? "guitar " : "") . ($do_bass ? "bass " : "") . ($do_drums ? "drums" : "");
	
	$x = 25;
	$y = 100;
	
	// calculate image height
	for ($i = 0; $i < count($measures_all["guitar"]); $i++) {
   	    // this looks really weird doesn't it?
   	   if ($x + PXPERBEAT * $measures_all["guitar"][$i]["num"] * 4 / $measures_all["guitar"][$i]["denom"] > WIDTH - 25) {
	       $x = 25;
	       
	       $y += 5*DRAWPLAYERLINES;
	       //$y += 15 * ($do_guitar + $do_bass + $do_drums + $do_vocals);
	       $y += 25;
	       if ($do_guitar) $y += 45 + (5 - ($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_bass) $y += 45 + (5 - ($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_drums) $y += 45 + 4 * STAFFHEIGHT;
	       if ($do_vocals) $y += 55 + 7 * (STAFFHEIGHT/2);
	       if ($num_inst > 1) $y += 15;
	   }
	   if ($x + PXPERBEAT * $measures_all["guitar"][$i]["num"] * 4 / $measures_all["guitar"][$i]["denom"] > WIDTH - 50
	     && $i != count($measures_all["guitar"]) - 1) {
	       $x = 25;

	       $y += 5*DRAWPLAYERLINES;
	       //$y += 15 * ($do_guitar + $do_bass + $do_drums + $do_vocals);
	       $y += 25;
	       if ($do_guitar) $y += 45 + (5 - ($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_bass) $y += 45 + (5 - ($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_drums) $y += 45 + 4 * STAFFHEIGHT;
	       if ($do_vocals) $y += 55 + 7 * (STAFFHEIGHT/2);
	       if ($num_inst > 1) $y += 15;
	   }
	   else {
	       $x += PXPERBEAT * $measures_all["guitar"][$i]["num"] * 4 / $measures_all["guitar"][$i]["denom"];
	   }
	}
	
	global $HEIGHT;
	//$HEIGHT = $y + 200 + 75 * ($do_vocals) + 50 * ($do_guitar) + 50 * ($do_bass) + 50 * ($do_drums);
	$y += 40 + 5*DRAWPLAYERLINES;
    if ($do_guitar) $y += 45 + 5 * STAFFHEIGHT;
    if ($do_bass) $y += 45 + 5 * STAFFHEIGHT;
    if ($do_drums) $y += 45 + 4 * STAFFHEIGHT;
    if ($do_vocals) $y += 55 + 7 * (STAFFHEIGHT/2);
    $HEIGHT = $y;
	
	$im = imagecreate(WIDTH, $HEIGHT) or die("Cannot intialize new GD image");
	

// ugly hack
	global $white; $white = imagecolorallocate($im, 255, 255, 255);
 	global $black; $black = imagecolorallocate($im, 0, 0, 0);
	global $red; $red = imagecolorallocate($im, 255, 0, 0);
	global $gray; $gray = imagecolorallocate($im, 134, 134, 134);

	global $downbeatline; $downbeatline = imagecolorallocate($im, 150, 150, 150);
	global $upbeatline; $upbeatline = imagecolorallocate($im, 224, 224, 224);
	global $outline;  $outline = &$black;
	global $staffline; $staffline = &$downbeatline;
	global $tempo; $tempo = &$downbeatline;
	global $measnum; $measnum = imagecolorallocate($im, 200, 0, 0);
	global $sectionname; $sectionname = &$outline;
	global $measscore; $measscore = &$outline;
	global $cumscore; $cumscore = &$measnum;
	global $bonusscore; $bonusscore = imagecolorallocate($im, 0, 100, 0);
	global $timesig; $timesig = &$downbeatline;
	global $player1; $player1 = imagecolorallocate($im, 255, 0, 0);
	global $player2; $player2 = imagecolorallocate($im, 0, 0, 255);
	global $solo;
	$solo = imagecolorallocate($im, 224, 224, 255);
	global $fill;
    $fill = imagecolorallocate($im, 255, 200, 200);
	global $whammy; $whammy = imagecolorallocate($im, 0, 0, 192);
	global $phrase;
	// fix this color
	global $activated; $activated = imagecolorallocate($im, 175, 175, 255);
	$phrase = imagecolorallocate($im, 192, 255, 192);
	global $force_strum; $force_strum = imagecolorallocate($im, 255,192, 203);
	global $force_hopo; $force_hopo = imagecolorallocate($im, 255, 253, 208);


    global $noteColors, $silver, $lightsilver, $blue;
    $blue = imagecolorallocate($im, 0, 0, 255);
    $noteColors = array();
    $noteColors[] = imagecolorallocate($im, 0, 192, 0);
    $noteColors[] = &$red; //imagecolorallocate($im, 255, 0, 0);
    $noteColors[] = imagecolorallocate($im, 253, 233, 16);
    $noteColors[] = &$blue;
    $noteColors[] = imagecolorallocate($im, 255, 127, 0);
    $silver = imagecolorallocate($im, 168, 168, 168);
    $lightsilver = imagecolorallocate($im, 212, 212, 212);
    
    
    // make the colors for the API calls
    foreach ($events_all["colors"] as $e) {
        $APICOLORS[$e["n"]] = imagecolorallocate($im, $e["r"], $e["g"], $e["b"]);
    }

	
	///////////
	
	
	imagefill($im, 0, 0, $white);
	
	imagestring($im, 5, 0, 0, (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname), $black);
	imagestring($im, 5, 0, 15, strtolower($instruments), $black);
	/* if (strtolower($instrument) != "vocals") */ imagestring($im, 5, 0, 30, strtolower($diff), $black);
	imagestring($im, 3, 0, $HEIGHT - 27, "http://www.ajanata.com/charts/" . strtolower($game) . "/", $gray);
	imagestring($im, 3, 0, $HEIGHT - 13, "WARNING: Work in progress. This information has NOT BEEN VERIFIED and MAY BE INCORRECT.", $red);
	#imagestring($im, 3, WIDTH - 200, $HEIGHT - 27, "Generated by chartgen " . CHARTGENVERSION . ".", $gray);
	#imagestring($im, 2, WIDTH - 200, $HEIGHT - 13, "chartlib " . CHARTLIBVERSION . " -- parselib " . PARSELIBVERSION, $gray);
	imagestring($im, 3, WIDTH - 320, $HEIGHT - 27, "Generated at " . date("r") .".", $gray);
	//global $REVISION;
	imagestring($im, 3, WIDTH - 320, $HEIGHT - 13, "Generated by ajanata's Chart Generator, r" . REVISION . ".", $gray);
	
	// key
	imagefilledrectangle($im, WIDTH-185, 0, WIDTH, 30 + DRAWPLAYERLINES*15, $silver);
    imagestring($im, 3, WIDTH-180, 0, "Color Key", $black);
    imagestring($im, 3, WIDTH-110, 0, "Phrase", $phrase);
    if ($game == "RB" ) {
        imagestring($im, 3, WIDTH-64, 0, "Solo", $solo);
        imagestring($im, 3, WIDTH-30, 0, "Fill", $fill);
        if (defined("SHOWFORCED") && SHOWFORCED == true) {
            imagestring($im, 3, WIDTH-170, 15, "Force Strum", $force_strum);
            imagestring($im, 3, WIDTH-80, 15, "Force Hopo", $force_hopo);
        }
    }
    if (defined("SHOWPLAYERLINES") && DRAWPLAYERLINES) {
        imagestring($im, 3, WIDTH-120, 30, "Player 1", $player1);
        imagestring($im, 3, WIDTH-60, 30, "Player 2", $player2);
    }
	
	
	$x = 25;
	$y = 100;
	
	foreach($measures_all["guitar"] as $index => &$meas) {
	   
	   if ($x + PXPERBEAT * $meas["num"] * 4 / $meas["denom"] > WIDTH - 25) {
	       $x = 25;

	       $y += 5*DRAWPLAYERLINES;
	       //$y += 15 * ($do_guitar + $do_bass + $do_drums + $do_vocals);
	       $y += 25;
	       if ($do_guitar) $y += 45 + 5 * STAFFHEIGHT;
	       if ($do_bass) $y += 45 + 5 * STAFFHEIGHT;
	       if ($do_drums) $y += 45 + 4 * STAFFHEIGHT;
	       if ($do_vocals) $y += 55 + 7 * (STAFFHEIGHT/2);
	       if ($num_inst > 1) $y += 15;
	   }
	   
	    $oldy = $y;
	    
	    drawBeat($im, $x, $y, $meas, $beat);
	    
	    $measScore = 0;
	    
        if ($do_vocals && $game == "RB") {
	        // draw vocals measure
            drawMeasureBackground($im, $x, $y, $meas, $events_all["vocals"], $sections, "vocals", "expert", $game);
            drawVocals($im, $x, $y, $meas, $vocals, $events_all["vocals"]);
            
            $y += 7 * (STAFFHEIGHT/2) + 55;
        }
        if ($do_guitar) {
	        // draw lead guitar measure
            drawMeasureBackground($im, $x, $y, $meas, $events_all["guitar"], $sections, "guitar", $diff, $game);
            drawMeasureNotes($im, $x, $y, $meas, $notetracks["guitar"][$diff], $game, "guitar", $diff);
            drawMeasureScores($im, $x, $y + STAFFHEIGHT*(4-($game == "GHOT")), $meas, $diff);
            
            $y += (5 - ($game == "GHOT")) * STAFFHEIGHT + 45;
            
            $measScore += 4 * $measures_all["guitar"][$index]["mscore"][$diff];
        }
        if ($do_bass && $game != "GH1") {
            // draw bass measure
            drawMeasureBackground($im, $x, $y, $measures_all["bass"][$index], $events_all["bass"], $sections, "bass", $diff, $game);
            drawMeasureNotes($im, $x, $y, $measures_all["bass"][$index], $notetracks["bass"][$diff], $game, "bass", $diff);
            drawMeasureScores($im, $x, $y + STAFFHEIGHT*(4-($game == "GHOT")), $measures_all["bass"][$index], $diff);
                                    
            $y += (5 - ($game == "GHOT")) * STAFFHEIGHT + 45;
            
            $measScore += ($game == "RB" ? 6 : 4) * $measures_all["bass"][$index]["mscore"][$diff];
        }
        if ($do_drums && $game == "RB") {
            // draw drums measure
            drawMeasureBackground($im, $x, $y, $measures_all["drums"][$index], $events_all["drums"], $sections, /*"drums"*/ "drums", $diff, $game);
            drawMeasureNotes($im, $x, $y, $measures_all["drums"][$index], $notetracks["drums"][$diff], $game, /*"drums"*/ "drums", $diff);
            drawMeasureScores($im, $x, $y + STAFFHEIGHT*3, $measures_all["drums"][$index], $diff);
    
            $y += 4 * STAFFHEIGHT + 45;
            
            $measScore += 4 * $measures_all["drums"][$index]["mscore"][$diff];
        }

        if ($num_inst > 1) imagestring($im, 4, $x + $meas["num"]*PXPERBEAT*4/$meas["denom"] - 8*strlen($measScore), $y - 22, $measScore, $black);

        $y = $oldy;
        
	   if ($x + PXPERBEAT * $meas["num"] * 4 / $meas["denom"] > WIDTH - 50) {
	       $x = 25;

	       $y += 5*DRAWPLAYERLINES;
	       //$y += 15 * ($do_guitar + $do_bass + $do_drums + $do_vocals);
	       $y += 25;
           if ($do_guitar) $y += 45 + (5-($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_bass) $y += 45 + (5-($game == "GHOT")) * STAFFHEIGHT;
	       if ($do_drums) $y += 45 + 4 * STAFFHEIGHT;
	       if ($do_vocals) $y += 55 + 7 * (STAFFHEIGHT/2);
	       if ($num_inst > 1) $y += 15;
	   }
	   else {
	       $x += PXPERBEAT * $meas["num"] * 4 / $meas["denom"];
	   }
	   
	}

	return $im;
} // makeChart

 
function drawBeat($im, $x, $y, $meas, $beat) {
    global $noteColors, $timebase;
    
    $index = 0;
    while (isset($beat[$index]) && $beat[$index]["time"] < $meas["time"]) {
        $index++;        
    }

    imagesetthickness($im, 2);
    
    while (isset($beat[$index]) && $beat[$index]["time"] < $meas["time"] + $timebase*$meas["num"] * 4 / $meas["denom"]) {
        $offset = $beat[$index]["time"] - $meas["time"];
        $offset /= $timebase;
        // this DOES NOT get the denom fix as this is purely in x/4
        $offset *= PXPERBEAT;
        $offset += ($beat[$index]["number"] == 13 ? 3 : 0);
        
        /*
        $endoffset = $beat[$index]["duration"];
        $endoffset /= $timebase;
        $endoffset *= PXPERBEAT;
        $endoffset += $offset;
        */
        
        imageline($im, $x + $offset, $y - 30, $x + $offset, $y - 38, $noteColors[1]);
        $index++;
    }

}


function drawMeasureBackground($im, $x, $y, $meas, $events, $sections, $instrument, $difficulty, $game) {
    global $timebase, $black, $APICOLORS;
    static $oldNum = array ("guitar" => 0, "bass" => 0, "drums" => 0, "vocals" => 0);
	static $oldDenom = array ("guitar" => 0, "bass" => 0, "drums" => 0, "vocals" => 0);
	static $oldBPM = array ("guitar" => 0, "bass" => 0, "drums" => 0, "vocals" => 0);

	if ($meas["number"] == 1) {
	   // new song, reset all the static variables
       /*
       $oldNum = array ("guitar" -> 0, "bass" -> 0, "drums" -> 0, "vocals" -> 0);
       $oldDenom = array ("guitar" -> 0, "bass" -> 0, "drums" -> 0, "vocals" -> 0);
       $oldBPM = array ("guitar" -> 0, "bass" -> 0, "drums" -> 0, "vocals" -> 0);
       */
       $oldNum[$instrument] = 0;
       $oldDenom[$instrument] = 0;
       $oldBPM[$instrument] = 0;
	}

	   // really freaking ugly hacks
	global $downbeatline; // if (!$downbeatline) $downbeatline = imagecolorallocate($im, 134, 134, 134);
	global $upbeatline; // if (!$upbeatline) $upbeatline = imagecolorallocate($im, 224, 224, 224);
	global $outline; if (!$outline) $outline = &$black;
	global $staffline; if (!$staffline) $staffline = &$downbeatline;
	global $tempo; if (!$tempo) $tempo = &$downbeatline;
	global $measnum; if (!$measnum) $measnum = imagecolorallocate($im, 200, 0, 0);
	global $sectionname; if (!$sectionname) $sectionname = &$outline;
	global $measscore; if (!$measscore) $measscore = &$outline;
	global $cumscore; if (!$cumscore) $cumscore = &$measnum;
	global $timesig; if (!$timesig) $timesig = &$downbeatline;
	global $player1; if (!$player1) $player1 = imagecolorallocate($im, 255, 0, 0);
	global $player2; if (!$player2) $player2 = imagecolorallocate($im, 0, 0, 255);
	global $solo; if (!$solo) $solo = imagecolorallocate($im, 134, 134, 255);
	global $bonusscore; if (!$bonusscore) $bonusscore = imagecolorallocate($im, 0, 100, 0);
	global $phrase; //if (!$phrase) $phrase = &$downbeatline;  //&$upbeatline;
	global $fill; //if (!$fill) $fill = imagecolorallocate($im, 255, 127, 0);
	global $whammy; //if (!$whammy) $whammy = imagecolorallocate($im, 0, 0, 192);
	global $activated;
	global $force_strum; if (!$force_strum) $force_strum = imagecolorallocate($im, 255,192, 203);
	global $force_hopo; if (!$force_hopo) $force_hopo = imagecolorallocate($im, 255, 253, 208);
    global $white; if (!$white) $white = imagecolorallocate($im, 255, 255, 255);


	//////////////////////
	// check for event lines in this measure
    imagesetthickness($im, 2);
    
	foreach ($events as $e) {
	   if (isset($e["difficulty"]) && $e["difficulty"] != $difficulty) continue;
	   
	   // cases to check:
	   //  wholly contained in this measure
	   //  goes through entire mesaure
	   //  starts before and ends in measure
	   //  starts in and ends after measure
	   
	   $c = 0;
	   $bY = 0;
	   
       imagesetthickness($im, 2);

	   switch ($e["type"]) {
	       case "fill":
           case "bre":
               // BRE needs more code later
	           $c = $fill;
	           $bY = $y;// - 25;
	           $beY = $y + STAFFHEIGHT*(4-($instrument == "drums")) - STAFFHEIGHT*($instrument == "vocals");
	           break;
           case "solo":
                $c = $solo;
                $bY = $y - 10;
                $beY = $y + 10 + STAFFHEIGHT*(4-($instrument == "drums")) - STAFFHEIGHT*($instrument == "vocals");
                
                // need to draw the number of notes in the solo too
                /*
                if ($e["end"] > $meas["time"] && $e["end"] <= $meas["time"] + $timebase*$meas["num"]) {
                    $tX = $e["end"] - $meas["time"];
                    $tX /= $timebase;
                    $tX *= PXPERBEAT;
                    $tX += $x + 2;
                    $tY = $y;
                    $tY -= 35;
                    imagestring($im, 2, $tX, $tY, $e["notes"] . " notes", $black);
                }
                */
               
                break;
            case "star":
                $c = $phrase;
                $bY = $y - 5;
                $beY = $y + 5 + STAFFHEIGHT*(4-($instrument == "drums")-($game == "GHOT")) - STAFFHEIGHT*($instrument=="vocals");
                 //+ 2*STAFFHEIGHT*($instrument == "vocals");
                #if ($instrument == "vocals") {
                #    $bY += 10;
                #    $beY -= 10;
                #}
                break;
            case "activation":
                $c = $activated;
                $bY = $y - 15;
                $beY = $y + 15 + STAFFHEIGHT*(4-($instrument == "drums")-($game == "GHOT")) - STAFFHEIGHT*($instrument=="vocals");
                //+ 2*STAFFHEIGHT*($instrument == "vocals");
                break;
            case "p1":
                if (DRAWPLAYERLINES) {
                    $c = $player1;
                    $bY = $y - 26;
                    $beY = $bY;
                }
                
                if ($instrument == "vocals") {
                    $c = $upbeatline;
                    $bY = $y;
                    $beY = $y + 6 * (STAFFHEIGHT/2);
                }
                break;
            case "p2":
                if (DRAWPLAYERLINES) {
                    $c = $player2;
                    $bY = $y - 30;
                    $beY = $bY;
                }
                
                if ($instrument == "vocals") {
                    $c = $upbeatline;
                    $bY = $y;
                    $beY = $y + 6 * (STAFFHEIGHT/2);
                }
                break;
            case "hopo":
                if (!defined("SHOWFORCED") || SHOWFORCED == false) break;
                $c = $force_hopo;
                $bY = $y - 20;
                $beY = $y + 20 + STAFFHEIGHT*(4-($instrument == "drums")-($game == "GHOT")) - STAFFHEIGHT*($instrument=="vocals");
                break;
            case "strum":
                if (!defined("SHOWFORCED") || SHOWFORCED == false) break;
                $c = $force_strum;
                $bY = $y - 20;
                $beY = $y + 20 + STAFFHEIGHT*(4-($instrument == "drums")-($game == "GHOT")) - STAFFHEIGHT*($instrument=="vocals");
                break;
            // API additions
            case "api-line":
                $c = $APICOLORS[$e["color"]];
                $bY = $beY = $y - $e["offset"];
                imagesetthickness($im, $e["height"]);
                break;
            case "api-fill":
                $c = $APICOLORS[$e["color"]];
                $bY = $y - $e["height"];
                $beY = $y + $e["height"] + STAFFHEIGHT*(4-($instrument == "drums")-($game == "GHOT")) - STAFFHEIGHT*($instrument=="vocals");
                break;
            case "api-text":
                // ugly hax to make the rest of the code not destroy stuff
                $c = $white;
                $bY = $beY = 0;
                break;
	   }
	   
	   if ($c == 0) continue;
	   
	   if ($e["start"] >= $meas["time"] && $e["end"] <= $meas["time"] + $timebase*$meas["num"]* 4 / $meas["denom"]) {
	       // wholly contained in this measure
	       $bX = $e["start"] - $meas["time"];
	       $bX /= $timebase;
	       #$bX *= 4 / $meas["denom"];
	       $bX *= PXPERBEAT;
	       $bX += $x;
	       $beX = $e["end"] - $meas["time"];
	       $beX /= $timebase;
	       #$beX *= 4 / $meas["denom"];
	       $beX *= PXPERBEAT;
	       $beX += $x;
	       if ($bY != $beY) {
	           imagefilledrectangle($im, $bX, $bY, $beX, $beY, $c);
	       }
	       else {
    	       imageline($im, $bX, $bY, $beX, $beY, $c);
	       }
	       
	       // draw number of notes in solo
	       // or BRE score
	       // or clock delay since last OD note
	       if ($e["type"] == "solo" || isset($e["brescore"]) || isset($e["delay"]) || $e["type"] == "api-text") {
	           $tX = $e["end"] - $meas["time"];
               $tX /= $timebase;
               #$tX *= 4 / $meas["denom"];
               $tX *= PXPERBEAT;
               $tX += $x + 2;
               $tY = $y;
               $tY -= 35;
               $tc = $black;
               $ts = 2;
               if ($e["type"] == "solo") {
                   $xyzzy = $e["notes"] . " notes";
               }
               else if ($e["type"] == "api-text") {
                   $xyzzy = $e["text"];
                   $tc = $APICOLORS[$e["color"]];
                   $ts = $e["size"];
               }
               else if (isset($e["brescore"])) {
                   $xyzzy = $e["brescore"] . " Ending Bonus";
               }
               else {
                    if (is_array($e["delay"])) {
                        $xyzzy = $e["delay"][$difficulty] . "s";
                    }
                    else {
                        $xyzzy = $e["delay"] . "s";
                    }
               }
               imagestring($im, $ts, $tX, $tY, $xyzzy, $tc);
	       }
	       if ($instrument == "vocals" && ($e["type"] == "p1" || $e["type"] == "p2")) {
	           imagesetthickness($im, 3);
	           imageline($im, $bX, $bY, $bX, $beY, $black);
	           imageline($im, $beX, $bY, $beX, $beY, $black);
	       }
       }
	   else if ($e["start"] < $meas["time"] && $e["end"] > $meas["time"] + $timebase*$meas["num"]*4 / $meas["denom"]) {
	       // goes through entire measure
	       $bX = $x;
	       $beX = $x + PXPERBEAT*$meas["num"]*4 / $meas["denom"];
	       if ($bY != $beY) {
	           imagefilledrectangle($im, $bX, $bY, $beX, $beY, $c);
	       }
	       else {
    	       imageline($im, $bX, $bY, $beX, $beY, $c);
	       }
	   }
	   else if ($e["start"] < $meas["time"] && $e["end"] >= $meas["time"] && $e["end"] <= $meas["time"] + $timebase*$meas["num"]*4 / $meas["denom"]) {
	       // starts before, ends in
	       $bX = $x;
	       $beX = $e["end"] - $meas["time"];
	       $beX /= $timebase;
	       #$beX *= 4 / $meas["denom"];
	       $beX *= PXPERBEAT;
	       $beX += $x;
	       if ($bY != $beY) {
	           imagefilledrectangle($im, $bX, $bY, $beX, $beY, $c);
	       }
	       else {
    	       imageline($im, $bX, $bY, $beX, $beY, $c);
	       }
	       
	       // draw number of notes in solo
	       // or BRE score
	       if ($e["type"] == "solo" || isset($e["brescore"]) || isset($e["delay"]) || $e["type"] == "api-text") {
	           $tX = $e["end"] - $meas["time"];
               $tX /= $timebase;
               #$tX *= 4 / $meas["denom"];
               $tX *= PXPERBEAT;
               $tX += $x + 2;
               $tY = $y;
               $tY -= 35;
               $ts = 2;
               $tc = $black;
               if ($e["type"] == "solo") {
                   $xyzzy = $e["notes"] . " notes";
               }
               else if ($e["type"] == "api-text") {
                   $xyzzy = $e["text"];
                   $tc = $APICOLORS[$e["color"]];
                   $ts = $e["size"];
               }
               else if (isset($e["brescore"])) {
                   $xyzzy = $e["brescore"] . " Ending Bonus";
               }
               else {
                    if (is_array($e["delay"])) {
                        $xyzzy = $e["delay"][$difficulty] . "s";
                    }
                    else {
                        $xyzzy = $e["delay"] . "s";
                    }
               }
               imagestring($im, $ts, $tX, $tY, $xyzzy, $tc);
	       }
	       if ($instrument == "vocals" && ($e["type"] == "p1" || $e["type"] == "p2")) {
	           imagesetthickness($im, 3);
	           imageline($im, $beX, $bY, $beX, $beY, $black);
	       }
	   }
	   else if ($e["start"] >= $meas["time"] && $e["start"] < $meas["time"] + $timebase*$meas["num"]*4 / $meas["denom"] && $e["end"] >= $meas["time"] + $timebase*$meas["num"]*4 / $meas["denom"]) {
	       // starts in, ends after
	       $bX = $e["start"] - $meas["time"];
	       $bX /= $timebase;
	       #$bX *= 4 / $meas["denom"];
	       $bX *= PXPERBEAT;
	       $bX += $x;
           $beX = $x + PXPERBEAT*$meas["num"]*4 / $meas["denom"];
	       if ($bY != $beY) {
	           imagefilledrectangle($im, $bX, $bY, $beX, $beY, $c);
	       }
	       else {
    	       imageline($im, $bX, $bY, $beX, $beY, $c);
	       }
	       if ($instrument == "vocals" && ($e["type"] == "p1" || $e["type"] == "p2")) {
	           imagesetthickness($im, 3);
	           imageline($im, $bX, $bY, $bX, $beY, $black);
	       }
	   }
	}

	
	
	// measure outline
	imagesetthickness($im, 1);
	imageline($im, $x, $y, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]), $y, $outline);
	imageline($im, $x, $y + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /*+ 2*STAFFHEIGHT*($instrument == "vocals")*/, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]), $y + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /* + 2*STAFFHEIGHT*($instrument == "vocals") */, $outline);
	imagesetthickness($im, 1);
	imageline($im, $x, $y, $x, $y + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /*+ 2*STAFFHEIGHT*($instrument == "vocals")*/, $outline);
    // right side line is drawn after the beat lines
    // because the beat lines will draw over it sometimes (mainly on not x/4 measures)
    
    	
	// beat lines
	for ($i = 0; $i < ($meas["num"] * 4 / $meas["denom"]); $i++) {
		// up beat line
		imagesetthickness($im, 1);
		imageline($im, $x + ($i * PXPERBEAT + PXPERBEAT / 2.0), $y+1, $x + ($i * PXPERBEAT + PXPERBEAT / 2), $y-1 + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /*+ 2*STAFFHEIGHT*($instrument == "vocals")*/, $upbeatline);
		
		// don't draw the down beat line for the last one
		if ($i+1 < ($meas["num"] * 4 / $meas["denom"])) {
			imagesetthickness($im, 1);
			imageline($im, $x + (($i+1) * PXPERBEAT), $y+1, $x + (($i+1) * PXPERBEAT), $y-1 + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /*+ 2*STAFFHEIGHT*($instrument == "vocals")*/, $downbeatline);
		}
		
		
		if (DRAWPULSES) imagestring($im, 2, $x+$i*PXPERBEAT, $y+6+STAFFHEIGHT*(4-($instrument=="drums")), $meas["time"] + $timebase*$i, $black);
	}
	
    // right side
	imageline($im, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]), $y, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]), $y + (STAFFHEIGHT * (4 - ($instrument == "drums")-($game == "GHOT"))) - STAFFHEIGHT*($instrument=="vocals") /*+ 2*STAFFHEIGHT*($instrument == "vocals")*/, $outline);

	
	// staff lines
	for ($i = 1; $i < 4 - ($instrument == "drums") - ($game == "GHOT") + 2*($instrument == "vocals"); $i++) {
	   imagesetthickness($im, 1);
	   imageline($im, $x+1, $y + ((STAFFHEIGHT * $i)/($instrument=="vocals"?2:1)), $x-1 + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]), $y + ((STAFFHEIGHT * $i)/($instrument=="vocals"?2:1)), $staffline);
	   
	}
	
	
	// time signature
	if ($meas["num"] != $oldNum[$instrument] || $meas["denom"] != $oldDenom[$instrument]) {
		$oldNum[$instrument] = $meas["num"];
		$oldDenom[$instrument] = $meas["denom"];
		
		imagestring($im, 5, $x+2, $y+2 - (3 * ($instrument == "drums" || $game == "GHOT")) - (3 * ($instrument == "vocals")), $oldNum[$instrument], $timesig);
		imagestring($im, 5, $x+2, $y+2 + (STAFFHEIGHT * 2) - (4 * ($instrument == "drums" || $game == "GHOT")) - (4 * ($instrument == "vocals")),
		      $oldDenom[$instrument], $timesig);
	}
	
	
	// measure number
	imagestring($im, 2, $x, $y-14, $meas["number"], $measnum);
	
	
	// section name
	if (is_array($sections)) {
        foreach ($sections as $sect) {
            if ($sect["time"] >= $meas["time"] && $sect["time"] < $meas["time"] + $timebase*$meas["num"]) {
                imagestring($im, 2, $x + PXPERBEAT, $y-14, $sect["name"], $sectionname);
            }
        }
	}	
	
	
	
	
	// tempo
	foreach ($meas["tempos"] as $bpm) {
		if (round($bpm["bpm"], BPMPRECISION) != $oldBPM[$instrument]) {
		  $bX = $bpm["time"] - $meas["time"];
		  $bX /= $timebase;
		  $bX *= 4 / $meas["denom"];
		  $bX *= PXPERBEAT;
		  $bX += $x;
		  imagefilledellipse($im, $bX+2, $y-16, 5, 5, $tempo);
		  imageline($im, $bX+4, $y-16, $bX+4, $y-22, $tempo);
		  imagestring($im, 2, $bX+6, $y-25, "=" . round($bpm["bpm"], BPMPRECISION), $tempo);
		  $oldBPM[$instrument] = round($bpm["bpm"], BPMPRECISION);
		}
	}

}



function drawMeasureScores($im, $x, $y, $meas, $diff) {
    global $black, $measnum;    
	global $measscore; if (!$measscore) $measscore = &$black;
	global $cumscore; if (!$cumscore) $cumscore = &$measnum;
	global $bonusscore; if (!$bonusscore) $bonusscore = imagecolorallocate($im, 0, 100, 0);
 
    
 
	// measure score
	imagestring($im, 2, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]) - (strlen($meas["mscore"][$diff]) * 6), $y + 2, $meas["mscore"][$diff], $measscore);
	
	
	// cumulative score
	// or, for drums, measure score outside of fills, but it doesn't really matter :)
	imagestring($im, 2, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]) - (strlen($meas["cscore"][$diff]) * 6), $y + 11, $meas["cscore"][$diff], $cumscore);
	
	
	// cumulative score with solo bonuses
	if (isset($meas["bscore"][$diff])) {
		imagestring($im, 2, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]) -
		      ((strlen($meas["cscore"][$diff]) + strlen($meas["bscore"][$diff]) + 1) * 6), $y + 11, $meas["bscore"][$diff], $bonusscore);
	}
}

	
function drawMeasureNotes($im, $x, $y, $meas, $notes, $game, $inst, $diff) {
    global $timebase, $whammy;
    static $leftovers; if (!is_array($leftovers)) $leftovers = array("guitar" => array(), "bass" => array(), "drums" => array());
    static $overwhammies; if (!is_array($overwhammies)) $overwhammies = array("guitar" => 0, "bass" => 0, "drums" => 0);
    
    if ($meas["number"] == 1) {
        #$leftovers[$inst] == array();
        $leftovers = array("guitar" => array(), "bass" => array(), "drums" => array());
        #$overwhammies[$inst] = 0;
        $overwhammies = array("guitar" => 0, "bass" => 0, "drums" => 0);
    }
    
    $newLeftovers = array();
    // take care of leftover sustains
    /*
    foreach($leftovers[$inst] as $l) {
        
        $nY = STAFFHEIGHT;
        $nY *= $l["note"];
        $nY += $y;

        $eX = $l["duration"];
        if ($eX > $meas["num"] * 4/$meas["denom"]) {
            // this sustain goes into the next measure
            $ln = count($newLeftovers);
            $newLeftovers[$ln]["note"] = $l["note"];
            $newLeftovers[$ln]["duration"] = $l["duration"] - $meas["num"] * 4/$meas["denom"];
            $newLeftovers[$ln]["phrase"] = $l["phrase"];
            $newLeftovers[$ln]["color"] = $l["color"];
            $eX = $meas["num"];
        }
        $eX *= PXPERBEAT;
        $eX += $x;
        imagesetthickness($im, 3);
        imageline($im, $x-1, $nY, $eX, $nY, $l["color"]);
    
    }
    */
    
    foreach($leftovers[$inst] as $l) {
        $nY = STAFFHEIGHT;
        $nY *= $l["note"];
        $nY += $y;
        
        $eP = $l["duration"];
        if ($eP > $meas["num"] * 4/$meas["denom"] * $timebase) {
            // this sustain goes into the next measure
            $ln = count($newLeftovers);
            $newLeftovers[$ln]["note"] = $l["note"];
            $newLeftovers[$ln]["duration"] = $l["duration"] - $meas["num"] * 4/$meas["denom"] * $timebase;
            $newLeftovers[$ln]["phrase"] = $l["phrase"];
            $newLeftovers[$ln]["color"] = $l["color"];
            $eP = $meas["num"] * 4/$meas["denom"] * $timebase;
        }

        $eX = $eP;
        $eX *= 4/$meas["denom"];
        $eX /= $timebase;
        $eX *= PXPERBEAT;
        $eX += $x;
        imagesetthickness($im, 3);
        imageline($im, $x+1, $nY, $eX, $nY, $l["color"]);
    }
    
    $leftovers[$inst] = $newLeftovers;

	
	// notes
	$whammies = $overwhammies[$inst];
	if ($overwhammies[$inst] > $meas["num"]) {
	   $overwhammies[$inst] -= $meas["num"];
	   $whammies = $meas["num"];
	}
	else {
	   $overwhammies[$inst] = 0;
	}
	
	foreach ($meas["notes"][$diff] as $nIndex => $note) {
		// draw the note
		$r = drawNote($im, $x, $y, $meas, $notes[$note], $game, ($inst == "drums"));
        // store any leftover sustains it gave us
		foreach ($r as $rr) {
		    $leftovers[$inst][] = $rr;
		}
		
		// see if this note has whammy beats
		if ($notes[$note]["phrase"] > 0 && isset($notes[$note]["duration"]) && $notes[$note]["duration"]) {
		    if ($notes[$note]["time"] + $notes[$note]["duration"] > $meas["time"] + $timebase*$meas["num"] * 4/$meas["denom"]) {
		        // this sustain goes to the next measure
		        $overwhammies[$inst] += (($notes[$note]["time"] + $notes[$note]["duration"]) - ($meas["time"] + $timebase*$meas["num"]* 4/$meas["denom"])) / $timebase;
		        $whammies += (($meas["time"] + $timebase*$meas["num"]* 4/$meas["denom"]) - $notes[$note]["time"]) / $timebase;
		    }
		    else {
                $whammies += $notes[$note]["duration"] / $timebase;
		    }
		}

	}

	
	if ($whammies > 0 || isset($meas["whammy"][$diff])) {
	   $whammies = round($whammies, 3);
	   if (isset($meas["whammy"][$diff])) $whammies = $meas["whammy"][$diff];
	   $whammies .= " " . ($game == "RB" ? "OD" : "SP");
	   imagestring($im, 2, $x + (PXPERBEAT * $meas["num"] * 4 / $meas["denom"]) - (strlen($whammies) * 6), $y + (STAFFHEIGHT*(4-($game == "GHOT"))) + 20, $whammies, $whammy);
	}
}




////////////////////////////////////////////////////

// $x and $y of the measure, this function figures out where to put inside the measure
function drawNote($im, $x, $y, $meas, $note, $game, $drums = false) {
    
    global $timebase, $NOTES, $black;
    global $noteColors;
    // for OD notes
    global $silver, $lightsilver;


    $leftovers = array();
    $color = array();
    
    $sorted = $note["note"];
    sort($sorted);
    
    foreach($sorted as $n) {
        switch ($n) {
            case 0:
                $color[] = 0;
                break;
                
            case 1:
                $color[] = 1;
                break;
                
            case 2:
                $color[] = 2;
                break;
                
            case 3:
                $color[] = 3;
                break;
                
            case 4:
                $color[] = 4;
                break;
        }
    }
    
    
    $nX = $note["time"] - $meas["time"];
    $nX /= $timebase;
    $nX *= PXPERBEAT;
    $nX += $x;
    foreach ($color as $n) {
        if (!$drums) {
            $nY = STAFFHEIGHT;
            $nY *= $n;
            $nY += $y;
            
            $drawColor = 0;
            
            if (strtolower($game) == "rb") {
                if ($note["phrase"] != 0) {
                    // OD phrase, so make it silver
                    $drawColor = $silver;
                }
                else {
                    $drawColor = $noteColors[$n];
                }
                
                imagesetthickness($im, 3);

                if (isset($note["hopo"]) && $note["hopo"]) {
                    imageline($im, $nX, $nY-3, $nX, $nY+3, $drawColor);
                    imagesetthickness($im, 1);
                    if ($note["phrase"] > 0) {
                        imageline($im, $nX, $nY-1, $nX, $nY+1, $lightsilver);
                    }
                    imageline($im, $nX-1, $nY-4, $nX+1, $nY-4, $black);
                    imageline($im, $nX-1, $nY+4, $nX+1, $nY+4, $black);
                }
                else {
                    imageline($im, $nX, $nY-4, $nX, $nY+4, $drawColor);
                    imagesetthickness($im, 1);
                    if ($note["phrase"] > 0) {
                        imageline($im, $nX, $nY-2, $nX, $nY+2, $lightsilver);
                    }
                    imageline($im, $nX-1, $nY-5, $nX+1, $nY-5, $black);
                    imageline($im, $nX-1, $nY+5, $nX+1, $nY+5, $black);
                }
                
                /*
                if (isset($note["duration"]) && $note["duration"] > 0) {
                    $eX = $note["time"] + $note["duration"] - $meas["time"];
                    $eX /= $timebase;
                    // $eX is end beat of the note w.r.t. start beat of measure
                    
                    if ($eX > $meas["num"] * 4/$meas["denom"]) {
                        // this sustain goes into the next measure
                        $l = count($leftovers);
                        $leftovers[$l]["note"] = $n;
                        $leftovers[$l]["duration"] = $eX - $meas["num"] * 4/$meas["denom"];
                        $leftovers[$l]["phrase"] = $note["phrase"];
                        $leftovers[$l]["color"] = $drawColor;
                        $eX = $meas["num"];
                    }
                    
                    $eX *= PXPERBEAT;
                    $eX += $x;
                    imagesetthickness($im, 3);
                    imageline($im, $nX+1, $nY, $eX, $nY, $drawColor);
                }
                */
                
                if (isset($note["duration"]) && $note["duration"] > 0) {
                    // end midi pulse
                    $eP = $note["time"] + $note["duration"];
                    
                    if ($eP > $meas["time"] + $meas["num"] * 4 / $meas["denom"] * $timebase) {
                        // this sustain goes into the next measure
                        $l = count($leftovers);
                        $leftovers[$l]["note"] = $n;
                        $leftovers[$l]["duration"] = $eP - ($meas["time"] + $meas["num"] * 4/$meas["denom"] * $timebase);
                        $leftovers[$l]["phrase"] = $note["phrase"];
                        $leftovers[$l]["color"] = $drawColor;
                        $eP = $meas["time"] + $meas["num"] * 4/$meas["denom"] * $timebase;
                    }
                    
                    $eX = $eP - $meas["time"];
                    #$eX *= 4/$meas["denom"];
                    $eX /= $timebase;
                    $eX *= PXPERBEAT;
                    $eX += $x;
                    imagesetthickness($im, 3);
                    imageline($im, $nX+1, $nY, $eX, $nY, $drawColor);
                    
                }
               
            }
            else {
              
                $drawColor = $noteColors[$n];
                
                // draw the sustains first so we get the entire black ring
                
                /*
                if (isset($note["duration"]) && $note["duration"] > 0) {
                    $eX = $note["time"] + $note["duration"] - $meas["time"];
                    $eX /= $timebase;
                    // $eX is end beat of the note w.r.t. start beat of measure
                    
                    if ($eX > $meas["num"]) {
                        // this sustain goes into the next measure
                        $l = count($leftovers);
                        $leftovers[$l]["note"] = $n;
                        $leftovers[$l]["duration"] = $eX - $meas["num"];
                        $leftovers[$l]["phrase"] = $note["phrase"];
                        $leftovers[$l]["color"] = $drawColor;
                        $eX = $meas["num"];
                    }
                    
                    $eX *= PXPERBEAT;
                    $eX += $x;
                    imagesetthickness($im, 3);
                    imageline($im, $nX+1, $nY, $eX, $nY, $drawColor);
                }
                */
                
                if (isset($note["duration"]) && $note["duration"] > 0) {
                    // end midi pulse
                    $eP = $note["time"] + $note["duration"];
                    
                    if ($eP > $meas["time"] + $meas["num"] * 4 / $meas["denom"] * $timebase) {
                        // this sustain goes into the next measure
                        $l = count($leftovers);
                        $leftovers[$l]["note"] = $n;
                        $leftovers[$l]["duration"] = $eP - ($meas["time"] + $meas["num"] * 4/$meas["denom"] * $timebase);
                        $leftovers[$l]["phrase"] = $note["phrase"];
                        $leftovers[$l]["color"] = $drawColor;
                        $eP = $meas["time"] + $meas["num"] * 4/$meas["denom"] * $timebase;
                    }
                    
                    $eX = $eP - $meas["time"];
                    #$eX *= 4/$meas["denom"];
                    $eX /= $timebase;
                    $eX *= PXPERBEAT;
                    $eX += $x;
                    imagesetthickness($im, 3);
                    imageline($im, $nX+1, $nY, $eX, $nY, $drawColor);
                    
                }
                
               
                if ($note["phrase"] == 0) {
                    imagesetthickness($im, 1);
                    imagefilledellipse($im, $nX, $nY, 7, 7, $drawColor);
                    imageellipse($im, $nX, $nY, 7, 7, $black);

                }
                else {
                    // note is a star note
                    
                    imagesetthickness($im, 1);
                    imagefilledellipse($im, $nX, $nY, 5, 5, $drawColor);

                    // top
                    imageline($im, $nX, $nY - 5, $nX - 2, $nY - 2, $black);
                    imageline($im, $nX, $nY - 5, $nX + 2, $nY - 2, $black);
                    
                    // right
                    imageline($im, $nX + 2, $nY - 2, $nX + 5, $nY - 2, $black);
                    imageline($im, $nX + 5, $nY - 2, $nX + 2, $nY + 1, $black);
                    
                    // bottom right
                    imageline($im, $nX + 2, $nY + 1, $nX + 2, $nY + 4, $black);
                    imageline($im, $nX + 2, $nY + 4, $nX, $nY + 2, $black);
                    
                    // bottom left
                    imageline($im, $nX, $nY + 2, $nX - 2, $nY + 4, $black);
                    imageline($im, $nX - 2, $nY + 4, $nX - 2, $nY + 1, $black);
                    
                    // left
                    imageline($im, $nX - 2, $nY + 1, $nX - 5, $nY - 2, $black);
                    imageline($im, $nX - 5, $nY - 2, $nX - 2, $nY - 2, $black);
                    
                    
                    // fix up the color fill
                    imageline($im, $nX, $nY, $nX, $nY - 4, $drawColor);
                    imageline($im, $nX, $nY - 1, $nX - 3, $nY - 1, $drawColor);
                    imageline($im, $nX, $nY - 1, $nX + 3, $nY - 1, $drawColor);
                    imageline($im, $nX - 1, $nY - 2, $nX - 1, $nY + 2, $drawColor);
                    imageline($im, $nX + 1, $nY - 2, $nX + 1, $nY + 2, $drawColor);
                }
               
               
               
               
            }
        }
        else {
            // drums
            $nY = STAFFHEIGHT;
            $nY *= ($n-1);
            $nY += $y;
            
            $drawColor = 0;
            
            if ($note["phrase"] != 0) {
                // OD phrase, so make it silver
                $drawColor = $silver;
            }
            else {
                // drums are weird.
                // green is kick, orange is green
                if ($n == 0) {
                    // green
                    $drawColor = $noteColors[4];    // kicks are drawn orange
                }
                else if ($n == 4) {
                    // orange
                    $drawColor = $noteColors[0];    // actually a green note
                }
                else {
                    // red, yellow, or blue, so leave it alone
                    $drawColor = $noteColors[$n];
                }
            } // phrase != 0
        

            imagesetthickness($im, 3);
            if ($n != 0) {
                // not a kick
                imageline($im, $nX, $nY-4, $nX, $nY+4, $drawColor);
                imagesetthickness($im, 1);
                /*
                if ($note["phrase"] > 0) {
                    imageline($im, $nX, $nY-2, $nX, $nY+2, $lightsilver);
                }
                */
                imageline($im, $nX-1, $nY-5, $nX+1, $nY-5, $black);
                imageline($im, $nX-1, $nY+5, $nX+1, $nY+5, $black);
            }
            else {
                // it's a kick...
                imageline($im, $nX, $y-1, $nX, $y+2+STAFFHEIGHT*3, $drawColor);
                imagesetthickness($im, 1);
                if ($note["phrase"] > 0) {
                    imageline($im, $nX, $y, $nX, $y+1+STAFFHEIGHT*3, $lightsilver);
                }
                imageline($im, $nX-1, $y-2, $nX+1, $y-2, $black);
                imageline($im, $nX-1, $y+3+STAFFHEIGHT*3, $nX+1, $y+3+STAFFHEIGHT*3, $black);
            }
        
            
        }
        
    }
    
    return $leftovers;
    
}


?>