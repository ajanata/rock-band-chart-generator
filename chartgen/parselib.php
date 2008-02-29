<?php

    define("DEBUG", 0);
    define("VERBOSE", 0);
    define("OMGVERBOSE", 0);
	define("PARSELIBVERSION", "0.4.7");

    header("Content-type:text/plain");

    require_once 'notevalues.php';
    require_once '../classes/midi.class.php';
    require_once 'songnames.php';


    function parseFile($file, $difficulty, $game, $instrument) {
        global $songname, $timebase, $CONFIG, $NOTES;
        $songname = "";

        $mid = new Midi;
        $mid->importMid($file);
        $timebase = $mid->getTimebase();
    
        $tracknum = 0;
        $trackname = "";
        switch ($game) {
            case "RB":
                switch ($instrument) {
                    case "GUITAR":
                        $trackname = "PART GUITAR";
                        break;
                    case "BASS":
                        $trackname = "PART BASS";
                        break;
                    case "DRUMS":
                        $trackname = "PART DRUMS";
                        break;
                    case "VOX":
                        $trackname = "PART VOCALS";
                }
                break;
            case "GH1":
                $trackname = "T1 GEMS";     // GH1 only has one instrument
                break;
            default:
                switch ($instrument) {
                    case "GUITAR":
                        $trackname = "PART GUITAR";
                        break;
                    case "BASS":
                        $trackname = "PART BASS";      // check this
                        break;
                    case "COOP":
                        $trackname = "PART GUITAR COOP";      // check this too
                        break;
                }
        }
        
        
        $eventsTrack = 0;
        for ($i = 1; $i < $mid->getTrackCount(); $i++) {
            $temp = $mid->getMsg($i, 0);
            //echo substr($temp, 16); 
            if (substr($temp, 16) == $trackname . "\"") {
                $tracknum = $i;
            }
            if (substr($temp, 16) == "EVENTS\"") {
                $eventsTrack = $i;
            }
        }
        
    //echo $mid->getTrackTxt($tracknum);
        
        // $events is start/end times for phrases, fills, and solos
        list($notetrack, $events) = filterDifficulty($mid->getTrackTxt($tracknum), $NOTES[$game][$difficulty]);
        $timetrack = parseTimeTrack($mid->getTrackTxt(0));
        $measures = makeMeasureTable($timetrack, $notetrack);
        list($measures, $notetrack) = putNotesInMeasures($measures, $notetrack);
    
        list ($measures, $events) = calcBaseScores($measures, $notetrack, $events, $CONFIG[$game], ($instrument=="DRUMS"), ($instrument=="BASS" && $game=="RB"));
        $measures = getSectionNames($measures, $mid->getTrackTxt($eventsTrack));


        return array($measures, $notetrack, $songname, $events);
    }
    
    
    
function getSectionNames($measures, $eventstrk) {
    
    $events = explode("\n", $eventstrk);
    $mIndex = 0;
    
    foreach ($events as $event) {
        $e = explode(" ", $event);
        if (isset($e[1]) && $e[1] == "Meta" && $e[2] == "Text" && $e[3] == "\"[section") {
            $section = substr($e[4], 0, strlen($e[4]) - 2);
            while ($measures[$mIndex]["time"] < $e[0]) $mIndex++;
            $measures[$mIndex]["section"] = $section;
        }
        
    }
    
    return $measures;
}
    
    

