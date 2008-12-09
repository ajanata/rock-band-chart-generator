<?php

    define("OPTGUITARVERSION", "0.0.1");
    // amount of bar per quarter note
    define("WHAMMY_RATE", .034);

    define("OPTDEBUG", true);
    define("OPTCACHE", true);
    
    require_once "parselib.php";

function opt_guitar(&$notetrack, &$events, &$timetrack, &$beat, $diff) {
    $path = opt_guitar_recurse($notetrack, $events, $timetrack, $beat, $diff, 0);
    

    print_r($path);
/*
    $out = "";
    $concise = "";
    for ($foo = 0; $foo < count($path); $foo++) {
        if ($foo != 0) {
            $concise .= "-";
            $out .= "- ";
        }
        $out .= $path[$foo]["text"] . " ";
        $concise .= substr($path[$foo]["text"], -7, 1);
    }
    echo $out;
    echo "for $total_notes total score\n";
    echo $concise . "\n";
*/
    
    /*
    $oldevents = $events;
    
    foreach($path as $activation) {
        $index = count($events);
        $events[$index]["type"] = "activation";
        $events[$index]["difficulty"] = $diff;
        $events[$index]["start"] = $activation["start"];
        $events[$index]["end"] = $activation["end"];
    }
    
    foreach ($oldevents as $oe) {
        $events[] = $oe;
    }
    */
    
    return $concise;
}

/*
start program by passing the whole chart to process_chart()
 
process_chart(chart section)
{
        analyze chart - count number of valid activation points (interesting that occur after 2 phrases)
        
        if (number of valid activation points == 0)
                return "do nothing", 0 points // recursion-ending case
        
        best_score_gain = 0
        
        for (i = 1; i <= number of valid activation points; i++) // loop over all possible activation points
        {
                point_gain = number of points activating here will add
                point_gain -= points from interesting that were skipped to activate here
        
                calculate the ending point of this activation
        
                point_gain += process_chart(subchart starting from this ending point)
        
                if point_gain > best_score_gain
                        mark "activate at interesting #i" + (the sub-path returned by process_chart) as the best path yet found for this section of chart
        }
        
        return best path (and its score gain) calculated for this section of chart
}

*/

