<?php

    define("DRAWPULSES", true);
	define("WIDTH", 1010);
	define("BPMPRECISION", 1);
	define("PXPERBEAT", 60);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
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
    
    if (!file_exists(OUTDIR . "rb/fullband-debug")) {
        if (!mkdir(OUTDIR . "rb/fullband-debug", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/fullband-debug\n");
    }

    $idx = null;
    if (false === ($idx = fopen(OUTDIR . "rb/fullband-debug/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/fullband-debug/index.html for writing.\n");
    }

    
    $cache = loadCache(RB_CACHE);
    
    // put the header into every file
    index_header($idx, "Full Band DEBUG");
    
    echo "Preparing charts for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";

    	// fullband with debug info
    	echo " [fullband-debug] expert";
    	if (isset($cache[$shortname]["fullband-debug"]) && $cache[$shortname]["fullband-debug"]["version"] >= CHARTVERSION) {
    	   // we already have a valid image for this
    	   echo " {cached}";
    	}
    	else {
    	    // have to regenerate the image
        	$im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, "expert", "rb", /* guitar */ true,
               /* bass*/ true, /* drums */ true, /* vocals */ true, $realname, $beat);
            imagepng($im, OUTDIR . "rb/fullband-debug/" . $shortname . "_fullband-debug_blank.png");
            imagedestroy($im);
            $cache[$shortname]["fullband-debug"]["version"] = CHARTVERSION;
    	}
        
        fwrite($idx, "<a href=\"".$shortname."_fullband-debug_blank.png\">$realname</a><br>\n");

        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx, "</body>\n</html>");
    fclose($idx);

    saveCache(RB_CACHE, $cache);

    exit;

?>