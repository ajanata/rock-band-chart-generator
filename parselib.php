<?php

define("DEBUG", 0);
define("VERBOSE", 0);
define("OMGVERBOSE", 0);

require_once 'notevalues.php';
require_once 'hopothresholds.php';
require_once 'classes/midi.class.php';
require_once 'songnames.php';


// returns (songname, events[guitar...vocals], timetrack, measures[guitar...drums]notes[easy...expert], notetracks[guitar...drums][easy...expert], vocals, beat, harm1, harm2, harm3)
// measures has one or more of guitar, coop, bass, drums.
// vocals will be null if not rock band
function parseFile($file, $game, $ignoreCache = false) {
    global $timebase, $CONFIG, $NOTES, $HOPOS;
    global $CACHED;
    $CACHED = false;
    
    if (!$ignoreCache && file_exists($file . ".parsecache")) {
        $CACHED = true;
        $cache = fopen($file . ".parsecache", 'rb');
        $stat = fstat($cache);
        $serialized = gzuncompress(fread($cache, $stat["size"]));
        fclose($cache);
        list ($timebase, $unserialized) = unserialize($serialized);
        return $unserialized;
    }
    
    
    $songname = "";

    $mid = new Midi;
    $mid->importMid($file);
    $timebase = $mid->getTimebase();

    $game = strtoupper($game);
    
    $eventsTrack = $guitarTrack = $guitarCoopTrack = $bassTrack = $drumsTrack = $vocalsTrack = $beatTrack = 0;
    $harm1Track = $harm2Track = $harm3Track = 0;
    for ($i = 1; $i < $mid->getTrackCount(); $i++) {
        $temp = $mid->getMsg($i, 0);
        #echo substr($temp, 16) . "\n";
       if (substr($temp, 16) == "PART GUITAR\"") {
       // if (substr($temp, 16) == "PART KEYS\"") {
            $guitarTrack = $i;
        }
        if (substr($temp, 16) == "PART GUITAR COOP\"") {
            $guitarCoopTrack = $i;
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
        if (substr($temp, 16) == "PART HARM1\"" || substr($temp, 16) =="HARM1\"") {
            $harm1Track = $i;
        }
        if (substr($temp, 16) == "PART HARM2\"" || substr($temp, 16) =="HARM2\"") {
            $harm2Track = $i;
        }
        if (substr($temp, 16) == "PART HARM3\"" || substr($temp, 16) =="HARM3\"") {
            $harm3Track = $i;
        }
        if (substr($temp, 16) == "EVENTS\"") {
            $eventsTrack = $i;
        }
        if (substr($temp, 16) == "BEAT\"") {
            $beatTrack = $i;
        }
        // required by N.I.B.
        if (substr($temp, 16) == "BEATS\"") {
            $beatTrack = $i;
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
    $beat = array();
    $vocals = (($game == "RB" || $game == "TBRB") ? array() : null);

    switch ($game) {
        case "TBRB":
        case "RB":
            $hopoThreshold = $CONFIG["RB"]["hopo_threshold"];
            if (isset($HOPOS["RB"][$songname])) $hopoThreshold = $HOPOS["RB"][$songname];
        
            $notetracks["guitar"] = parseNoteTrack($mid->getTrackTxt($guitarTrack), $NOTES["RB"], $hopoThreshold);
            $notetracks["bass"] = parseNoteTrack($mid->getTrackTxt($bassTrack), $NOTES["RB"], $hopoThreshold);
            $notetracks["drums"] = parseNoteTrack($mid->getTrackTxt($drumsTrack), $NOTES["RB"], 0);

            $events["guitar"] = parsePhraseEvents($mid->getTrackTxt($guitarTrack), $NOTES["RB"]);
            $events["bass"] = parsePhraseEvents($mid->getTrackTxt($bassTrack), $NOTES["RB"]);
            $events["drums"] = parsePhraseEvents($mid->getTrackTxt($drumsTrack), $NOTES["RB"]);
            $events["vocals"] = parsePhraseEvents($mid->getTrackTxt($vocalsTrack), $NOTES["RB"], true);
            
            $vocals = parseVocals($mid->getTrackTxt($vocalsTrack));
            $harm1 = $harm2 = $harm3 = null;
            if ($game == "TBRB") {
                if ($harm1Track != 0) {
                    $harm1 = parseVocals($mid->getTrackTxt($harm1Track));
                    $events["harm1"] = parsePhraseEvents($mid->getTrackTxt($harm1Track), $NOTES["RB"], true);
                }
                if ($harm2Track != 0) {
                    $harm2 = parseVocals($mid->getTrackTxt($harm2Track));
                    $events["harm2"] = parsePhraseEvents($mid->getTrackTxt($harm2Track), $NOTES["RB"], true);
                }
                if ($harm3Track != 0) {
                    $harm3 = parseVocals($mid->getTrackTxt($harm3Track));
                    $events["harm3"] = parsePhraseEvents($mid->getTrackTxt($harm3Track), $NOTES["RB"], true);
                }
            }

            list ($notetracks, $events) = applyEventsToNoteTracks($notetracks, $events, $timetrack);
            
            $beat = parseBeat($mid->getTrackTxt($beatTrack));

            $events["vocals"] = fixVocalEvents($vocals, $events["vocals"], $timetrack);

            if ($game == "TBRB") {
                if ($harm1Track != 0) {
                    $events["harm1"] = fixVocalEvents($harm1, $events["harm1"], $timetrack);
                }
                if ($harm2Track != 0) {
                    $events["harm2"] = fixVocalEvents($harm2, $events["harm2"], $timetrack);
                }
                if ($harm3Track != 0) {
                    $events["harm3"] = fixVocalEvents($harm3, $events["harm3"], $timetrack);
                }
            }
            
            $endOfSong = max($notetracks["guitar"]["TrkEnd"], $notetracks["bass"]["TrkEnd"], $notetracks["drums"]["TrkEnd"],
                                $vocals["TrkEnd"]);
            
            $measures = makeMeasureTable($timetrack, $endOfSong);
            
            list ($measures, $notetracks) = putNotesInMeasures($measures, $notetracks);
            
            $measures = calcScores($measures, $notetracks, $events, $CONFIG[$game], strtolower($game), $songname);
            
            $events = getSectionNames($events, $mid->getTrackTxt($eventsTrack));


            break;
        case "GH1":
            
            #$notetracks["guitar"] = parseNoteTrack($mid->getTrackTxt($guitarTrack), $NOTES[$game]);
            #$events["guitar"] = parsePhraseEvents($mid->getTrackTxt($guitarTrack), $NOTES[$game]);
        

            break;
            
        default:
            // gh2 and gh80s are the same
            // also ghot
            // gh3 should be, too, but I'm not worrying about it now
            
            $notetracks["guitar"] = ($guitarTrack > 0 ? parseNoteTrack($mid->getTrackTxt($guitarTrack), $NOTES[$game], 170) : null);
            $notetracks["bass"] = ($bassTrack > 0 ? parseNoteTrack($mid->getTrackTxt($bassTrack), $NOTES[$game], 170) : null);

            $events["guitar"] = ($guitarTrack > 0 ? parsePhraseEvents($mid->getTrackTxt($guitarTrack), $NOTES[$game]) : null);
            $events["bass"] = ($bassTrack > 0 ? parsePhraseEvents($mid->getTrackTxt($bassTrack), $NOTES[$game]) : null);
            
            list ($notetracks, $events) = applyEventsToNoteTracks($notetracks, $events, $timetrack);
            
            $measures = makeMeasureTable($timetrack, $notetracks["guitar"]["TrkEnd"]);
            
            list ($measures, $notetracks) = putNotesInMeasures($measures, $notetracks);
            
            $measures = calcScores($measures, $notetracks, $events, $CONFIG[$game], strtolower($game));
            
            $events = getSectionNames($events, $mid->getTrackTxt($eventsTrack));

            
    }


// returns (songname, events[guitar...vocals], timetrack, measures[guitar...drums][easy...expert], notetracks[guitar...drums][easy...expert], vocals)
// stick beat at the end of that

    if (!$ignoreCache) {
        $cache = fopen($file . ".parsecache", 'wb');
        if ($cache) {
            fwrite($cache, gzcompress(serialize(array($timebase, array($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat, $harm1, $harm2, $harm3)))));
        }
        fclose($cache);
    }

    return array($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat, $harm1, $harm2, $harm3);
}


function fixVocalEvents($vox, $events, &$timetrack) {
    // first: go over all the phrase events and make sure things that start at the same time, end at the same time
    // I have seen cases where this is not the case, and the longer ones are correct.
    // keep doing it until we don't make any changes
    $changed = true;
    while ($changed) {
        $changed = false;
        $lastStart = $lastEnd = $lastIndex = 0;
        for ($i = 0; $i < count($events); $i++) {
            if ($events[$i]["start"] > $lastStart) {
                $lastStart = $events[$i]["start"];
                $lastEnd = $events[$i]["end"];
                $lastIndex = $i;
            }
            else if ($events[$i]["start"] == $lastStart) {
                if ($events[$i]["end"] < $lastEnd) {
                    $events[$i]["end"] = $lastEnd;
                    $changed = true;
                }
                else if ($events[$i]["end"] > $lastEnd) {
                    $lastEnd = $events[$i]["end"];
                    $events[$lastIndex]["end"] = $lastEnd;
                    $changed = true;
                }
            }
        }
    }
    
    // now, for each event, find the note (vocal or percussion) that ends right before the phrase ends
    // and snap the phrase end to that
    // we should never have to go backwards in the list of notes
    $voxIndex = 0;
    foreach ($events as $eIndex => &$e) {
      #echo "$eIndex ";
        while ($vox[$voxIndex]["time"] < $e["end"]) {
            $voxIndex++;
            // don't go past the end!
            if (!isset($vox[$voxIndex])) break;
        }
        // go back to the last one that was less than
        $voxIndex--;
        if (!isset($vox[$voxIndex]["percussion"]) || !$vox[$voxIndex]["percussion"]) {
            $e["end"] = $vox[$voxIndex]["time"] + (isset($vox[$voxIndex]["duration"]) ? $vox[$voxIndex]["duration"] : 0);
        }
        $e["percussion"] = isset($vox[$voxIndex]["percussion"]) && $vox[$voxIndex]["percussion"];
    }
    
    // do it again, but for the first note and the start time
    // but still need to draw with the original data, so we only store it in a temporary array here
    $fixedStart = array();
    $voxIndex = 0;
    foreach ($events as $eIndex => &$e) {
        while ($vox[$voxIndex]["time"] < $e["start"]) {
            $voxIndex++;
            if (!isset($vox[$voxIndex])) break;
        }
        $fixedStart[$eIndex] = $vox[$voxIndex]["time"];
    }

    // now that the phrases are cleaned up, we can actually figure out where activation zones are
    
    $lastChecked = 0;
    // we need to store this here because we end up adding stuff to the array later, and we don't want to loop over that
    $endAt = count($events);

    for ($i = 0; $i < $endAt-1; $i++) {
        if ($events[$i]["end"] > $lastChecked) {
            $lastChecked = $events[$i]["end"];
            $compareTo = 0;
            $checkEvent = $i + 1;
            while ($compareTo < $events[$i]["end"] && $i < $endAt && $checkEvent < $endAt) {
                $compareTo = $fixedStart[$checkEvent];
                $checkEvent++;
                if (!isset($events[$checkEvent])) break 2;
            }
            $checkEvent--;
            if ($i >= $endAt || $checkEvent >= $endAt) break;
            $size = getClockTimeBetweenPulses($timetrack, $events[$i]["end"], $compareTo);
            if ($size >= VOCAL_FILL_WINDOW && !($events[$i]["percussion"] && $events[$checkEvent]["percussion"])) {
                $j = count($events);
                $events[$j]["type"] = "fill";
                $events[$j]["delay"] = round($size, 3);
                $events[$j]["start"] = $events[$i]["end"] + 1;
                $events[$j]["end"] = $events[$checkEvent]["start"] - 1;
            }
            else {
                // we just want to show the time
                $j = count($events);
                $events[$j]["type"] = "fill";
                $events[$j]["delay"] = round($size, 3);
                $events[$j]["start"] = $events[$checkEvent]["start"] - 1;
                $events[$j]["end"] = $events[$checkEvent]["start"] - 1;
            }
        }
    }
    $j = count($events);
    $events[$j]["type"] = "fill";
    $events[$j]["start"] = $events[$endAt - 1]["end"] + 1;
    $events[$j]["end"] = $vox["TrkEnd"];

    return $events;
}


function parseBeat($txt) {
    $ret = array();
    $trk = explode("\n", $txt);
    $index = 0;
    
    foreach ($trk as $line) {
        $info = explode(" ", $line);
        if (!isset($info[1])) continue;
        if ($info[1] == "Meta") continue;
        
        if (!isset($info[3]) || !isset($info[4])) continue;
        $note = (int)substr($info[3], 2);
        $vel = (int)substr($info[4], 2);


        if ($info[1] == "On" && $vel > 0) {
            // beat on
            $ret[$index] = array();
            $ret[$index]["time"] = (int) $info[0];
            $ret[$index]["number"] = $note;
        }
        else if (($info[1] == "On" && $vel == 0) || $info[1] == "Off") {
            $ret[$index]["duration"] = $info[0] - $ret[$index]["time"];
            $index++;
        }
    }
    
    return $ret;    
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
            $ret["sigs"][$sigIndex]["num"] = $num;       
            $ret["sigs"][$sigIndex]["denom"] = $denom;

        }
        else {
            $tempoIndex++;
            $ret["tempos"][$tempoIndex]["time"] = (int)$info[0];
            $ret["tempos"][$tempoIndex]["tempo"] = (int)$info[2];
            //$ret["tempos"][$tempoIndex]["bpm"] = round(60000000/$info[2]);
            $ret["tempos"][$tempoIndex]["bpm"] = (double)(60000000/$info[2]);
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
function parsePhraseEvents($txt, $gameNotes, $isVox = false) {
    
    $track = explode("\n", $txt);
    $events = array();
    
    $index = $lastFill = 0;
    
    $lastStar = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $spNum = array("e" => 1, "m" => 1, "h" => 1, "x" => 1);
    $lastP1 = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastP2 = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastSolo = array("e" => 0, "m" => 0, "h" => 0, "x" => 0);
    $lastStrum = array("e"=> 0, "m" => 0, "h" => 0, "x" => 0); 
    $lastHopo = array("e"=> 0, "m" => 0, "h" => 0, "x" => 0);

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
        if (!$isVox) {
        
            if (isset($gameNotes["EASY"]["SOLO"]) && $note == $gameNotes["EASY"]["SOLO"]) {
                
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
            
    
            if (isset($gameNotes["MEDIUM"]["SOLO"]) && $note == $gameNotes["MEDIUM"]["SOLO"]) {
                
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
            
       
            if (isset($gameNotes["HARD"]["SOLO"]) && $note == $gameNotes["HARD"]["SOLO"]) {
                
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
            
           
            if (isset($gameNotes["EXPERT"]["SOLO"]) && $note == $gameNotes["EXPERT"]["SOLO"]) {
                
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
            if (isset($gameNotes["EASY"]["FILL"]["G"]) && $note == $gameNotes["EASY"]["FILL"]["G"]) {
               
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
        } // !isVox
        
        
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


        if (!$isVox) {
            // forced hopos
            if (isset($gameNotes["EXPERT"]["HOPO"]) && $note == $gameNotes["EXPERT"]["HOPO"]) {
                
                // hopo expert
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "hopo";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "expert";
                    $lastHopo["x"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["x"]]["end"] = $info[0];
                    
                }
            } // hopo expert
    
            if (isset($gameNotes["HARD"]["HOPO"]) && $note == $gameNotes["HARD"]["HOPO"]) {
                
                // hopo hard
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "hopo";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "hard";
                    $lastHopo["h"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["h"]]["end"] = $info[0];
                    
                }
            } // hopo hard
    
    
            if (isset($gameNotes["MEDIUM"]["HOPO"]) && $note == $gameNotes["MEDIUM"]["HOPO"]) {
                
                // hopo medium
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "hopo";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "medium";
                    $lastHopo["m"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["m"]]["end"] = $info[0];
                    
                }
            } // hopo medium
            
            if (isset($gameNotes["EASY"]["HOPO"]) && $note == $gameNotes["EASY"]["HOPO"]) {
                
                // hopo expert
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "hopo";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "easy";
                    $lastHopo["e"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["e"]]["end"] = $info[0];
                    
                }
            } // hopo easy
    
            // forced strums
            if (isset($gameNotes["EXPERT"]["STRUM"]) && $note == $gameNotes["EXPERT"]["STRUM"]) {
                
                // strum expert
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "strum";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "expert";
                    $lastHopo["x"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["x"]]["end"] = $info[0];
                    
                }
            } // strum expert
    
            if (isset($gameNotes["HARD"]["STRUM"]) && $note == $gameNotes["HARD"]["STRUM"]) {
                
                // strum hard
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "strum";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "hard";
                    $lastHopo["h"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["h"]]["end"] = $info[0];
                    
                }
            } // strum hard
    
    
            if (isset($gameNotes["MEDIUM"]["STRUM"]) && $note == $gameNotes["MEDIUM"]["STRUM"]) {
                
                // strum medium
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "strum";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "medium";
                    $lastHopo["m"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["m"]]["end"] = $info[0];
                    
                }
            } // strum medium
            
            if (isset($gameNotes["EASY"]["STRUM"]) && $note == $gameNotes["EASY"]["STRUM"]) {
                
                // strum expert
                if ($info[1] == "On" && $vel > 0) {
                    // start
                    $events[$index]["type"] = "strum";
                    $events[$index]["start"] = $info[0];
                    $events[$index]["difficulty"] = "easy";
                    $lastHopo["e"] = $index++;
                    
                }
                else if ($info[1] == "Off" || ($info[1] == "On" && $vel == 0)) {
                    // end
                    $events[$lastHopo["e"]]["end"] = $info[0];
                    
                }
            } // strum easy
        } // !isVox

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
    
    $measure = $curTime = $sigIndex = $tempoIndex = 0;
    
    // first we make all of the measures. don't care about anything but getting the time sig and such right
    while ($curTime < $trkend) {
        // in theory this will always be set
        $num = $timetrack["sigs"][$sigIndex]["num"];
        $denom = $timetrack["sigs"][$sigIndex]["denom"];
        
        $measDur = $timebase * $num * 4 / $denom;
        
        $end = (isset($timetrack["sigs"][$sigIndex+1]) ? $timetrack["sigs"][$sigIndex+1]["time"] : $trkend);
        while ($curTime < $end) {
            $ret[$measure] = array();
            $ret[$measure]["number"] = $measure + 1;
            $ret[$measure]["time"] = $curTime;
            $ret[$measure]["duration"] = $measDur;
            $ret[$measure]["num"] = $num;
            $ret[$measure]["denom"] = $denom;
            $ret[$measure]["notes"] = array();
            $ret[$measure]["notes"]["easy"] = array();
            $ret[$measure]["notes"]["medium"] = array();
            $ret[$measure]["notes"]["hard"] = array();
            $ret[$measure]["notes"]["expert"] = array();
            $ret[$measure]["tempos"] = array();
            
            $measure++;
            $curTime += $measDur;
        }
        $sigIndex++;   
    }
    
    // now that we have the measures properly made we can worry about the tempos
    $lastTempo = $timetrack["tempos"][0];
    foreach ($ret as &$meas) {            
        if (!(isset($timetrack["tempos"][$tempoIndex+1]["time"])
            && $timetrack["tempos"][$tempoIndex+1]["time"] == $meas["time"])) {
                // add the last tempo to this measure since there isn't a tempo change
                // at the beginning of the measure
                $meas["tempos"][] = $lastTempo;
        }

        while (isset($timetrack["tempos"][$tempoIndex+1]) && is_array($timetrack["tempos"][$tempoIndex+1]) &&
                $timetrack["tempos"][$tempoIndex+1]["time"] < $meas["time"] + $meas["duration"]) {
            // add this tempo change to the measure
            $meas["tempos"][/*$measTempo++*/] = $timetrack["tempos"][$tempoIndex+1];
            $lastTempo = $timetrack["tempos"][$tempoIndex+1];
            $tempoIndex++;
        }
    }
    
    return array("guitar" => $ret, "bass" => $ret, "drums" => $ret);
}


function parseNoteTrack($txt, $gameNotes, $hopoThreshold) {
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
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes,
                        $hopoThreshold, $ret["easy"], $chord["e"], $index["e"], $lastRealNote["e"]);
                //($time, $type, $note, $vel, $gameNotes, &$notetrack, &$chord, &$index, &$lastRealNote)
                
                break;
                
            case $gameNotes["MEDIUM"]["G"]:
            case $gameNotes["MEDIUM"]["R"]:
            case $gameNotes["MEDIUM"]["Y"]:
            case $gameNotes["MEDIUM"]["B"]:
            case $gameNotes["MEDIUM"]["O"]:
                // medium
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes,
                        $hopoThreshold, $ret["medium"], $chord["m"], $index["m"], $lastRealNote["m"]);                
                
                break;
                
            case $gameNotes["HARD"]["G"]:
            case $gameNotes["HARD"]["R"]:
            case $gameNotes["HARD"]["Y"]:
            case $gameNotes["HARD"]["B"]:
            case $gameNotes["HARD"]["O"]:
                // hard
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes,
                        $hopoThreshold, $ret["hard"], $chord["h"], $index["h"], $lastRealNote["h"]);                
                
                break;
            
            case $gameNotes["EXPERT"]["G"]:
            case $gameNotes["EXPERT"]["R"]:
            case $gameNotes["EXPERT"]["Y"]:
            case $gameNotes["EXPERT"]["B"]:
            case $gameNotes["EXPERT"]["O"]:
                // expert
                dealWithNote((int)$info[0], $info[1], $note, $vel, $gameNotes,
                        $hopoThreshold, $ret["expert"], $chord["x"], $index["x"], $lastRealNote["x"]);
                
                break;
        } // switch note
    } // foreach
    
    return $ret;
}   // parseNoteTrack


