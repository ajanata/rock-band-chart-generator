<?php

	define("MIDIPATH", "mids/");
	define("OUTDIR", "charts/");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";

    $DIFFICULTIES = array("easy", "medium", "hard", "expert");

    if (isset($argv[1]) && $argv[1] == "--help") do_help();
    if (isset($argv[1]) && $argv[1] == "--version") do_version();

    if (!isset($argv[1]) && $argv[1] != "rb" && $argv[1] != "tbrb") die ("specify rb or tbrb on command line");
    $game = $argv[1];

    $files = array();
    
    $dir = opendir(MIDIPATH . $game . "/");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, -9) == ".voxfills") continue;
        if ($file == ".svn") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }
    
    sort($files, SORT_STRING);
    
    $dir = opendir(MIDIPATH . $game . "/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . $game . "/ for reading.\n");
    
    umask(0);
    
    if (!file_exists(OUTDIR . $game . "/csv-scores/")) {
        if (!mkdir(OUTDIR . $game . "/csv-scores/", 0777, true)) die("Unable to create output directory " . OUTDIR . $game . "/csv-scores/\n");
    }
    
    
    $idx = array();
    $idx["fullband"] = null;
    if (false === ($idx["fullband"] = fopen(OUTDIR . $game . "/csv-scores/index.html", "w"))) {
        die("Unable to open file " . OUTDIR . $game . "/csv-scores/index.html for writing.\n");
    }

    $idx["stuff"] = null;
    if (false === ($idx["stuff"] = fopen(OUTDIR . $game . "/useful_stuff.csv", "w"))) {
        die("Unable to open file " . OUTDIR . $game . "/useful_stuff.csv for writing.\n");
    }
    
    $idx["bonuses"] = null;
    if (false === ($idx["bonuses"] = fopen(OUTDIR . $game . "/bonuses.csv", "w"))) {
        die("Unable to open file " . OUTDIR . $game . "/bonuses.csv for writing.\n");
    }

    
    index_header($idx["fullband"], "Full Band .csv scores");
    
    // open the tables
    fwrite($idx["fullband"], "<table border=\"1\">");
    fwrite($idx["stuff"], "short_name,guitar_easy,guitar_medium,guitar_hard,guitar_expert,bass_easy,bass_medium,bass_hard,bass_expert,drums_easy,drums_medium,drums_hard,drums_expert,guitar_base_easy,guitar_base_medium,guitar_base_hard,guitar_base_expert,bass_base_easy,bass_base_medium,bass_base_hard,bass_base_expert,drums_base_easy,drums_base_medium,drums_base_hard,drums_base_expert,drums_allfills_easy,drums_allfills_medium,drums_allfills_hard,drums_allfills_expert,percussion_hits,nonpercussion_phrases,length\n");
    fwrite($idx["bonuses"], "short_name,gtr_easy_solos,gtr_medium_solos,gtr_hard_solos,gtr_expert_solos,drums_easy_solos,drums_medium_solos,drums_hard_solos,drums_expert_solos,big_rock_ending\n");


    echo "Making .csv files for " . count($files) . " files...\n";
    
    foreach ($files as $i => $file) {
        $shortname = substr($file, 0, strlen($file) - 4);
        echo "File " . ($i + 1) . " of " . count($files) . " ($shortname) [parsing]";
        
    	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat, $harm1, $harm2) = parseFile(MIDIPATH . $game . "/" . $file, $game);
    	if ($CACHED) echo " [cached]";
    	    	
    	$realname = (isset($NAMES[$songname]) ? $NAMES[$songname] : $songname);
    	echo " ($realname)";


        // csv full band scores
        echo " [csvscores]";
        fwrite($idx["fullband"], "<tr><td>" . $realname . "</td>");
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";

            $csv = null;
            if (false === ($csv = fopen(OUTDIR . $game . "/csv-scores/" . $shortname . "_fullband_" . $diff . "_scores.csv", "w"))) {
                die("Unable to open file " . OUTDIR . $game . "/csv-scores/" . $shortname . "_fullband_" . $diff . "_scores.csv for writing.\n");
            }
            
            fwrite($csv, "meas,vocals,guitar,bass,drums,base,,vocals mult,guitar mult,bass mult,drums mult,mult,16 BEAT,24 BEAT,32 BEAT\n");
            for ($i = 0; $i < count($measures["guitar"]); $i++) {
                fprintf($csv, "%d,0,%d,%d,%d,=SUM(B%d:E%d),,=4*B%d,=4*C%d,=6*D%d,=4*E%d,=SUM(H%d:K%d),,,\n", $i+1, 
                        $measures["guitar"][$i]["mscore"][$diff], $measures["bass"][$i]["mscore"][$diff],
                        $measures["drums"][$i]["mscore"][$diff], $i+2, $i+2, $i+2, $i+2, $i+2, $i+2, $i+2, $i+2);
            }
            
            fclose($csv);
            
            fwrite($idx["fullband"], "<td><a href=\"" . $shortname . "_fullband_" . $diff . "_scores.csv\">" . $diff. "</a></td>");
        } // csv scores diffs
        fwrite($idx["fullband"], "</tr>\n");
        
        
        // fc note streaks scores
        fwrite($idx["stuff"], $songname);

        // guitar
        echo " [fcstreaks guitar]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $streak = $measures["guitar"][count($measures["guitar"])-1]["streak"][$diff];
            fwrite($idx["stuff"], "," . $streak);
        } // guitar diffs

        // bass
        echo " [fcstreaks bass]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $streak = $measures["bass"][count($measures["bass"])-1]["streak"][$diff];
            fwrite($idx["stuff"], "," . $streak);
        } // bass diffs
    
        // drums
        echo " [notecounts drums]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $streak = $measures["drums"][count($measures["drums"])-1]["streak"][$diff];
            fwrite($idx["stuff"], "," . $streak);
        } // drums diffs


        // BASE SCORES
        
        // guitar
        echo " [guitar base]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $basescore = 0;
            foreach ($measures["guitar"] as $m) {
                $basescore += $m["mscore"][$diff];
            }
            fwrite($idx["stuff"], "," . $basescore);
        } // guitar base
        
        // bass
        echo " [bass base]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $basescore = 0;
            foreach ($measures["bass"] as $m) {
                $basescore += $m["mscore"][$diff];
            }
            fwrite($idx["stuff"], "," . $basescore);
        } // bass base

        // drums
        echo " [drums base]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $basescore = 0;
            foreach ($measures["drums"] as $m) {
                $basescore += $m["mscore"][$diff];
            }
            fwrite($idx["stuff"], "," . $basescore);
        } // drums base
        
        // now drums again, but assuming all fills show up and cover all notes
        // this is mainly for looking for 4* FCs
        echo " [drums allfills]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $score = 0;
            foreach ($measures["drums"] as $m) {
                $score += $m["cscore"][$diff];
            }
            fwrite($idx["stuff"], "," . $score);
        } // drums allfills diffs

        // percussion hits
        echo " [percussion]";
        $percussions = 0;
        foreach ($vocals as $v) {
            if (isset($v["percussion"]) && $v["percussion"]) {
                $percussions++;
            }
        }
        fwrite($idx["stuff"], "," . $percussions);
        
        // nonpercussion phrase count
        $voxphrases = $laststart = 0;
        foreach ($events["vocals"] as $e) {
            if ($e["start"] == $laststart) continue;
            $laststart = $e["start"];
            if ($e["type"] != "fill" && (!isset($e["percussion"]) || $e["percussion"] == false)) {
                $voxphrases++;
            }
        }
        fwrite($idx["stuff"], "," . $voxphrases);

        // song length
        echo " [length]";
        fwrite($idx["stuff"], "," . getClockTimeBetweenPulses($timetrack, 0, max($notetracks["guitar"]["TrkEnd"], $notetracks["bass"]["TrkEnd"], $notetracks["drums"]["TrkEnd"], $vocals["TrkEnd"])));


