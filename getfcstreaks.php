<?php

	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/rb/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if (isset($argv[1]) && $argv[1] == "--help") do_help();
    if (isset($argv[1]) && $argv[1] == "--version") do_version();


    $files = array();
    
    $dir = opendir(MIDIPATH . "rb/");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, -10) == "_short.mid") continue;
        if (substr($file, 1) == "_") continue;
        $files[] = $file;
    }
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    
    umask(0);
    
    
    $idx = null;
    if (false === ($idx = fopen(OUTDIR . "fc_note_streaks.csv", "w"))) {
        die("Unable to open file " . OUTDIR . "fc_note_streaks.csv for writing.\n");
    }        
    
    
    // open the table
    fwrite($idx, "short_name,guitar_easy,guitar_medium,guitar_hard,guitar_expert,bass_easy,bass_medium,bass_hard,bass_expert\n"); //,vocals\n");

    echo "Getting FC note streaks for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";

        fwrite($idx, $songname);

        // guitar
        echo " [guitar]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $streak = $measures["guitar"][count($measures["guitar"])-1]["streak"][$diff];
            fwrite($idx, "," . $streak);
        } // guitar diffs

        // bass
        echo " [bass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $streak = $measures["bass"][count($measures["bass"])-1]["streak"][$diff];
            fwrite($idx, "," . $streak);
        } // bass diffs

/*
        // vocals
        echo " [vocals]";
        $last = -1;
        $streak = 0;
        foreach ($events["vocals"] as $e) {
            if (!($e["type"] == "p1" || $e["type"] == "p2")) continue;
            if (($e["type"] == "p1" || $e["type"] == "p2") && $e["start"] > $last) {
                $last = $e["start"];
                $streak++;
            }
        } // vocal events
        fwrite($idx, "," . $streak);
*/
        fwrite($idx, "\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fclose($idx);

    exit;


    function do_help() {
        // TODO
        exit;
    }

    
    function do_version() {
        // TODO
        exit;
    }



?>