function calcBaseScores($measures, $notetrack, $events, $config, $drums = false, $goesTo6 = false) {
    global $timebase;
    $mult = 1;
    $oldmult = 1;
    $total = 0;
    $totalWithBonuses = 0;
    $streak = 0;
    $over = 0;
    $overChord = 0;
    $overScore = 0;
    $hadAFill = false;
    $fillNoteScore = 0;
    $BREscore = 0;
    
    $totalOverScore = 0;
    
    foreach ($measures as $mindex => &$meas) {
        $meas["number"] = $mindex + 1;
        $mScore = 0;
        
        
        if ($drums) {
            $total = 0;
            // base score with multiplier doesn't really mean anything with drums
            // and there aren't sustains either for rounding issues...
            
            // so just add gem_score * gem count to both scores :)
            
            for ($i = 0; $i < count($meas["notes"]); $i++) {
                $mScore += $config["gem_score"] * count($notetrack[$meas["notes"][$i]]["note"]);
                if (!$notetrack[$meas["notes"][$i]]["fill"]) {
                    $total += $config["gem_score"] * count($notetrack[$meas["notes"][$i]]["note"]);
                }
            }
        }
        // not drums
        else {
            // take care of leftovers from last measure first
            if ($over > 0) {
                
                $newover = 0;
                $newOverScore = 0;
                $newTotalOverScore = 0;
                if ($over > $meas["numerator"]) {
                    // this sustain goes through the entire measure into the next
                    $newover = $over - $meas["numerator"];
                    $newOverScore = $overScore - ($config["ticks_per_beat"] * $meas["numerator"] * $overChord);
                    $newTotalOverScore = $totalOverScore - ($config["ticks_per_beat"] * $meas["numerator"] * $overChord);
                    $overScore = $config["ticks_per_beat"] * $meas["numerator"] * $overChord;
                    $totalOverScore = $overScore;
                    $over = $meas["numerator"];
                }
                
                $mScore += $overScore;
                $total += $mult * $totalOverScore;
                $totalWithBonuses += $mult * $totalOverScore;
                
                $over = $newover;
                $overScore = $newOverScore;
                $totalOverScore = $newTotalOverScore;
            }
            
        
            for ($i = 0; $i < count($meas["notes"]); $i++) {
                $note = &$notetrack[$meas["notes"][$i]];
                if (isset($note["fill"]) && $note["fill"]) {
                    // in a fill, so the notes don't count for anything for guitar parts
                    // so uh don't do anything? :)
                    $hadAFill = true;
                    
                    $gems = $config["gem_score"] * count($note["note"]);
                    $ticks = floor($config["ticks_per_beat"] * ($note["duration"] / $timebase) + EPS);
                    $ticks *= ($config["chord_sustain_bonus"] ? count($note["note"]) : 1);
                    $fillNoteScore += $gems + $ticks;
                }
                else if ($hadAFill) {
                    // notes after the BRE count for streak but not for points
                    $streak++;
                }
                else {
                    // score the note
                    $streak++;
                    $oldmult = $mult;
                    
                    if ($streak == $config["multi"][0] || $streak == $config["multi"][1] || $streak == $config["multi"][2]) {
                        // multiplier change
                        $mult++;
                    }
                    if ($goesTo6 && ($streak == $config["multi"][3] || $streak == $config["multi"][4])) {
                        $mult++;
                    }
                    
                    $over = 0;
                    if (($note["time"] + $note["duration"]) > ($meas["time"] + $timebase*$meas["numerator"])) {
                        $over = (($note["time"] + $note["duration"]) - ($meas["time"] + $timebase*$meas["numerator"]) ) / $timebase;
                    }
                    
                    // measure score
                    
                    $gems = $config["gem_score"] * count($note["note"]);
                    $ticks = floor($config["ticks_per_beat"] * ($note["duration"] / $timebase) + EPS);
                    if ($over > 0) {
                        $mTicks = floor($ticks * ($meas["time"] + $timebase*$meas["numerator"] - $note["time"])
                                    / $note["duration"]);
                        $overScore = $ticks - $mTicks;
                        $overScore *= ($config["chord_sustain_bonus"] ? count($note["note"]) : 1);
                        $ticks = $mTicks;
                    }
                    $ticks *= ($config["chord_sustain_bonus"] ? count($note["note"]) : 1);
                    $mScore += $gems + $ticks;
                    
                                
                    // $sustain ? $chordsize * int ( 25 * ($eb-$sb) + $EPS ) : 0;
                    
                    
                    // total score
                    
                    $totalTicks = floor($config["ticks_per_beat"] * ($note["duration"] / $timebase) + 0.5 + EPS);
                    if ($over > 0) {
                        $totalMTicks = floor($totalTicks * ($meas["time"] + $timebase*$meas["numerator"] - $note["time"])
                                            / $note["duration"]);
                        $totalOverScore = $totalTicks - $totalMTicks;
                        $totalOverScore *= ($config["chord_sustain_bonus"] ? count($note["note"]) : 1);
                        $totalTicks = $totalMTicks;
                    }
                    $totalTicks *= ($config["chord_sustain_bonus"] ? count($note["note"]) : 1);
                    $total += ($oldmult * $gems) + ($oldmult * $totalTicks);
                    $totalWithBonuses += ($oldmult * $gems) + ($oldmult * $totalTicks);
                                
                    //$mult * ($sustain ? $chordsize * int ( 25 * ($eb-$sb) + 0.5 + $EPS ) : 0);
                    
                    $overChord = $config["chord_sustain_bonus"] ? count($note["note"]) : 1;
                }
            }
            // see if a solo ended this measure to add in its bonus
            // also BRE
            foreach ($events as &$e) {
                if ($e["type"] == "solo") {
                    if ($e["end"] >= $meas["time"] && $e["end"] < $meas["time"] + $timebase*$meas["numerator"]) {
                        $totalWithBonuses += $e["notes"] * 100;
                    }
                }
                else if ($e["type"] == "fill") {
                    //if ($e["end"] >= $meas["time"] && $e["end"] < $meas["time"] + $timebase*$meas["numerator"]) {
                    if (($e["start"] >= $meas["time"] && $e["start"] <= ($meas["time"] + $meas["numerator"]*$timebase))
                       || ($meas["time"] <= $e["end"] && ($meas["time"] + $meas["numerator"]*$timebase) >= $e["end"])
                       || ($e["start"] <= $meas["time"] && $e["end"] >= ($meas["time"] + $timebase*$meas["numerator"]))) {
                        
                        /*
                        $breScore = ($e["end"] - $e["start"]) / $timebase / $meas["tempos"][0]["bpm"] / 1.5 * 60 * (150*5);
                        $leftoverTime = ($e["end"] - $e["start"]) / $timebase / $meas["tempos"][0]["bpm"];
                        while ($leftoverTime > 1.5) $leftoverTime -= 1.5;
                        $breScore += $leftoverTime * (150*5);
                        $totalWithBonuses += (int)$breScore;
                        $e["brescore"] = (int)$breScore;
                        */
                        
                        $measLength = 0;
                        for ($xyzzy = 0; $xyzzy < count($meas["tempos"]); $xyzzy++) {
                            $t = $meas["tempos"][$xyzzy];
                            // start
                            $thisLength = $t["time"] - $meas["time"];
                            if ($xyzzy + 1 == count($meas["tempos"])) {
                                // this is the last tempo, so use measure end time
                                echo "$thisLength ";
                                $thisLength += ($meas["time"] + $meas["numerator"] * $timebase) - $t["time"];
                                echo "$thisLength \n";
                            }
                            else {
                                echo "case 2 $thisLength ";
                                $thisLength += $meas["tempos"][$xyzzy+1]["time"] - $t["time"];
                                echo "$thisLength \n";
                            }
                            $thisLength /= $timebase;
                            $thisLength /= $t["bpm"];
                            $measLength += $thisLength * 60;
                        }
                        
                        $BREscore += 500 * $measLength;

                            //echo "$BREscore   $measLength \n";
                        
                        $hadAFill = true;
                    }
                    if ($meas["time"] <= $e["end"] && ($meas["time"] + $meas["numerator"]*$timebase) >= $e["end"]) {
                        // last measure with the BRE
                        $totalWithBonuses += 750 + (int)$BREscore;
                    }
                }
            }
            //if (!$goesTo6) $meas["bscore"] = (int)$totalWithBonuses;
            if ($total != $totalWithBonuses) $meas["bscore"] = (int)$totalWithBonuses;
        }
        
        $meas["mscore"] = (int)$mScore;
        $meas["cscore"] = (int)$total;
        if ($fillNoteScore > 0) $meas["fillnotescore"] = $fillNoteScore;
        
    }
    
    if ($BREscore > 0) {
        foreach ($events as &$e) {
            if ($e["type"] != "fill") continue;
            $e["brescore"] = 750 + (int)$BREscore;
        }
        
    }
    
    return array($measures, $events);
}



