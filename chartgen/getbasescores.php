<?php

	define("MIDIPATH", "../mids/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if ($argc < 3) {
        die("You must specify which game and instrument you wish base scores to be calculated for on the command line.\n"
            . "Example: php " . $argv[0] . " rb drums\n");
    }

    $game = strtoupper($argv[1]);
    $instrument = strtoupper($argv[2]);


	if (!($game == "GH1" || $game == "GH2" || $game == "GH3" || $game == "RB")) {
	   die("Invalid game -- specify one of gh1, gh2, gh3, or rb.\n");
	}
	
	if ($game == "RB") {
	   if (!($instrument == "GUITAR" || $instrument == "BASS" || $instrument == "DRUMS" || $instrument == "VOX")) {
	       die("Invalid instrument for rock band -- specify one of guitar, bass, drums, or vox.\n");
	   }
	   if ($instrument == "VOX") die("Not yet implemented.\n");
	}
	else {
	   if (!($instrument == "GUITAR" || $instrument == "BASS" || $instrument == "COOP")) {
	       die("Invalid instrument for guitar hero -- specify one of guitar, bass (also reads rhythm), or coop (for lead/rhythm songs).\n");
	   }
	   if ($instrument != "GUITAR") die ("Not yet implemented.\n");
	}


    $files = array();
    
    $dir = opendir(MIDIPATH . strtolower($game) . "/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . strtolower($game) . "/ for reading.\n");
    
    while (false !== ($file = readdir($dir))) {
        // fixme: that won't work for vox :)
        if (!($file == "." || $file == ".." || substr($file, -10) == "_short.mid")) $files[] = $file;
    }
    
    echo "Preparing to calculate base scores for " . count($files) . " files...";
        
    foreach ($files as $index => $file) {
        $x = substr($file, 0, strlen($file) - 4);
        echo "\nFile " . ($index+1) . " of " . count($files) . " (" . $file . " / " . $NAMES[$x] . "): ";
        foreach ($DIFFICULTIES as $diff) {
            echo $diff . ": ";
            list ($measures, $notetrack, $songname, $events) = parseFile(MIDIPATH . strtolower($game) . "/" . $file, strtoupper($diff),
                        $game, $instrument);
            $basescore = 0;
            if ($instrument == "DRUMS") {
                foreach ($measures as $m) {
                    $basescore += $m["mscore"];
                }
            }
            else {
                $basescore = $measures[count($measures)-1]["mscore"];
            }
            echo $basescore . " ... ";
                    
        }
    }
    
?>