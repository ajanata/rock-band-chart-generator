<?php

	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/rb/csv-scores/");

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
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    
    umask(0);
    
    if (!file_exists(OUTDIR)) {
        if (!mkdir(OUTDIR, 0777, true)) die("Unable to create output directory " . OUTDIR . "\n");
    }
    
    
    $idx = null;
    if (false === ($idx = fopen(OUTDIR . "index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "index.html for writing.\n");
    }
    
    
    index_header($idx, "Full Band .csv scores");
    
    // open the table
    fwrite($idx, "<table border=\"1\">");

    echo "Making .csv files for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";


        // csv scores
        echo " [csvscores]";
        fwrite($idx, "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";

            $csv = null;
            if (false === ($csv = fopen(OUTDIR . $shortname . "_fullband_" . $diff . "_scores.csv", "w"))) {
                die("Unable to open file " . OUTDIR . $shortname . "_fullband_" . $diff . "_scores.csv for writing.\n");
            }
            
            fwrite($csv, "meas,vocals,guitar,bass,drums,base,,vocals mult,guitar mult,bass mult,drums mult,mult,16 BEAT,24 BEAT,32 BEAT\n");
            for ($i = 0; $i < count($measures["guitar"]); $i++) {
                fprintf($csv, "%d,0,%d,%d,%d,=SUM(B%d:E%d),,=4*B%d,=4*C%d,=6*D%d,=4*E%d,=SUM(H%d:K%d),,,\n", $i+1, 
                        $measures["guitar"][$i]["mscore"][$diff], $measures["bass"][$i]["mscore"][$diff],
                        $measures["drums"][$i]["mscore"][$diff], $i+2, $i+2, $i+2, $i+2, $i+2, $i+2, $i+2, $i+2);
            }
            
            fclose($csv);
            
            fwrite($idx, "<td><a href=\"" . $shortname . "_fullband_" . $diff . "_scores.csv\">" . $diff. "</a></td>");
        } // csv scores diffs
        fwrite($idx, "</tr>\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx, "</table>\n</body>\n</html>");
    fclose($idx);

    exit;


    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p>These files are meant to assist full-band pathing using the spreadsheet method. They contain the per-measure scores for <strike>vocals</strike> (not yet, working on it!), guitar, bass, and drums. Unfortunately, I did not have my code set up to easily be able to determine the multiplier score per measure without redoing the score calculation code. I'll try to address this at some point, but for now you'll have to go through and re-work the first few measures per instrument to get the right multiplier score.</p>
<p>These are .csv (comma-seperated value) files, which every spreadsheet program should be able to open. You will have to re-save it in your program's native format to add colors to cells. Depending on your system configuration, clicking the links may open the file directly in your spreadsheet program; you probably have to right-click and Save As... to save to your hard drive.</p>
<p>They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
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