function putNotesInMeasures($measures, $notetrack) {
    global $timebase;
    
    // $index = 0;
    
    $target = count($notetrack);
    
    $last = -1;
    
    foreach ($notetrack as $notekey => $note) {
        
        $index = 0;


        if ($notekey == "TrkEnd") continue;
        if ($notekey != (int)$notekey) continue;
        
        while (isset($measures[$index]) && is_array($measures[$index]) && $note["time"] >= $measures[$index]["time"]) {
            //echo "measures[$index+1][time] = " . $measures[$index+1]["time"] . "   note[time] = " . $note["time"] . "\n";
            $index++;
        }
        //if (is_array($measures[$index])) $index--;
        $index--;
        
        // should also put the measure number, at least, into the note
        // probably the tempo too
        $notetrack[$notekey]["measure"] = $index;
        
        for ($i = 0; $i < count($measures[$index]["tempos"]); $i++) {
            // find the tempo region we're in
            if (isset($measures[$index]["tempos"][$i+1]) && !(is_array($measures[$index]["tempos"][$i+1]))) {
                // this is the last one so we have to be in it
                //$notetrack[$j1]["tempo"] = $measures[$index]["tempos"][$i]["tempo"];
                $notetrack[$notekey]["bpm"] = $measures[$index]["tempos"][$i]["bpm"];
            }
            else {
                // there is still at least one more after this, do some checking
                if ($note["time"] >= $measures[$index]["tempos"][$i]["time"] &&
                    isset($measures[$index]["tempos"][$i+1]["time"]) &&
                    $note["time"] < $measures[$index]["tempos"][$i+1]["time"]) {
                        $notetrack[$notekey]["tempo"] = $measures[$index]["tempos"][$i]["tempo"];
                        $notetrack[$notekey]["bpm"] = $measures[$index]["tempos"][$i]["bpm"];
                }
            }
        }
        
        
        $measures[$index]["notes"][] = $notekey; // $note;
    }
    
    if (DEBUG) print_r($measures);
    
    return array($measures, $notetrack);
}