/*
        // vocals
        echo " [vocals]";
        $last = -1;
        $streak = 0;
        foreach ($events["vocals"] as $e) {
            if (!($e["type"] == "p1" || $e["type"] == "p2")) continue;
            if (($e["type"] == "p1" || $e["type"] == "p2") && $e["start"] > $last) {
                $last = $e["start"];
                $streak++;
            }
        } // vocal events
        fwrite($idx, "," . $streak);
*/
        fwrite($idx["stuff"], "\n");
        // / fc note streaks scores
        
        
        // bonuses
        fwrite($idx["bonuses"], $songname);
        
        // guitar solos
        echo " [solo bonuses]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $solonotes = 0;
            foreach ($events["guitar"] as $e) {
                if ($e["type"] == "solo" && $e["difficulty"] == $diff) {
                    $solonotes += $e["notes"];
                }
            }
            fwrite($idx["bonuses"], "," . $solonotes);
        } // solos diffs

        // drum solos
        echo " [solo bonuses]";
        foreach ($DIFFICULTIES as $diff) {
            echo " ($diff)";
            $solonotes = 0;
            foreach ($events["drums"] as $e) {
                if ($e["type"] == "solo" && $e["difficulty"] == $diff) {
                    $solonotes += $e["notes"];
                }
            }
            fwrite($idx["bonuses"], "," . $solonotes);
        } // solos diffs

        // big rock ending
        echo " [big rock ending]";
        $brescore = 0;
        foreach ($events["guitar"] as $e) {
            if ($e["type"] != "bre") continue;
            $brescore = $e["brescore"];
            break;
        }
        fwrite($idx["bonuses"], "," . $brescore . "\n");
        
        echo "\n";
    } // foreach file


    // close the files
    fwrite($idx["fullband"], "</table>\n</body>\n</html>");
    fclose($idx["fullband"]);
    fclose($idx["stuff"]);
    fclose($idx["bonuses"]);

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
