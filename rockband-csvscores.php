<?php

	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/");

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
        $files[] = $file;
    }
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    
    umask(0);
    
    if (!file_exists(OUTDIR . "rb/fullband")) {
        if (!mkdir(OUTDIR . "rb/fullband", 0777, true)) die("Unable to create output directory " . OUTDIR . "rb/fullband\n");
    }
    
    
    $idx = null;
    if (false === ($idx = fopen(OUTDIR . "rb/fullband/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . "rb/fullband/index.html for writing.\n");
    }        
    
    
    index_header($idx, "Full Band");
    
    // open the table
    fwrite($idx, "<table border=\"1\">");

    echo "Preparing charts for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals) = parseFile(MIDIPATH . "rb/" . $file, "rb");
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";


        // full band
        echo " [fullband]";
        fwrite($idx, "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, "rb", /* guitar */ true,
                   /* bass*/ true, /* drums */ true, /* vocals */ true, $realname);
            imagepng($im, OUTDIR . "rb/fullband/" . $shortname . "_fullband_" . $diff . "_blank.png");
            imagedestroy($im);
            
            fwrite($idx, "<td><a href=\"" . $shortname . "_fullband_" . $diff . "_blank.png\">" . $diff. "</a></td>");
        } // fullband diffs
        fwrite($idx, "</tr>\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx, "</table>\n</body>\n</html>");

    exit;


    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums</a> and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar part only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure.</p>
<p>Vocal activation zones are not stored in the .mid as they are with drums. This leads me to believe that any gap larger than a certain amount of time (be it clock time or number of beats, I'm not sure) is an activation zone. At some point in the not-too-distant future I intend to do more research on this.</li>
<p>Overdrive phrase backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example).</p>
<p>Significant changes since last time:
<ul>
<li>Big Rock Ending estimates might be right now? Changed how they're calculated, haven't check to see if they make sense.</li>
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