function makeMeasureTable($timetrack, $notetrack) {
    $ret = array();
    global $timebase;
    
    $measure = $curTime = 0;
    $sigIndex = $tempoIndex = -1;
    $lastTempo = $timetrack["tempos"][0];
    
    while ($curTime < $notetrack["TrkEnd"]) {
        $duration = 0;
        if (isset($timetrack["tempos"][$tempoIndex+1]) && is_array($timetrack["tempos"][$tempoIndex+1]) &&
                isset($timetrack["sigs"][$sigIndex+1]) && is_array($timetrack["sigs"][$sigIndex+1])) {
            // both of them have entries left
            if ($timetrack["sigs"][$sigIndex+1]["time"] <= $timetrack["tempos"][$tempoIndex+1]["time"]) {
                // time sig change before tempo change
                
                if (isset($timetrack["sigs"][$sigIndex+2]) &&  is_array($timetrack["sigs"][$sigIndex+2])) {
                    // still more time sig changes, so see if the next one is before the next tempo change
                    $duration = (($timetrack["sigs"][$sigIndex+2]["time"] < $timetrack["tempos"][$tempoIndex+1]["time"])
                                    ? $timetrack["sigs"][$sigIndex+2]["time"] : $timetrack["tempos"][$tempoIndex+1]["time"]) - $curTime;
                }
                else {
                    // this is the last time sig change, so the next tempo change is our end
                    $duration = $timetrack["tempos"][($tempoIndex == -1 ? 0 : $tempoIndex)+1]["time"] - $curTime;
                }
                $sigIndex++;
            }
            else {
                // tempo change before time sig change
                
                if (isset($timetrack["tempos"][$tempoIndex+2]) && is_array($timetrack["tempos"][$tempoIndex+2])) {
                    // still more tempo changes, so see if the next one is before the next time sig change
                    $duration = (($timetrack["tempos"][$tempoIndex+2]["time"] < $timetrack["sigs"][$sigIndex+1]["time"])
                                    ? $timetrack["tempos"][$tempoIndex+2]["time"] : $timetrack["sigs"][$sigIndex+1]["time"]) - $curTime;
                }
                else {
                    // this is the last tempo change, so the next time sig change is our end
                    $duration = $timetrack["sigs"][$sigIndex+1]["time"] - $curTime;
                }
                $tempoIndex++;
            }
        }
        else if (isset($timetrack["sigs"][$sigIndex+2]) && is_array($timetrack["sigs"][$sigIndex+2])) {
            $duration = $timetrack["sigs"][$sigIndex+1]["time"] - $curTime;
            $sigIndex++;
        }
        else if (isset($timetrack["tempos"][$tempoIndex+2]) && is_array($timetrack["tempos"][$tempoIndex+2])) {
            $duration = $timetrack["tempos"][$tempoIndex+2]["time"] - $curTime;
            $lastTempo = $timetrack["tempos"][$tempoIndex+1];
            $tempoIndex++;
        }
        else {
            $duration = $notetrack["TrkEnd"] - $curTime;
        }
        
        $measDur = $timebase * $timetrack["sigs"][$sigIndex]["numerator"];
        $numMeas = $duration / $measDur;
        
        $oldMeasure = $measure;
        for (; $measure < $oldMeasure + $numMeas; $measure++) {
            $ret[$measure]["time"] = $curTime;
            $ret[$measure]["numerator"] = $timetrack["sigs"][$sigIndex]["numerator"];
            $ret[$measure]["denominator"] = $timetrack["sigs"][$sigIndex]["denominator"];
            $ret[$measure]["notes"] = array();
   
            
            $measEnd = $curTime + $measDur;
            $measTempo = 0;

            if (!(isset($timetrack["tempos"][$tempoIndex+1]["time"]) && $timetrack["tempos"][$tempoIndex+1]["time"] == $curTime)) {
                // add the last tempo to this measure since there isn't a tempo change
                // at the beginning of the measure
                $ret[$measure]["tempos"][] = $lastTempo;
            }

            while (isset($timetrack["tempos"][$tempoIndex+1]) && is_array($timetrack["tempos"][$tempoIndex+1]) &&
                    $timetrack["tempos"][$tempoIndex+1]["time"] < $measEnd) {
                // add this tempo change to the measure
                $ret[$measure]["tempos"][/*$measTempo++*/] = $timetrack["tempos"][$tempoIndex+1];
                $lastTempo = $timetrack["tempos"][$tempoIndex+1];
                $tempoIndex++;
            }
            
            $curTime += $measDur;
        }
    }
        
    if (DEBUG >= 1 && VERBOSE) {
        print_r($ret);
    }
    
    return $ret;
}


