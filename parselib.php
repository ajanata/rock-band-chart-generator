<?php

define("DEBUG", 0);
define("VERBOSE", 0);
define("OMGVERBOSE", 0);
define("PARSELIBVERSION", "0.7.0");

require_once 'notevalues.php';
require_once 'classes/midi.class.php';
require_once 'songnames.php';


// returns (songname, events[guitar...vocals], timetrack, measures[guitar...drums]notes[easy...expert], notetracks[guitar...drums][easy...expert], vocals)
// measures has one or more of guitar, coop, bass, drums.
// vocals will be null if not rock band
function parseFile($file, $game) {
    global $timebase, $CONFIG, $NOTES;
    
    $songname = "";

    $mid = new Midi;
    $mid->importMid($file);
    $timebase = $mid->getTimebase();

    $game = strtoupper($game);
    
    $eventsTrack = $guitarTrack = $guitarCoopTrack = $bassTrack = $drumsTrack = $vocalsTrack = 0;
    for ($i = 1; $i < $mid->getTrackCount(); $i++) {
        $temp = $mid->getMsg($i, 0);
        //echo substr($temp, 16); 
        if (substr($temp, 16) == "PART GUITAR\"") {
            $guitarTrack = $i;
        }
        if (substr($temp, 16) == "PART GUITAR COOP\"") {
            $guitarTrack = $i;
        }
        if (substr($temp, 16) == "T1 GEMS\"") {
            $guitarTrack = $i;
        }
        if (substr($temp, 16) == "PART BASS\"") {
            $bassTrack = $i;
        }
        if (substr($temp, 16) == "PART DRUMS\"") {
            $drumsTrack = $i;
        }
        if (substr($temp, 16) == "PART VOCALS\"") {
            $vocalsTrack = $i;
        }
        if (substr($temp, 16) == "EVENTS\"") {
            $eventsTrack = $i;
        }
    }
    

    // common to all games
    list ($timetrack, $songname) = parseTimeTrack($mid->getTrackTxt(0));


    /*
        New logic:
            - get time track
            - parse into structure notes for every instrument for every difficulty
            - parse into structure events (phrases, solos, fills, player on/off)
            - lolwut vocals
            - apply events to notes
                - check for stuff that makes notes invalid
            - stick notes into measures
            - calculate scores
    
    */


    $events = array();
    $measures = array();
    $notetracks = array();
    $vocals = ($game == "RB" ? array() : null);

    switch ($game) {
        case "RB":
            $notetracks["guitar"] = parseNoteTrack($mid->getTrackTxt($guitarTrack), $NOTES[$game]);
            $notetracks["bass"] = parseNoteTrack($mid->getTrackTxt($bassTrack), $NOTES[$game]);
            $notetracks["drums"] = parseNoteTrack($mid->getTrackTxt($drumsTrack), $NOTES[$game]);

            $events["guitar"] = parsePhraseEvents($mid->getTrackTxt($guitarTrack), $NOTES[$game]);
            $events["bass"] = parsePhraseEvents($mid->getTrackTxt($bassTrack), $NOTES[$game]);
            $events["drums"] = parsePhraseEvents($mid->getTrackTxt($drumsTrack), $NOTES[$game]);
            $events["vocals"] = parsePhraseEvents($mid->getTrackTxt($vocalsTrack), $NOTES[$game]);
            
            $vocals = parseVocals($mid->getTrackTxt($vocalsTrack));

            //$notetracks["guitar"] = applyEventsToNotetrack($notetracks["guitar"], $events["guitar"]);
            //$notetracks["bass"] = applyEventsToNotetrack($notetracks["bass"], $events["bass"]);
            //$notetracks["drums"] = applyEventsToNotetrack($notetracks["drums"], $events["drums"]);
            
            list ($notetracks, $events) = applyEventsToNoteTracks($notetracks, $events);
            
            $measures = makeMeasureTable($timetrack, $vocals["TrkEnd"]);
            
            list ($measures, $notetracks) = putNotesInMeasures($measures, $notetracks);
            
            #list ($measures, $events) = calcBaseScores($measures, $notetracks, $events, $CONFIG[$game]);
            
            $events = getSectionNames($events, $mid->getTrackTxt($eventsTrack));


            break;
        case "GH1":


            break;
        default:
            // gh2 and gh80s are the same
            // gh3 should be, too, but I'm not worrying about it now
            
            $notetracks["guitar"] = parseNoteTrack($mid->getTrackTxt($guitarTrack), $NOTES[$game]);
            
            
            
            
    }



    /*
    
    // $events is start/end times for phrases, fills, and solos
    list($notetrack, $events) = filterDifficulty($mid->getTrackTxt($tracknum), $NOTES[$game][$difficulty]);
    $timetrack = parseTimeTrack($mid->getTrackTxt(0));
    $measures = makeMeasureTable($timetrack, $notetrack);
    list($measures, $notetrack) = putNotesInMeasures($measures, $notetrack);

    list ($measures, $events) = calcBaseScores($measures, $notetrack, $events, $CONFIG[$game], ($instrument=="DRUMS"), ($instrument=="BASS" && $game=="RB"));
    $measures = getSectionNames($measures, $mid->getTrackTxt($eventsTrack));
    
    */

    //return array($measures, $notetrack, $songname, $events);


// returns (songname, events[guitar...vocals], timetrack, measures[guitar...drums][easy...expert], notetracks[guitar...drums][easy...expert], vocals)

    return array($songname, $events, $timetrack, $measures, $notetracks, $vocals);
}
    
    
    
    
    
