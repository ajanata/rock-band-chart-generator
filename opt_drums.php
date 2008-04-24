<?php

    define("FILL_DELAY", 2.43);

    define("OPTDRUMSVERSION", "0.3.0");

    define("OPTDEBUG", false);
    define("OPTCACHE", true);

function opt_drums(&$notetrack, &$events, &$timetrack, $diff) {
    $path = opt_drums_recurse($notetrack, $events, $timetrack, $diff, 0);
    
    $total_notes = drums_count_notes($notetrack, 0, $notetrack["TrkEnd"]);
    $total_notes += $path[0]["total_gain"];
    $total_notes *= 100;
    $total_notes -= 1425;
    
    print_r($path);
    echo "for $total_notes total score\n";
    
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

function opt_drums_recurse(&$notetrack, &$events, &$timetrack, &$diff, $start) {
    static $cache;
    if (!$cache) $cache = array();
    
    if (OPTCACHE && isset($cache[$start . "$" . $diff])) {
        if (OPTDEBUG) echo "opt_drums_recurse CACHE hit for $start - returning array with " . count($cache[$start . "$" . $diff])
                . " items\n";
        return $cache[$start . "$" . $diff];
    }
    
    global $timebase;
    if (OPTDEBUG) echo "opt_drums_recurse entered $start \n";
    if (OPTDEBUG) echo "opt_drums_recurse entry memory usage: " . memory_get_usage() . " -- " . memory_get_usage(true) . "\n";
    
    $firstRecurse = false;
    static $recurse_count = 0;
    $recurse_count++;
#    if ($recurse_count > 10000) die("recursed 10000 times -- stopping\n");
    if ($recurse_count == 1) $firstRecurse = true;
    
    // figure out number of possible activations
    // get index into phrase/fill array of the first event at or after the start time
    $eventIndex = find_phrase_after_time($events, $notetrack, $start, $diff);
    
    if ($eventIndex === false) {
        // no fills or phrases after here, so we can't activate
        // end recursion
        if (OPTDEBUG) echo "opt_drums_recurse ending because no events after $start \n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    // now we need to find two phrases as well as a fill
    // and then we can count the number of fills, ignoring phrases
    $phrases = 0;
    $got_activation_time = 0;
    $fillIndex = 0;
    $fills = array();
    
    if (OPTDEBUG) echo "opt_drums_recurse looking for phrases\n";
    
    // find 2 phrases first
    while (isset($events[$eventIndex])) {
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            $phrases++;
            $got_activation_time = $notetrack[$events[$eventIndex]["last_note"]]["time"];
            if (OPTDEBUG) echo "opt_drums_recurse found phrase ending at $got_activation_time \n";
        }
        $eventIndex++;
        if ($phrases >= 2) break;
    }
    
    if ($phrases < 2) {
        // we don't have enough OD for an activation
        if (OPTDEBUG) echo "opt_drums_recurse ending because not enough phrases after $start ($phrases)\n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    $skipped_notes = 0;
    // now find a fill -- there should definitely be at least one!
    if (OPTDEBUG) echo "opt_drums_recurse looking for valid fills\n";
    while (isset($events[$eventIndex])) {
        if (OPTDEBUG) {
            if ($events[$eventIndex]["type"] == "fill") {
                echo "opt_drums_recurse fill delay after activation ";
                echo $got_activation_time . " ". $events[$eventIndex]["start"];
                echo " time " . getClockTimeBetweenPulses($timetrack, $got_activation_time, $events[$eventIndex]["start"]) . "\n";
            }
        }
        
        if ($events[$eventIndex]["type"] == "star" && $events[$eventIndex]["difficulty"] == $diff) {
            $phrases++;
            if (OPTDEBUG) echo "opt_drums_recurse found phrase while looking for fills at " . $events[$eventIndex]["start"] . "\n";
            /*
            if ($phrases >= 6 && count($fills) > 4) {
                if (OPTDEBUG) echo "opt_drums_recurse stopping looking for fills - got 6 phrases with at least 4 fills\n";
                break;
            }
            */
        }
        else if ($events[$eventIndex]["type"] == "fill"
            && getClockTimeBetweenPulses($timetrack, $got_activation_time, $events[$eventIndex]["start"]) > FILL_DELAY) {
                $fills[$fillIndex]["index"] = $eventIndex;
                $fills[$fillIndex]["phrases"] = $phrases;

                // round the end of a fill to a beat
                $fill_end = $events[$eventIndex]["end"];
                if (($fill_end % $timebase) != 0) {
                    $fill_end = (int)($fill_end / $timebase);
                    $fill_end *= $timebase;
                    $fill_end += $timebase;
                }

                $skipped_notes += drums_count_notes($notetrack, $events[$eventIndex]["start"], $fill_end);
                $fills[$fillIndex]["skipped_notes"] = $skipped_notes;
                $fillIndex++;
        }
        $eventIndex++;
    }

    if (count($fills) == 0) {
        // there isn't a fill (this *should* not happen but I guess it could)
        if (OPTDEBUG) echo "opt_drums_recurse ending because no fills after $start \n";
        $cache[$start . "$" . $diff] = array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
        return array(array("text" => "do nothing", "gain" => 0, "total_gain" => 0, "start" => 0, "end" => 0));
    }
    
    if (OPTDEBUG) echo "opt_drums_recurse found " . count($fills) . " fills\n";
    
    $best_score_gain = 0;
    $best_path = "";
        
    for ($i = 0; $i < count($fills); $i++) {
        $activation_start = $events[$fills[$i]["index"]]["end"];
        if (($activation_start % $timebase) != 0) {
            $activation_start = (int)($activation_start / $timebase);
            $activation_start *= $timebase;
            $activation_start += $timebase;
        }
        list ($activation_end, $overrun) = drums_determine_activation_end($notetrack, $events, $timetrack,
                $activation_start, min(1, $fills[$i]["phrases"]/4), $diff);
        $score_gain = drums_count_notes($notetrack, $activation_start + 1, $activation_end);
        $score_gain -= $fills[$i]["skipped_notes"];
        $my_gain = $score_gain;
        
        $recursepath = opt_drums_recurse($notetrack, $events, $timetrack, $diff, $activation_end + 1);
        $score_gain += $recursepath[0]["total_gain"];
        
        if ($score_gain > $best_score_gain) {
            $best_score_gain = $score_gain;
            $path = array();
            $path[0] = array();

            $path[0]["text"] = $fills[$i]["phrases"] . " phrases, overrun $overrun, fill #" . $i;
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
    
    if (OPTDEBUG) echo "opt_drums_recurse final return \"$best_path\" $best_score_gain \n";
    if ($firstRecurse) echo "Recursed $recurse_count times.\n";
    $cache[$start . "$" . $diff] = $path;
    return $path;
}


function drums_determine_activation_end(&$notetrack, &$events, &$timetrack, &$start, &$bar_amount, &$diff) {
    // TO-DO: check the tempo to figure out if it's a funky section that is not 32 beats for a full bar
    // currently only checks for overrunning phrases

    static $cache;
    if (!$cache) $cache = array();

    if (OPTCACHE && isset($cache[$start . "$" . $bar_amount . "$" . $diff])) {
        if (OPTDEBUG) echo "drums_determine_activation_end CACHED ".$cache[$start."$".$bar_amount."$".$diff]." activation at $start ends at $end \n";
        return $cache[$start . "$" . $bar_amount . "$" . $diff];
    }
    
    if (OPTDEBUG) echo "drums_determine_activation_end entry memory usage: " . memory_get_usage() . " -- " . memory_get_usage(true) . "\n";
    
    global $timebase;
    $overrun = 0;
    
    $end = $start + ($timebase * 32 * $bar_amount);
    $eventIndex = 0;
    
    while ($events[$eventIndex]["start"] < $start) {
        if (isset($events[$eventIndex+1])) $eventIndex++;
        else {
            // there are no possible phrases to run over, so we already know the end time
            if (OPTDEBUG) echo "drums_determine_activation_end activation at $start ends at $end (no events)\n";
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
                if (OPTDEBUG) echo "drums_determine_activation_end phrase at " . $events[$eventIndex]["start"] . " ends at "
                        . $notetrack[$events[$eventIndex]["last_note"]]["time"] . " which is after activation end at $end \n";
                $eventIndex++;
                continue;
            }
            
            // we run over this phrase and get another 1/4 bar
            if (OPTDEBUG) echo "drums_determine_activation_end activation at $start overruns phrase at " . $events[$eventIndex]["start"] . "\n";
            $end += $timebase * 8;
            $overrun++;
        }
        
        $eventIndex++;
    }
    
    if (OPTDEBUG) echo "drums_determine_activation_end activation at $start ends at $end \n";
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


function drums_count_notes(&$notetrack, $start, $end) {
    static $cache;
    if (!$cache) $cache = array();
    
    if (OPTCACHE && isset($cache[$start . "$" . $end])) {
        if (OPTDEBUG) echo "drums_count_notes CACHED found " . $cache[$start . "$" . $end] . " notes between $start and $end \n";
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
    
    if (OPTDEBUG) echo "drums_count_notes found $noteCount notes between $start and $end \n";
    $cache[$start . "$" . $end] = $noteCount;
    return $noteCount;
}

?>