// hopoThreshold MUST be set to something. either take it from the config for the game, or a per-song value
function dealWithNote($time, $type, $note, $vel, $gameNotes, $hopoThreshold, &$notetrack, &$chord, &$index, &$lastRealNote) {

    // check for a chord
    if (arrayTimeExists($notetrack, $time, CHORD) === false && ($type == "On" && $vel > 0)) {
        $index++;
        $chord = 0;
    }
    
    // regular note
    if ($type == "On" && $vel > 0) {
        if (!isset($notetrack[$index]["time"])) $notetrack[$index]["time"] = $time;

        $notetrack[$index]["count"] = $chord;
        $notetrack[$index]["note"][$chord++] = noteValToCanonical($note, $gameNotes);
        $notetrack[$index]["phrase"] = 0;
        
        // handle hopos
        if ($chord > 1) {
            // chords are never auto-hopoed!
            // override what we may have set previously
            $notetrack[$index]["hopo"] = false;
        }
        else if (isset($notetrack[$lastRealNote])
            && ($notetrack[$index]["time"] - $notetrack[$lastRealNote]["time"] <= $hopoThreshold)) {
                // check each of the notes in the last note
                // a note in the last chord cannot be hopoed,
                // but other notes can be
                $notetrack[$index]["hopo"] = true;
                foreach ($notetrack[$lastRealNote]["note"] as $checkNote) {
                    if ($checkNote == $notetrack[$index]["note"][0]) {
                        $notetrack[$index]["hopo"] = false;
                    }
                }
            #&& ($notetrack[$index]["note"][0] != $notetrack[$lastRealNote]["note"][0])) {

        }
        
        if ($chord == 1) {
            // make sure the previous note has a duration set
            if ($lastRealNote != -1 && !isset($notetrack[$lastRealNote]["duration"])) {
                // no end event, make sure it's at least 161 pulses long
                if ($time - $notetrack[$lastRealNote]["time"] <= 161) {
                    // that last note should be ignored!
                    //unset($notetrack[$lastRealNote]);
                }
                else {
                    // it's long enough to be a real note
                    // now see if it's a sustain
                    // isn't this supposed to be SUSTAIN == 160?
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

    $tempoIndex = 1;
    // find the first tempo change after the start
    while (isset($timetrack["tempos"][$tempoIndex])
           && $timetrack["tempos"][$tempoIndex]["time"] < $start) {
                $tempoIndex++;
    }
    
    // back up to the one before the start
    // this should always be >= 1 after that loop
    $tempoIndex--;
    
    // end of the current calculation
    $curEnd = 0;

    // start of current calculation
    $curStart = $start;

    while ($curEnd < $end) {
        if (isset($timetrack["tempos"][$tempoIndex+1])
           && $timetrack["tempos"][$tempoIndex+1]["time"] < $end) {
                $curEnd = $timetrack["tempos"][$tempoIndex+1]["time"];# - 1;
        }
        else {
            $curEnd = $end;
        }
        
        #$clockTime += (($curEnd - $curStart) / $timebase) / ($timetrack["tempos"][$tempoIndex]["bpm"] / 60);
        $beats = ($curEnd - $curStart) / $timebase;
        $timePerBeat = 60 / $timetrack["tempos"][$tempoIndex]["bpm"];
        $time = $beats * $timePerBeat;
        $clockTime += $time;
        
        $tempoIndex++;
        if (isset($timetrack["tempos"][$tempoIndex])) {
            $curStart = $timetrack["tempos"][$tempoIndex]["time"];
        }
        else {
            break;
        }
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
    
            if ($difficulty == "TrkEnd" || $difficulty == "") continue;
                
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



function applyEventsToNotetracks($notetracks, $events, &$timetrack) {
    $foundBRE = false;
    $breAt = 0;
    
    foreach ($events as $inst => &$instevents) {
        $phraseEnd = array("easy" => 0, "medium" => 0, "hard" => 0, "expert" => 0);
        
        foreach ($instevents as $eventIndex => &$event) {
            // TODO handle P1/P2 events?
            
            if ($event["type"] == "star") {
                $noteIndex = findFirstThingAtTime($notetracks[$inst][$event["difficulty"]], $event["start"]);
                if ($noteIndex === false) continue;
                
                // we have the first note in this event
                while ($notetracks[$inst][$event["difficulty"]][$noteIndex]["time"] < $event["end"]) {
                    $notetracks[$inst][$event["difficulty"]][$noteIndex]["phrase"] = $event["phrase"];
                    $noteIndex++;
                }
                
                // now we're pointing to the note after the last note in the phrase
                $event["last_note"] = $noteIndex - 1;
                
                // store when the last note is for fill detection later
                $phraseEnd[$event["difficulty"]] = $notetracks[$inst][$event["difficulty"]][$noteIndex - 1]["time"];
            } // star event


            if ($event["type"] == "hopo") {
                $noteIndex = findFirstThingAtTime($notetracks[$inst][$event["difficulty"]], $event["start"]);
                if ($noteIndex === false) continue;
                
                // we have the first note in this event
                while ($notetracks[$inst][$event["difficulty"]][$noteIndex]["time"] < $event["end"]
                    && isset($notetracks[$inst][$event["difficulty"]][$noteIndex])) {
                        $notetracks[$inst][$event["difficulty"]][$noteIndex]["hopo"] = true;
                        $noteIndex++;
                }
            } // hopo event


            if ($event["type"] == "strum") {
                $noteIndex = findFirstThingAtTime($notetracks[$inst][$event["difficulty"]], $event["start"]);
                if ($noteIndex === false) continue;
                
                // we have the first note in this event
                while ($notetracks[$inst][$event["difficulty"]][$noteIndex]["time"] < $event["end"]
                    && isset($notetracks[$inst][$event["difficulty"]][$noteIndex])) {
                        $notetracks[$inst][$event["difficulty"]][$noteIndex]["hopo"] = false;
                        $noteIndex++;
                }
            } // strum event

            
            // fills on guitar or bass are BREs
            else if ($event["type"] == "fill" && ($inst == "guitar" || $inst == "bass")) {
                $foundBRE = true;
                $breAt = $event["start"];
                $event["type"] = "bre";
                $breScore = getClockTimeBetweenPulses($timetrack, $event["start"], $event["end"]);
                $breScore *= 500;
                $breScore += 750;
                $event["brescore"] = (int) $breScore;
                
                foreach (array("easy", "medium", "hard", "expert") as $margush) {
                    $breNotes = 0;
                    
                    $noteIndex = findFirstThingAtTime($notetracks[$inst][$margush], $event["start"]);
                    if ($noteIndex === false) continue;
                    
                    // we have the first note in this event
                    while ($notetracks[$inst][$margush][$noteIndex]["time"] < $event["end"]) {
                        $notetracks[$inst][$margush][$noteIndex]["fill"] = true;
                        $noteIndex++;
                        $breNotes++;
                    }
                    
                    // now we're pointing to the note after the last note in the fill
                    $event["last_note"] = $noteIndex - 1;
                    $event["notes"] = $breNotes;
                }
            } // guitar/bass fill
            
            else if ($event["type"] == "fill" && $foundBRE && $inst == "drums" && $event["start"] == $breAt) {
                // this fill is a BRE
                $event["type"] = "bre";
                $breScore = getClockTimeBetweenPulses($timetrack, $event["start"], $event["end"]);
                $breScore *= 500;
                $breScore += 750;
                $event["brescore"] = (int) $breScore;
            } // drum BRE
            
            // normal drum fill
            else if ($event["type"] == "fill" && $inst == "drums") {
                foreach (array("easy", "medium", "hard", "expert") as $margush) {
                    $fillNotes = 0;
                    
                    $noteIndex = findFirstThingAtTime($notetracks[$inst][$margush], $event["start"]);
                    if ($noteIndex === false) continue;
                    
                    // we have the first note in this event
                    while (isset($notetracks[$inst][$margush][$noteIndex])
                        && $notetracks[$inst][$margush][$noteIndex]["time"] < $event["end"]) {
                            $notetracks[$inst][$margush][$noteIndex]["fill"] = true;
                            $noteIndex++;
                            $fillNotes++;
                    }
                    
                    // now we're pointing to the note after the last note in the fill
                    $event["last_note"] = $noteIndex - 1;
                    $event["notes"] = $fillNotes;
                    
                    $event["delay"][$margush] = round(getClockTimeBetweenPulses($timetrack, $phraseEnd[$margush], $event["start"]), 3);
                }
            } // drum activation fill

            else if ($event["type"] == "solo") {
                $soloNotes = 0;
                
                $noteIndex = findFirstThingAtTime($notetracks[$inst][$event["difficulty"]], $event["start"]);
                if ($noteIndex === false) continue;

                // we have the first note in this event
                while (isset($notetracks[$inst][$event["difficulty"]][$noteIndex])
                    && $notetracks[$inst][$event["difficulty"]][$noteIndex]["time"] < $event["end"]) {
                        $notetracks[$inst][$event["difficulty"]][$noteIndex]["solo"] = true;
                        $noteIndex++;
                        if ($inst == "drums") {
                            $soloNotes += count($notetracks[$inst][$event["difficulty"]][$noteIndex]["note"]);
                        }
                        else {
                            $soloNotes++;
                        }
                }
                
                // now we're pointing to the note after the last note in the fill
                $event["last_note"] = $noteIndex - 1;
                $event["notes"] = $soloNotes;
            } // solo
            
        }
    }
    
    return array($notetracks, $events);
} // applyEventsToNotetracks


// this needs re-done to be a binary search, not a linear search
function findFirstThingAtTime(&$haystack, $time, $key = "time") {
    if (isset($haystack[0])) $index = 0;
    else $index = 1;
    
    while ($haystack[$index][$key] < $time) {
        if (isset($haystack[$index+1])) $index++;
        else return false;
    }
    return $index;
}


 
# $measures = calcScores($measures, $notetracks, $events, $CONFIG[$game]);
function calcScores($measures, $notetracks, $events, $config, $game, $songname = "") {
    
    global $timebase;
    
    $bre = null;
    foreach ($events["guitar"] as $e) {
        if ($e["type"] == "bre") {
            $bre = $e;
        }
    }
    if ($bre === null) {
        $bre = array();
        $bre["type"] = "bre";
        $bre["brescore"] = 0;
        $bre["start"] = 99999999999;
        $bre["end"] = 99999999999;
        $bre["last_note"] = 99999999;
        $bre["notes"] = 0;
    }
    
    foreach (array("easy", "medium", "hard", "expert") as $diff) {
        foreach ($measures as $inst => &$insttrack) {
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
            
            
            foreach ($insttrack as $mindex => &$meas) {
                $mScore = 0;
                
                if ($inst == "drums") {
                    // drums are much simpler so get them out of the way first
                    // no sustains, and base score with multiplier doesn't mean much of anything
                    // ... it of course matters for cutoffs
                    // just add gem_score * gem_count to both scores :)
                    
                    $total = 0;
                    
                    foreach ($meas["notes"][$diff] as $note) {
                        if ($notetracks[$inst][$diff][$note]["time"] < $bre["start"]) {
                            // note is before the BRE, add it to the note count streak
                            // NOTE THAT this isn't an FC streak on drums since it's impossible
                            // to hit every single note due to fills
                            // but this is still needed for the base score for cutoffs
                            $streak += count($notetracks[$inst][$diff][$note]["note"]);
                        }
                        
                        $mScore += $config["gem_score"] * count($notetracks[$inst][$diff][$note]["note"]);
                        
                        if (!isset($notetracks[$inst][$diff][$note]["fill"]) || !$notetracks[$inst][$diff][$note]["fill"]) {
                            $total += $config["gem_score"] * count($notetracks[$inst][$diff][$note]["note"]);
                        }
                        #$meas["streak"][$diff] = $streak;
                    }
                }
                // not drums
                else {
                    // take care of leftovers from last measure first
                    if ($over > 0) {
                        $newOver = 0;
                        $newOverScore = 0;
                        $newTotalOverScore = 0;
                        if ($over > $meas["num"]) {
                            // this sustain goes through the entire measure into the next
                            $newOver = $over - $meas["num"];
                            $newOverScore = $overScore - ($config["ticks_per_beat"] * $meas["num"] * $overChord);
                            $newTotalOverScore = $totalOverScore - ($config["ticks_per_beat"] * $meas["num"] * $overChord);
                            $overScore = $config["ticks_per_beat"] * $meas["num"] * $overChord;
                            $totalOverScore = $overScore;
                            $over = $meas["num"];
                        }
                        
                        $mScore += $overScore;
                        $total += $mult * $totalOverScore;
                        $totalWithBonuses += $mult * $totalOverScore;
                        
                        $over = $newOver;
                        $overScore = $newOverScore;
                        $totalOverScore = $newTotalOverScore;
                    } // over > 0
                    
                    foreach ($meas["notes"][$diff] as $note) {
                        $n = $notetracks[$inst][$diff][$note];
                        
                        if (isset($n["fill"]) && $n["fill"]) {
                            // in a fill, we know we're doing a guitar part, the notes don't count for anything
                            $hadAFill = true;
                            
                            $gems = $config["gem_score"]  * count($n["note"]);
                            $ticks = floor($config["ticks_per_beat"] * ($n["duration"] / $timebase) + EPS);
                            $ticks *= ($config["chord_sustain_bonus"] ? count($n["note"]) : 1);

                            $fillNoteScore += $gems + $ticks;
                        }
                        if ($n["time"] > $bre["end"]) {
                            // this note is after the BRE, same as the $hadAFill case
                            $hadAFill = true;
                        }
                        else if ($hadAFill) {
                            // notes after the BRE acount for streak but not for points
                            // actually they count for % but not for streak
                            // so we don't do anything here.
                            //$streak++;
                        }
                        else {
                            // normal note so score it
                            $streak++;
                            $oldmult = $mult;
                            
                            if ($streak == $config["multi"][0] || $streak == $config["multi"][1] || $streak == $config["multi"][2]) {
                                // multiplier change
                                $mult++;
                            }
                            if (($game == "rb" || $game == "tbrb") && $inst == "bass" && ($streak == $config["multi"][3] || $streak == $config["multi"][4])) {
                                $mult++;
                            }
                            
                            $over = 0;
                            if (($n["time"] + $n["duration"]) > ($meas["time"] + $timebase*$meas["num"])) {
                                $over = (($n["time"] + $n["duration"]) - ($meas["time"] + $timebase*$meas["num"])) / $timebase;
                            }
                            
                            // measure score
                            
                            $gems = $config["gem_score"] * count($n["note"]);
                            if (!isset($n["duration"])) {
                                // this really indicates an issues with the note parsing
                                #echo "\n\nNOTICE: Found note with unset duration in $songname $diff $inst $streak $note \n\n";
                                $n["duration"] = 0;
                            }
                            $ticks = floor($config["ticks_per_beat"] * ($n["duration"] / $timebase) + EPS);
                            
                            if ($over > 0) {
                                $mTicks = floor($ticks * ($meas["time"] + $timebase*$meas["num"] - $n["time"]) / $n["duration"]);
                                $overScore = $ticks - $mTicks;
                                $overScore *= ($config["chord_sustain_bonus"] ? count($n["note"]) : 1);
                                $ticks = $mTicks;
                            }

                            $ticks *= ($config["chord_sustain_bonus"] ? count($n["note"]) : 1);
                            $mScore += $gems + $ticks;
                            
                            
                            // total score
                            
                            $totalTicks = floor($config["ticks_per_beat"] * ($n["duration"] / $timebase) + 0.5 + EPS);
                            if ($over > 0) {
                                $totalMTicks = floor($totalTicks *
                                    ($meas["time"] + $timebase*$meas["num"] - $n["time"]) / $n["duration"]);
                                $totalOverScore = $totalTicks - $totalMTicks;
                                $totalOverScore *= ($config["chord_sustain_bonus"] ? count($n["note"]) : 1);
                                $totalTicks = $totalMTicks;
                            }
                            $totalTicks *= ($config["chord_sustain_bonus"] ? count($n["note"]) : 1);
                            
                            $tickmult = $config["ticks_at_new_multi"] ? $mult : $oldmult;
                            $total += ($gems * $oldmult) + ($tickmult * $totalTicks);
                            $totalWithBonuses += ($gems * $oldmult) + ($tickmult * $totalTicks);
                            
                            $overChord = $config["chord_sustain_bonus"] ? count($n["note"]) : 1;
                        } // normal note
                        
                    } // notes as note
                } // not drums
                
                // see if a solo or BRE ended this measure to add its bonus
                if (isset($events[$inst])) {
                    foreach ($events[$inst] as $e) {
                        if ($e["type"] == "solo" && $e["difficulty"] == $diff) {
                            if ($e["end"] >= $meas["time"] && $e["end"] < $meas["time"] + $timebase*$meas["num"]) {
                                $totalWithBonuses += $e["notes"] * 100;
                            }
                        }
                        else if ($e["type"] == "bre" /* && $e["difficulty"] == $diff */) {
                            if ($e["end"] > $meas["time"] && $e["end"] < $meas["time"] + $timebase*$meas["num"]) {
                                $totalWithBonuses += $e["brescore"];
                            }
                        }
                    } // for events
                }
                
                if ($total != $totalWithBonuses && $inst != "drums") $meas["bscore"][$diff] = (int) $totalWithBonuses;
                $meas["mscore"][$diff] = (int) $mScore;
                $meas["cscore"][$diff] = (int) $total;
                $meas["streak"][$diff] = $streak;
                if ($fillNoteScore > 0) $meas["fscore"][$diff] = (int) $fillNoteScore;
            } // foreach meas
        } // foreach inst
    } // foreach diff

    return $measures;
}


?>