function opt_guitar_recurse(&$notetrack, &$events, &$timetrack, &$beat, &$diff, $start) {
    static $cache;
    if (!$cache) $cache = array();
    
    if (OPTCACHE && isset($cache[$start . "$" . $diff])) {
        if (OPTDEBUG) echo "opt_guitar_recurse CACHE hit for $start - returning array with " . count($cache[$start . "$" . $diff])
                . " items\n";
        return $cache[$start . "$" . $diff];
    }
    
    global $timebase;
    if (OPTDEBUG) echo "opt_guitar_recurse entered $start \n";
    if (OPTDEBUG) echo "opt_guitar_recurse entry memory usage: " . memory_get_usage() . " -- " . memory_get_usage(true) . "\n";
    
    $firstRecurse = false;
    static $recurse_count = 0;
    $recurse_count++;
#    if ($recurse_count > 10000) die("recursed 10000 times -- stopping\n");
    if ($recurse_count == 1) $firstRecurse = true;
    
    // figure out number of possible activations
    // get index into phrase/interesting array of the first event at or after the start time
    $eventIndex = find_phrase_after_time($events, $notetrack, $start, $diff);
    $noteIndex = findFirstThingAtTime($notetrack, $start);
    
    if ($eventIndex === false || $noteIndex === false) {
        // no interesting or phrases after here, so we can't activate
        // end recursion
        if (OPTDEBUG) echo "opt_guitar_recurse ending because no events or notes after $start \n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    // now we need to find half a bar of OD
    // and then try to activate on every note from there to the end of the song -_-
    $bar = 0;
    $phrases++;
    $interestingIndex = 0;
    $interesting = array();
    
    if (OPTDEBUG) echo "opt_guitar_recurse looking for half-bar\n";
    
    // find half a bar of OD
    while (isset($events[$eventIndex])) {
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            $bar += .25;
            $bar += opt_guitar_whammy_amount($notetrack, $events[$eventIndex]);
            $phrases++;
            #if (OPTDEBUG) echo "opt_guitar_recurse found phrase ending at $got_activation_time \n";
        }
        $eventIndex++;
        if ($bar >= .5) break;
    }
    if ($bar > 1) $bar = 1;
    
    if ($bar < .5) {
        // we don't have enough OD for an activation
        if (OPTDEBUG) echo "opt_guitar_recurse ending because not enough OD after $start ($bar)\n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    // now find a note to activate on -- there should definitely be at least one!
    if (OPTDEBUG) echo "opt_guitar_recurse looking for valid notes\n";
    while (isset($notetrack[$noteIndex])) {
        /*
        if (OPTDEBUG) {
            if ($events[$eventIndex]["type"] == "interesting") {
                echo "opt_guitar_recurse interesting delay after activation ";
                echo $got_activation_time . " ". $events[$eventIndex]["start"];
                echo " time " . getClockTimeBetweenPulses($timetrack, $got_activation_time, $events[$eventIndex]["start"]) . "\n";
            }
        }
        */
        
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            $bar += .25;
            $bar += opt_guitar_whammy_amount($notetrack, $events[$eventIndex]);
            $phrases++;
            if (OPTDEBUG) echo "opt_guitar_recurse found phrase while looking for notes at " . $events[$eventIndex]["start"] . "\n";
            
            if ($phrases >= 6) {
                if (OPTDEBUG) echo "opt_guitar_recurse stopping looking for notes - got 6 phrases\n";
                break;
            }
            
        }
        if ($bar > 1) $bar = 1;
        
        $interesting[$interestingIndex]["note"] = $noteIndex;
        $interesting[$interestingIndex]["bar"] = $bar;
        $interesting[$interestingIndex]["phrases"] = $phrases;

        $interestingIndex++;
        $noteIndex++;
        $eventIndex++;
    }

    if (count($interesting) == 0) {
        // there isn't a interesting (this *should* not happen but I guess it could)
        if (OPTDEBUG) echo "opt_guitar_recurse ending because no notes after $start \n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    if (OPTDEBUG) echo "opt_guitar_recurse found " . count($interesting) . " notes\n";
    
    $best_score_gain = 0;
    $best_path = "";
        
    for ($i = 0; $i < count($interesting); $i++) {
        $activation_start = $notetrack[$interesting[$i]["note"]]["time"];

        list ($activation_end, $overrun) = guitar_determine_activation_end($notetrack, $events, $timetrack, $beat,
                $activation_start, $bar, $diff);
        $score_gain = guitar_score($notetrack, $activation_start + 1, $activation_end);
        $my_gain = $score_gain;
        
        $recursepath = opt_guitar_recurse($notetrack, $events, $timetrack, $beat, $diff, $activation_end + 1);
        $score_gain += $recursepath[0]["total_gain"];
        
        if ($score_gain > $best_score_gain) {
            $best_score_gain = $score_gain;
            $path = array();
            $path[0] = array();

            $path[0]["text"] = $interesting[$i]["phrases"] . " phrases, " . $interesting[$i]["bar"] . "bar, overrun $overrun, activate at "
                    . $notetrack[$interesting[i]["note"]]["time"];
            $path[0]["start"] = $activation_start;
            $path[0]["end"] = $activation_end;
            $path[0]["total_gain"] = $best_score_gain;
            $path[0]["gain"] = $my_gain;
            
            foreach ($recursepath as $new) {
                if ($new["text"] != "do nothing") {
                    $index = count($path);
                    $path[$index] = $new;
                }
            }
        }
        
    }
    
    if (OPTDEBUG) echo "opt_guitar_recurse final return \"$best_path\" $best_score_gain \n";
    if ($firstRecurse) echo "Recursed $recurse_count times.\n";
    $cache[$start . "$" . $diff] = $path;
    return $path;
}

function opt_guitar_whammy_amount(&$notetrack, &$events[$eventIndex]) {
    return 0;
}

// returns array(end pulse, phrases overlapped)
function guitar_determine_activation_end(&$notetrack, &$events, &$timetrack, &$beat, &$start, &$bar_amount, &$diff) {

    static $cache;
    if (!$cache) $cache = array();

    if (OPTCACHE && isset($cache[$start . "$" . $bar_amount . "$" . $diff])) {
        if (OPTDEBUG) echo "guitar_determine_activation_end CACHED ".$cache[$start."$".$bar_amount."$".$diff]." activation at $start ends at $end \n";
        return $cache[$start . "$" . $bar_amount . "$" . $diff];
    }
    
    if (OPTDEBUG) echo "guitar_determine_activation_end entry memory usage: " . memory_get_usage() . " -- " . memory_get_usage(true) . "\n";
    
    global $timebase;
    $overrun = 0;
    
    $beatindex = findFirstThingAtTime($beat, $start, "time");
    $beatindex += floor(32 * $bar_amount);
    $beatindex = min($beatindex, count($beat) - 1);
    $end = $beat[$beatindex]["time"];
    
    $eventIndex = 0;
    
    while ($events[$eventIndex]["start"] < $start) {
        if (isset($events[$eventIndex+1])) $eventIndex++;
        else {
            // there are no possible phrases to run over, so we already know the end time
            if (OPTDEBUG) echo "guitar_determine_activation_end activation at $start ends at $end (no events)\n";
            $cache[$start . "$" . $bar_amount . "$" . $diff] = array($end, 0);
            return array($end, 0);
        }
    }
    
    // now we're looking at the first event that starts after our activation starts
    
    while (isset($events[$eventIndex])) {
        
        if ($events[$eventIndex]["start"] > $end) {
            $eventIndex++;
            continue;
        }
        
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            if ($notetrack[$events[$eventIndex]["last_note"]]["time"] > $end) {
                if (OPTDEBUG) echo "guitar_determine_activation_end phrase at " . $events[$eventIndex]["start"] . " ends at "
                        . $notetrack[$events[$eventIndex]["last_note"]]["time"] . " which is after activation end at $end \n";
                $eventIndex++;
                continue;
            }
            
            ## XXX this is a lot more daunting than it seems at first.
            
            // we run over this phrase and get another 1/4 bar
            if (OPTDEBUG) echo "guitar_determine_activation_end activation at $start overruns phrase at " . $events[$eventIndex]["start"] . "\n";
            $beatindex += 8;
            $beatindex = min($beatindex, count($beat) - 1);
            $end = $beat[$beatindex]["time"];
            $overrun++;
        }
        
        $eventIndex++;
    }
    
    if (OPTDEBUG) echo "guitar_determine_activation_end activation at $start ends at $end \n";
    $cache[$start . "$" . $bar_amount . "$" . $diff] = array($end, $overrun);
    return array($end, $overrun);
}