function parseTimeTrack($tracktxt) {
    $ret = array();
    $ret["sigs"] = array();
    $ret["tempos"] = array();
    $loop = 0;
    $tempoIndex = -1;
    $sigIndex = -1;
    $songname = "";
    
    
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
            $ret["sigs"][$sigIndex]["time"] = (int)$info[0];

            $num = (int)substr($info[2], 0, strpos($info[2], "/"));
            $denom = (int)substr($info[2], strpos($info[2], "/") + 1);
            $ret["sigs"][$sigIndex]["numerator"] = $num;       
            $ret["sigs"][$sigIndex]["denominator"] = $denom;

        }
        else {
            $tempoIndex++;
            $ret["tempos"][$tempoIndex]["time"] = (int)$info[0];
            $ret["tempos"][$tempoIndex]["tempo"] = (int)$info[2];
            $ret["tempos"][$tempoIndex]["bpm"] = round(60000000/$info[2]);
        }
        
    }
    
    if (DEBUG >= 1 && VERBOSE == 1) {
        var_dump(array_values($ret));
    }
    
    return array($ret, $songname);
}


function getSectionNames($events, $eventstrk) {
    
    $foo = explode("\n", $eventstrk);
    $index = 0;
    
    foreach ($foo as $event) {
        $e = explode(" ", $event);
        if (isset($e[1]) && $e[1] == "Meta" && $e[2] == "Text" && $e[3] == "\"[section") {
            $section = substr($e[4], 0, strlen($e[4]) - 2);
            
            //$events["sections"][$index]["type"] = "section";
            $events["sections"][$index]["time"] = $e[0];
            $events["sections"][$index]["name"] = $section;
            $index++;
        }
    }
    return $events;
}
    
    
    
    // TODO: This could break horribly if there's a song with different solo/star power/p1/p2 stuff for different difficulties
