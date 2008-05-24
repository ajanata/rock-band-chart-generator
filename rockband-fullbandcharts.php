<?php

	define("WIDTH", 1010);
	define("PXPERBEAT", 30);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.7.5");
	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";
	require_once "chartlib.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if (isset($argv[1]) && $argv[1] == "--help") do_help();
    if (isset($argv[1]) && $argv[1] == "--version") do_version();


    $files = array();
    
    $dir = opendir(MIDIPATH . "rb/");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        $files[] = $file;
    }
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    
    umask(0);
    
    if (!file_exists(OUTDIR . "rb/fullband")) {
        if (!mkdir(OUTDIR . "rb/fullband", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/fullband\n");
    }

    if (!file_exists(OUTDIR . "rb/guitarbass")) {
        if (!mkdir(OUTDIR . "rb/guitarbass", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/guitarbass\n");
    }

    
    $idx = array();
    
    $idx["fullband"] = null;
    if (false === ($idx["fullband"] = fopen(OUTDIR . "rb/fullband/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/fullband/index.html for writing.\n");
    }
    
    $idx["guitarbass"] = array();
    $idx["guitarbass"]["idx"] = null;
    if (false === ($idx["guitarbass"]["idx"] = fopen(OUTDIR . "rb/guitarbass/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/guitarbass/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["guitarbass"][$diff] = null;
        if (false === ($idx["guitarbass"][$diff] = fopen(OUTDIR . "rb/guitarbass/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . "rb/guitarbass/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    
    index_header($idx["fullband"], "Full Band");
    foreach ($idx["guitarbass"] as $foo => $bar) { index_header($bar, "$foo guitar+bass"); }

    
    // open the table for full band
    fwrite($idx, "<table border=\"1\">");
    
    
    // open the complex table for guitarbass
    foreach ($idx["guitarbass"] as $foo) {
        foreach ($foo as $baz => $bar) {
            if ($baz == "idx") {
                // links to difficulties on the index page
                fwrite($bar, <<<EOT
<a href="index_easy.html">easy</a> <a href="index_medium.html">medium</a> <a href="index_hard.html">hard</a> <a href="index_expert.html">expert</a>
EOT
);
            }
            else {
                fwrite($bar, <<<EOT
<table border="1">
<tr><th>Song</th><th>Absolute Base Score (no multiplier or bonuses)</th><th>Base Score (multiplier, no bonuses)</th><th>FC Score (multiplier, bonuses, no overdrive)</th><!-- --> <th>BRE Note Score</th> <!-- --></tr>
EOT
);
            }
        }
    }


    echo "Preparing charts for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";


        // full band
        echo " [fullband]";
        fwrite($idx["fullband"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                   /* bass*/ true, /* drums */ true, /* vocals */ true, $realname, $beat);
            imagepng($im, OUTDIR . "rb/fullband/" . $shortname . "_fullband_" . $diff . "_blank.png");
            imagedestroy($im);
            
            fwrite($idx["fullband"], "<td><a href=\"" . $shortname . "_fullband_" . $diff . "_blank.png\">" . $diff. "</a></td>");
        } // fullband diffs
        fwrite($idx["fullband"], "</tr>\n");
        
        
        // guitarbass
        echo " [guitarbass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                   /* bass*/ true, /* drums */ false, /* vocals */ false, $realname, $beat);
            imagepng($im, OUTDIR . "rb/guitarbass/" . $shortname . "_guitarbass_" . $diff . "_blank.png");
            imagedestroy($im);
            
            fwrite($idx["guitarbass"][$diff], "<tr><td><a href=\"" . $shortname . "_guitarbass_" . $diff . "_blank.png\">" . $realname. "</a></td>");
            
            // ugly score kludges
            $absbasescore = 0;
            foreach ($measures["guitar"] as $m) {
                $absbasescore += $m["mscore"][$diff];
            }
            foreach ($measures["bass"] as $m) {
                $absbasescore += $m["mscore"][$diff];
            }

            $basescore = $measures["guitar"][count($measures["guitar"])-1]["cscore"][$diff];
            $basescore += $measures["bass"][count($measures["bass"])-1]["cscore"][$diff];
            
            $bonusscore = (isset($measures["guitar"][count($measures["guitar"])-1]["bscore"][$diff])
                    ? $measures["guitar"][count($measures["guitar"])-1]["bscore"][$diff] : 0);
            $bonusscore += (isset($measures["bass"][count($measures["bass"])-1]["bscore"][$diff])
                    ? $measures["bass"][count($measures["bass"])-1]["bscore"][$diff] : 0);

        	$brenotescore = (isset($measures["guitar"][count($measures["guitar"])-1]["fscore"][$diff])
        	       ? $measures["guitar"][count($measures["guitar"])-1]["fscore"][$diff] : 0);
        	$brenotescore += (isset($measures["bass"][count($measures["bass"])-1]["fscore"][$diff])
        	       ? $measures["bass"][count($measures["bass"])-1]["fscore"][$diff] : 0); 
            
        	fwrite($idx["guitarbass"][$diff], "<td>" . $absbasescore . "</td>");
        	fwrite($idx["guitarbass"][$diff], "<td>" . $basescore . "</td>");
        	if ($bonusscore == 0) {
        	   // no solo or BRE
        	   $bonusscore = $basescore;
        	}
        	fwrite($idx["guitarbass"][$diff], "<td>" . $bonusscore . "</td>");
        	/**/fwrite($idx["guitarbass"][$diff], "<td>" . $brenotescore . "</td>");
	        fwrite($idx["guitarbass"][$diff], "</tr>\n");
            
        } // guitarbass diffs
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx["fullband"], "</table>\n</body>\n</html>");
    foreach ($idx["guitarbass"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }


    exit;


    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass/basstar</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7625">vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7626">vocaltar</a>, and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7627">full band</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar parts only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure.</p>
<p>Vocal activation zones are not stored in the .mid as they are with drums. This leads me to believe that any gap larger than a certain amount of time (be it clock time or number of beats, I'm not sure) is an activation zone. At some point in the not-too-distant future I intend to do more research on this.</li>
<p>Overdrive phrase backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="/charts/rb/guitar/foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example).</p>
<p>Significant changes since last time:
<ul>
<li><b>The .mid BEAT track is now displayed on every chart.</b> The game uses this to determine how long Overdrive lasts. A full bar of Overdrive always lasts for exactly 32 BEAT track beats. Most of the time this is 16, 32, or 64 noteboard beats, depending on tempo. Sometimes, it isn't (see the first break in Foreplay/Long Time for an example). I don't see the two events in the BEAT track doing different things in the gameplay (perhaps different stage lighting or something but nothing that matters for pathing), so I've drawn them all in the same color. If it isn't obvious, you want to look at the small red lines above every set of lines (this also makes a nice seperator for multi-instrument parts). <u>Note that this <b>DOES NOT</b affect whammy rate, only usage rate.</u> Whammy is always based on noteboard beats.</li>
<li><b>Band per-measure scores</b>, more or less. This is currently done "stupidly", and does not include vocals. It is "stupid" because it takes each instrument's per-measure score and multiplies it by the instrument's maximum multiplier, regardless of whether such a multiplier is possible yet at that point. Vocals is on the to-do list and maybe a smarter way of doing it.</li>
<li>Vocal pitch lines should be much more accurate. Thank pata70 for going out of his way to figure out a better way to do it.</li>
</ul></p>
EOT
);
    }


    function do_help() {
        // TODO
        exit;
    }

    
    function do_version() {
        // TODO
        exit;
    }

?>