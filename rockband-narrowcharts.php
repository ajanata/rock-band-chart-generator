<?php

	define("WIDTH", 1010);
	define("BPMPRECISION", 0);
	define("PXPERBEAT", 30);
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
        if (substr($file, -9) == ".voxfills") continue;
        if ($file == ".svn") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    closedir($dir);
        
    umask(0);
    
    if (!file_exists(OUTDIR . "rb/fullband")) {
        if (!mkdir(OUTDIR . "rb/fullband", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/fullband\n");
    }

    if (!file_exists(OUTDIR . "rb/guitarbass")) {
        if (!mkdir(OUTDIR . "rb/guitarbass", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/guitarbass\n");
    }

    if (!file_exists(OUTDIR . "rb/guitardrums")) {
        if (!mkdir(OUTDIR . "rb/guitardrums", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/guitardrums\n");
    }


    
    $idx = array();
    
    $idx["fullband"] = null;
    if (false === ($idx["fullband"] = fopen(OUTDIR . "rb/fullband/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/fullband/index.html for writing.\n");
    }

    $idx["guitardrums"] = null;
    if (false === ($idx["guitardrums"] = fopen(OUTDIR . "rb/guitardrums/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/guitardrums/index.html for writing.\n");
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
    index_header($idx["guitardrums"], "guitar+drums");
    foreach ($idx["guitarbass"] as $foo => $bar) { index_header($bar, "$foo guitar+bass"); }

    $cache = loadCache(RB_CACHE);

    
    // open the table for full band
    fwrite($idx["fullband"], "<table border=\"1\">");
#    fwrite($idx["guitarbass"], "<table border=\"1\">");
    fwrite($idx["guitardrums"], "<table border=\"1\">");
    
    
    // open the complex table for guitarbass and guitardrums
//    foreach (array($idx["guitarbass"], $idx["guitardrums"]) as $foo) {
        foreach ($idx["guitarbass"] as $baz => $bar) {
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
<tr><th>Song</th><th>Absolute Base Score (no multiplier or bonuses)</th><th>Base Score (multiplier, no bonuses)</th><th>FC Score (multiplier, bonuses, no overdrive)</th><!-- --> <th>BRE Note Score</th> <!-- --></tr>
EOT
);
            #}
        }
//    }


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
        	if (isset($cache[$shortname]["fullband"][$diff]) && $cache[$shortname]["fullband"][$diff]["version"] >= CHARTVERSION+DRUMSVERMOD) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                       /* bass*/ true, /* drums */ true, /* vocals */ true, $realname, $beat);
                imagepng($im, OUTDIR . "rb/fullband/" . $shortname . "_fullband_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["fullband"][$diff]["version"] = CHARTVERSION+DRUMSVERMOD;
          	}
            
            fwrite($idx["fullband"], "<td><a href=\"" . $shortname . "_fullband_" . $diff . "_blank.png\">" . $diff. "</a></td>");
        } // fullband diffs
        fwrite($idx["fullband"], "</tr>\n");
        
        
        // guitarbass
        echo " [guitarbass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $absbasescore = $basescore = $bonusscore = $brescore = 0;
        	if (isset($cache[$shortname]["guitarbass"][$diff]) && $cache[$shortname]["guitarbass"][$diff]["version"] >= CHARTVERSION) {
        	   // we already have a valid image for this
        	   echo " {cached}";
        	   $absbasescore = $cache[$shortname]["guitarbass"][$diff]["abs"];
        	   $basescore = $cache[$shortname]["guitarbass"][$diff]["base"];
        	   $bonusscore = $cache[$shortname]["guitarbass"][$diff]["bonus"];
        	   $brescore = $cache[$shortname]["guitarbass"][$diff]["bre"];
               $brenotescore = $cache[$shortname]["guitarbass"][$diff]["brenotescore"];
        	}
        	else {
                // have to re-generate the chart and get all the numbers and stuff

            
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                       /* bass*/ true, /* drums */ false, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . "rb/guitarbass/" . $shortname . "_guitarbass_" . $diff . "_blank.png");
                imagedestroy($im);

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
    
            	if ($bonusscore == 0) {
            	   // no solo or BRE
            	   $bonusscore = $basescore;
            	}
            	
            	$cache[$shortname]["guitarbass"][$diff]["version"] = CHARTVERSION;
            	$cache[$shortname]["guitarbass"][$diff]["abs"] = $absbasescore;
            	$cache[$shortname]["guitarbass"][$diff]["base"] = $basescore;
            	$cache[$shortname]["guitarbass"][$diff]["bonus"] = $bonusscore;
            	$cache[$shortname]["guitarbass"][$diff]["bre"] = $brescore;
            	$cache[$shortname]["guitarbass"][$diff]["brenotescore"] = $brenotescore;
        	}

            
            fwrite($idx["guitarbass"][$diff], "<tr><td><a href=\"" . $shortname . "_guitarbass_" . $diff . "_blank.png\">" . $realname. "</a></td>");
        	fwrite($idx["guitarbass"][$diff], "<td>" . $absbasescore . "</td>");
        	fwrite($idx["guitarbass"][$diff], "<td>" . $basescore . "</td>");
        	fwrite($idx["guitarbass"][$diff], "<td>" . $bonusscore . "</td>");
        	fwrite($idx["guitarbass"][$diff], "<td>" . $brenotescore . "</td>");
	        fwrite($idx["guitarbass"][$diff], "</tr>\n");
            
        } // guitarbass diffs


        // guitardrums
        echo " [guitardrums]";
        fwrite($idx["guitardrums"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            
        	if (isset($cache[$shortname]["guitardrums"][$diff]) && $cache[$shortname]["guitardrums"][$diff]["version"] >= CHARTVERSION+DRUMSVERMOD) {
        	   // we already have a valid image for this
        	   echo " {cached}";
    	    }
          	else {
                $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                       /* bass*/ false, /* drums */ true, /* vocals */ false, $realname, $beat);
                imagepng($im, OUTDIR . "rb/guitardrums/" . $shortname . "_guitardrums_" . $diff . "_blank.png");
                imagedestroy($im);
                
                $cache[$shortname]["guitardrums"][$diff]["version"] = CHARTVERSION+DRUMSVERMOD;
          	}
            
            fwrite($idx["guitardrums"], "<td><a href=\"" . $shortname . "_guitardrums_" . $diff . "_blank.png\">" . $diff. "</a></td>");
        } // guitardrums diffs
        fwrite($idx["guitardrums"], "</tr>\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx["fullband"], "</table>\n</body>\n</html>");
    foreach ($idx["guitarbass"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }
    fwrite($idx["guitardrums"], "</table>\n</body>\n</html>");


    saveCache(RB_CACHE, $cache);

    exit;

?>