function parsePhraseEvents($txt, $gameNotes) {
    
    $track = explode("\n", $txt);
    $events = array();
    
    $index = $lastFill = 0;
    
    $lastStar = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $spNum = array("e" => 1, "m" => 1, "h" => 1, "x" => 1);
    $lastP1 = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastP2 = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastSolo = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);

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



        if ($note == $gameNotes["EASY"]["STAR"]) {
            // star power phrase
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "star";
                $events[$index]["start"] = $info[0];
                $events[$index]["phrase"] = $spNum["e"]++;
                $events[$index]["difficulty"] = "easy";
                $lastStar["e"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastStar["e"]]["end"] = $info[0];
            }
        } // star easy
        

        if ($note == $gameNotes["MEDIUM"]["STAR"]) {
            // star power phrase
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "star";
                $events[$index]["start"] = $info[0];
                $events[$index]["phrase"] = $spNum["m"]++;
                $events[$index]["difficulty"] = "medium";
                $lastStar["m"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastStar["m"]]["end"] = $info[0];
            }
        } // star medium
        
        
        if ($note == $gameNotes["HARD"]["STAR"]) {
            // star power phrase
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "star";
                $events[$index]["start"] = $info[0];
                $events[$index]["phrase"] = $spNum["h"]++;
                $events[$index]["difficulty"] = "hard";
                $lastStar["h"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastStar["h"]]["end"] = $info[0];
            }
        } // star hard
        
        
        if ($note == $gameNotes["EXPERT"]["STAR"]) {
            // star power phrase
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "star";
                $events[$index]["start"] = $info[0];
                $events[$index]["phrase"] = $spNum["x"]++;
                $events[$index]["difficulty"] = "expert";
                $lastStar["x"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastStar["x"]]["end"] = $info[0];
            }
        } // star expert

        
        /////// solo
        
        if ($note == $gameNotes["EASY"]["SOLO"]) {
            
            // solo
            if ($info[1] == "On" && $vel > 0) {
                // start
                $events[$index]["type"] = "solo";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "easy";
                $events[$index]["notes"] = -1;
                $lastSolo["e"] = $index++;
                
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // end
                $events[$lastSolo["e"]]["end"] = $info[0];
                
            }
        } // solo easy
        

        if ($note == $gameNotes["MEDIUM"]["SOLO"]) {
            
            // solo
            if ($info[1] == "On" && $vel > 0) {
                // start
                $events[$index]["type"] = "solo";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "medium";
                $events[$index]["notes"] = -1;
                $lastSolo["m"] = $index++;
                
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // end
                $events[$lastSolo["m"]]["end"] = $info[0];
                
            }
        } // solo medium
        
   
        if ($note == $gameNotes["HARD"]["SOLO"]) {
            
            // solo
            if ($info[1] == "On" && $vel > 0) {
                // start
                $events[$index]["type"] = "solo";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "hard";
                $events[$index]["notes"] = -1;
                $lastSolo["h"] = $index++;
                
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // end
                $events[$lastSolo["h"]]["end"] = $info[0];
                
            }
        } // solo hard
        
       
        if ($note == $gameNotes["EXPERT"]["SOLO"]) {
            
            // solo
            if ($info[1] == "On" && $vel > 0) {
                // start
                $events[$index]["type"] = "solo";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "expert";
                $events[$index]["notes"] = -1;
                $lastSolo["x"] = $index++;
                
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // end
                $events[$lastSolo["x"]]["end"] = $info[0];
                
            }
        } // solo expert
        
         
        // TODO: look at the other fill notes
        // note: by definition, all difficulties have the same fill notes
        if ($note == $gameNotes["EASY"]["FILL"]["G"]) {
           
            if ($info[1] == "On" && $vel > 0) {
                // start
                $events[$index]["type"] = "fill";
                $events[$index]["start"] = $info[0];
                $lastFill = $index++;
               
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // end
                $events[$lastFill]["end"] = $info[0];
               
            }
        } // fill
        
        
        
        ///////////////// player 1

        if ($note == $gameNotes["EASY"]["P1"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p1";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "easy";
                $lastP1["e"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP1["e"]]["end"] = $info[0];
            }
        } // p1 easy
        

        if ($note == $gameNotes["MEDIUM"]["P1"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p1";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "medium";
                $lastP1["m"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP1["m"]]["end"] = $info[0];
            }
        } // p1 medium
        
        
        if ($note == $gameNotes["HARD"]["P1"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p1";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "hard";
                $lastP1["h"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP1["h"]]["end"] = $info[0];
            }
        } // p1 hard
        
        
        if ($note == $gameNotes["EXPERT"]["P1"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p1";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "expert";
                $lastP1["x"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP1["x"]]["end"] = $info[0];
            }
        } // p1 expert

        
        ///////////////// player 2

        if ($note == $gameNotes["EASY"]["P2"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p2";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "easy";
                $lastP2["e"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP2["e"]]["end"] = $info[0];
            }
        } // p2 easy
        

        if ($note == $gameNotes["MEDIUM"]["P2"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p2";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "medium";
                $lastP2["m"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP2["m"]]["end"] = $info[0];
            }
        } // p2 medium
        
        
        if ($note == $gameNotes["HARD"]["P2"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p2";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "hard";
                $lastP2["h"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP2["h"]]["end"] = $info[0];
            }
        } // p2 hard
        
        
        if ($note == $gameNotes["EXPERT"]["P2"]) {
            if ($info[1] == "On" && $vel > 0) {
                // phrase start
                $events[$index]["type"] = "p2";
                $events[$index]["start"] = $info[0];
                $events[$index]["difficulty"] = "expert";
                $lastP2["x"] = $index++;
            }
            else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                // phrase end
                $events[$lastP2["x"]]["end"] = $info[0];
            }
        } // p2 expert

    } // foreach
    
    return $events;
}
    
    
    
