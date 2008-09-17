<?php

	define("MIDIPATH", "mids/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";

    $files = array();
    
    $dir = opendir(MIDIPATH . "rb/");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }

    echo "Outputting sorted list of drum fill delays and location for " . count($files) . "files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)\n";
    	
    	$fills = array();
    	
    	foreach ($events["drums"] as &$e) {
            if ($e["type"] != "fill") continue;
            $n["delay"] = $e["delay"];
            $n["start"] = $e["start"];
            $fills[] = $n;
    	}
    	
    	// figure out the measure numbers
    	// findFirstThingAtTime(haystack, needle, key = time)
    	foreach ($fills as &$f) {
    	   $f["measure"] = $measures["drums"][findFirstThingAtTime($measures["drums"], $f["start"])]["number"];
    	}
        
        // sort it
        usort($fills, "fill_compare");

        foreach ($fills as $f) {
            echo "(meas " . $f["measure"] . ": " . round($f["delay"], 5) . "s) ";
        }
        echo "\n\n";
    }
    
    
    
    exit;
    
    
    function fill_compare($a, $b) {
        // < 0 for a < b
        // etc.
        // dammit, needs to be an integer. stupid php
        if ($a["delay"] < $b["delay"]) return -1;
        else if ($a["delay"] > $b["delay"]) return 1;
        else return 0;
    }

?>