<?php

	define("WIDTH", 1010);
	define("BPMPRECISION", 1);
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

    if (!isset($argv[1]) && $argv[1] != $game && $argv[1] != "tbrb") die ("specify rb or tbrb on command line");
    $game = $argv[1];

    $files = array();
    
    $dir = opendir(MIDIPATH . $game . "/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . $game . "/ for reading.\n");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, -9) == ".voxfills") continue;
        if ($file == ".svn") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    closedir($dir);
        
    umask(0);
    
    foreach (array("guitar", "bass", "drums", "vocals", /* "guitarbass", */ "vocaltar", "vocaldrums") as $xyzzy) {
        if (file_exists(OUTDIR . $game . "/" . $xyzzy)) continue;
        if (!mkdir(OUTDIR . $game . "/" . $xyzzy, 0777, true)) die("Unable to create output directory " . OUTDIR . $game . "/" . $xyzzy . "\n");
    }

    
    $idx = array();

    $idx["vox"] = null;
    if (false === ($idx["vox"] = fopen(OUTDIR . $game ."/vocals/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/vocals/index.html for writing.\n");
    }

    $idx["voxtar"] = null;
    if (false === ($idx["voxtar"] = fopen(OUTDIR . $game ."/vocaltar/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/vocaltar/index.html for writing.\n");
    }

    $idx["voxdrums"] = null;
    if (false === ($idx["voxdrums"] = fopen(OUTDIR . $game ."/vocaldrums/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/vocaldrums/index.html for writing.\n");
    }

    $idx["voxbass"] = null;
    if (false === ($idx["voxbass"] = fopen(OUTDIR . $game ."/vocalbass/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/vocalbass/index.html for writing.\n");
    }

    $idx["drums"] = null;
    if (false === ($idx["drums"] = fopen(OUTDIR . $game ."/drums/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/drums/index.html for writing.\n");
    }
    
    $idx["guitar"] = array();
    $idx["guitar"]["idx"] = null;
    if (false === ($idx["guitar"]["idx"] = fopen(OUTDIR . $game ."/guitar/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/guitar/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["guitar"][$diff] = null;
        if (false === ($idx["guitar"][$diff] = fopen(OUTDIR . $game ."/guitar/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . $game ."/guitar/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    $idx["bass"] = array();
    $idx["bass"]["idx"] = null;
    if (false === ($idx["bass"]["idx"] = fopen(OUTDIR . $game ."/bass/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/bass/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["bass"][$diff] = null;
        if (false === ($idx["bass"][$diff] = fopen(OUTDIR . $game ."/bass/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . $game ."/bass/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    /*
    $idx["guitarbass"] = array();
    $idx["guitarbass"]["idx"] = null;
    if (false === ($idx["guitarbass"]["idx"] = fopen(OUTDIR . $game ."/guitarbass/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game ."/guitarbass/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["guitarbass"][$diff] = null;
        if (false === ($idx["guitarbass"][$diff] = fopen(OUTDIR . $game ."/guitarbass/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . $game ."/guitarbass/index_" . $diff . "_.html for writing.\n");
        }
    }
    */
    
    
    $cache = loadCache(RB_CACHE);
    
    // put the header into every file
    index_header($idx["vox"], "Vocals");
    index_header($idx["voxtar"], "Vocals+guitar");
    index_header($idx["voxdrums"], "Vocals+drums");
    index_header($idx["voxbass"], "Vocals+bass");
    index_header($idx["drums"], "Drums");
    foreach ($idx["guitar"] as $foo => $bar) { index_header($bar, "$foo guitar"); }
    foreach ($idx["bass"] as $foo => $bar) { index_header($bar, "$foo bass"); }
    //foreach ($idx["guitarbass"] as $foo => $bar) { index_header($bar, "$foo guitar+bass"); }
    

    // open the tables
    // vocals doesn't need a table -- just a flat list of links
    // drums and voxtar and voxdrums get the simple table
    fwrite($idx["drums"], "<table border=\"1\">");
    fwrite($idx["voxtar"], "<table border=\"1\">");
    fwrite($idx["voxdrums"], "<table border=\"1\">");
    fwrite($idx["voxbass"], "<table border=\"1\">");
    
    // everything else gets the complex table
    foreach (array($idx["guitar"], $idx["bass"]/*, $idx["guitarbass"]*/) as $foo) {
        foreach ($foo as $baz => $bar) {
            #if ($baz == "idx") {
                // links to difficulties on the index page
                fwrite($bar, <<<EOT
<a href="index_easy.html">easy</a> <a href="index_medium.html">medium</a> <a href="index_hard.html">hard</a> <a href="index_expert.html">expert</a>
EOT
);
            #}
            #else {
                fwrite($bar, <<<EOT
<table border="1">
<tr><th>Song</th><th>Base Score (no multiplier or bonuses)</th><th>Useless Score (multiplier, no bonuses)</th><th>FC Score (multiplier, bonuses, no overdrive)</th><th>BRE Note Score</th></tr>
EOT
);
            #}
        }
    }

    echo "Preparing charts for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat, $harm1, $harm2) = parseFile(MIDIPATH . $game . "/" . $file, $game);
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
        	$im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ false,
               /* bass*/ false, /* drums */ false, /* vocals */ true, $realname, $beat, 0, $harm1, $harm2);
            imagepng($im, OUTDIR . $game ."/vocals/" . $shortname . "_vocals_blank.png");
            imagedestroy($im);
            $cache[$shortname]["vocals"]["version"] = CHARTVERSION;
    	}
        
        fwrite($idx["vox"], "<a href=\"".$shortname."_vocals_blank.png\">$realname</a><br>\n");
        
        
        // drums
        echo " [drums]";
        fwrite($idx["drums"], "<tr><td>" . $realname . "</td>");
        
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
        	if (isset($cache[$shortname]["drums"][$diff]) && $cache[$shortname]["drums"][$diff]["version"] >= CHARTVERSION+DRUMSVERMOD) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ false,
                     /* bass*/ false, /* drums */ true, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . $game ."/drums/" . $shortname . "_drums_" . $diff . "_blank.png");
                imagedestroy($im);
                $cache[$shortname]["drums"][$diff]["version"] = CHARTVERSION+DRUMSVERMOD;
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
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ true,
                       /* bass*/ false, /* drums */ false, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . $game ."/guitar/" . $shortname . "_guitar_" . $diff . "_blank.png");
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
    
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ false,
                       /* bass*/ true, /* drums */ false, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . $game ."/bass/" . $shortname . "_bass_" . $diff . "_blank.png");
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
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar * / true,
                   /* bass* / true, /* drums * / false, /* vocals * / false, $realname, $beat);
            imagepng($im, OUTDIR . $game ."/guitarbass/" . $shortname . "_guitarbass_" . $diff . "_blank.png");
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
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ true,
                       /* bass*/ false, /* drums */ false, /* vocals */ true, $realname, $beat, 0, $harm1, $harm2);
                imagepng($im, OUTDIR . $game ."/vocaltar/" . $shortname . "_vocaltar_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["voxtar"][$diff]["version"] = CHARTVERSION;
          	}
            
            fwrite($idx["voxtar"], "<td><a href=\"" . $shortname . "_vocaltar_" . $diff . "_blank.png\">" . $diff . "</a></td>");            
        } // voxtar diffs
        fwrite($idx["voxtar"], "</tr>\n");


        // voxdrums
        echo " [voxdrums]";
        
        fwrite($idx["voxdrums"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
        	if (isset($cache[$shortname]["voxdrums"][$diff]) && $cache[$shortname]["voxdrums"][$diff]["version"] >= CHARTVERSION+DRUMSVERMOD+1) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ false,
                       /* bass*/ false, /* drums */ true, /* vocals */ true, $realname, $beat, 0, $harm1, $harm2);
                imagepng($im, OUTDIR . $game ."/vocaldrums/" . $shortname . "_vocaldrums_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["voxdrums"][$diff]["version"] = CHARTVERSION+DRUMSVERMOD+1;
          	}
            
            fwrite($idx["voxdrums"], "<td><a href=\"" . $shortname . "_vocaldrums_" . $diff . "_blank.png\">" . $diff . "</a></td>");            
        } // voxdrums diffs
        fwrite($idx["voxdrums"], "</tr>\n");


        // voxbass
        echo " [voxbass]";
        
        fwrite($idx["voxbass"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
        	if (isset($cache[$shortname]["voxbass"][$diff]) && $cache[$shortname]["voxbass"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /* guitar */ false,
                       /* bass*/ true, /* drums */ false, /* vocals */ true, $realname, $beat, 0, $harm1, $harm2);
                imagepng($im, OUTDIR . $game ."/vocalbass/" . $shortname . "_vocalbass_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["voxbass"][$diff]["version"] = CHARTVERSION;
          	}
            
            fwrite($idx["voxbass"], "<td><a href=\"" . $shortname . "_vocalbass_" . $diff . "_blank.png\">" . $diff . "</a></td>");            
        } // voxtar diffs
        fwrite($idx["voxbass"], "</tr>\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx["vox"], "</body>\n</html>");
    fwrite($idx["voxtar"], "</table>\n</body>\n</html>");
    fwrite($idx["voxdrums"], "</table>\n</body>\n</html>");
    fwrite($idx["voxbass"], "</table>\n</body>\n</html>");
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

?>