function parseTimeTrack($tracktxt) {
    global $songname;
    
    $ret = array();
    $ret["sigs"] = array();
    $ret["tempos"] = array();
    $loop = 0;
    $tempoIndex = -1;
    $sigIndex = -1;
    
    
    $trk = explode("\n", $tracktxt);
    
    foreach ($trk as $line) {
        $loop++;
        $info = explode(" ", $line);

        if (!isset($info[1])) continue;        
        if ($info[1] == "Meta") {
            if ($info[2] == "TrkName") {
                preg_match('/.*\"(.*)\"$/', $line, $matches);
                $songname = $matches[1];
            }
            continue;
        }
        
        if ($info[1] != "Tempo" && $info[1] != "TimeSig") {
            continue;
        }

        
        if ($info[1] == "TimeSig") {
            $sigIndex++;
            $ret["sigs"][$sigIndex]["time"] = $info[0];
            $ret["sigs"][$sigIndex]["numerator"] = $info[2][0];
            $ret["sigs"][$sigIndex]["denominator"] = $info[2][2];
        }
        else {
            $tempoIndex++;
            $ret["tempos"][$tempoIndex]["time"] = $info[0];
            $ret["tempos"][$tempoIndex]["tempo"] = $info[2];
            $ret["tempos"][$tempoIndex]["bpm"] = round(60000000/$info[2]);
        }
        
    }
    
    if (DEBUG >= 1 && VERBOSE == 1) {
        var_dump(array_values($ret));
    }
    
    return $ret;
}


