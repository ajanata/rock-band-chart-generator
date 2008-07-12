<?php

	define("WIDTH", 1024);
	define("PXPERBEAT", 40);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.9.1");
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
    
    $dir = opendir(MIDIPATH . "ghot/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "ghot/ for reading.\n");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    closedir($dir);
        
    umask(0);
    
    foreach (array("guitar" /*, "coop" */) as $xyzzy) {
        if (file_exists(OUTDIR . "ghot/" . $xyzzy)) continue;
        if (!mkdir(OUTDIR . "ghot/" . $xyzzy, 0777, true)) die("Unable to create output directory " . OUTDIR . "ghot/" . $xyzzy . "\n");
    }

    
    $idx = array();

    
    $idx["guitar"] = array();
    $idx["guitar"]["idx"] = null;
    if (false === ($idx["guitar"]["idx"] = fopen(OUTDIR . "ghot/guitar/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "ghot/guitar/index.html for writing.\n");
    }    
    foreach ($DIFFICULTIES as $diff) {
        $idx["guitar"][$diff] = null;
        if (false === ($idx["guitar"][$diff] = fopen(OUTDIR . "ghot/guitar/index_" . $diff . ".html", "w"))) {
            die("Unable to open file " . OUTDIR . "ghot/guitar/index_" . $diff . "_.html for writing.\n");
        }
    }
    
    
    foreach ($idx["guitar"] as $foo => $bar) { index_header($bar, "$foo guitar"); }
    

    // open the tables
    // everything else gets the complex table
    foreach (array($idx["guitar"] /*, $idx["coop"]*/) as $foo) {
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
<tr><th>Song</th><th>Absolute Base Score (no multiplier)</th><th>FC Score (multiplier)</th></tr>
EOT
);
            }
        }
    }

    echo "Preparing charts for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MIDIPATH . "ghot/" . $file, "ghot");
    	if ($CACHED) echo " [cached]";
    	
    	$songname = strtolower($songname);
    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";
        
        
        // guitar
        echo " [guitar]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";

            $absbasescore = $basescore = 0;
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "ghot", /* guitar */ true,
                    /* bass*/ false, /* drums */ false, /* vocals */ false, $realname, $beat);

            global $black;
            imagestring($im, 5, 300, 0, "Tick points are not calculated correctly yet.", $black);

            imagepng($im, OUTDIR . "ghot/guitar/" . $shortname . "_guitar_" . $diff . "_blank.png");
            imagedestroy($im);

            // ugly score kludges
            $absbasescore = 0;
            foreach ($measures["guitar"] as $m) {
                $absbasescore += $m["mscore"][$diff];
            }
            $basescore = $measures["guitar"][count($measures["guitar"])-1]["cscore"][$diff];
        	
            
            fwrite($idx["guitar"][$diff], "<tr><td><a href=\"" . $shortname . "_guitar_" . $diff . "_blank.png\">" . $realname . "</a></td>");
            fwrite($idx["guitar"][$diff], "<td>" . $absbasescore . "</td>");
        	fwrite($idx["guitar"][$diff], "<td>" . $basescore . "</td>");
	        fwrite($idx["guitar"][$diff], "</tr>\n");
            
        } // guitar diffs

        echo "\n";
    } // foreach file


    // close the files
    foreach ($idx["guitar"] as $bar => $foo) {
        if ($bar != "idx") fwrite($foo, "</table>\n");
        fwrite($foo, "</body>\n</html>");
    }
    exit;


    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Guitar Hero: On Tour $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p><a href="#skip">Skip to the charts!</a></p>
<!--
<p>Significant changes recently:
<ul>
</ul></p>
-->
<p>These charts are blank. They have not been verified against the game and may be faulty. In fact, I know they're not perfect. Guitar Hero: On Tour does some funky ticking, and we're still trying to figure out exactly how to do it mathematically. <b>This means that the scores are going to be off as soon as there's a sustain note.</b> If you see something else horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Huge thanks to tma, debr, TheDave, and Revelus.</p>
<a name="skip" />
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