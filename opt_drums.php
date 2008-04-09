<?php

    define("OPTDRUMSVERSION", "0.0.0");

    define("OPTDEBUG", true);

function opt_drums(&$notetrack, &$events, &$timetrack, $diff) {
    list ($path, $points) = opt_drums_recurse($notetrack, $events, $timetrack, $diff, 0);
    
    $total_notes = count_notes($notetrack, 0, $notetrack["TrkEnd"]);
    $total_notes += $points;
    $total_notes *= 100;
    $total_notes -= 1425;
    
    echo $path . " -- for " . $points . " increase and $total_notes total score\n";
    
}

/*
start program by passing the whole chart to process_chart()
 
process_chart(chart section)
{
        analyze chart - count number of valid activation points (fills that occur after 2 phrases)
        
        if (number of valid activation points == 0)
                return "do nothing", 0 points // recursion-ending case
        
        best_score_gain = 0
        
        for (i = 1; i <= number of valid activation points; i++) // loop over all possible activation points
        {
                point_gain = number of points activating here will add
                point_gain -= points from fills that were skipped to activate here
        
                calculate the ending point of this activation
        
                point_gain += process_chart(subchart starting from this ending point)
        
                if point_gain > best_score_gain
                        mark "activate at fill #i" + (the sub-path returned by process_chart) as the best path yet found for this section of chart
        }
        
        return best path (and its score gain) calculated for this section of chart
}

*/

function opt_drums_recurse(&$notetrack, &$events, &$timetrack, $diff, $start) {
    global $timebase;
    
    /*
    static $recurse_count = 0;
    $recurse_count++;
    if ($recurse_count > 1000) die("recursed 1000 times -- stopping\n");
    */
    
    // figure out number of possible activations
    // get index into phrase/fill array of the first event at or after the start time
    $eventIndex = find_event_after_time($events, $start);
    
    if ($eventIndex === false) {
        // no fills or phrases after here, so we can't activate
        // end recursion
        if (OPTDEBUG) echo "opt_drums_recurse ending because no events after $start \n";
        return array("do nothing", 0);
    }
    
    // now we need to find two phrases as well as a fill
    // and then we can count the number of fills, ignoring phrases
    $phrases = 0;
    $got_activation_time = 0;
    $fillIndex = 0;
    $fills = array();
    
    // find 2 phrases first
    while (isset($events[$eventIndex])) {
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            $phrases++;
            $got_activation_time = $events[$eventIndex]["end"];
        }
        $eventIndex++;
        if ($phrases >= 2) break;
    }
    
    if ($phrases < 2) {
        // we don't have enough OD for an activation
        if (OPTDEBUG) echo "opt_drums_recurse ending because not enough phrases after $start ($phrases)\n";
        return array("do nothing", 0);
    }
    
    $skipped_notes = 0;
    // now find a fill -- there should definitely be at least one!
    while (isset($events[$eventIndex])) {
        if (OPTDEBUG) {
            if ($events[$eventIndex]["type"] == "fill") {
                echo "time " . getClockTimeBetweenPulses($timetrack, $got_activation_time, $events[$eventIndex]["start"]) . "\n";
            }
        }
        
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff)
            $phrases++;
        else if ($events[$eventIndex]["type"] == "fill"
            && getClockTimeBetweenPulses($timetrack, $got_activation_time, $events[$eventIndex]["start"]) > 2.5) {
                $fills[$fillIndex]["index"] = $eventIndex;
                $fills[$fillIndex]["phrases"] = $phrases;
                $skipped_notes += count_notes($notetrack, $events[$eventIndex]["start"], $events[$eventIndex]["end"]);
                $fills[$fillIndex]["skipped_notes"] = $skipped_notes;
                $fillIndex++;
        }
        $eventIndex++;
    }

    if (count($fills) == 0) {
        // there isn't a fill (this *should* not happen but I guess it could)
        if (OPTDEBUG) echo "opt_drums_recurse ending because no fills after $start \n";
        return array("do nothing", 0);
    }
    
    $best_score_gain = 0;
    $best_path = "";
    
    for ($i = 0; $i < count($fills); $i++) {
        $activation_start = $events[$fills[$i]["index"]]["end"];
        if (($activation_start % $timebase) != 0) {
            $activation_start = (int)($activation_start / $timebase);
            $activation_start *= $timebase;
            $activation_start += $timebase;
        }
        $activation_end = determine_activation_end($events, $timetrack, $activation_start, min(1, $fills[$i]["phrases"]/4), $diff);
        $score_gain = count_notes($notetrack, $activation_start + 1, $activation_end);
        $score_gain -= $fills[$i]["skipped_notes"];
        
        list ($path, $recurse_gain) = opt_drums_recurse($notetrack, $events, $timetrack, $diff, $activation_end + 1);
        $score_gain += $recurse_gain;
        
        if ($score_gain > $best_score_gain) {
            $best_score_gain = $score_gain;
            $best_path = $fills[$i]["phrases"] . " phrases, fill #" . $i . " -- " . $path;
        }
        
    }
    
    if (OPTDEBUG) echo "opt_drums_recurse final return \"$best_path\" $best_score_gain \n";
    return array($best_path, $best_score_gain);
}


function determine_activation_end(&$events, &$timetrack, $start, $bar_amount, $diff) {
    // TO-DO: check the tempo to figure out if it's a funky section that is not 32 beats for a full bar
    // currently only checks for overrunning phrases
    
    global $timebase;
    
    $end = $start + ($timebase * 32 * $bar_amount);
    $eventIndex = 0;
    
    while ($events[$eventIndex]["start"] < $start) {
        if (isset($events[$eventIndex+1])) $eventIndex++;
        else {
            // there are no possible phrases to run over, so we already know the end time
            if (OPTDEBUG) echo "determine_activation_end activation at $start ends at $end (no events)\n";
            return $end;
        }
    }
    
    // now we're looking at the first event that starts after our activation starts
    
    while (isset($events[$eventIndex])) {
        if ($events[$eventIndex]["end"] > $end) {
            $eventIndex++;
            continue;
        }
        
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            // we run over this phrase and get another 1/4 bar
            if (OPTDEBUG) echo "determine_activation_end activation at $start overruns phrase at " . $events[$eventIndex]["start"] . "\n";
            $end += $timebase * 8;
        }
        
        $eventIndex++;
    }
    
    if (OPTDEBUG) echo "determine_activation_end activation at $start ends at $end \n";
    return $end;
}


function find_event_after_time(&$events, $time) {
    $eventIndex = 0;
    
    while ($events[$eventIndex]["start"] < $time) {
        if (isset($events[$eventIndex+1])) $eventIndex++;
        else return false;
    }
    return $eventIndex;
}


function count_notes(&$notetrack, $start, $end) {
    $noteIndex = 0;
    
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
    
    if (OPTDEBUG) echo "count_notes found $noteCount notes between $start and $end \n";
    return $noteCount;
}








?>