function parseVocals($txt) {
    
    $track = explode("\n", $txt);
    $vox = array();
    $index = $lastIndex = 0;
    
    
    foreach ($track as $line) {
        if ($line == "MTrk") continue;
        $info = explode(" ", $line);
        
        if (!isset($info[1])) continue;
        
        if ($info[1] == "Meta" && $info[2] == "Text" && strpos($info[3], "[") !== false) continue;
        
        if ($info[1] == "Meta" && ($info[2] == "Lyric" || ($info[2] == "Text" && strpos($info[3], "[") === false))) {
            $lyric = str_replace("\"", "", $info[3]);
            // !== as in !(===) as in not identical
            //$talky = (strpos($lyric, "#") !== false);
            if (strpos($lyric, "#") !== false || strpos($lyric, "^") !== false || strpos($lyric, "*") !== false) {
                $talky = true;
            }
            else {
                $talky = false;
            }
            
            $i = arrayTimeExists($vox, $info[0], 0);
            if ($i === false) {
                $i = $index++;
            }
            
            #$lyric = str_replace("#", "", $lyric);
            #$lyric = str_replace("^", "", $lyric);
            #$lyric = str_replace("*", "", $lyric);
            
            $vox[$i]["lyric"] = $lyric;
            $vox[$i]["talky"] = $talky;
            $vox[$i]["time"] = (int)$info[0];

            continue;
        }

        if ($info[1] == "Meta") {
            if ($info[2] == "TrkEnd") {
                $vox["TrkEnd"] = (int)$info[0];
            }
            continue;
        }

        if (!isset($info[3]) || !isset($info[4])) continue;
        $note = (int)substr($info[3], 2);
        $vel = (int)substr($info[4], 2);
        
        if ($info[1] == "On" && $vel > 0) {
            // note start
            if ($note == 96) {
                // percussion note
                // there will not be a lyric here
                $vox[$index]["time"] = (int)$info[0];
                $vox[$index++]["percussion"] = true;
            }
            else if (($note >= 36) && ($note <= 95)) {
                // pitch
                $i = arrayTimeExists($vox, $info[0], 0);
                if ($i === false) {
                    $i = $index++;
                }
                
                $vox[$i]["time"] = (int)$info[0];
                $vox[$i]["pitch"] = $note;
                $vox[$i]["velocity"] = $vel;
                $lastIndex = $i;
            
            }
        } // on note
        
        
        if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
            // note end
            if ($note == 96) {
                // we don't care about percussion off events
            }
            else if (($note >= 36) && ($note <= 95)) {
                // pitch
                $vox[$lastIndex]["duration"] = (int)$info[0] - $vox[$lastIndex]["time"];
            }
        } // off note
        
    } // foreach
    
    return $vox;
}