function filterDifficulty($tracktxt, $difNotes) {
    
    /* Stuff that will eventually need to be addressed:
    
    5) Valid non-sustained notes must have a corresponding note-off event. If a note endpoint is a second note-on event and the duration of the note is less than 161 pulses, the game considers the note to be an invalid note and it is ignored for all purposes (as exhibited by Cheat on the Church) 
    5) [sic] If a player section note-off event occurs more than 15 (30?) pulses prior to the endpoint of a sustained note, the sustained note is ignored by the game for all purposes, even in single player mode (as exhibited in the solo of You Got Another Thing Comin')

    */
    
    
    $notes = array();
    $events = array();
    
    $track = explode("\n", $tracktxt);
    $index = 0;
    $eventIndex = 0;
    // indexes
    $lastStar = $lastFill = $lastSolo = $lastP1 = $lastP2 = 0;
    $soloNotes = 0;
    $lastRealNote = -1;
    $SP = false;
    $SPphrase = 0;
    $p1 = false;
    $p2 = false;
    $solo = false;
    $fill = false;
    
    foreach ($track as $line) {
        if ($line == "MTrk") continue;
        $info = explode(" ", $line);
        
        if (!isset($info[1])) continue;
        if ($info[1] == "Meta") {
            if ($info[2] == "TrkEnd") {
                $notes["TrkEnd"] = (int)$info[0];
            }
            continue;
        }
        
        if (!isset($info[3]) || !isset($info[4])) continue;
        $note = (int)substr($info[3], 2);
        $vel = (int)substr($info[4], 2);
        
        // filter out stuff for the difficulty we're interested in
        // last bit is hack for RB which has it out of order
        if (($note >= $difNotes["G"] && $note <= $difNotes["P2"]) || $note == $difNotes["STAR"] 
                || ($note >= $difNotes["FILL"]["G"] && $note <= $difNotes["FILL"]["O"])
                || $note == $difNotes["SOLO"]) {
            
            // check for star power
            if ($note == $difNotes["STAR"] && ($info[1] == "On" && $vel >= 100)) {
                $SP = true;
                $SPphrase++;
                $lastStar = $eventIndex++;
                $events[$lastStar]["type"] = "star";
                $events[$lastStar]["start"] = $info[0];
                if (DEBUG == 2 && VERBOSE) echo "SP phrase $SPphrase start at " . $info[0] . "\n";

                // see if notes are already at this time
                if (arrayTimeExists($notes, $info[0], 0)) {
                    //echo $info[0] . " " . $notes[$index]["time"] . "\n";
                    $notes[$index]["phrase"] = $SPphrase;
                }
            }
            else if ($note == $difNotes["STAR"] && ($info[1] == "Off" || ($info[1] == "On" && $vel == 0))) {
                $SP = false;
                $events[$lastStar]["end"] = $info[0];
                if (DEBUG == 2 && VERBOSE) echo "SP phrase $SPphrase end at " . $info[0] . "\n";
            }
            else if (isset($difNotes["SOLO"]) && $note == $difNotes["SOLO"] && ($info[1] == "On" && $vel >= 100)) {
                // solo section (rock band)
                $solo = true;
                $soloNotes = 0;
                $lastSolo = $eventIndex++;
                $events[$lastSolo]["type"] = "solo";
                $events[$lastSolo]["start"] = $info[0];
                // see if notes are already at this time
                if (arrayTimeExists($notes, $info[0], 0)) {
                    //echo $info[0] . " " . $notes[$index]["time"] . "\n";
                    $notes[$index]["solo"] = $solo;
                    $soloNotes++;
                }
            }
            else if (isset($difNotes["SOLO"]) && $note == $difNotes["SOLO"] && ($info[1] == "Off" || ($info[1] == "On" && $vel == 0))) {
                $solo = false;
                $events[$lastSolo]["notes"] = $soloNotes;
                $events[$lastSolo]["end"] = $info[0];
            }
            // FIXME: hax
            else if (is_array($difNotes["FILL"]) && ($note == $difNotes["FILL"]["G"] /* && $note <= $difNotes["FILL"]["O"]*/)
                        && ($info[1] == "On" && $vel >= 100)) {
                            // fill section (rock band)
                            $fill = true;
                            $lastFill = $eventIndex++;
                            $events[$lastFill]["type"] = "fill";
                            $events[$lastFill]["start"] = $info[0];
            }
            else if (is_array($difNotes["FILL"]) && ($note == $difNotes["FILL"]["G"] /*&& $note <= $difNotes["FILL"]["O"]*/)
                        && ($info[1] == "Off" || ($info[1] == "On" && $vel == 0))) {
                            $fill = false;
                            $events[$lastFill]["end"] = $info[0];
            }
            else if (is_array($difNotes["FILL"]) && ($note >= $difNotes["FILL"]["R"] && $note <= $difNotes["FILL"]["O"])) {
                continue;
            }
            else { //if ($note != $difNotes["STAR"]) {
                
                // check for player1/player2 stuff
                if ($note == $difNotes["P1"] && ($info[1] == "On" && $vel >= 100)) {
                    $p1 = true;
                    $lastP1 = $eventIndex++;
                    $events[$lastP1]["type"] = "p1";
                    $events[$lastP1]["start"] = $info[0];
                    if (DEBUG == 2 && VERBOSE) echo "Player 1 on at " . $info[0] . "\n";
                }
                else if ($note == $difNotes["P1"] && ($info[1] == "Off" || ($info[1] == "On" && $vel == 0))) {
                    $p1 = false;
                    $events[$lastP1]["end"] = $info[0];
                    if (DEBUG == 2 && VERBOSE) echo "Player 1 off at " . $info[0] . "\n";
                }
                if ($note == $difNotes["P2"] && ($info[1] == "On" && $vel >= 100)) {
                    $p2 = true;
                    $lastP2 = $eventIndex++;
                    $events[$lastP2]["type"] = "p2";
                    $events[$lastP2]["start"] = $info[0];
                    if (DEBUG == 2 && VERBOSE) echo "Player 2 on at " . $info[0] . "\n";
                }
                else if ($note == $difNotes["P2"] && ($info[1] == "Off" || ($info[1] == "On" && $vel == 0))) {
                    $p2 = false;
                    $events[$lastP2]["end"] = $info[0];
                    if (DEBUG == 2 && VERBOSE) echo "Player 2 off at " . $info[0] . "\n";
                }
                else  if ($note != $difNotes["P1"] && $note != $difNotes["P2"]) {
                    
                    // see if we already have something at this time index, using the chord window for leniency
                    if (arrayTimeExists($notes, $info[0], CHORD) == false && ($info[1] == "On" && $vel >= 100)) {
                        $index++;
                        $chord = 0;
                    }
                    
                    
                    // regular note
                    if ($info[1] == "On" && $vel >= 100) {
                        if (!isset($notes[$index]["time"])) $notes[$index]["time"] = (int)$info[0];
                        $notes[$index]["phrase"] = ($SP ? $SPphrase : 0);
                        $notes[$index]["count"] = $chord;
                        $notes[$index]["note"][$chord++] = $note;
                        
                        // check to see if last note had an end event
                        // and that this is the first note in the chord
                        if ($chord == 1) {
                            if ($lastRealNote != -1 && !(isset($notes[$lastRealNote]["duration"]))) {
                                
                                if (DEBUG && VERBOSE) {
                                    echo "lastRealNote $lastRealNote index $index lastRealNote duration " . $notes[$lastRealNote]["duration"] . "\n";
                                    echo "lastRealNote time " . $notes[$lastRealNote]["time"] . " info[0] " . $info[0] . "\n";
                                }
                                
                                // no end event, make sure it's at least 161 pulses long
                                //if ($notes[$lastRealNote]["time"] + 161 >= $info[0]) {
                                if ($info[0] - $notes[$lastRealNote]["time"] <= 161) {
                                    // that last note should be ignored!
                                    if (DEBUG && VERBOSE) echo "deleting note (not really)\n";
                                    //unset($notes[$lastRealNote]);
                                }
                                else {
                                    // it's long enough to be a real note
                                    // now see if it's a sustain
                                    //if ($notes[$lastRealNote]["time"] + 240 <= $info[0]) {
                                    if ($info[0] - $notes[$lastRealNote]["time"] <= 240) {
                                        // not a sustain
                                        if (DEBUG && VERBOSE) echo "not a sustain (SHOULD NOT HAPPEN)\n";
                                        $notes[$lastRealNote]["duration"] = 0;
                                    }
                                    else {
                                        // it's a sustain until this note
                                        if (DEBUG && VERBOSE) echo "sustain\n";
                                        $notes[$lastRealNote]["duration"] = $info[0] - $notes[$lastRealNote]["time"];
                                    }
                                }
                            }
                            
                            $lastRealNote = $index;
                        }
                        
                        
                    }
                    
                    // sustain check
                    if (($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) &&
                        $info[0] > $notes[$index]["time"] + SUSTAIN
                        && is_array($notes[$index]["note"])
                        ) {
                        //&& !isset($notes[$index]["duration"])) {
                            if (isset($notes[$index]["duration"])) {
                                
                                if ($notes[$index]["duration"] > ($info[0] - $notes[$index]["time"])) {
                                    if (DEBUG && VERBOSE) echo "Changing duration of note $index from " . $notes[$index]["duration"];
                                    if (DEBUG && VERBOSE) echo " to " . ($info[0] - $notes[$index]["time"]) . "\n";
                                    $notes[$index]["duration"] = $info[0] - $notes[$index]["time"];
                                }
                            }
                            else {
                                $notes[$index]["duration"] = $info[0] - $notes[$index]["time"];
                            }
                    }
                    // make sure end events are for real notes
                    else if ($info[1] == "On" && $vel == 0 && is_array($notes[$index]["note"]) && !isset($notes[$index]["duration"])) {
                        $notes[$index]["duration"] = 0;
                    }
                    
                    // star power
                    if (is_array($notes[$index]["note"])) {
                        
                        if ($SP) {
                            $notes[$index]["phrase"] = $SPphrase;
                        }
                        else if (!isset($notes[$index]["phrase"])) {
                            $notes[$index]["phrase"] = 0;
                        }
                        
                        
                        $notes[$index]["player1"] = $p1;
                        $notes[$index]["player2"] = $p2;
                        if (isset($difNotes["SOLO"])) {
                            if ($solo && $info[1] == "On" && $vel >= 100 && count($notes[$index]["note"]) == 1) $soloNotes++;
                            //echo $soloNotes . " ";
                            $notes[$index]["solo"] = $solo;
                        }
                        
                        if (is_array($difNotes["FILL"])) {
                            $notes[$index]["fill"] = $fill;
                        }
                    }
                }
            }
        }
    }
    
    if (DEBUG == 2) {
        print_r(array_values($notes));
    }
    
    return array($notes, $events);
}


function arrayTimeExists($array, $time, $window) {
    // $window is how much tolerance we have
    if (!is_array($array)) {
        return false;
    }

    foreach ($array as $item) {
        //if (($item["time"] >= ($time - (($item["count"] + 1) * $window))) && ($item["time"] <= ($time + ($item["count"]+1) * $window))) {
        if ($item["time"] >= ($time - ($item["count"] + 1) * $window) && $item["time"] <= $time) {
            return true;
        }
    }

    return false;
}




?>