function find_phrase_after_time(&$events, &$notetrack, &$time, &$diff) {
    static $cache;
    if (!$cache) $cache = array();
    
    if (OPTCACHE && isset($cache[$time . "$" . $diff])) {
        if (OPTDEBUG) echo "find_phrase_after_time CACHED found " . $cache[$time . "$" . $diff] . " for $time \n";
        return $cache[$time . "$" . $diff];
    }
    
    foreach ($events as $i => &$e) {
        if ($e["type"] != "star") continue;
        if ($e["difficulty"] != $diff) continue;
        
        if ($notetrack[$e["last_note"]]["time"] > $time) {
            $cache[$time . "$" . $diff] = $i;
            return $i;
        }
    }
    $cache[$time . "$" . $diff] = false;
    return false;
}


function guitar_count_notes(&$notetrack, $start, $end) {
    static $cache;
    if (!$cache) $cache = array();
    
    if (OPTCACHE && isset($cache[$start . "$" . $end])) {
        if (OPTDEBUG) echo "guitar_count_notes CACHED found " . $cache[$start . "$" . $end] . " notes between $start and $end \n";
        return $cache[$start . "$" . $end];
    }
    
    $noteIndex = 1;
    
    while ($notetrack[$noteIndex]["time"] < $start) {
        if (isset($notetrack[$noteIndex+1])) $noteIndex++;
        else return 0;
    }
    
    $noteCount = 0;
    
    while ($notetrack[$noteIndex]["time"] <= $end) {
        $noteCount += $notetrack[$noteIndex]["count"] + 1;  // this is 0-based for some reason
        if (isset($notetrack[$noteIndex+1])) $noteIndex++;
        else break;
    }
    
    if (OPTDEBUG) echo "guitar_count_notes found $noteCount notes between $start and $end \n";
    $cache[$start . "$" . $end] = $noteCount;
    return $noteCount;
}

?>