function makeMeasureTable($timetrack, $trkend) {
    $ret = array();
    global $timebase;
    
    $measure = $curTime = 0;
    $sigIndex = $tempoIndex = -1;
    $lastTempo = $timetrack["tempos"][0];
    
    while ($curTime < $trkend) {
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
            $duration = $trkend - $curTime;
        }
        
        $measDur = $timebase * $timetrack["sigs"][$sigIndex]["numerator"];
        $numMeas = $duration / $measDur;
        
        $oldMeasure = $measure;
        for (; $measure < $oldMeasure + $numMeas; $measure++) {
            $ret[$measure]["number"] = $measure + 1;
            $ret[$measure]["time"] = $curTime;
            $ret[$measure]["numerator"] = $timetrack["sigs"][$sigIndex]["numerator"];
            $ret[$measure]["denominator"] = $timetrack["sigs"][$sigIndex]["denominator"];
            $ret[$measure]["notes"] = array();
            $ret[$measure]["notes"]["easy"] = array();
            $ret[$measure]["notes"]["medium"] = array();
            $ret[$measure]["notes"]["hard"] = array();
            $ret[$measure]["notes"]["expert"] = array();
   
            
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
    
    return array("guitar" => $ret, "bass" => $ret, "drums" => $ret);
} // makeMeasureTable


function parseNoteTrack($txt, $gameNotes) {
    
    /* Stuff that will eventually need to be addressed:
    
    5) Valid non-sustained notes must have a corresponding note-off event. If a note endpoint is a second note-on event and the duration of the note is less than 161 pulses, the game considers the note to be an invalid note and it is ignored for all purposes (as exhibited by Cheat on the Church) 
    5) [sic] If a player section note-off event occurs more than 15 (30?) pulses prior to the endpoint of a sustained note, the sustained note is ignored by the game for all purposes, even in single player mode (as exhibited in the solo of You Got Another Thing Comin')

    */

    
    $ret["easy"] = array();
    $ret["medium"] = array();
    $ret["hard"] = array();
    $ret["expert"] = array();

    $track = explode("\n", $txt);
    $events = array();
    
    $index = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastRealNote = array("e" => -1, "m" => -1, "h" => -1, "x" => -1);
    $chord = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);

    foreach ($track as $line) {
        if ($line == "MTrk") continue;
        $info = explode(" ", $line);
        
        if (!isset($info[1])) continue;
        if ($info[1] == "Meta") {
            if ($info[2] == "TrkEnd") {
                $ret["TrkEnd"] = (int)$info[0];
                $ret["easy"]["TrkEnd"] = (int)$info[0];
                $ret["medium"]["TrkEnd"] = (int)$info[0];
                $ret["hard"]["TrkEnd"] = (int)$info[0];
                $ret["expert"]["TrkEnd"] = (int)$info[0];
            }
            continue;
        }
        
        if (!isset($info[3]) || !isset($info[4])) continue;
        $note = (int)substr($info[3], 2);
        $vel = (int)substr($info[4], 2);
        
        switch($note) {
            case $gameNotes["EASY"]["G"]:
            case $gameNotes["EASY"]["R"]:
            case $gameNotes["EASY"]["Y"]:
            case $gameNotes["EASY"]["B"]:
            case $gameNotes["EASY"]["O"]:
                // easy
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes, $ret["easy"], $chord["e"], $index["e"], $lastRealNote["e"]);
                //($time, $type, $note, $vel, $gameNotes, &$notetrack, &$chord, &$index, &$lastRealNote)
                
                break;
                
            case $gameNotes["MEDIUM"]["G"]:
            case $gameNotes["MEDIUM"]["R"]:
            case $gameNotes["MEDIUM"]["Y"]:
            case $gameNotes["MEDIUM"]["B"]:
            case $gameNotes["MEDIUM"]["O"]:
                // medium
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes, $ret["medium"], $chord["m"], $index["m"], $lastRealNote["m"]);                
                
                break;
                
            case $gameNotes["HARD"]["G"]:
            case $gameNotes["HARD"]["R"]:
            case $gameNotes["HARD"]["Y"]:
            case $gameNotes["HARD"]["B"]:
            case $gameNotes["HARD"]["O"]:
                // hard
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes, $ret["hard"], $chord["h"], $index["h"], $lastRealNote["h"]);                
                
                break;
            
            case $gameNotes["EXPERT"]["G"]:
            case $gameNotes["EXPERT"]["R"]:
            case $gameNotes["EXPERT"]["Y"]:
            case $gameNotes["EXPERT"]["B"]:
            case $gameNotes["EXPERT"]["O"]:
                // expert
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes, $ret["expert"], $chord["x"], $index["x"], $lastRealNote["x"]);
                
                break;
        } // switch note
    } // foreach
    
    return $ret;
}   // parseNoteTrack


