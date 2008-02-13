<?php

	define("WIDTH", 1010);
	define("PXPERBEAT", 60);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.2.0");
	define("MIDIPATH", "../mids/");
	define("OUTDIR", "output/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";
	require_once "chartlib.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if ($argc < 3) {
        die("You must specify which game and instrument you wish charts to be generated for on the command line.\n"
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
    
    umask(0);
    if (!mkdir(OUTDIR . strtolower($game) . "/" . strtolower($instrument), 077, true)) {
        die("Unable to create output directory " . OUTDIR . strtolower($game) . "/" . strtolower($instrument) ." -- does it already exist?\n");
    }
    
    $indexFile = null;
    if (false === ($indexFile = fopen(OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index.html for writing.\n");
    }
    
    while (false !== ($file = readdir($dir))) {
        // fixme: that won't work for vox :)
        if (!($file == "." || $file == ".." || substr($file, -10) == "_short.mid")) $files[] = $file;
    }
    
    echo "Preparing to generate charts for " . count($files) . " files...";
    
    fwrite($indexFile, "<html>\n<head>\n<title>Blank Charts for $game " . strtolower($instrument) . "</title>\n</head>\n");
    fwrite($indexFile, <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. There are some known glitches (especially with the overlines either covering too much or not enough empty space). If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero.</p>
<p>They are in alphabetical order. Probably easier to find a song this way anyway.</p>
<p>Sometimes the overlines either cover too much (overdrive, solos) or too little (solos, fills) empty space. Fills that end in the middle of a measure tend to extend to the next note (or at least beat). Keep this in mind for drums squeezing. Overlines that should extend into the next measure(s) due to sustains but the measure otherwise contains no notes do not get extended. There are bound to be other glitches with the overlines as well, and I will fix them as time permits.</p>
<table border="1">

EOT
);

    
    foreach ($files as $index => $file) {
        $x = substr($file, 0, strlen($file) - 4);
        echo "\nFile " . ($index+1) . " of " . count($files) . " (" . $file . " / " . $NAMES[$x] . "): ";
        fwrite($indexFile, "<tr><td>" . (isset($NAMES[$x]) ? $NAMES[$x] : $x) . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo $diff . "... ";
            $im = makeChart(MIDIPATH . strtolower($game) . "/" . $file, $diff, strtolower($game), strtolower($instrument), (isset($NAMES[$x]) ? $NAMES[$x] : $x));
        	imagepng($im,  OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/" . $x . "_"
        	           . strtolower($instrument) . "_" . $diff . "_blank.png");
        	imagedestroy($im);
        	fwrite($indexFile, "<td><a href=\"" . $x . "_" . strtolower($instrument) ."_" . $diff . "_blank.png\">$diff</a></td>");
        }
        fwrite($indexFile, "</tr>\n");
    }
    
    fwrite($indexFile, <<<EOT
</table>
</body>
</html>
EOT
);

?>