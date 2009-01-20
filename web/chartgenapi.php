<?php

    define("LIB_PATH", "../");

    define("DRAWPULSES", false);
    define("SHOWFORCED", false);

    define("WIDTH", 1024);
    define("BPMPRECISION", 1);
    define("PXPERBEAT", 45);
    define("STAFFHEIGHT", 12);
    define("DRAWPLAYERLINES", 0);

    define("MID_PATH", "../mids/");
    define("ACCESS_LOG", LIB_PATH . "api_access.log");

    require_once LIB_PATH . "parselib.php";
    require_once LIB_PATH . "notevalues.php";
    require_once LIB_PATH . "songnames.php";
    require_once LIB_PATH . "chartlib.php";
    require_once "users.php";


    if (!isset($_GET["game"])) {
        echo "Game not specified - Use query string parameter, i.e., chartgenapi.php?game=rb (or gh1, or gh2, or gh3)";
        exit;
    }

    $game = strtolower($_GET["game"]);
    if (!($game == "gh1" || $game == "gh2" || $game == "gh3" || $game == "rb" || $game == "ghot")) {
       die("Invalid game -- specify one of gh1, gh2, gh3, ghot, or rb (use rb for rb2).");
    }
    
    
    if (!isset($_GET["file"])) {
        echo "File not specified - Use query string parameter, i.e., chartgenapi.php?file=danicalifornia (no extension)";
        exit;
    }
    
    $file = preg_replace("-[\./]-", "", $_GET["file"]);
    if (!file_exists(MID_PATH . strtolower($_GET["game"]) . "/" . $file . ".mid")) {
       die("Specified file does not exist.");
    }
    
    
    if (!(isset($_GET["guitar"]) || isset($_GET["bass"]) || isset($_GET["drums"]) || isset($_GET["vocals"]))) {
       die("No instruments specified - Use query string parameter, i.e., chartgenapi.php?guitar=expert");
    }
    
    // FIXME need to make the back-end work with different difficulties first, then we can do the same here
    // for now just use the first of guitar, bass, drums, vocals
    $diff = "";
    if (isset($_GET["guitar"])) $diff = $_GET["guitar"];
    else if (isset($_GET["bass"])) $diff = $_GET["bass"];
    else if (isset($_GET["drums"])) $diff = $_GET["drums"];
    else $diff = $_GET["vocals"];
    
    if ($diff != "easy" && $diff != "medium" && $diff != "hard" && $diff != "expert") {
       die("Invalid difficulty $diff -- choose one of easy, medium, hard, or expert.");
    }
    
    if (isset($_GET["input"])) {
       if (substr($_GET["input"], 0, 7) != "http://") die ("Input location must be an http:// URL.");
       $inputloc = $_GET["input"];
    }
    else $inputloc = "php://input";
    
    if (($input = fopen($inputloc, "r")) === false) {
       die("Unable to open $inputloc for reading");
    }
    
    // make sure the user is valid
    $line = explode(" ", rtrim(fgets($input)));
    $log = fopen(ACCESS_LOG, "a");
    fwrite($log, date("r") . " - " . $_SERVER["REMOTE_ADDR"] . " - ");
    if ($line[0] != "user" || !isset($USERS[$line[1]]) || $line[2] != md5($USERS[$line[1]] . $file)) {
        fwrite($log, "Access denied for user " . $line[1] . " for song " . $file . " with hash " . $line[2] . "\n"); 
        die("First line of input does not contain valid user information.");
    }
    else {
        $user = $line[1];
        fwrite($log, "User " . $user . " requested song " . $file . "\n");
    }
    
    // now we know they're valid, so do the normal parsing so we can start messing with it
    
    list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MID_PATH . $game . "/" . $file . ".mid", $game, false);
    
    // now that we have the song parsed, we can loop over everything they want us to do and mutilate the image
    
    $events["colors"] = array();
    $do_later = array();
    #$colors = array();
    
    while (!feof($input)) {
        $rawline = rtrim(fgets($input));
        if ($rawline == "") continue;
        $line = explode(" ", strtolower($rawline));
        switch ($line[0]) {
            case "comment": continue;
            case "color":
                $e = array();
                $e["type"] = "api-color";
                $e["n"] = $line[1];
                $e["r"] = $line[2];
                $e["g"] = $line[3];
                $e["b"] = $line[4];
                $events["colors"][] = $e;
                #$colors[] = &$e;
                break;
                
            case "string":
                $size = $line[1];
                if ($size > 5) $size = 5;
                else if ($size < 1) $size = 1;
                $color = $line[2];
                $x = $line[3];
                $y = $line[4];
                //$text = substr($rawline, strpos($rawline, " ", strpos($rawline, " ", strpos($rawline, " ", strpos($rawline, " ", strpos($rawline, " ") + 1) + 1) + 1) + 1) + 1);
                $text = preg_replace("/string \d+ \d+ \d+ (-)?\d+ /", "", $rawline, 1);
                if ($y == 0) {
                    // this text gets drawn above a notechart
                    $e = array();
                    $e["type"] = "api-text";
                    $e["start"] = $x;
                    $e["end"] = $x;
                    $e["color"] = $color;
                    $e["text"] = strstr($text, " ");
                    $e["size"] = $size;
                    $xyzzy = explode(" ", $text);
                    $events[$xyzzy[0]][] = $e;
                }
                else {
                    // absolutely positioned text. we have to store it for later as we don't have the image yet
                    $e = array();
                    $e["x"] = $x;
                    // we fix for bounds later
                    $e["y"] = $y;
                    $e["size"] = $size;
                    $e["color"] = $color;
                    $e["text"] = $text;
                    $do_later[] = $e;
                }
                break;
                
            case "line":
                //line <instrument> <color> <start> <end> <y> <height>
                $e = array();
                $e["type"] = "api-line";
                $e["start"] = $line[3];
                $e["end"] = $line[4];
                $e["color"] = $line[2];
                $e["offset"] = $line[5];
                if ($e["offset"] < -4 * STAFFHEIGHT) $e["offset"] = -4 * STAFFHEIGHT;
                else if ($e["offset"] > 30) $e["offset"] = 30;
                $e["height"] = $line[6];
                if ($e["height"] > 5) $e["height"] = 5;
                else if ($e["height"] < 1) $e["height"] = 1;
                $events[$line[1]][] = $e;
                break;
                
            case "fill":
                //fill <instrument> <color> <start> <end> <height>
                $e = array();
                $e["type"] = "api-fill";
                $e["start"] = $line[3];
                $e["end"] = $line[4];
                $e["color"] = $line[2];
                $e["height"] = $line[5];
                if ($e["height"] > 30) $e["height"] = 30;
                else if ($e["height"] < 0) $e["height"] = 0;
                $events[$line[1]][] = $e;
                break;
                
            // FIXME these 4 need to know what difficulty the individual instrument is using when I fix that above
            case "measscore":
                //measscore <instrument> <#> <score>
                $measures[$line[1]][$line[2]-1]["mscore"][$diff] = $line[3];
                break;
                
            case "totalscore":
                $measures[$line[1]][$line[2]-1]["cscore"][$diff] = $line[3];
                break;
                
            case "bonusscore":
                $measures[$line[1]][$line[2]-1]["bscore"][$diff] = $line[3];
                break;
                
            case "whammy":
                // this isn't actually stored in the array presently, but we're sticking it there for the override
                // chartlib has been updated to use this if present and fall back to default behavior if it isn't
                $measures[$line[1]][$line[2]-1]["whammy"][$diff] = $line[3];
                break;
        }
       
    }
    fclose($input);
    
    // and now draw the image with the new data structures
    
    $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, isset($_GET["guitar"]),
           isset($_GET["bass"]), isset($_GET["drums"]), isset($_GET["vocals"]), (isset($NAMES[$file]) ? $NAMES[$file] : $file), $beat);

    global $APICOLORS;
    foreach ($do_later as $e) {
        if ($e["y"] < 0) $e["y"] = imagesy($im) + $e["y"];
        if ($e["y"] < 45) $e["y"] = 45;
        else if ($e["y"] > imagesy($im) - 60) $e["y"] = imagesy($im) - 60;
        imagestring($im, $e["size"], $e["x"], $e["y"], $e["text"], $APICOLORS[$e["color"]]);
    }


    // draw the chart api information
    $gray = imagecolorallocate($im, 134, 134, 134);
    imagestring($im, 3, 0, imagesy($im) - 40, "Generated with chartgen API by user " . $user . " -- see http://ajanata.com/projects/phpspopt/api for more information.", $gray);
    
    header("Content-type: image/png");
    imagepng($im);
    imagedestroy($im);


?>