function dealWithNote($time, $type, $note, $vel, $gameNotes, &$notetrack, &$chord, &$index, &$lastRealNote) {

    // check for a chord
    if (arrayTimeExists($notetrack, $time, CHORD) === false && ($type == "On" && $vel > 0)) {
        $index++;
        $chord = 0;
    }
    
    // regular note
    if ($type== "On" && $vel > 0) {
        if (!isset($notetrack[$index]["time"])) $notetrack[$index]["time"] = $time;

        $notetrack[$index]["count"] = $chord;
        $notetrack[$index]["note"][$chord++] = noteValToCanonical($note, $gameNotes);
        $notetrack[$index]["phrase"] = 0;
        
        if ($chord == 1) {
            if ($lastRealNote != -1 && !isset($notetrack[$lastRealNote]["duration"])) {
                // no end event, make sure it's at least 161 pulses long
                if ($info[0] - $notetrack[$lastRealNote]["time"] <= 161) {
                    // that last note should be ignored!
                    //unset($notetrack[$lastRealNote]);
                }
                else {
                    // it's long enough to be a real note
                    // now see if it's a sustain
                    if ($time - $notetrack[$lastRealNote]["time"] <= 240) {
                        // not a sustain
                        $notetrack[$lastRealNote]["duration"] = 0;
                    }
                    else {
                        // it's a sustain until this note
                        $notetrack[$lastRealNote]["duration"] = $time - $notetrack[$lastRealNote]["time"];
                    }
                }
            } // last note didn't have an end event
            $lastRealNote = $index;
        } // chord == 1
    } // regular note on
    
    // sustain check
    if (($type == "Off" || ($type == "On" && $vel == 0))
        && $time > $notetrack[$index]["time"] + SUSTAIN
        && is_array($notetrack[$index]["note"])
        ) {
            if (isset($notetrack[$index]["duration"])) {
                if ($notetrack[$index]["duration"] > ($time - $notetrack[$index]["time"])) {
                    if (VERBOSE) echo "Changing duration of note " . $index . " from " . $notetrack[$index]["duration"];
                    if (VERBOSE) echo " to " . ($time - $notetrack[$index]["time"]) . "\n";
                    
                    $notetrack[$index]["duration"] = $time - $notetrack[$index]["time"];
                }
            }
            else {
                $notetrack[$index]["duration"] = $time - $notetrack[$index]["time"];
            }
    }
    
    // make sure end events are for real notes
    else if ((($type == "On" && $vel == 0) || $type == "Off") && isset($notetrack[$index]["note"])
        && is_array($notetrack[$index]["note"]) && !isset($notetrack[$index]["duration"])) {
            $notetrack[$index]["duration"] = 0;
    }
    
}


