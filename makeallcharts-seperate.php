<?php


/*
18:39:45 < elx> under page setup
18:39:51 < elx> uncheck horizontal and vertical centering
18:40:00 < elx> portrait orientation
18:41:01 < elx> set all margins to 0 (they will result to a small number, thats alright)
18:41:52 < elx> under scaling, set fit to 1 by x pages
18:42:15 < elx> you will need to adjust x depending on the length of the chart, use print preview to make sure the chart isnt being smashed
18:42:19 < elx> and thats it
*/

	define("WIDTH", 1010);
	define("PXPERBEAT", 60);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.3.7");
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
    $indexen = array();
    if (false === ($indexFile = fopen(OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index.html for writing.\n");
    }
    
    foreach($DIFFICULTIES as $i => $diff) {
        if (false === ($indexen[$i] = fopen(OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/index_ " . $diff . ".html for writing.\n");
        }
    }
    
    while (false !== ($file = readdir($dir))) {
        // fixme: that won't work for vox :)
        if (!($file == "." || $file == ".." || substr($file, -10) == "_short.mid")) $files[] = $file;
    }
    
    echo "Preparing to generate charts for " . count($files) . " files...";
    
    fwrite($indexFile, "<html>\n<head>\n<title>Blank Charts for $game " . strtolower($instrument) . "</title>\n</head>\n");
    fwrite($indexFile, <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero.</p>
EOT
);

    foreach ($DIFFICULTIES as $i => $diff) {
        fwrite($indexen[$i], "<html>\n<head>\n<title>Blank Charts for $game " . $diff . " " . strtolower($instrument) . "</title>\n</head>\n");
        fwrite($indexen[$i], <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums</a> and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass</a>.</p>
<p>They are in alphabetical order. Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar part only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure.</p>
<p>Overdrive backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example).</p>
<p>Significant changes since last time:
<ul>
<li><b>Disabled Big Rock Ending estimates.</b> I was horribly off on them, and need to rethink how I was doing it. It will be revisted before too horribly long.</li>
<li>Changed a couple colors.</li>
</ul></p>
<table border="1">
<tr><th>Song</th><th>Absolute Base Score (no multiplier or bonuses)</th><th>Base Score (multiplier, no bonuses)</th><th>FC Score (multiplier, bonuses, no overdrive)</th><th>BRE Note Score</th></tr>
EOT
);
        fwrite($indexFile, "<a href=\"index_" . $diff . ".html\">" . $diff . "</a> ");
    }

    
    foreach ($files as $index => $file) {
        $x = substr($file, 0, strlen($file) - 4);
        echo "\nFile " . ($index+1) . " of " . count($files) . " (" . $file . " / " . $NAMES[$x] . "): ";
        foreach ($DIFFICULTIES as $i => $diff) {
            fwrite($indexen[$i], "<tr><td><a href=\"" . $x . "_" . strtolower($instrument) ."_" . $diff . "_blank.png\">" . (isset($NAMES[$x]) ? $NAMES[$x] : $x) . "</a></td>");
            echo $diff . "... ";
            list ($im, $measures) = makeChart(MIDIPATH . strtolower($game) . "/" . $file, $diff, strtolower($game), strtolower($instrument), (isset($NAMES[$x]) ? $NAMES[$x] : $x));
            
            $absbasescore = 0;
            foreach ($measures as $m) {
                $absbasescore += $m["mscore"];
            }
            $basescore = $measures[count($measures)-1]["cscore"];
            $bonusscore = (isset($measures[count($measures)-1]["bscore"]) ? $measures[count($measures)-1]["bscore"] : 0);
            
        	imagepng($im,  OUTDIR . strtolower($game) . "/" . strtolower($instrument) . "/" . $x . "_"
        	           . strtolower($instrument) . "_" . $diff . "_blank.png");
        	imagedestroy($im);
        	fwrite($indexen[$i], "<td>" . $absbasescore . "</td>");
        	fwrite($indexen[$i], "<td>" . $basescore . "</td>");
        	if ($bonusscore == 0) {
        	   // no solo or BRE
        	   $bonusscore = $basescore;
        	}
        	fwrite($indexen[$i], "<td>" . $bonusscore . "</td>");
        	fwrite($indexen[$i], "<td>" . (isset($measures[count($measures)-1]["fillnotescore"]) ? $measures[count($measures)-1]["fillnotescore"] : " ") . "</td>");
	        fwrite($indexen[$i], "</tr>\n");
        }
    }
    
    foreach ($DIFFICULTIES as $i => $diff) {
        fwrite($indexen[$i], <<<EOT
</table>
</body>
</html>
EOT
);
        fclose($indexen[$i]);
    }
    fwrite($indexFile, "</body></html>");
    fclose($indexFile);
?>