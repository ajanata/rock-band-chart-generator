<?php

    define("PHPSPOPTVERSION", "0.3.0");
    // 1170
	define("WIDTH", 900);
	// 70
	define("PXPERBEAT", 35);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.5.0");
	define("BPMPRECISION", 1);


    require_once "parselib.php";
    require_once "songnames.php";
    require_once "notevalues.php";
    require_once "chartlib.php";
    
    require_once "opt_drums.php";
    
    if (isset($_SERVER["SERVER_NAME"])) {
        die("This script needs to be run from a command line, not from a web browser.\n");
    }

    if ($argc < 5 || $argv[1] == "--help") {
        doHelp();
        exit;
    }


    $game = $argv[1];
    $inst = $argv[2];
    $diff = $argv[3];
    $song = $argv[4];

    if (!file_exists("mids/" . $game . "/" . $song . ".mid")) {
        die("File mids/" . $game . "/" . $song . ".mid does not exist.");
    }
    
    if ($game != "rb" && $game != "gh2" && $game != "gh" && $game != "gh1" && $game != "gh80s") {
        die("Game must be one of rb, gh, gh2, or gh80s.");
    }
    
    if ($diff != "easy" && $diff != "medium" && $diff != "hard" && $diff != "expert") {
        die("Difficulty must be one of easy, medium, hard, or expert.");
    }


    echo "Parsing file " . $song . "...\n";
    list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat)
            = parseFile("mids/" . $game . "/" . $song . ".mid", strtoupper($game), false);


    switch ($inst) {
        case "drums":
            echo "Optimizing $diff drums...\n";
            // opt_drums mucks around with the events track is is passed
            $path = opt_drums($notetracks["drums"][$diff], $events["drums"], $timetrack, $beat, $diff);

            echo "Generating image...\n";

            $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, /*guitar*/ false,
                    /*bass*/ false, /*drums*/ true, /*vocals*/ false, $songname, $beat);
	
	        global $activated, $black;
	        
	        imagestring($im, 5, WIDTH - (strlen($path) * 9), 40, $path, $black);
	        imagestring($im, 4, 500, 0, "Fill delay: " . FILL_DELAY . "s", $black);
        	imagefilledrectangle($im, WIDTH-185, 16, WIDTH, 30, $silver);
            imagestring($im, 3, WIDTH-100, 15, "Activation", $activated);
   
	
        	imagepng($im, "optimal_paths/rb/drums/" . $diff . "/" . $songname . "_drums_" . $diff . "_optimal.png");
        	imagedestroy($im);

            
            exit;
            
        default:
            die("Invalid instrument, or instrument not coded for yet.");
    }


    // should never get here
    exit;


    function doHelp() {
        global $argv;
?>
phpspopt version <?= PHPSPOPTVERSION ?> - parselib version <?= PARSELIBVERSION ?>, chartlib version <?= CHARTLIBVERSION ?>
, opt_drums version <?= OPTDRUMSVERSION ?>.
(C) 2008 Andy Janata <ajanata@gmail.com> <http://ajanata.com/charts/>

Usage: php <?= $argv[0] ?> { --help | GAME INSTRUMENT DIFFICULTY FILE }

    GAME        Game to optimize for. One of rb, gh, gh2, or gh80s.
    INSTRUMENT  Instrument to optimize for. One of guitar, bass, or drums.
    DIFFICULTY  Difficulty to optimize for. One of easy, medium, hard, or expert.
    FILE        File to optimize. Must be in path mids/{GAME}/{FILE}.mid        

<?php
        exit;
    }



?>