function getClockTimeBetweenPulses(&$timetrack, $start, $end) {
    global $timebase;
    
    if ($end < $start) {
        $temp = $end;
        $end = $start;
        $start = $end;
    }
    
    $clockTime = 0;
    
    foreach ($timetrack["tempos"] as $index => $timeevent) {
        #if ($timeevent["time"] < $start) continue;
        if ($timeevent["time"] > $end) continue;
        
        $duration = 0;
        
        if (isset($timetrack["tempos"][$index+1])) {
            // there is another tempo change after this one, see what its time is
            if ($end > $timetrack["tempos"][$index+1]["time"]) {
                // the next event is still in the range we want
                $x = $timeevent["time"];
                if ($timeevent["time"] < $start) $x = $start;
                $duration = $timetrack["tempos"][$index+1]["time"] - $x;
                if ($timeevent["time"] + $duration < $start) continue;
            }
            else {
                // the range we want ends with the current tempo
                $x = $timeevent["time"];
                if ($timeevent["time"] < $start) $x = $start;
                $duration = $end - $x;
            }
        }
        else {
            // this is the last tempo event
            $duration = $end - $timeevent["time"];
        }
        
        // we now have $duration pulses at this tempo
        $thisClockTime = ($duration / $timebase) / ($timeevent["bpm"] / 60);
        $clockTime += $thisClockTime;
        
    }
    return $clockTime;
    
}


function noteValToCanonical($note, $gameNotes) {

    switch ($note) {
        case $gameNotes["EASY"]["G"]:
        case $gameNotes["MEDIUM"]["G"]:
        case $gameNotes["HARD"]["G"]:
        case $gameNotes["EXPERT"]["G"]:
            return 0;
        
        case $gameNotes["EASY"]["R"]:
        case $gameNotes["MEDIUM"]["R"]:
        case $gameNotes["HARD"]["R"]:
        case $gameNotes["EXPERT"]["R"]:
            return 1;
        
        case $gameNotes["EASY"]["Y"]:
        case $gameNotes["MEDIUM"]["Y"]:
        case $gameNotes["HARD"]["Y"]:
        case $gameNotes["EXPERT"]["Y"]:
            return 2;
        
        case $gameNotes["EASY"]["B"]:
        case $gameNotes["MEDIUM"]["B"]:
        case $gameNotes["HARD"]["B"]:
        case $gameNotes["EXPERT"]["B"]:
            return 3;
        
        case $gameNotes["EASY"]["O"]:
        case $gameNotes["MEDIUM"]["O"]:
        case $gameNotes["HARD"]["O"]:
        case $gameNotes["EXPERT"]["O"]:
            return 4;
        
        default:
            return -1;
    }
        
} // noteValToName


function arrayTimeExists(&$array, $time, $window) {
    // $window is how much tolerance we have
    if (!is_array($array)) {
        return false;
    }

    // binary search could help this :)
    
    foreach ($array as $index => $item) {
        if ($item["time"] >= ($time - ((isset($item["count"]) ? $item["count"] : 0) + 1) * $window) && $item["time"] <= $time) {
            return $index;
        }
    }

    return false;
}




