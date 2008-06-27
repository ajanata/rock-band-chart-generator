<?php

	define("WIDTH", 1010);
	define("PXPERBEAT", 60);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.9.0");
	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/");
	
	require_once "makeall-common.php";

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";
	require_once "chartlib.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if (isset($argv[1]) && $argv[1] == "--help") do_help();
    if (isset($argv[1]) && $argv[1] == "--version") do_version();


    $files = array();
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    closedir($dir);
        
    umask(0);
    
    foreach (array("guitar", "bass", "drums", "vocals", /* "guitarbass", */ "vocaltar") as $xyzzy) {
        if (file_exists(OUTDIR . "rb/" . $xyzzy)) continue;
        if (!mkdir(OUTDIR . "rb/" . $xyzzy, 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/" . $xyzzy . "\n");
    }

    
    $idx = array();

    $idx["vox"] = null;
    if (false === ($idx["vox"] = fopen(OUTDIR . "rb/vocals/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/vocals/index.html for writing.\n");
    }

    $idx["voxtar"] = null;
    if (false === ($idx["voxtar"] = fopen(OUTDIR . "rb/vocaltar/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/vocaltar/index.html for writing.\n");
    }

    $idx["drums"] = null;
    if (false === ($idx["drums"] = fopen(OUTDIR . "rb/drums/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/drums/index.html for writing.\n");
    }
    
    $idx["guitar"] = array();
    $idx["guitar"]["idx"] = null;
    if (false === ($idx["guitar"]["idx"] = fopen(OUTDIR . "rb/guitar/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/guitar/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["guitar"][$diff] = null;
        if (false === ($idx["guitar"][$diff] = fopen(OUTDIR . "rb/guitar/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . "rb/guitar/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    $idx["bass"] = array();
    $idx["bass"]["idx"] = null;
    if (false === ($idx["bass"]["idx"] = fopen(OUTDIR . "rb/bass/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/bass/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["bass"][$diff] = null;
        if (false === ($idx["bass"][$diff] = fopen(OUTDIR . "rb/bass/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . "rb/bass/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    /*
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
    */
    
    
    $cache = loadCache(RB_CACHE);
    
    // put the header into every file
    index_header($idx["vox"], "Vocals");
    index_header($idx["voxtar"], "Vocaltar");
    index_header($idx["drums"], "Drums");
    foreach ($idx["guitar"] as $foo => $bar) { index_header($bar, "$foo guitar"); }
    foreach ($idx["bass"] as $foo => $bar) { index_header($bar, "$foo bass"); }
    //foreach ($idx["guitarbass"] as $foo => $bar) { index_header($bar, "$foo guitar+bass"); }
    

    // open the tables
    // vocals doesn't need a table -- just a flat list of links
    // drums and voxtar get the simple table
    fwrite($idx["drums"], "<table border=\"1\">");
    fwrite($idx["voxtar"], "<table border=\"1\">");
    
    // everything else gets the complex table
    foreach (array($idx["guitar"], $idx["bass"]/*, $idx["guitarbass"]*/) as $foo) {
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

    	// vocals first
    	echo " [vocals]";
    	if (isset($cache[$shortname]["vocals"]) && $cache[$shortname]["vocals"]["version"] >= CHARTVERSION) {
    	   // we already have a valid image for this
    	   echo " {cached}";
    	}
    	else {
    	    // have to regenerate the image
        	$im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ false,
               /* bass*/ false, /* drums */ false, /* vocals */ true, $realname, $beat);
            imagepng($im, OUTDIR . "rb/vocals/" . $shortname . "_vocals_blank.png");
            imagedestroy($im);
            $cache[$shortname]["vocals"]["version"] = CHARTVERSION;
    	}
        
        fwrite($idx["vox"], "<a href=\"".$shortname."_vocals_blank.png\">$realname</a><br>\n");
        
        
        // drums
        echo " [drums]";
        fwrite($idx["drums"], "<tr><td>" . $realname . "</td>");
        
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
        	if (isset($cache[$shortname]["drums"][$diff]) && $cache[$shortname]["drums"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ false,
                     /* bass*/ false, /* drums */ true, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . "rb/drums/" . $shortname . "_drums_" . $diff . "_blank.png");
                imagedestroy($im);
                $cache[$shortname]["drums"][$diff]["version"] = CHARTVERSION;
          	}

            fwrite($idx["drums"], "<td><a href=\"" . $shortname . "_drums_" . $diff . "_blank.png\">" . $diff . "</a></td>");
        } // drums diffs
        fwrite($idx["drums"], "</tr>\n");
        
        
        // guitar
        echo " [guitar]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";

            $absbasescore = $basescore = $bonusscore = $brescore = 0;
        	if (isset($cache[$shortname]["guitar"][$diff]) && $cache[$shortname]["guitar"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
        	   $absbasescore = $cache[$shortname]["guitar"][$diff]["abs"];
        	   $basescore = $cache[$shortname]["guitar"][$diff]["base"];
        	   $bonusscore = $cache[$shortname]["guitar"][$diff]["bonus"];
        	   $brescore = $cache[$shortname]["guitar"][$diff]["bre"];
        	}
        	else {
                // have to re-generate the chart and get all the numbers and stuff
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                       /* bass*/ false, /* drums */ false, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . "rb/guitar/" . $shortname . "_guitar_" . $diff . "_blank.png");
                imagedestroy($im);

                // ugly score kludges
                $absbasescore = 0;
                foreach ($measures["guitar"] as $m) {
                    $absbasescore += $m["mscore"][$diff];
                }
                $basescore = $measures["guitar"][count($measures["guitar"])-1]["cscore"][$diff];
                $bonusscore = (isset($measures["guitar"][count($measures["guitar"])-1]["bscore"][$diff])
                        ? $measures["guitar"][count($measures["guitar"])-1]["bscore"][$diff] : 0);
            	if ($bonusscore == 0) {
            	   // no solo or BRE
            	   $bonusscore = $basescore;
            	}
            	
            	$brescore = (isset($measures["guitar"][count($measures["guitar"])-1]["fscore"][$diff])
                    ? $measures["guitar"][count($measures["guitar"])-1]["fscore"][$diff] : " ");

                $cache[$shortname]["guitar"][$diff]["version"] = CHARTVERSION;
                $cache[$shortname]["guitar"][$diff]["abs"] = $absbasescore;
                $cache[$shortname]["guitar"][$diff]["base"] = $basescore;
                $cache[$shortname]["guitar"][$diff]["bonus"] = $bonusscore;
                $cache[$shortname]["guitar"][$diff]["bre"] = $brescore;
        	}
            
            
            fwrite($idx["guitar"][$diff], "<tr><td><a href=\"" . $shortname . "_guitar_" . $diff . "_blank.png\">" . $realname . "</a></td>");
            fwrite($idx["guitar"][$diff], "<td>" . $absbasescore . "</td>");
        	fwrite($idx["guitar"][$diff], "<td>" . $basescore . "</td>");
        	fwrite($idx["guitar"][$diff], "<td>" . $bonusscore . "</td>");
        	fwrite($idx["guitar"][$diff], "<td>" . $brescore . "</td>");
	        fwrite($idx["guitar"][$diff], "</tr>\n");
            
        } // guitar diffs


        // bass
        echo " [bass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $absbasescore = $basescore = $bonusscore = $brescore = 0;
        	if (isset($cache[$shortname]["bass"][$diff]) && $cache[$shortname]["bass"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
        	   $absbasescore = $cache[$shortname]["bass"][$diff]["abs"];
        	   $basescore = $cache[$shortname]["bass"][$diff]["base"];
        	   $bonusscore = $cache[$shortname]["bass"][$diff]["bonus"];
        	   $brescore = $cache[$shortname]["bass"][$diff]["bre"];
        	}
        	else {
                // have to re-generate the chart and get all the numbers and stuff
    
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ false,
                       /* bass*/ true, /* drums */ false, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . "rb/bass/" . $shortname . "_bass_" . $diff . "_blank.png");
                imagedestroy($im);
                
                // ugly score kludges
                $absbasescore = 0;
                foreach ($measures["bass"] as $m) {
                    $absbasescore += $m["mscore"][$diff];
                }
                $basescore = $measures["bass"][count($measures["bass"])-1]["cscore"][$diff];
                $bonusscore = (isset($measures["bass"][count($measures["bass"])-1]["bscore"][$diff])
                        ? $measures["bass"][count($measures["bass"])-1]["bscore"][$diff] : 0);

            	if ($bonusscore == 0) {
            	   // no solo or BRE
            	   $bonusscore = $basescore;
            	}
            	
            	$brescore =  (isset($measures["bass"][count($measures["bass"])-1]["fscore"][$diff])
        	       ? $measures["bass"][count($measures["bass"])-1]["fscore"][$diff] : " ");

                $cache[$shortname]["bass"][$diff]["version"] = CHARTVERSION;
                $cache[$shortname]["bass"][$diff]["abs"] = $absbasescore;
                $cache[$shortname]["bass"][$diff]["base"] = $basescore;
                $cache[$shortname]["bass"][$diff]["bonus"] = $bonusscore;
                $cache[$shortname]["bass"][$diff]["bre"] = $brescore;
        	}

            fwrite($idx["bass"][$diff], "<tr><td><a href=\"" . $shortname . "_bass_" . $diff . "_blank.png\">" . $realname . "</a></td>");
        	fwrite($idx["bass"][$diff], "<td>" . $absbasescore . "</td>");
        	fwrite($idx["bass"][$diff], "<td>" . $basescore . "</td>");
        	fwrite($idx["bass"][$diff], "<td>" . $bonusscore . "</td>");
        	fwrite($idx["bass"][$diff], "<td>" . $brescore . "</td>");
	        fwrite($idx["bass"][$diff], "</tr>\n");
            
        } // bass diffs


        // guitarbass
        /*
        echo " [guitarbass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar * / true,
                   /* bass* / true, /* drums * / false, /* vocals * / false, $realname, $beat);
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
        	/** /fwrite($idx["guitarbass"][$diff], "<td>" . $brenotescore . "</td>");
	        fwrite($idx["guitarbass"][$diff], "</tr>\n");
            
        } // guitarbass diffs
        */
        
        // voxtar
        echo " [voxtar]";
        
        fwrite($idx["voxtar"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
        	if (isset($cache[$shortname]["voxtar"][$diff]) && $cache[$shortname]["voxtar"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                       /* bass*/ false, /* drums */ false, /* vocals */ true, $realname, $beat);
                imagepng($im, OUTDIR . "rb/vocaltar/" . $shortname . "_vocaltar_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["voxtar"][$diff]["version"] = CHARTVERSION;
          	}
            
            fwrite($idx["voxtar"], "<td><a href=\"" . $shortname . "_vocaltar_" . $diff . "_blank.png\">" . $diff . "</a></td>");            
        } // voxtar diffs
        fwrite($idx["voxtar"], "</tr>\n");

        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx["vox"], "</body>\n</html>");
    fwrite($idx["voxtar"], "</table>\n</body>\n</html>");
    fwrite($idx["drums"], "</table>\n</body>\n</html>");
    foreach ($idx["guitar"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }
    foreach ($idx["bass"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }
    /*
    foreach ($idx["guitarbass"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }
    */

    saveCache(RB_CACHE, $cache);

    exit;


    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass/guitar+bass</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7625">vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7626">vocaltar</a>, and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7627">full band</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar parts only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure.</p>
<p>Vocal activation zones are not stored in the .mid as they are with drums. This leads me to believe that any gap larger than a certain amount of time (be it clock time or number of beats, I'm not sure) is an activation zone. At some point in the not-too-distant future I intend to do more research on this.</li>
<p>Overdrive phrase backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="/charts/rb/guitar/foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example).</p>
<p>Significant changes since last time:
<ul>
<li><b>The .mid BEAT track is now displayed on every chart.</b> The game uses this to determine how long Overdrive lasts. A full bar of Overdrive always lasts for exactly 32 BEAT track beats. Most of the time this is 16, 32, or 64 noteboard beats, depending on tempo. Sometimes, it isn't (see the first break in Foreplay/Long Time for an example). I don't see the two events in the BEAT track doing different things in the gameplay (perhaps different stage lighting or something but nothing that matters for pathing), so I've drawn them all in the same color. If it isn't obvious, you want to look at the small red lines above every set of lines (this also makes a nice seperator for multi-instrument parts). <u>Note that this <b>DOES NOT</b> affect whammy rate, only usage rate.</u> Whammy is always based on noteboard beats.</li>
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