function putNotesInMeasures($measures, $notetracks) {
    global $timebase;
    
    foreach ($notetracks as $instrument => &$insttrack) {
        
        if ($instrument != "guitar" && $instrument != "bass" && $instrument != "drums") continue;
        
        foreach ($insttrack as $difficulty => &$notetrack) {
    
            if ($difficulty == "TrkEnd") continue;
                
            $target = count($notetrack);
            
            $last = -1;
            
            foreach ($notetrack as $notekey => &$note) {
                
                $index = 0;
        
                if ($notekey == "TrkEnd") continue;
                if ($notekey != (int)$notekey) continue;
                
                while (isset($measures[$instrument][$index]) && is_array($measures[$instrument][$index])
                    && $note["time"] >= $measures[$instrument][$index]["time"]) {
                        $index++;
                }
                $index--;
                
                // should also put the measure number, at least, into the note
                // probably the tempo too
                $notetrack[$notekey]["measure"] = $index;
                
                for ($i = 0; $i < count($measures[$instrument][$index]["tempos"]); $i++) {
                    // find the tempo region we're in
                    if (isset($measures[$instrument][$index]["tempos"][$i+1]) && !(is_array($measures[$instrument][$index]["tempos"][$i+1]))) {
                        // this is the last one so we have to be in it
                        $notetrack[$instrument][$notekey]["bpm"] = $measures[$instrument][$index]["tempos"][$i]["bpm"];
                    }
                    else {
                        // there is still at least one more after this, do some checking
                        if ($note["time"] >= $measures[$instrument][$index]["tempos"][$i]["time"] &&
                            isset($measures[$instrument][$index]["tempos"][$i+1]["time"]) &&
                            $note["time"] < $measures[$instrument][$index]["tempos"][$i+1]["time"]) {
                                $notetrack[$notekey]["tempo"] = $measures[$instrument][$index]["tempos"][$i]["tempo"];
                                $notetrack[$notekey]["bpm"] = $measures[$instrument][$index]["tempos"][$i]["bpm"];
                        }
                    }
                }
                
                
                $measures[$instrument][$index]["notes"][$difficulty][] = $notekey;
            } // notetrack as note
        } // difftrack as notetrack
    } // notetracks as difftrack
    
    if (DEBUG) print_r($measures);
    
    return array($measures, $notetracks);
} // putNotesInMeasures



function applyEventsToNotetracks($notetracks, $events) {
    $foundBRE = false;
    $breAt = 0;
    
    foreach ($events as $inst => &$instevents) {
        #echo "doing $inst \n";
        
        foreach ($instevents as $eventIndex => &$event) {
            // TODO handle the other events
            // for now we just care about phrases
            
            if ($event["type"] == "star") {
                #echo "found a star event for " . $event["difficulty"] . "\n";
                
                $noteIndex = findFirstThingAtTime($notetracks[$inst][$event["difficulty"]], $event["start"]);
                if ($noteIndex === false) continue;
                
                #echo "first note at $noteIndex \n";
                
                // we have the first note in this event
                while ($notetracks[$inst][$event["difficulty"]][$noteIndex]["time"] < $event["end"]) {
                    $notetracks[$inst][$event["difficulty"]][$noteIndex]["phrase"] = $event["phrase"];
                    $noteIndex++;
                }
                
                #echo "last note before $noteIndex \n";
                
                // now we're pointing to the note after the last note in the phrase
                $event["last_note"] = $noteIndex - 1;
            } // star event
            
            // fills on guitar or bass are BREs
            if ($event["type"] == "fill" && ($inst == "guitar" || $inst == "bass")) {
                $foundBRE = true;
                $breAt = $event["start"];
                $event["type"] = "bre";
            } // guitar/bass fill
            
            if ($event["type"] == "fill" && $foundBRE && $inst == "drums" && $event["start"] == $breAt) {
                // this fill is a BRE
                $event["type"] = "bre";
            } // drum BRE
            
        }
    }
    
    return array($notetracks, $events);
}



function findFirstThingAtTime(&$haystack, $time) {
    if (isset($haystack[0])) $index = 0;
    else $index = 1;
    
    while ($haystack[$index]["time"] < $time) {
        if (isset($haystack[$index+1])) $index++;
        else return false;
    }
    return $index;
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
                                //echo "$thisLength ";
                                $thisLength += ($meas["time"] + $meas["numerator"] * $timebase) - $t["time"];
                                //echo "$thisLength \n";
                            }
                            else {
                                //echo "case 2 $thisLength ";
                                $thisLength += $meas["tempos"][$xyzzy+1]["time"] - $t["time"];
                                //echo "$thisLength \n";
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
        $meas["streak"] = $streak;
        if ($fillNoteScore > 0) $meas["fillnotescore"] = $fillNoteScore;
        
    }
    
    // disabled BRE score for now because it's horribly broken
    /*
    if ($BREscore > 0) {
        foreach ($events as &$e) {
            if ($e["type"] != "fill") continue;
            $e["brescore"] = 750 + (int)$BREscore;
        }
        
    }
    */
    
    return array($measures, $events);
}






    





?>